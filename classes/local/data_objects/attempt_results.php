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
 * Data object for the results of a single attempt at an exam.
 *
 * @package     mod_bizexaminer
 * @category    data_objects
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bizexaminer\local\data_objects;

use DateTime;
use mod_bizexaminer\data_object;
use mod_bizexaminer\util;

/**
 * DAO/DTO for the results of a single attempt at an exam.
 * @package mod_bizexaminer
 */
class attempt_results extends data_object {

    public const TABLE = 'bizexaminer_attempt_results';

    /**
     * Foreign key reference to the attempt.
     * @var int
     */
    public int $attemptid;

    /**
     * When the attempt was finished in bizExaminer.
     * @var DateTime
     */
    public \DateTime $whenfinished;

    /**
     * The number of seconds it took the user.
     * @var int
     */
    public int $timetaken;

    /**
     * The result in percentage.
     * @var float
     */
    public float $result;

    /**
     * Whether the user passed according to bizExaminer configuration.
     * @var bool
     */
    public bool $pass;

    /**
     * Sum of the points the user got.
     * @var int
     */
    public int $achievedscore;

    /**
     * The max possible points the user could have gotten.
     * @var int
     */
    public int $maxscore;

    /**
     * The number of questions the user was shown.
     * @var null|int
     */
    public ?int $questionscount;

    /**
     * The number of questions the user filled out / got completely correct.
     * @var null|int
     */
    public ?int $questionscorrectcount;

    /**
     * URL to the bizExaminer certificate
     * If returned by API = if enabled in bizExaminer
     *
     * @var null|string
     */
    public ?string $certificateurl;

    public function get_data(): \stdClass {
        $data = parent::get_data();
        $data->attemptid = $this->attemptid;
        $data->whenfinished = $this->whenfinished->getTimestamp();
        $data->timetaken = $this->timetaken;
        $data->result = $this->result;
        $data->pass = $this->pass ? 1 : 0;
        $data->achievedscore = $this->achievedscore;
        $data->maxscore = $this->maxscore;
        $data->questionscount = $this->questionscount;
        $data->questionscorrectcount = $this->questionscorrectcount;
        $data->certificateurl = $this->certificateurl;

        return $data;
    }

    public static function load_data(data_object $attemptresults, \stdClass $data): void {
        parent::load_data($attemptresults, $data);
        $attemptresults->attemptid = $data->attemptid;
        $attemptresults->whenfinished = util::create_date($data->whenfinished);
        $attemptresults->timetaken = $data->timetaken;
        $attemptresults->result = $data->result;
        $attemptresults->pass = (bool)$data->pass;
        $attemptresults->achievedscore = $data->achievedscore;
        $attemptresults->maxscore = $data->maxscore;
        $attemptresults->questionscount = $data->questionscount ?? null;
        $attemptresults->questionscorrectcount = $data->questionscorrectcount ?? null;
        $attemptresults->certificateurl = $data->certificateurl ?? null;
    }
}
