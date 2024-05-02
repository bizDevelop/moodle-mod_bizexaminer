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
 * Exam modules service/helper.
 *
 * @package     mod_bizexaminer
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bizexaminer\local\gradebook;

use mod_bizexaminer\bizexaminer_exception;
use mod_bizexaminer\local\data_objects\attempt;
use mod_bizexaminer\local\data_objects\exam_grade;
use mod_bizexaminer\local\data_objects\exam;
use mod_bizexaminer\local\data_objects\exam_feedback;
use mod_bizexaminer\util;
use stdClass;

/**
 * Service for calculating/managing grades and syncinc to gradebook api
 * @package mod_bizexaminer
 */
class grading {
    /**
     * Grademethod "highest" - use best attempt to calculate grade.
     * @var int
     */
    public const GRADEHIGHEST = 1;

    /**
     * Grademethod "average" - use average of all attempts to calculate grade.
     * @var int
     */
    public const GRADEAVERAGE = 2;

    /**
     * Grademethod "first" - use first attempt to calculate grade.
     * @var int
     */
    public const GRADEFIRST = 3;

    /**
     * Grademethod "last" - use last attempt to calculate grade.
     * @var int
     */
    public const GRADELAST = 4;

    /**
     * Calculates the grade for an exam and user
     * and saves it into bizexaminer_grades table
     *
     * bizexaminer_update_grades should be called afterwards so its synced to gradebook
     *
     * @param int $examid
     * @param int $userid
     * @return bool
     * @throws bizexaminer_exception
     */
    public function save_grade(int $examid, int $userid): bool {
        // Delete any previous grades - therefore errors don't need any handling
        // because bizexaminer_update_grades will then store a null raw grade.
        exam_grade::delete_all(['examid' => $examid, 'userid' => $userid]);
        $exam = exam::get($examid);
        if (!$exam) {
            return false;
        }

        $attemptgrade = $this->build_grade($exam, $userid);
        if ($attemptgrade) {
            $savedgrade = exam_grade::save($attemptgrade);
            if (!$savedgrade) {
                throw new bizexaminer_exception('exam_error_save_results', 'mod_bizexaminer');
            }
            return true;
        }
        return false;
    }

    /**
     * Builds the raw grade (=float) based on the grademethod
     * @param exam $exam
     * @param int $userid
     * @return null|exam_grade
     */
    protected function build_grade(exam $exam, int $userid): ?exam_grade {
        /** @var moodle_database $DB */ // phpcs:ignore moodle.Commenting.InlineComment.TypeHintingMatch
        global $DB;

        if (self::has_grading($exam->grade)) {
            $result = null; // Percentage calculated from bizExaminer results.
        }
        $timesubmitted = null; // Time of the attempt.

        // 1. Get the matching attempt results depending on the grade method.
        if ($exam->grademethod === self::GRADEAVERAGE) {
            $averageresult = $DB->get_record_sql('
            SELECT AVG(res.result) as avgresult, MAX(res.whenfinished) AS lastwhenfinished
                FROM {bizexaminer_attempts} att
                JOIN {bizexaminer_attempt_results} res ON res.attemptid = att.id
            WHERE att.examid = ?
                AND att.userid = ?
                AND att.status = ?
        ', [$exam->id, $userid, attempt::STATUS_COMPLETED]);
            if ($averageresult) {
                $result = (float)($averageresult->avgresult);
                $timesubmittedtimestamp = (int)$averageresult->lastwhenfinished;
                $timesubmitted = util::create_date($timesubmittedtimestamp);
            }
        } else {
            $attempt = $this->get_attempt_for_grading($exam, $userid, $exam->grademethod);
            if ($attempt) {
                $attemptresults = $attempt->get_results();
                if ($attemptresults) {
                    $result = $attemptresults->result;
                    $timesubmitted = $attemptresults->whenfinished;
                }
            }
        }

        if (!$result || !$timesubmitted) {
            return null;
        }

        // 2. Calculate grade based on result
        $gradepoints = $this->calculate_grade_from_result($result, $exam->grade);
        $gradepoints = (float)($gradepoints);

        $attemptgrade = new exam_grade();
        $attemptgrade->examid = $exam->id;
        $attemptgrade->userid = $userid;
        $attemptgrade->grade = $gradepoints;
        $attemptgrade->timemodified = util::create_date(time());
        $attemptgrade->timesubmitted = $timesubmitted ?? util::create_date(time());

        return $attemptgrade;
    }

    /**
     * Get the matching attempt depending on the grademethod.
     * Can also be used to just get the attempt with the highest score.
     *
     * @param exam $exam
     * @param int $userid
     * @param int $grademethod - defaults to self::GRADEHIGHEST
     * @return null|attempt
     */
    public function get_attempt_for_grading(exam $exam, int $userid, $grademethod = self::GRADEHIGHEST): ?attempt {
        $attemptsql = '
            SELECT att.*
                FROM {bizexaminer_attempts} att
                JOIN {bizexaminer_attempt_results} res ON res.attemptid = att.id
            WHERE att.examid = ?
                AND att.userid = ?
                AND att.status = ?
                AND att.hasresults = 1
        ';

        switch($grademethod) {
            case self::GRADEHIGHEST:
                $attempt = attempt::get_by_sql($attemptsql . '
                    ORDER BY res.result DESC
                    LIMIT 1
                ', [$exam->id, $userid, attempt::STATUS_COMPLETED]);
                return $attempt;
            case self::GRADEFIRST:
                $attempt = attempt::get_by_sql($attemptsql . '
                    ORDER BY res.whenfinished ASC
                    LIMIT 1
                ', [$exam->id, $userid, attempt::STATUS_COMPLETED]);
                return $attempt;
            case self::GRADELAST:
                $attempt = attempt::get_by_sql($attemptsql . '
                    ORDER BY res.whenfinished DESC
                    LIMIT 1
                ', [$exam->id, $userid, attempt::STATUS_COMPLETED]);
                return $attempt;
            case self::GRADEAVERAGE:
            default:
                return null;
        }
    }

    /**
     * This calculates and saves grades for all users
     *
     * TODO: change this to run in database so it's more performant when run for a lot of users
     *
     * @param int $examid
     * @return bool
     */
    public function save_grades(int $examid): bool {
        $exam = exam::get($examid);
        if (!$exam) {
            return false;
        }

        // TODO: maybe use get_recordset_sql here for large number of objects
        // TODO: maybe use transactions for deleting and saving all?
        $previousgrades = exam_grade::get_all(['examid' => $examid]);

        $savedno = 0;

        foreach ($previousgrades as $previousgrade) {
            $newgrade = $this->build_grade($exam, $previousgrade->userid);
            exam_grade::delete($previousgrade->id); // Delete previous grade.
            $saved = exam_grade::save($newgrade); // Svae new grade.
            if ($saved) {
                $savedno++;
            }
        }

        return $savedno === count($previousgrades);
    }

    /**
     * Returns the grade for a single user for a single exam/activity module instance
     *
     * @param int $examid
     * @param int $userid
     * @return stdClass|null grade object in format for bizexaminer_grade_item_update
     */
    public function get_user_grade($examid, $userid): ?stdClass {
        $attemptgrade = exam_grade::get_by(['examid' => $examid, 'userid' => $userid]);
        if (!$attemptgrade) {
            return null;
        }
        $gradeobject = $this->create_grade_object_from_exam_grade($attemptgrade);
        return $gradeobject;
    }

    /**
     * Returns the grades for all users for a single exam/activity module instance
     * Only return users who have attempted this exam
     *
     * @param int $examid
     * @return stdClass[] grade objects in format for bizexaminer_grade_item_update, indexed by userid
     */
    public function get_users_grade(int $examid): array {
        $attemptgrades = exam_grade::get_all(['examid' => $examid]);
        $gradeobjs = [];

        foreach ($attemptgrades as $attemptgrade) {
            $userid = $attemptgrade->userid;
            $gradeobjs[$userid] = $this->create_grade_object_from_exam_grade($attemptgrade);
        }

        return $gradeobjs;
    }

    /**
     * Get the grading info from the gradebook
     * Retuns an object with keys from a single grade for a single user (see grade_get_grades)
     * and grademin, grademax, gradepass properties (for comparison).
     *
     * @param exam $exam
     * @param int $userid
     * @return null|stdClass
     */
    public function get_gradebook_grade(exam $exam, int $userid): ?stdClass {
        global $CFG;
        require_once($CFG->libdir . '/gradelib.php');
        /** @var \stdClass $gradinginfo */
        $gradinginfo = grade_get_grades($exam->course, 'mod', 'bizexaminer', $exam->id, $userid);

        if (!$gradinginfo || !isset($gradinginfo->items) || !isset($gradinginfo->items[0])) {
            return null;
        }

        $return = [
            'grademin' => $gradinginfo->items[0]->grademin,
            'grademax' => $gradinginfo->items[0]->grademax,
            'gradepass' => $gradinginfo->items[0]->gradepass,
        ];

        if (isset($gradinginfo->items[0]->grades[$userid])) {
            $return = array_merge($return, (array)$gradinginfo->items[0]->grades[$userid]);
        }

        return (object)$return;
    }

    /**
     * Gets the overall feedback corresponding to a particular grade (for all attempts).
     *
     * @param float $grade
     * @param int $examid
     * @return null|exam_feedback
     */
    public function get_feedback_for_grade(float $grade, int $examid): ?exam_feedback {
        // With CBM etc, it is possible to get -ve grades, which would then not match
        // any feedback. Therefore, we replace -ve grades with 0.
        $grade = max($grade, 0);

        // Get the feedback with the highest mingrade that is >= the current grade.
        $feedback = exam_feedback::get_by_select(
            'examid = ? AND mingrade <= ? ORDER BY mingrade DESC LIMIT 1',
            [$examid, $grade, $grade]
        );

        return $feedback;
    }

    /**
     * Calculate a raw grade from the bizExaminer percentage result
     * @param float $resultpercentage from bizExaminer
     * @param float $maxpoints configured in moodle as 'grade'
     * @return float
     */
    protected function calculate_grade_from_result(float $resultpercentage, float $maxpoints): float {
        $grade = $maxpoints * ($resultpercentage / 100);
        return $grade;
    }

    /**
     * Creates a grade object for _grade_item_ugprade
     *
     * @param exam_grade $attemptgrade
     * @return stdClass grade object in format for bizexaminer_grade_item_update
     */
    protected function create_grade_object_from_exam_grade(exam_grade $attemptgrade): stdClass {
        $gradeobject = (object)[
            'userid' => $attemptgrade->userid,
            'rawgrade' => $attemptgrade->grade,
            'dategraded' => $attemptgrade->timemodified->getTimestamp(),
            'datesubmitted' => $attemptgrade->timesubmitted->getTimestamp(),
            // TODO: maybe add feedback message?
        ];
        return $gradeobject;
    }

    /**
     * Get the available grade method options.
     * @return string[]
     */
    public static function get_grademethod_options(): array {
        return [
            self::GRADEHIGHEST => get_string('gradehighest', 'mod_bizexaminer'),
            self::GRADEAVERAGE => get_string('gradeaverage', 'mod_bizexaminer'),
            self::GRADEFIRST => get_string('gradeattemptfirst', 'mod_bizexaminer'),
            self::GRADELAST => get_string('gradeattemptlast', 'mod_bizexaminer'),
        ];
    }

    /**
     * Note that Moodle scales are stored as a positive integer if they are numeric,
     * as a negative integer if they are a custom scale and 0 means the forum is ungraded
     * @param int $grade the grade stored in database for activity module
     * @return bool
     */
    public static function has_grading(int $grade): bool {
        return $grade > 0 || $grade < 0;
    }

    /**
     * Round a grade to to the correct number of decimal places, and format it for display.
     *
     * @param float $grade The grade to round.
     * @return string
     */
    public static function format_grade(float $grade): string {
        return format_float($grade);
    }
}
