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

use mod_bizexaminer\local\data_objects\exam;
use mod_bizexaminer\util;

/**
 * Structure step to restore one exam activity
 *
 * @package     mod_bizexaminer
 * @category    backup
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Provides the steps to perform one complete restore of the bizExaminer exam instance
 */
class restore_bizexaminer_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {
        $paths = [];
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('exam', '/activity/bizexaminer');
        $paths[] = new restore_path_element('exam_proctor_option', '/activity/bizexaminer/proctor_options/proctor_option');
        $paths[] = new restore_path_element('exam_feedback', '/activity/bizexaminer/feedbacks/feedback');

        if ($userinfo) {
            $paths[] = new restore_path_element('exam_grade', '/activity/bizexaminer/grades/grade');
            $paths[] = new restore_path_element('exam_attempt', '/activity/bizexaminer/attempts/attempt');
            $paths[] = new restore_path_element('exam_attempt_result', '/activity/bizexaminer/attempts/attempt/attempt_results');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process the exam data.
     *
     * @param stdClass|array $data
     */
    protected function process_exam($data) {
        global $DB;
        $oldid = $data['id'];

        $exam = exam::create_from_data((object)$data);
        $exam->course = $this->get_courseid();

        $exam->timeopen = $data->timeopen ? util::create_date($this->apply_date_offset($data->timeopen)) : null;
        $exam->timeclose = $data->timeclose ? util::create_date($this->apply_date_offset($data->timeclose)) : null;
        // The dates for timecreated + timemodified are not mapped automatically
        // But probably also makes more sense to keep them at original values.

        // TODO: check api credentials exist.

        // Directly save into DB instead of using exam::save
        // because exam::save also saves proctor_options and feedbacks
        // but these are handled in their own functions in this class.
        $newitemid = $DB->insert_record('bizexaminer', $exam->get_activity_module());
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
        $this->set_mapping('exam', $oldid, $newitemid);
    }

    protected function process_exam_proctor_option($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->examid = $this->get_new_parentid('exam');

        $newitemid = $DB->insert_record('bizexaminer_proctor_options', $data);
        $this->set_mapping('exam_proctor_option', $oldid, $newitemid);
    }

    protected function process_exam_feedback($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->examid = $this->get_new_parentid('exam');

        $newitemid = $DB->insert_record('bizexaminer_feedbacks', $data);
        $this->set_mapping('exam_feedback', $oldid, $newitemid, true); // Has related files.
    }

    protected function process_exam_grade($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->examid = $this->get_new_parentid('exam');

        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('bizexaminer_grades', $data);
        $this->set_mapping('exam_grade', $oldid, $newitemid);
    }

    protected function process_exam_attempt($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->examid = $this->get_new_parentid('exam');

        // Get user mapping, return early if no mapping found for the exam attempt.
        $olduserid = $data->userid;
        $data->userid = $this->get_mappingid('user', $olduserid, 0);
        if ($data->userid === 0) {
            $this->log('Mapped user ID not found for user ' . $olduserid . ', exam ' . $this->get_new_parentid('exam') .
                ', attempt ' . $data->attempt . '. Skipping exam attempt', backup::LOG_INFO);

            return;
        }

        $data->validto = $this->apply_date_offset($data->validto);
        // The dates for timecreated + timemodified are not mapped automatically
        // But probably also makes more sense to keep them at original values.

        $newitemid = $DB->insert_record('bizexaminer_attempts', $data);
        $this->set_mapping('exam_attempt', $oldid, $newitemid);
    }

    protected function process_exam_attempt_result($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->attemptid = $this->get_new_parentid('exam_attempt');

        $data->whenfinished = $this->apply_date_offset($data->whenfinished);

        $newitemid = $DB->insert_record('bizexaminer_attempt_results', $data);
        $this->set_mapping('bizexaminer_attempt_result', $oldid, $newitemid);
    }

    protected function after_execute() {
        parent::after_execute();
        // Add exam related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_bizexaminer', 'intro', null);
        // Add feedback related files, matching by itemname = 'exam_feedback'.
        $this->add_related_files('mod_bizexaminer', 'feedback', 'exam_feedback');
    }
}
