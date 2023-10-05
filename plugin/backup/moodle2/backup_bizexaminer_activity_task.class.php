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
 * defines backup_bizexaminer_activity_task class
 *
 * @package     mod_bizexaminer
 * @category    backup
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/bizexaminer/backup/moodle2/backup_bizexaminer_stepslib.php');

/**
 * Provides the steps to perform one complete backup of the bizExaminer exam instance
 */
class backup_bizexaminer_activity_task extends backup_activity_task {

    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
    }

    /**
     * Defines backup steps to store the instance data and required questions
     */
    protected function define_my_steps() {
        // Generate the exam.xml file containing all the exam information.
        $this->add_step(new backup_bizexaminer_activity_structure_step('bizexaminer_exam_structure', 'exam.xml'));
    }

    /**
     * Encodes URLs to the index.php and view.php scripts
     *
     * @param string $content some HTML text that eventually contains URLs to the activity instance scripts
     * @return string the content with the URLs encoded
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');

        // Link to exam view by moduleid.
        $search = "/(".$base."\/mod\/bizexaminer\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@EXAMVIEWBYID*$2@$', $content);

        // Link to exam view by examid.
        $search = "/(".$base."\/mod\/bizexaminer\/view.php\?examid\=)([0-9]+)/";
        $content = preg_replace($search, '$@EXAMVIEWBYEXAMID*$2@$', $content);

        // Link to the list of exams.
        $search = "/(".$base."\/mod\/bizexaminer\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@EXAMINDEX*$2@$', $content);

        // Link to single attempt by attemptid.
        $search = "/(".$base."\/mod\/bizexaminer\/attempt.php\?attemptid\=)([0-9]+)/";
        $content = preg_replace($search, '$@EXAMATTEMPT*$2@$', $content);

        // Link to all attempts by examid.
        $search = "/(".$base."\/mod\/bizexaminer\/attempts.php\?examid\=)([0-9]+)/";
        $content = preg_replace($search, '$@EXAMATTEMPTSBYEXAMID*$2@$', $content);

        return $content;
    }
}
