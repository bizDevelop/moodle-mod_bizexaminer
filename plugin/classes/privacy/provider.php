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
 * Privacy Subsystem implementation for mod_bizexaminer.
 *
 * @package     mod_bizexaminer
 * @category    privacy
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bizexaminer\privacy;

use context;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;
use mod_bizexaminer\bizexaminer;
use mod_bizexaminer\data_objects\attempt;
use mod_bizexaminer\data_objects\attempt_results;
use mod_bizexaminer\data_objects\exam;
use mod_bizexaminer\gradebook\grading;
use mod_bizexaminer\util;

class provider implements
    // This plugin has data.
    \core_privacy\local\metadata\provider,

    // This plugin currently implements the original plugin_provider interface.
    \core_privacy\local\request\plugin\provider,

    // This plugin is capable of determining which users have data within it.
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Get the list of contexts that contain user information.
     *
     * @param collection $items The collection to add metadata to.
     * @return collection The array of metadata
     */
    public static function get_metadata(collection $collection): collection {
        // The table bizexaminer_attempts stores a record for each exam attempt for each user.
        $collection->add_database_table('bizexaminer_attempts', [
            'examid' => 'privacy:metadata:attempts:examid',
            'userid' => 'privacy:metadata:attempts:userid',
            'status' => 'privacy:metadata:attempts:status',
            'bookingid' => 'privacy:metadata:attempts:bookingid',
            'participantid' => 'privacy:metadata:attempts:participantid',
            'timecreated' => 'privacy:metadata:attempts:timecreated',
            'timemodified' => 'privacy:metadata:attempts:timemodified',
            'attempt' => 'privacy:metadata:attempts:attempt',
            'validto' => 'privacy:metadata:attempts:validto',
        ], 'privacy:metadata:attempts');

        // The table bizexaminer_attempt_results contains the detailed results for attempts.
        $collection->add_database_table('bizexaminer_attempt_results', [
            'userid' => 'privacy:metadata:attempt_results:userid',
            'attemptid' => 'privacy:metadata:attempt_results:attemptid',
            'whenfinished' => 'privacy:metadata:attempt_results:whenfinished',
            'timetaken' => 'privacy:metadata:attempt_results:timetaken',
            'result' => 'privacy:metadata:attempt_results:result',
            'pass' => 'privacy:metadata:attempt_results:pass',
            'achievedscore' => 'privacy:metadata:attempt_results:achievedscore',
            'maxscore' => 'privacy:metadata:attempt_results:maxscore',
            'questionscount' => 'privacy:metadata:attempt_results:questionscount',
            'questionscorrectcount' => 'privacy:metadata:attempt_results:questionscorrectcount',
            'certificateurl' => 'privacy:metadata:attempt_results:certificateurl',
        ], 'privacy:metadata:attempt_results');

        // The table bizexaminer_grades contains the grades for users for exams.
        $collection->add_database_table('bizexaminer_grades', [
            'examid' => 'privacy:metadata:grades:examid',
            'userid' => 'privacy:metadata:grades:userid',
            'grade' => 'privacy:metadata:grades:grade',
            'timemodified' => 'privacy:metadata:grades:timemodified',
            'timesubmitted' => 'privacy:metadata:grades:timesubmitted',
        ], 'privacy:metadata:grades');

        // The table bizexaminer_feedbacks does not contain any user-related data.
        // It contains only generic (for all students) texts entered by teachers.

        // The table bizexaminer does not contain any user-related data.
        // It contains the configuration for the exam.

        // The table bizexaminer_proctor_options does not contain any user-related ata.
        // It contains only configuration for the remote proctors.

        // The data sent to bizExaminer.
        $collection->add_external_location_link('bizexaminer', [
            'firstname' => 'privacy:metadata:bizexaminer:firstname',
            'lastname' => 'privacy:metadata:bizexaminer:lastname',
            'email' => 'privacy:metadata:bizexaminer:email',
        ], 'privacy:metadata:bizexaminer');

        // TODO: Data about remote proctoring? See #46.

        // Although mod_bizexaminer supports the core_completion API these will be
        // noted by the manager as all activity modules are capable of supporting this functionality.
        // See https://moodledev.io/docs/apis/subsystems/privacy#indicating-that-you-store-content-in-a-moodle-subsystem .

        return $collection;
    }

    /**
     * Get the list of contexts where the specified user has attempted an exam.
     *
     * Returns context ids from course_modules (=exams) where this user has an attempt.
     *
     * @param int $userid The user to search.
     * @return contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $resultset = new contextlist();

        // Users who attempted an exam.
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {bizexaminer} b ON b.id = cm.instance
                  JOIN {bizexaminer_attempts} ba ON ba.examid = b.id
                 WHERE ba.userid = :userid";
        $params = ['contextlevel' => CONTEXT_MODULE, 'modname' => 'bizexaminer', 'userid' => $userid];
        $resultset->add_from_sql($sql, $params);

        return $resultset;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * Returns all userids which have an attempt in a specific course module
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        // Users who attempted the exam.
        $sql = "SELECT ba.userid
            FROM {course_modules} cm
            JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            JOIN {bizexaminer} b ON b.id = cm.instance
            JOIN {bizexaminer_attempts} ba ON ba.examid = b.id
            WHERE cm.id = :cmid AND qa.preview = 0";
        $params = ['cmid' => $context->instanceid, 'modname' => 'bizexaminer'];
        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * Exports exams incl. grades and exam attempts inc. results (in subcontext).
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        if (!count($contextlist)) {
            return;
        }

        self::export_exams($contextlist);
        self::export_exam_attempts($contextlist);
    }

    /**
     * Delete all data for all users in the specified context.
     * Deletes attempts, results, grades.
     *
     * @param context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(context $context) {
        if ($context->contextlevel != CONTEXT_MODULE) {
            // Only activity module will be handled.
            return;
        }

        $cm = get_coursemodule_from_id('bizexaminer', $context->instanceid);
        if (!$cm) {
            // Only bizexaminer module will be handled.
            return;
        }

        $exam = exam::get($cm->instance);
        if (!$exam) {
            return;
        }
        /** @var exams $examsservice */
        $examsservice = bizexaminer::get_instance()->get_service('exams', $exam->get_api_credentials());

        // Delete attempts, results, grades, gradebook api.
        // This does NOT deletes gradebook grades as well, guess moodle core handles that?
        $examsservice->delete_all_attempts($exam);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     * Deletes attempts, results, grades for one user.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        foreach ($contextlist as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                // Only activity module will be handled.
                continue;
            }

            $cm = get_coursemodule_from_id('bizexaminer', $context->instanceid);
            if (!$cm) {
                // Only activity module will be handled.
                continue;
            }

            // Fetch the details of the data to be removed.
            $exam = exam::get($cm->instance);
            if (!$exam) {
                continue;
            }
            $user = $contextlist->get_user();

            $examsservice = bizexaminer::get_instance()->get_service('exams', $exam->get_api_credentials());

            // Delete attempts, results, grades.
            // This does NOT deletes gradebook grades as well, guess moodle core handles that?
            $examsservice->delete_user_attempts($user->id, $exam);
        }
    }

    /**
     * Delete multiple users within a single context.
     * Deletes attempts, results, grades for specified users.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        $context = $userlist->get_context();

        if ($context->contextlevel != CONTEXT_MODULE) {
            // Only activity module will be handled.
            return;
        }

        $cm = get_coursemodule_from_id('bizexaminer', $context->instanceid);
        if (!$cm) {
            // Only activity module will be handled.
            return;
        }

        $exam = exam::get($cm->instance);
        if (!$exam) {
            return;
        }
        $userids = $userlist->get_userids();

        $examsservice = bizexaminer::get_instance()->get_service('exams', $exam->get_api_credentials());

        foreach ($userids as $userid) {
            // Delete attempts, results, grades.
            // This does NOT deletes gradebook grades as well, guess moodle core handles that?
            $examsservice->delete_user_attempts($userid, $exam);
        }
    }

    /**
     * Store all exams including results for the contextlist.
     *
     * @param approved_contextlist $contextlist
     */
    protected static function export_exams(approved_contextlist $contextlist) {
        global $DB;
        $user = $contextlist->get_user();
        $userid = $user->id;

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT
                b.*,
                bg.id AS hasgrade,
                bg.grade AS grade,
                bg.timemodified AS grademodified,
                bg.timesubmitted AS gradesubmitted,
                c.id AS contextid,
                cm.id AS cmid
                FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {bizexaminer} b ON b.id = cm.instance
            LEFT JOIN {bizexaminer_grades} bg ON bg.examid = b.id AND bg.userid = :bguserid
                WHERE c.id {$contextsql}";

        $params = [
            'contextlevel'      => CONTEXT_MODULE,
            'modname'           => 'bizexaminer',
            'bguserid'          => $userid,
        ];
        $params += $contextparams;

        /** @var grading $gradingservice */
        $gradingservice = bizexaminer::get_instance()->get_service('grading');

        // Fetch the individual exams.
        $exams = $DB->get_recordset_sql($sql, $params);
        foreach ($exams as $exam) {
            list($course, $cm) = get_course_and_cm_from_cmid($exam->cmid, 'bizexaminer');
            $context = util::get_cm_context($cm);

            $data = \core_privacy\local\request\helper::get_context_data($context, $contextlist->get_user());
            \core_privacy\local\request\helper::export_context_files($context, $contextlist->get_user());

            writer::with_context($context)->export_data([], $data);

            if ($exam->hasgrade) {
                $gradedata = (object) [
                    'grade' => grading::format_grade($exam->grade),
                    'grademodified' => transform::datetime($exam->grademodified),
                    'gradesubmitted' => transform::datetime($exam->gradesubmitted),
                ];
                $feedback = $gradingservice->get_feedback_for_grade($exam->grade, $exam->id);
                if (!empty($feedback)) {
                    $gradedata->feedback = $feedback->feedbacktext;
                }
                writer::with_context($context)->export_data([get_string('grade_infos', 'mod_bizexaminer')], $gradedata);
            }

        }
        $exams->close(); // Close recordset.
    }

    /**
     * Store all exam attempts including results for the contextlist.
     *
     * @param approved_contextlist $contextlist
     */
    protected static function export_exam_attempts(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        $sql = "SELECT
                    c.id AS contextid,
                    cm.id AS cmid,
                    ba.*
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'bizexaminer'
                  JOIN {bizexaminer} b ON b.id = cm.instance
                  JOIN {bizexaminer_attempts} ba ON ba.examid = b.id
            WHERE ba.userid = :userid
        ";

        $params = [
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ];

        // Use recordset instead of data_object functions for performance.
        $attempts = $DB->get_recordset_sql($sql, $params);

        foreach ($attempts as $attempt) {
            $context = \context_module::instance($attempt->cmid);

            // This attempt was made by the user.
            // They 'own' all data on it.
            // Store the question usage data.

            // Store the exam attempt data.
            $data = (object) [
                'status' => attempt::attempt_status_label($attempt->status),
                'bookingid' => $attempt->bookingid,
                'participantid' => $attempt->participantid,
                'attempt' => $attempt->attempt,
            ];

            if ($attempt->timecreated) {
                $data->timecreated = transform::datetime($attempt->timecreated);
            }
            if ($attempt->timemodified) {
                $data->timemodified = transform::datetime($attempt->timemodified);
            }
            if ($attempt->validto) {
                $data->validto = transform::datetime($attempt->validto);
            }

            $results = attempt_results::get_by(['attemptid' => $attempt->id]);
            if ($attempt->hasresults && $results) {
                $data->results = (object) [
                    'timetaken' => $results->timetaken,
                    'result' => transform::percentage($results->result / 100),
                    'pass' => transform::yesno($results->pass),
                    'achievedscore' => $results->achievedscore,
                    'maxscore' => $results->maxscore,
                    'questionscount' => $results->questionscount,
                    'questionscorrectcount' => $results->questionscorrectcount,
                ];

                if (!empty($results->certificateurl)) {
                    $data->results->certificateurl = $results->certificateurl;
                }

                if ($results->whenfinished) {
                    $data->results->whenfinished = transform::datetime($results->whenfinished->getTimestamp());
                }
            }

            // Create subcontext for 'Attempts' and then per attempt no.
            $attemptsubcontext = [get_string('attempts', 'mod_bizexaminer'), $attempt->attempt];
            writer::with_context($context)->export_data($attemptsubcontext, $data);
        }
        $attempts->close(); // Close recordset.
    }
}
