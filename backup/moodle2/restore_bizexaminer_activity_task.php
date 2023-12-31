<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Defines restore_bizexaminer_activity_task class
 *
 * @package     mod_bizexaminer
 * @category    backup
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/bizexaminer/backup/moodle2/restore_bizexaminer_stepslib.php');


/**
 * Exam restore task that provides all the settings and steps to perform one complete restore of the activity
 */
class restore_bizexaminer_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // BizExaminer only has one structure step.
        $this->add_step(new restore_bizexaminer_activity_structure_step('bizexaminer_exam_structure', 'exam.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    public static function define_decode_contents() {
        $contents = [];

        $contents[] = new restore_decode_content('bizexaminer', ['intro'], 'exam');
        $contents[] = new restore_decode_content('bizexaminer_feedbacks', ['feedbacktext'], 'exam_feedback');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    public static function define_decode_rules() {
        $rules = [];

        $rules[] = new restore_decode_rule('EXAMVIEWBYID',
                '/mod/bizexaminer/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('EXAMVIEWBYEXAMID',
                '/mod/bizexaminer/view.php?examid=$1', 'exam');
        $rules[] = new restore_decode_rule('EXAMINDEX',
                '/mod/bizexaminer/index.php?id=$1', 'course');
        $rules[] = new restore_decode_rule('EXAMATTEMPT',
            '/mod/bizexaminer/attempt.php?attemptidid=$1', 'exam_attempt');
        $rules[] = new restore_decode_rule('EXAMATTEMPTSBYEXAMID',
            '/mod/bizexaminer/attempts.php?examid=$1', 'exam');

        return $rules;

    }

    /**
     * Define the restore log rules that will be applied
     * by the {@see restore_logs_processor} when restoring
     * exam logs. It must return one array
     * of {@see restore_log_rule} objects
     */
    public static function define_restore_log_rules() {
        $rules = [];
        // TODO.
        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@see restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@see restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    public static function define_restore_log_rules_for_course() {
        $rules = [];
        // TODO.
        return $rules;
    }
}
