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
 * Data object for a grade at an exam.
 *
 * @package     mod_bizexaminer
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bizexaminer\local\data_objects;

use DateTime;
use mod_bizexaminer\data_object;
use mod_bizexaminer\util;
use stdClass;

/**
 * DAO/DTO for a grade at an exam.
 * @package mod_bizexaminer
 */
class exam_grade extends data_object {
    /**
     * The table name in the database (without moodle prefix).
     * @var string
     */
    public const TABLE = 'bizexaminer_grades';

    /**
     * Foreign key reference to the exam that was attempted.
     * @var int
     */
    public int $examid;

    /**
     * Foreign key reference to the user whose attempt this is.
     * @var int
     */
    public int $userid;

    /**
     * The overall grade from the exam. Not affected by overrides in the gradebook.
     * @var float
     */
    public float $grade;

    /**
     * The last time this grade changed.
     * @var DateTime
     */
    public \DateTime $timemodified;

    /**
     * The time the attempt was submitted.
     * @var DateTime
     */
    public \DateTime $timesubmitted;

    /**
     * Get the data_objects data as a moodle data object (eg for mod_form, database)
     *
     * @return stdClass
     */
    public function get_data(): stdClass {
        $data = parent::get_data();
        $data->examid = $this->examid;
        $data->userid = $this->userid;
        $data->grade = $this->grade;
        $data->timemodified = $this->timemodified->getTimestamp();
        $data->timesubmitted = $this->timesubmitted->getTimestamp();

        return $data;
    }

    /**
     * Loads data from a moodle data object (eg mod_form, database) into an instance of the data_object
     *
     * @param data_object $examgrade
     * @param stdClass $data
     */
    public static function load_data(data_object $examgrade, stdClass $data): void {
        parent::load_data($examgrade, $data);
        $examgrade->examid = $data->examid;
        $examgrade->userid = $data->userid;
        $examgrade->grade = $data->grade;
        $examgrade->timemodified = util::create_date($data->timemodified);
        $examgrade->timesubmitted = util::create_date($data->timesubmitted);
    }
}
