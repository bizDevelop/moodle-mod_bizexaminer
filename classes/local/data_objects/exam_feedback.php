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
 * Data object for feedback text.
 *
 * @package     mod_bizexaminer
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bizexaminer\local\data_objects;

use mod_bizexaminer\data_object;
use stdClass;

/**
 * DAO/DTO for exam feedback texts
 * @package mod_bizexaminer
 */
class exam_feedback extends data_object {
    /**
     * The table name in the database (without moodle prefix).
     * @var string
     */
    public const TABLE = 'bizexaminer_feedbacks';

    /**
     * Foreign key references exam.id.
     * @var int
     */
    public int $examid;

    /**
     * WARNING: Always use format_text since it's not sanitized on saving.
     * @var string
     */
    public string $feedbacktext;

    /**
     * The moodle text format for the text (from editor).
     * @var int
     */
    public int $feedbacktextformat = 0;

    /**
     * The lower limit of this grade band. Inclusive.
     * @var float
     */
    public float $mingrade;

    /**
     * Get the data_objects data as a moodle data object (eg for mod_form, database)
     *
     * @return stdClass
     */
    public function get_data(): stdClass {
        $data = parent::get_data();
        $data->examid = $this->examid;
        $data->feedbacktext = $this->feedbacktext;
        $data->feedbacktextformat = $this->feedbacktextformat;
        $data->mingrade = $this->mingrade;
        return $data;
    }

    /**
     * Loads data from a moodle data object (eg mod_form, database) into an instance of the data_object
     *
     * @param data_object $examfeedback
     * @param stdClass $data
     */
    public static function load_data(data_object $examfeedback, stdClass $data): void {
        parent::load_data($examfeedback, $data);
        $examfeedback->examid = $data->examid;
        $examfeedback->feedbacktext = $data->feedbacktext;
        $examfeedback->feedbacktextformat = $data->feedbacktextformat;
        $examfeedback->mingrade = $data->mingrade;
    }
}
