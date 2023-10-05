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
 * @category    data_objects
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bizexaminer\data_objects;

use DateTime;
use mod_bizexaminer\data_object;
use mod_bizexaminer\util;

/**
 * DAO/DTO for a grade at an exam.
 * @package mod_bizexaminer
 */
class exam_grade extends data_object {

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

    public function get_data(): \stdClass {
        $data = parent::get_data();
        $data->examid = $this->examid;
        $data->userid = $this->userid;
        $data->grade = $this->grade;
        $data->timemodified = $this->timemodified->getTimestamp();
        $data->timesubmitted = $this->timesubmitted->getTimestamp();

        return $data;
    }

    public static function load_data(data_object $attempt, \stdClass $data): void {
        parent::load_data($attempt, $data);
        $attempt->examid = $data->examid;
        $attempt->userid = $data->userid;
        $attempt->grade = $data->grade;
        $attempt->timemodified = util::create_date($data->timemodified);
        $attempt->timesubmitted = util::create_date($data->timesubmitted);
    }
}
