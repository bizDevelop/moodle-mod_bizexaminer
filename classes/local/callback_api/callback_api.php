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
 * Main Handler for the callback api
 *
 * @package     mod_bizexaminer
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bizexaminer\local\callback_api;

use coding_exception;
use mod_bizexaminer\local\api\exams;
use mod_bizexaminer\bizexaminer;
use mod_bizexaminer\bizexaminer_exception;
use mod_bizexaminer\local\data_objects\attempt;
use mod_bizexaminer\local\data_objects\exam;
use mod_bizexaminer\util;
use moodle_url;
use required_capability_exception;

/**
 * A service which handles requests via the callback api to trigger certain actions.
 *
 * @package mod_bizexaminer
 */
class callback_api {

    /**
     * The actions this callback api can handle
     */
    public const ACTIONS = [
        'startexam' => 'startexam',
        'examcompleted' => 'examcompleted',
        'callback' => 'callback',
        'deleteattempt' => 'deleteattempt',
    ];

    /**
     * Whether the callback api can handle this action
     * @param string $action
     * @return bool
     */
    public function has_action(string $action): bool {
        return array_key_exists($action, self::ACTIONS);
    }

    /**
     * Handle a specific action.
     * @param string $action
     * @return mixed
     * @throws coding_exception
     */
    public function handle(string $action) {
        if (!$this->has_action($action)) {
            throw new \coding_exception(
                "$action cannot be handled by callback_api. please check with has_action before calling it.");
        }

        switch($action) {
            case self::ACTIONS['startexam']:
                return $this->handle_start_exam();
                break;
            case self::ACTIONS['examcompleted']:
                return $this->handle_exam_completed();
                break;
            case self::ACTIONS['callback']:
                return $this->handle_callback();
                break;
            case self::ACTIONS['deleteattempt']:
                return $this->handle_delete_attempt();
                break;
        }
    }

    /**
     * Build a url for the callback api.
     *
     * @param string $action
     * @param array $args
     * @return moodle_url
     * @throws coding_exception
     */
    public function make_url(string $action, array $args = []): moodle_url {
        if (!$this->has_action($action)) {
            throw new \coding_exception("$action is not an available action in callback_api");
        }

        return new moodle_url('/mod/bizexaminer/callback.php', array_merge($args, [
            'cbaction' => $action,
        ]));
    }

    /**
     * Handles the startexam action
     * @throws required_capability_exception
     */
    protected function handle_start_exam() {
        global $USER;
        $examid = required_param('examid', PARAM_INT);
        $userid = $USER->id;

        // Check nonce.
        require_sesskey();

        util::log('callback api: startexam (examid: ' . $examid . ')', DEBUG_ALL);

        $exam = exam::get($examid);
        if (!$exam) {
            util::log('invalid examid passed to callback api (' . $examid . ')');
            \core\notification::error(get_string('error_exam_not_found', 'mod_bizexaminer'));
            redirect(new moodle_url('/mod/bizexaminer/view.php', ['examid' => $examid]));
        }

        // Check login and capabilitiy.
        require_login($exam->course);
        // Need to check after exam exists, to get correct context.
        $coursemodule = util::get_coursemodule($exam->id, $exam->course);
        $context = util::get_cm_context($coursemodule);
        // May throw capability exception and show to user.
        require_capability('mod/bizexaminer:attempt', $context);

        /** @var exams $examsservice */
        $examsservice = bizexaminer::get_instance()->get_service('exams', $exam->get_api_credentials());
        try {
            $canaccess = $examsservice->can_access($exam, $userid);
            // Check if user can attempt.
            if ($canaccess !== true) {
                util::log('user tried to access exam but was not allowed. error message: ' . $canaccess);
                \core\notification::error($canaccess);
                redirect(new moodle_url('/mod/bizexaminer/view.php', ['examid' => $examid]));
            }
            $examurl = $examsservice->start_attempt($exam, $userid);
            if (!$examurl) {
                // A general error ocurred.
                // Throw exception so catch block is triggered.
                throw new bizexaminer_exception('error_general', 'mod_bizexaminer');
            }
        } catch (bizexaminer_exception $exception) {
            // If any bizexaminer error happens log it with contextual information
            // and redirect the user back to the exam view to show a generic error message.
            $exception->add_debug_info(['examid' => $exam->id, 'userid' => $userid]);
            util::log_exception($exception);
            \core\notification::error(get_string('error_general', 'mod_bizexaminer'));
            if ($exception->apierror) {
                \core\notification::error($exception->apierror->get_message());
            }
            redirect(new moodle_url('/mod/bizexaminer/view.php', ['examid' => $examid]));
        }

        // If attempt was saved successfully, redirect user to bizExaminer.
        redirect($examurl);
    }

    /**
     * Handles the examcompleted action
     * Handles when the user gets redirected back from bizExaminer on exam completion.
     * Errors are logged and added as notifications to the session,
     * then the user gets redirected to an appropriate page (mostly view.php)
     * Use view.php instead of attempt.php to show general grade, certificat einfo (see #81).
     *
     */
    protected function handle_exam_completed() {
        global $USER;

        $attemptid = required_param('attemptid', PARAM_INT);

        util::log('callback api: examcompleted (attemptid: ' . $attemptid . ')', DEBUG_ALL);

        // 1. Get all objects.
        $attempt = attempt::get($attemptid);
        if (!$attempt) {
            // No attemptid/examid therefore just redirect to root to show session notification.
            util::log('invalid attemptid passed to callback api (' . $attemptid . ')');
            \core\notification::error(get_string('error_general', 'mod_bizexaminer'));
            redirect(new moodle_url('/'));
        }

        $examurl = new moodle_url('/mod/bizexaminer/view.php', ['examid' => $attempt->examid]);

        $exam = $attempt->get_exam();
        if (!$exam) {
            // Log error and redirect user to overview.
            util::log('could not find exam for attempt ' . $attemptid);
            \core\notification::error(get_string('error_general', 'mod_bizexaminer'));
            redirect($examurl);
        }

        // 2. Require login based on course and check capability to attempt.
        require_login($exam->course);
        $coursemodule = util::get_coursemodule($exam->id, $exam->course);
        $context = util::get_cm_context($coursemodule);
        // May throw capability exception and show to user.
        require_capability('mod/bizexaminer:attempt', $context);

        // 3. Check it's the same user.
        if ((int)$USER->id !== $attempt->userid) {
            util::log(
                'another user (' . $USER->id . ') than the one starting the attempt has called the \
                exam completed callback for attempt ' . $attemptid);
            \core\notification::error(get_string('callbackapi_differentuser', 'mod_bizexaminer'));
            redirect($examurl);
        }

        // 4. Check if secret key is valid
        // Security key, to prevent unauthorized access
        // instead of sesskey nonce, because it needs to have a long lifetime.
        // Need to use PARAM_NOTAGS and not PARAM_ALPHANUMEXT since it could contain symbols.
        $userkey = required_param('key', PARAM_NOTAGS);
        if (!$attempt->is_key_valid($userkey)) {
            util::log('invalid key provided for attempt ' . $attemptid);
            \core\notification::error(get_string('error_general', 'mod_bizexaminer'));
            redirect($examurl);
        }

        // 5. Additionally compare the participant passed via URL and stored in attempt.
        // Just for completeness sake.
        $participantid = required_param('be:participantID', PARAM_INT);
        if ($attempt->participantid !== $participantid) {
            util::log('invalid participant provided for attempt ' . $attemptid);
            \core\notification::error(get_string('error_general', 'mod_bizexaminer'));
            redirect($examurl);
        }

        // 6. Maybe callback was already called to finish exam and maybe fetch results
        // Do not try to fetch results, just redirect user to view.
        // If it's still pending, the cron or callback my fetch the results later.
        // Though end_attempt does check for this as well, we want to redirect early without any exception/error.
        if ($attempt->status !== attempt::STATUS_STARTED) {
            redirect($examurl);
        }

        /** @var exams $examsservice */
        $examsservice = bizexaminer::get_instance()->get_service('exams', $exam->get_api_credentials());
        try {
            $ended = $examsservice->end_attempt($attempt, $exam);
            if ($ended === -1) {
                \core\notification::error(get_string('exam_access_timeclosed', 'mod_bizexaminer'));
            } else if (!$ended) {
                // Throw exception so catch block is triggered.
                throw new bizexaminer_exception('error_general', 'mod_bizexaminer');
            }
        } catch (bizexaminer_exception $exception) {
            // If any bizexaminer error happens log it with contextual information
            // and redirect the user back to the exam view to show a generic error message.
            $exception->add_debug_info(['attemptid' . $attempt->id, 'examid' => $exam->id]);
            util::log_exception($exception);
            \core\notification::error(get_string('error_general', 'mod_bizexaminer'));
        }

        // Always redirect to activity module overview.
        redirect($examurl);
    }

    /**
     * Handles the callback action
     * Handles callbacks from the bizExaminer API.
     * Since these happen without any user interaction errors are just logged and the script exits/dies
     * with a status 403.
     * No require_login and no require_capability, since these are sent from the bizExaminer API.
     */
    public function handle_callback() {
        $attemptid = required_param('attemptid', PARAM_INT);

        util::log('callback api: callback (attemptid: ' . $attemptid . ')', DEBUG_ALL);

        // 1. Get all objects.
        $attempt = attempt::get($attemptid);
        if (!$attempt) {
            $exception = new bizexaminer_exception('callbackapi_invalidattempt', 'mod_bizexaminer', '', null,
                'attemptid: ' . $attemptid);
            util::log_exception($exception);
            $this->die(403);
        }

        $exam = $attempt->get_exam();
        if (!$exam) {
            $exception = new bizexaminer_exception('callbackapi_invalidexam', 'mod_bizexaminer', '', null,
                'attemptid: ' . $attemptid);
            util::log_exception($exception);
            $this->die(403);
        }

        // Do not require login, since this is called by the API as a webhook without user interaction.

        // 2. Check if secret key is valid
        // Security key, to prevent unauthorized access
        // instead of sesskey, because it needs to have a long lifetime.
        // Need to use PARAM_NOTAGS and not PARAM_ALPHANUMEXT since it could contain symbols.
        $userkey = required_param('key', PARAM_NOTAGS);
        if (!$attempt->is_key_valid($userkey)) {
            $exception = new bizexaminer_exception('callbackapi_invalidkey', 'mod_bizexaminer', '', null,
                'attemptid: ' . $attemptid);
            util::log_exception($exception);
            $this->die(403);
        }

        $eventtype = required_param('eventType', PARAM_ALPHAEXT);

        /** @var exams $examsservice */
        $examsservice = bizexaminer::get_instance()->get_service('exams', $exam->get_api_credentials());

        util::log('callback api event callback with eventtype ' . $eventtype . ' called', DEBUG_ALL);

        switch ($eventtype) {
             // User finished the exam, does not necessarily mean results are available
             // results are handled by exam_evaluated callback.
            case 'exam_finished':
                $ended = null;
                try {
                    // Does also check for correct status.
                    $ended = $examsservice->end_attempt($attempt, $exam);
                    if (!$ended) {
                        // Throw exception so catch block is triggered.
                        throw new bizexaminer_exception('error_general', 'mod_bizexaminer');
                    }
                } catch (bizexaminer_exception $exception) {
                    // If any bizexaminer error happens log it with contextual information and exit.
                    $exception->add_debug_info(['attemptid' => $attempt->id, 'examid' => $exam->id]);
                    util::log_exception($exception);
                    $this->die(403);
                }

                if ($ended) {
                    $this->die(200);
                } else {
                    $this->die(403);
                }
                break;
            // Results are available
            // either directly after finishing or after manual evaluation.
            case 'exam_evaluated':
                $saved = null;
                try {
                    $saved = $examsservice->fetch_results($attempt);
                    if (!$saved) {
                        // Throw exception so catch block is triggered.
                        throw new bizexaminer_exception('error_general', 'mod_bizexaminer');
                    }
                } catch (bizexaminer_exception $exception) {
                    // If any bizexaminer error happens log it with contextual information and exit.
                    $exception->add_debug_info(['attemptid' => $attempt->id, 'examid' => $exam->id]);
                    util::log_exception($exception);
                    $this->die(403);
                }
                if ($saved) {
                    $this->die(200);
                } else {
                    $this->die(403);
                }
                break;
            case 'exam_started':
            case 'exam_sent_to_manual_evaluation':
            case 'exam_insight_pdf_available':
            case 'exam_archive_pdf_available':
            default:
                $this->die(200); // Default to 200 OK status.
        }
    }

    /**
     * Handles the deleteattempt action
     * Executed in the course/module view for teachers.
     */
    protected function handle_delete_attempt() {
        $attemptid = required_param('attemptid', PARAM_INT);

        require_sesskey();

        util::log('callback api: delete attempt (attemptid: ' . $attemptid . ')', DEBUG_ALL);

        $attempt = attempt::get($attemptid);
        if (!$attempt) {
            util::log('invalid attemptid passed to callback api (' . $attemptid . ')');
            \core\notification::error(get_string('error_general', 'mod_bizexaminer'));
            redirect(new moodle_url('/mod/bizexaminer/attempts.php'));
        }
        $exam = $attempt->get_exam();

        // Need to check after attempt exists, to check correct context.
        $coursemodule = util::get_coursemodule($exam->id, $exam->course);
        $context = util::get_cm_context($coursemodule);
        // May throw capability exception and show to user.
        require_capability('mod/bizexaminer:deleteanyattempt', $context);

        /** @var exams $examsservice */
        $examsservice = bizexaminer::get_instance()->get_service('exams', $exam->get_api_credentials());
        $deleted = $examsservice->delete_attempt($attempt);

        if (!$deleted) {
            util::log('error deleting attempt (' . $attemptid . ')');
            \core\notification::error(get_string('error_general', 'mod_bizexaminer'));
            redirect(new moodle_url('/mod/bizexaminer/attempts.php', ['examid' => $attempt->examid]));
        } else {
            util::log('deleted attempt (' . $attemptid . ')');
            \core\notification::success(get_string('deletedattempt', 'mod_bizexaminer'));
            redirect(new moodle_url('/mod/bizexaminer/attempts.php', ['examid' => $attempt->examid]));
        }
    }

    /**
     * Helper to set the http response status and die.
     * @param int $code
     * @return never
     */
    protected function die($code) {
        http_response_code($code);
        die();
    }

    /**
     * Silent exception handler.
     *
     * @return callable exception handler
     */
    public static function get_exception_handler() {
        return function($exception) {
            util::log_exception($exception);

            if (http_response_code() == 200) {
                http_response_code(500);
            }

            die();
        };
    }
}
