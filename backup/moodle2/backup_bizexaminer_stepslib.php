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
 * Define all the backup steps that will be used by the backup_bizexaminer_activity_task.class.
 *
 * @package     mod_bizexaminer
 * @category    backup
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Single structure step to backup all exam data.
 */
class backup_bizexaminer_activity_structure_step extends backup_questions_activity_structure_step {

    /**
     * Define paths and structure of the mod backup.
     *
     * @return mixed
     */
    protected function define_structure() {
        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        $exam = new backup_nested_element('bizexaminer', ['id'], [
            'name', 'timecreated', 'timemodified', 'intro', 'introformat', 'productid', 'productpartsid', 'contentrevision',
            'remoteproctor', 'remoteproctortype', 'maxattempts', 'grademethod',
            'grade', 'password', 'subnet', 'delayattempt1', 'delayattempt2',
            'timeopen', 'timeclose', 'overduehandling', 'graceperiod', 'usebecertificate',
            'apicredentials',
        ]);

        $proctoroptions = new backup_nested_element('proctor_options');
        $proctoroption = new backup_nested_element('proctor_option', ['id'], [
            'proctortype', 'optionkey', 'optionvalue',
        ]);

        $feedbacks = new backup_nested_element('feedbacks');
        $feedback = new backup_nested_element('feedback', ['id'], [
            'feedbacktext', 'feedbacktextformat', 'mingrade',
        ]);

        $grades = new backup_nested_element('grades');
        $grade = new backup_nested_element('grade', ['id'], [
            'userid', 'grade', 'timemodified', 'timesubmitted',
        ]);

        $attempts = new backup_nested_element('attempts');
        $attempt = new backup_nested_element('attempt', ['id'], [
            'userid', 'status', 'bookingid', 'participantid', 'secretkey',
            'timecreated', 'timemodified', 'hasresults', 'attempt', 'validto',
        ]);
        $attemptresult = new backup_nested_element('attempt_results', ['id'], [
            'whenfinished', 'timetaken', 'result', 'pass', 'achievedscore',
            'maxscore', 'questionscount', 'questionscorrectcount', 'certificateurl',
        ]);

        // Build tree.
        $exam->add_child($proctoroptions);
        $proctoroptions->add_child($proctoroption);

        $exam->add_child($feedbacks);
        $feedbacks->add_child($feedback);

        $exam->add_child($grades);
        $grades->add_child($grade);

        $exam->add_child($attempts);
        $attempts->add_child($attempt);
        $attempt->add_child($attemptresult);

        // Set sources.
        $exam->set_source_table('bizexaminer', ['id' => backup::VAR_ACTIVITYID]);
        $feedback->set_source_table('bizexaminer_feedbacks', ['examid' => backup::VAR_PARENTID]);
        $proctoroption->set_source_table('bizexaminer_proctor_options', ['examid' => backup::VAR_PARENTID]);

        // All the rest of elements only happen if we are including user info.
        if ($userinfo) {
            $grade->set_source_table('bizexaminer_grades', ['examid' => backup::VAR_PARENTID]);
            $attempt->set_source_table('bizexaminer_attempts', ['examid' => backup::VAR_PARENTID]);
            // The parentid should be attemptid not examid.
            $attemptresult->set_source_table('bizexaminer_attempt_results', ['attemptid' => backup::VAR_PARENTID]);
        }

        // Define id annotations.
        $grade->annotate_ids('user', 'userid');
        $attempt->annotate_ids('user', 'userid');

        // Define file annotations.
        $exam->annotate_files('mod_bizexaminer', 'intro', null); // This file area hasn't itemid.
        $feedback->annotate_files('mod_bizexaminer', 'feedback', 'id');

        return $this->prepare_activity_structure($exam);
    }
}
