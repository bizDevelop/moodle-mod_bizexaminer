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
 * Data object for an attempt at an exam.
 *
 * @package     mod_bizexaminer
 * @category    data_objects
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bizexaminer\data_objects;

use coding_exception;
use DateTime;
use mod_bizexaminer\bizexaminer;
use mod_bizexaminer\data_object;
use mod_bizexaminer\util;
use moodle_url;

/**
 * DAO/DTO for an attempt at an exam.
 * @package mod_bizexaminer
 */
class attempt extends data_object {

    public const TABLE = 'bizexaminer_attempts';

    /**
     * Attempt status when it has started
     *
     * @var string
     */
    public const STATUS_STARTED = 'started';

    /**
     * Attempt status when it's ended, but no results yet
     *
     * @var string
     */
    public const STATUS_PENDING_RESULTS = 'pending_results';

    /**
     * Attempt status when it's completed and has results
     *
     * @var string
     */
    public const STATUS_COMPLETED = 'completed';

    /**
     * Attempt status when it has been aborted (eg due to timeclose constraint)
     *
     * @var string
     */
    public const STATUS_ABORTED = 'aborted';

    /**
     * Foreign key reference to the exam that was attempted.
     * @var null|int
     */
    public ?int $examid = null;

    /**
     * Foreign key reference to the user whose attempt this is.
     * @var null|int
     */
    public ?int $userid = null;

    /**
     * The current state of the attempts.
     * @var string
     */
    public string $status = 'created';

    /**
     * The time this object was created.
     * @var DateTime
     */
    public \DateTime $timecreated;

    /**
     * The time this object was last modified.
     * @var DateTime
     */
    public \DateTime $timemodified;

    /**
     * Sequential attempt number for this user in this exam.
     * @var null|int
     */
    public ?int $attempt = null;

    /**
     * The examBookingsId in bizExaminer.
     * @var null|int
     */
    public ?int $bookingid = null;

    /**
     * The participantId in bizExaminer.
     * @var null|int
     */
    public ?int $participantid = null;

    /**
     * The secret key used for API callbacks.
     * @var string
     */
    public string $secretkey = '';

    /**
     * Whether results have already been fetched and stored.
     * @var bool
     */
    public bool $hasresults = false;

    /**
     * Date until this attempt/booking is valid.
     * @var DateTime
     */
    public \DateTime $validto;

    /**
     * Get the exam this attempt belongs to.
     *
     * @return exam|false
     */
    public function get_exam() {
        $exam = exam::get($this->examid);
        if (!$exam) {
            return false;
        }
        return $exam;
    }

    /**
     * Checks if th secret key is valid.
     * @param string $key
     * @return bool
     */
    public function is_key_valid(string $key): bool {
        return hash_equals($this->secretkey, $key);
    }

    /**
     * Get the attempt results.
     *
     * @return null|attempt_results
     */
    public function get_results(): ?attempt_results {
        if (!$this->hasresults) {
            return null;
        }
        $results = attempt_results::get_by(['attemptid' => $this->id]);
        return $results;
    }

    /**
     * Is the attempt still valid based on validto
     * @return bool
     */
    public function is_valid(): bool {
        return $this->validto > util::create_date(time());
    }

    /**
     * Get the bizExaminer direct access URL
     * @return moodle_url|false
     */
    public function get_exam_url() {
        /** @var exams $examsservice */
        $examsservice = bizexaminer::get_instance()->get_service('exams', $this->get_exam()->get_api_credentials());
        $examurl = $examsservice->get_exam_accessurl($this);
        return $examurl ? new moodle_url($examurl) : false;
    }

    public function get_data(): \stdClass {
        $data = parent::get_data();
        $data->examid = $this->examid;
        $data->userid = $this->userid;
        $data->status = $this->status;
        $data->timecreated = $this->timecreated->getTimestamp();
        $data->timemodified = $this->timemodified->getTimestamp();
        $data->bookingid = $this->bookingid;
        $data->participantid = $this->participantid;
        $data->secretkey = $this->secretkey;
        $data->hasresults = $this->hasresults ? 1 : 0;
        $data->attempt = $this->attempt;
        $data->validto = $this->validto->getTimestamp();

        return $data;
    }

    public static function load_data(data_object $attempt, \stdClass $data): void {
        parent::load_data($attempt, $data);
        $attempt->examid = $data->examid ?? null;
        $attempt->userid = $data->userid ?? null;
        $attempt->status = $data->status ?? 'started';
        $attempt->timecreated = util::create_date($data->timecreated);
        $attempt->timemodified = util::create_date($data->timemodified);
        $attempt->bookingid = $data->bookingid ?? null;
        $attempt->participantid = $data->participantid ?? null;
        $attempt->secretkey = $data->secretkey ?? '';
        $attempt->hasresults = isset($data->hasresults) ? (bool)$data->hasresults : false;
        $attempt->attempt = $data->attempt;
        $attempt->validto = util::create_date($data->validto);
    }

    /**
     * Generate an exam attempt secret key with prefix.
     * By default generates a 13 digit secret + prefix = 20chars.
     *
     * @return string The secret key (20chars)
     */
    public static function generate_secret_key(): string {
        $key = generate_password(13);
        return 'be-att_' . $key;
    }

    /**
     * Gets the translated label for an attempt status
     * @param string $status
     * @return string
     */
    public static function attempt_status_label(string $status) {
        switch($status) {
            case self::STATUS_STARTED:
            case 'created':
                return get_string('attempt_status_started', 'mod_bizexaminer');
            case self::STATUS_PENDING_RESULTS:
                return get_string('attempt_status_pendingresults', 'mod_bizexaminer');
            case self::STATUS_COMPLETED:
                return get_string('attempt_status_completed', 'mod_bizexaminer');
            case self::STATUS_ABORTED:
                return get_string('attempt_status_aborted', 'mod_bizexaminer');
            default:
                throw new coding_exception('unknown exam attempt status', $status);
        }
    }

    public static function delete(int $id) {
        $deleted = parent::delete($id);

        if ($deleted) {
            attempt_results::delete_all(['attemptid' => $id]);
        }

        return $deleted;
    }

    public static function delete_all(?array $conditions = []): bool {
        /** @var moodle_database $DB */ // phpcs:ignore moodle.Commenting.InlineComment.TypeHintingMatch
        global $DB;

        // Use raw DB call because objects are not needed here.
        $attempts = $DB->get_records(self::TABLE, $conditions);
        foreach ($attempts as $attempt) {
            attempt_results::delete_all(['attemptid' => $attempt->id]);
        }

        return parent::delete_all($conditions);
    }
}
