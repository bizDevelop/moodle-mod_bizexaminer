<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Main exam service.
 *
 * @package     mod_bizexaminer
 * @category    api
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bizexaminer\api;

use mod_bizexaminer\bizexaminer;
use mod_bizexaminer\bizexaminer_exception;
use mod_bizexaminer\callback_api\callback_api;
use mod_bizexaminer\data_objects\attempt;
use mod_bizexaminer\data_objects\attempt_results;
use mod_bizexaminer\data_objects\exam;
use mod_bizexaminer\data_objects\exam_grade;
use mod_bizexaminer\gradebook\grading;
use mod_bizexaminer\task\fetch_results;
use mod_bizexaminer\util;
use stdClass;

/**
 * Main service for handling exam flow
 *
 * for rendering, starting, ending an exam and handling results
 */
class exams extends abstract_api_service {

    /**
     * If the user can access the exam based on restrictions
     *
     * @param exam $exam
     * @param int $userid
     * @return true|string true if is allowed, string with error message if not allowed
     */
    public function can_access(exam $exam, int $userid) {
        $now = util::create_date(time());
        // 1. Check time open
        if ($exam->timeopen) {
            if ($now < $exam->timeopen) {
                return get_string('exam_access_timeopen', 'mod_bizexaminer', userdate($exam->timeopen->getTimestamp()));
            }
        }
        // 2. Check time close - do not check graceperiod here
        if ($exam->timeclose) {
            if ($now > $exam->timeclose) {
                return get_string('exam_access_timeclose', 'mod_bizexaminer');
            }
        }

        // 3. Check subnet
        if ($exam->subnet) {
            if (!address_in_subnet(getremoteaddr(), $exam->subnet)) {
                return get_string('exam_access_subnetwrong', 'mod_bizexaminer');
            }
        }

        $previousattempts = attempt::count(['examid' => $exam->id, 'userid' => $userid]);

        // 4. Check max attempts
        if ($exam->maxattempts) { // Anything > 0.
            if ($previousattempts >= $exam->maxattempts) {
                return get_string('exam_access_nomoreattempts', 'mod_bizexaminer');
            }
        }

        // 5. Check delay between 1st and 2nd attempt
        if ($exam->delayattempt1 && $previousattempts === 1) {
            // Status of attempt does not matter.
            // If it's started the user should actually resume this (and should be shown a resume button in view.php)
            // If it's pending he may start a new attempt but has to wait.
            // If it's finished he also needs to wait.

            $previousattempt = attempt::get_by(['examid' => $exam->id, 'userid' => $userid]);
            $canattemptafterdelay = $this->can_attempt_after_delay($previousattempt, $exam->delayattempt1);
            if ($canattemptafterdelay !== true) {
                return $canattemptafterdelay;
            }
        }

        // 6. Check deleay between 2nd and further attempts
        if ($exam->delayattempt2 && $previousattempts > 1) {
            // Status of attempt does not matter.
            // If it's started the user should actually resume this (and should be shown a resume button in view.php)
            // If it's pending he may start a new attempt but has to wait.
            // If it's finished he also needs to wait.

            $previousattempt = attempt::get_by(['examid' => $exam->id, 'userid' => $userid], 'timemodified DESC');
            $canattemptafterdelay = $this->can_attempt_after_delay($previousattempt, $exam->delayattempt2);
            if ($canattemptafterdelay !== true) {
                return $canattemptafterdelay;
            }
        }

        return true;
    }

    /**
     * Checks the delay between attempts for can_access
     * @param null|attempt $previousattempt
     * @param int $delay
     * @return string|true
     */
    protected function can_attempt_after_delay(?attempt $previousattempt, int $delay) {
        if ($previousattempt) {
            $attemptfinished = null;
            if ($previousattempt->hasresults) {
                $results = $previousattempt->get_results();
                $attemptfinished = $results->whenfinished->getTimestamp();
            }

            // Fall back to time modified if no results yet.
            if (!$attemptfinished) {
                $attemptfinished = $previousattempt->timemodified->getTimestamp();
            }
            $timepassed = time() - $attemptfinished;
            if ($timepassed < $delay) {
                return get_string('exam_access_wait', 'mod_bizexaminer', userdate($attemptfinished + $delay));
            }
        }

        return true;
    }

    /**
     * Starts an attempt
     *
     * The caller should have checked if the user has the capability to attempt the exam
     * depending on the context and run $this->can_access
     * TODO: maybe check can_access in this service here?
     *
     * @param exam $exam
     * @param int $userid
     * @throws bizexaminer_exception
     * @return string The url to access the booking
     */
    public function start_attempt(exam $exam, int $userid): string {
        $api = $this->get_api();

        // 1. Validate exam configuration.
        /** @var exam_modules $exammodulesservice */
        $exammodulesservice = bizexaminer::get_instance()->get_service('exammodules');
        if (!$exammodulesservice->has_exam_module_content_revision($exam->get_exam_module_id())) {
            throw new bizexaminer_exception('exam_module_invalid', 'mod_bizexaminer');
        }

        // 2. Check/create participant.
        $participant = $this->get_participant($userid);
        if (!$participant) {
            throw new bizexaminer_exception('exam_error_participant', 'mod_bizexaminer');
        }

        // 3. Create valid to/from dates for booking.
        $validstart = util::create_date(strtotime('now'));
        $validend = util::create_date($validstart->getTimestamp());
        $validend->modify('+24 hours'); // Default duration for valid.

        // Maybe check if timeclose is earlier than default duration for valid.
        if ($exam->timeclose) {
            $validendtimeclose = clone $exam->timeclose;
            if ($exam->overduehandling === exam::OVERDUE_GRACEPERIOD) {
                $validendtimeclose->modify("+{$exam->graceperiod} seconds");
            }
            if ($validendtimeclose < $validend) {
                $validend = $validendtimeclose;
            }
        }

        // 4. Create Attempt.
        $previousattemptscount = attempt::count(['examid' => $exam->id, 'userid' => $userid]);
        $attempt = new attempt();
        $attempt->userid = $userid;
        $attempt->examid = $exam->id;
        $attempt->participantid = $participant;
        $attempt->timecreated = util::create_date(time());
        $attempt->timemodified = util::create_date(time());
        $attempt->secretkey = attempt::generate_secret_key();
        $attempt->attempt = $previousattemptscount + 1;
        $attempt->validto = $validend;

        $saved = attempt::save($attempt);
        if (!$saved) {
            throw new bizexaminer_exception('exam_error_save_attempt', 'mod_bizexaminer');
        }

        // 5. Create Booking in bizExaminer.
        /** @var callback_api $callbackapi */
        $callbackapi = bizexaminer::get_instance()->get_service('callbackapi');
        $returnurl = $callbackapi->make_url(callback_api::ACTIONS['examcompleted'],
            ['attemptid' => $attempt->id, 'key' => $attempt->secretkey])->out(false);
        $callbackurl = $callbackapi->make_url(callback_api::ACTIONS['callback'],
            ['attemptid' => $attempt->id, 'key' => $attempt->secretkey])->out(false);

        $booking = $api->book_exam(
            $exam->productpartsid,
            $exam->contentrevision,
            $participant,
            $returnurl,
            $callbackurl,
            $exam->remoteproctor,
            $exam->remoteproctoroptions[$exam->remoteproctortype] ?? [],
            util::get_lang(),
            $validstart,
            $validend,
            $exam->password
        );

        if (!$booking) {
            throw new bizexaminer_exception('exam_error_booking', 'mod_bizexaminer');
        }

        // 6. Save booking infos in attempt.
        $attempt->bookingid = $booking['bookingId'];
        $attempt->status = attempt::STATUS_STARTED;
        $attempt->timemodified = util::create_date(time());
        $attempt->validto = $validend;

        $saved = attempt::save($attempt);

        if (!$saved) {
            // Delete attempt and ignore errors while deleting.
            attempt::delete($attempt->id);
            throw new bizexaminer_exception('exam_error_save_attempt', 'mod_bizexaminer');
        }

        // 7. Schedule Fetch results in advance, as a fallback if user does not return and callbacks fail.
        $this->maybe_reschedule_results_check($attempt);

        // TODO: Maybe log moodle event that exam was started (#10).

        return $booking['url'];;
    }

    /**
     * Finish an attempt
     *
     * @param attempt $attempt
     * @param exam $exam
     * @throws bizexaminer_exception
     * @return bool|int -1 when attempt is now aborted; false on error; true on success
     */
    public function end_attempt(attempt $attempt, exam $exam) {
        if ($attempt->status !== attempt::STATUS_STARTED) {
            return false;
        }

        // Check if the user is still allowed to finish the attempt.
        if (!$this->check_finish_after_timeclose($exam)) {
            $attempt->status = attempt::STATUS_ABORTED;
            $attempt->timemodified = util::create_date(time());
            attempt::save($attempt);
            return -1;
        }

        $attempt->status = attempt::STATUS_PENDING_RESULTS;
        $attempt->timemodified = util::create_date(time());

        $saved = attempt::save($attempt);
        if (!$saved) {
            throw new bizexaminer_exception('exam_error_save_attempt', 'mod_bizexaminer');
        }

        try {
            $this->fetch_results($attempt);
        } catch (bizexaminer_exception $exception) { //phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
            // Silence exceptions, results can be fetched later as well.
        }

        return true;
    }

    /**
     * Fetches results from api and stores them.
     * If no results are available a cron job to fetch them later is scheduled.
     *
     * @param attempt $attempt
     * @throws bizexaminer_exception
     * @return bool
     */
    public function fetch_results(attempt $attempt): bool {
        if ($attempt->status !== attempt::STATUS_PENDING_RESULTS) {
            return false;
        }

        $exam = $attempt->get_exam();
        if (!$exam) {
            return false;
        }

        // Do not check for timeopen/timeclose.
        // Fetching results (eg via callback) should always be possible.

        if ($attempt->hasresults) {
            $results = $attempt->get_results();
            // Results already exist but somehow status is wrong -> updated id and bail early.
            if ($results) {
                $attempt->status = attempt::STATUS_COMPLETED;
                $savedattempt = attempt::save($attempt);
                return (bool) $savedattempt;
            } else {
                // Results need to be fetched, but correct hasresults flag.
                // Do not save.
                $attempt->hasresults = false;
            }

        }

        // Try to fetch results, if not schedule cron.
        $api = $this->get_api();
        $allresults = $api->get_participant_overview_with_details($attempt->participantid, $attempt->bookingid);

        if (empty($allresults) || !isset($allresults[0]->result)) {
            $this->maybe_reschedule_results_check($attempt);
            return false;
        } else {
            // Unschedule cron if results are available.
            $this->unschedule_results_check($attempt);
        }

        $rawresults = $allresults[0];

        $results = $this->build_results_from_raw_results($rawresults);
        $results->attemptid = $attempt->id;

        $savedresults = attempt_results::save($results);
        if (!$savedresults) {
            throw new bizexaminer_exception('exam_error_save_results', 'mod_bizexaminer');
        }

        $attempt->status = attempt::STATUS_COMPLETED;
        $attempt->hasresults = true;
        $attempt->timemodified = util::create_date(time());

        $savedattempt = attempt::save($attempt);
        if (!$savedattempt) {
            throw new bizexaminer_exception('exam_error_save_attempt', 'mod_bizexaminer');
        }

        if ($exam && grading::has_grading($exam->grade)) {
            // Calculate Grades.
            /** @var grading $gradingservice */
            $gradingservice = bizexaminer::get_instance()->get_service('grading');
            $gradingservice->save_grade($attempt->examid, $attempt->userid);
            // Write to Gradebook API.
            bizexaminer_update_grades($exam->get_activity_module(), $attempt->userid);
        }

        return true;
    }

    /**
     * Delete attempt and results and recalculate grade
     *
     * @param attempt $attempt
     * @return bool
     */
    public function delete_attempt(attempt $attempt): bool {
        // Also deletes attempt results.
        $deleted = attempt::delete($attempt->id);
        $exam = $attempt->get_exam();
        if ($deleted && $exam && grading::has_grading($exam->grade)) {
            // Recalculate Grades.
            /** @var grading $gradingservice */
            $gradingservice = bizexaminer::get_instance()->get_service('grading');
            $gradingservice->save_grade($attempt->examid, $attempt->userid);
            // Write to Gradebook API.
            bizexaminer_update_grades($exam->get_activity_module(), $attempt->userid);
            return true;
        }
        return (bool)$deleted;
    }

    /**
     * Deletes all attempts, results and custom grades for an exam
     * Does NOT delete grades from gradebook api
     *
     * @param exam $exam
     * @return bool
     */
    public function delete_all_attempts(exam $exam): bool {
        // Delete all attempts and their results and grades from our custom grades table.
        $deletedattempts = attempt::delete_all(['examid' => $exam->id]); // Handles deleting results.
        $deletedgrades = exam_grade::delete_all(['examid' => $exam->id]);

        return $deletedattempts && $deletedgrades;
    }

    /**
     * Deletes all attempts, results and custom grades for a SINGLE user in an exam
     * Does NOT delete grades from gradebook api
     *
     * @param exam $exam
     * @return bool
     */
    public function delete_user_attempts(int $userid, exam $exam): bool {
        // Delete all attempts and their results and grades from our custom grades table.
        $deletedattempts = attempt::delete_all(['examid' => $exam->id, 'userid' => $userid]); // Handles deleting results.
        $deletedgrades = exam_grade::delete_all(['examid' => $exam->id, 'userid' => $userid]);

        return $deletedattempts && $deletedgrades;
    }

    /**
     * Gets the direct exam access url to an exam.
     * This url should not be stored (see #42)
     *
     * This will also return false/error if the booking is not valid anymore (=expired).
     *
     * @param attempt $attempt
     * @return string|false
     */
    public function get_exam_accessurl(attempt $attempt) {
        if ($attempt->status !== attempt::STATUS_STARTED) {
            return false;
        }

        $api = $this->get_api();
        $examurl = $api->get_examination_accessurl($attempt->bookingid, util::get_lang());

        return $examurl;
    }

    /**
     * Get an participant for bizExaminer.
     * Checks for an existing or creates a new one for the user.
     *
     * @param int $userid
     * @return false|string
     */
    protected function get_participant(int $userid) {
        global $USER;
        $participantid = null;

        // TODO: Mabe give the user a permanent participant id (#12).

        $api = $this->get_api();

        // If there's a valid, loggedin user (may be 0 if current user is not logged in).
        if ($userid) {
            $participantid = $api->check_participant([
                'firstName' => $USER->firstname,
                'lastName' => $USER->lastname,
                'email' => $USER->email,
            ]);

            if (!$participantid) {
                // Do not catch exceptions here, let caller handle since it's a protected method.
                $newparticipantid = $api->create_participant(
                    $USER->firstname,
                    $USER->lastname,
                    $USER->email
                );

                if ($newparticipantid) {
                    $participantid = $newparticipantid;
                }
            }
        }
        // TODO: Maybe handle guest users (#13)?

        if (!$participantid) {
            return false;
        }
        return $participantid;
    }

    /**
     * Check if finishing the exam is still allowed/possible after the time of the exam has closed.
     * Depends on overduehandling.
     *
     * @param exam $exam
     * @return bool
     */
    protected function check_finish_after_timeclose(exam $exam): bool {
        $now = util::create_date(time());
        if ($exam->timeclose && $now > $exam->timeclose) {
            // If a graceperiod is given - check if its still in the graceperiod.
            if ($exam->overduehandling === exam::OVERDUE_GRACEPERIOD) {
                if (!$exam->graceperiod || ($exam->timeclose->getTimestamp() + $exam->graceperiod) < $now->getTimestamp()) {
                    return false;
                }
            } else if ($exam->overduehandling === exam::OVERDUE_CANCEL) {
                return false;
            }
        }
        return true;
    }

    protected function maybe_reschedule_results_check(attempt $attempt) {
        $crontask = fetch_results::instance($attempt->id, $attempt->userid);

        $crontask->set_next_run_time(time() + MINSECS * 5);
        \core\task\manager::reschedule_or_queue_adhoc_task($crontask);
    }

    protected function unschedule_results_check(attempt $attempt) {
        $crontask = fetch_results::instance($attempt->id, $attempt->userid);
        util::unschedule_adhoc_task($crontask);
    }

    /**
     * Builds results for moodle plugin format from bizExaminer raw format.
     *
     * @param stdClass $rawresults
     * @return attempt_results
     */
    protected function build_results_from_raw_results(stdClass $rawresults): attempt_results {
        $results = new attempt_results();
        $results->whenfinished = date_create($rawresults->whenFinished, new \DateTimeZone("UTC"));
        $results->timetaken = intval($rawresults->timeTaken);
        $results->result = floatval($rawresults->result);
        $results->pass = $rawresults->passed === 'Pass';
        $results->achievedscore = intval($rawresults->achievedScore);
        $results->maxscore = intval($rawresults->maxScore);
        $results->certificateurl = $rawresults->certDownloadUrl ?? null;

        $questions = 0;
        $questionscorrect = 0;

        if (isset($rawresults->questionDetails)) {
            foreach ($rawresults->questionDetails->blocks as $block) {
                foreach ($block->questions as $question) {
                    $questions++;
                    // Assume all questions with more than 0 points answered correctly
                    // not fully right, but enough for this stat.
                    // TODO: no way to get correct questions atm, only reached & max points.
                    if ($question->points_reached > 0) {
                        $questionscorrect++;
                    }
                }
            }
        }

        $results->questionscount = $questions;
        $results->questionscorrectcount = $questionscorrect;

        return $results;
    }
}
