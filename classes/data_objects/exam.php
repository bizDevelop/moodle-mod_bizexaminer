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
 * Data object for an exam.
 *
 * @package     mod_bizexaminer
 * @category    data_objects
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bizexaminer\data_objects;

use DateTime;
use mod_bizexaminer\api\api_credentials;
use mod_bizexaminer\api\exam_modules;
use mod_bizexaminer\api\remote_proctors;
use mod_bizexaminer\bizexaminer;
use mod_bizexaminer\data_object;
use mod_bizexaminer\util;
use stdClass;

/**
 * DAO/DTO for an exam
 * @package mod_bizexaminer
 */
class exam extends data_object {

    public const TABLE = 'bizexaminer';

    /**
     * Overdue handling with grace period.
     */
    public const OVERDUE_GRACEPERIOD = 'graceperiod';

    /**
     * Overdue handling which cancels attempts.
     */
    public const OVERDUE_CANCEL = 'cancel';

    /**
     * ID of the course this activity is part of.
     * Field for course FK needs to be called "course", required in many places like resetting
     * Allthough its against moodles coding styles.
     * @var null|int
     */
    public ?int $course;

    /**
     * The name of the activity module instance
     * @var string
     */
    public string $name = '';

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
     * Activity description.
     * @var string
     */
    public string $intro = '';

    /**
     * The format of the intro field from editor.
     * @var int
     */
    public int $introformat = 0;

    /**
     * The productId from bizExaminer
     * @var null|int
     */
    public ?int $productid = null;

    /**
     * The productPartsId from bizExaminer
     * @var null|int
     */
    public ?int $productpartsid = null;

    /**
     * The contentRevision from bizExaminer
     * @var null|int
     */
    public ?int $contentrevision = null;

    /**
     * Remote proctor id
     * @var null|string
     */
    public ?string $remoteproctor = null;

    /**
     * The type/provider of remote proctor.
     * @var null|string
     */
    public ?string $remoteproctortype = null;

    /**
     * An schemaless array of all configured options for the selected remote proctor.
     * @var array
     */
    public array $remoteproctoroptions = [];

    /**
     * Whether to use bizExaminer certificates.
     * @var bool
     */
    public ?bool $usebecertificate;

    /**
     *
     * @var exam_feedback[]
     */
    public ?array $feedbacks = [];

    /**
     * The maximum number of attempts a student is allowed.
     * @var null|int
     */
    public ?int $maxattempts = null;

    /**
     * The grad method used - one of grading::GRADEHIGHEST, grading::GRADEAVERAGE, grading::GRADEFIRST or grading::GRADELAST.
     * @var null|int
     */
    public ?int $grademethod = null;

    /**
     * The scale to be used for grading
     * Note that Moodle scales are stored as a positive integer if they are numeric,
     * as a negative integer if they are a custom scale and 0 means the forum is ungraded
     *
     * positive int = max possible points
     * 0 = no grade
     * negative int = custom scale
     *
     * @var null|int
     */
    public ?int $grade = null;

    /**
     * The time when this exam opens. (null = no restriction.)
     * @var null|DateTime
     */
    public ?\DateTime $timeopen;

    /**
     * The time when this exam closes. (null = no restriction.)
     * @var null|DateTime
     */
    public ?\DateTime $timeclose;

    /**
     * The method used to handle overdue attempts.
     * OVERDUE_GRACEPERIOD or OVERDUE_CANCEL
     * @var null|string
     */
    public ?string $overduehandling;

    /**
     * The amount of time (in seconds) after the time limit runs out
     * during which attempts can still be submitted, if overduehandling is set to allow it.
     * @var null|int
     */
    public ?int $graceperiod;

    /**
     * A password that the student must enter before starting or continuing an exam attempt.
     * @var null|string
     */
    public ?string $password;

    /**
     * Used to restrict the IP addresses from which this exam can be attempted.
     * The format is as requried by the address_in_subnet function.
     * @var null|string
     */
    public ?string $subnet;

    /**
     * Delay that must be left between the first and second attempt, in seconds.
     * @var null|int
     */
    public ?int $delayattempt1;

    /**
     * Delay that must be left between the second and subsequent attempt, in seconds.
     * @var null|int
     */
    public ?int $delayattempt2;

    /**
     * The ID of the api credentials to use.
     *
     * @var null|string
     */
    public ?string $apicredentials = null;

    public function get_api_credentials(): ?api_credentials {
        if (!$this->apicredentials) {
            return null;
        }

        $credentials = api_credentials::get_by_id($this->apicredentials);
        if ($credentials) {
            return $credentials;
        } else {
            // Will trigger api exception.
            return new api_credentials('', '', '', '', '');
        }
    }

    /**
     * Build exam module id from id parts.
     *
     * @return string
     */
    public function get_exam_module_id(): string {
        if (empty($this->productid) || empty($this->productpartsid) || empty($this->contentrevision)) {
            return null;
        }

        return exam_modules::build_exam_module_id($this->productid, $this->productpartsid, $this->contentrevision);
    }

    /**
     * Get data about the remote proctor by its id
     *
     * @return array|null
     */
    public function get_remote_proctor(): ?array {
        if (empty($this->remoteproctor)) {
            return null;
        }

        /** @var remote_proctors $remoteproctorsservice */
        $remoteproctorsservice = bizexaminer::get_instance()->get_service('remoteproctors', $this->get_api_credentials());
        $proctor = $remoteproctorsservice->get_remote_proctor($this->remoteproctor);
        return empty($proctor) ? null : $proctor;
    }

    /**
     * If the exam is still open based on timeopen & timeclose restrictions.
     * @return bool
     */
    public function is_open(): bool {
        if (!$this->timeopen && !$this->timeclose) {
            return true;
        }

        $now = util::create_date(time());
        if ($this->timeopen && $now < $this->timeopen) {
            return false;
        }

        // Timeclose always must be later than timeopen.
        if ($this->timeclose && $now > $this->timeclose) {
            return false;
        }

        return true;
    }

    public function get_data(): stdClass {
        $data = parent::get_data();
        $data->course = $this->course;
        $data->name = $this->name;
        $data->timecreated = $this->timecreated->getTimestamp();
        $data->timemodified = $this->timemodified->getTimestamp();
        $data->intro = $this->intro;
        $data->introformat = $this->introformat;
        $data->productid = $this->productid;
        $data->productpartsid = $this->productpartsid;
        $data->contentrevision = $this->contentrevision;
        $data->remoteproctor = $this->remoteproctor;
        $data->remoteproctortype = $this->remoteproctortype;
        $data->usebecertificate = $this->usebecertificate ? 1 : 0;

        $data->maxattempts = $this->maxattempts;
        $data->grademethod = $this->grademethod;
        $data->grade = $this->grade;

        $data->timeopen = $this->timeopen ? $this->timeopen->getTimestamp() : null;
        $data->timeclose = $this->timeclose ? $this->timeclose->getTimestamp() : null;
        $data->overduehandling = $this->overduehandling;
        $data->graceperiod = $this->graceperiod;
        $data->password = $this->password;
        $data->subnet = $this->subnet;
        $data->delayattempt1 = $this->delayattempt1;
        $data->delayattempt2 = $this->delayattempt2;

        $data->apicredentials = $this->apicredentials;

        return $data;
    }

    public static function load_data(data_object $exam, \stdClass $data): void {
        parent::load_data($exam, $data);
        $exam->course = $data->course ?? null;
        $exam->name = $data->name ?? '';
        $exam->timecreated = util::create_date($data->timecreated);
        $exam->timemodified = util::create_date($data->timemodified);
        $exam->intro = $data->intro ?? '';
        $exam->introformat = $data->introformat ?? 0;
        $exam->productid = $data->productid ?? null;
        $exam->productpartsid = $data->productpartsid ?? null;
        $exam->contentrevision = $data->contentrevision ?? null;
        $exam->remoteproctor = !empty($data->remoteproctor) ? $data->remoteproctor : null;
        $exam->remoteproctortype = !empty($data->remoteproctortype) ? $data->remoteproctortype : null;
        $exam->usebecertificate = (int)$data->usebecertificate === 1;

        $exam->maxattempts = $data->maxattempts;
        $exam->grademethod = $data->grademethod;
        $exam->grade = $data->grade;

        $exam->timeopen = util::create_date($data->timeopen);
        $exam->timeclose = util::create_date($data->timeclose);
        $exam->overduehandling = $data->overduehandling;
        $exam->graceperiod = $data->graceperiod;
        $exam->password = $data->password;
        $exam->subnet = $data->subnet;
        $exam->delayattempt1 = $data->delayattempt1;
        $exam->delayattempt2 = $data->delayattempt2;

        $exam->apicredentials = $data->apicredentials;
    }

    /**
     * Gets the exam as a standard activity module object.
     * Useful for passing to moodle core functions.
     *
     * @return stdClass
     */
    public function get_activity_module(): \stdClass {
        return $this->get_data();
    }

    public static function get(int $id, int $strictness = IGNORE_MISSING): ?data_object {
        $exam = parent::get($id);
        if ($exam && $exam->id) {
            if ($exam->remoteproctor) {
                self::load_remote_proctor_options($exam);
            }
            self::load_feedbacks($exam);
        }
        return $exam;
    }

    public static function save(data_object $exam) {
        $saved = parent::save($exam);
        if ($saved) {
            $updatedoptions = self::save_remote_proctor_options($exam);
            $updatedfeedbacks = self::save_feedbacks($exam);
        }
        return $saved && $updatedoptions && $updatedfeedbacks;
    }

    public static function delete(int $id) {
        /** @var moodle_database $DB */ // phpcs:ignore moodle.Commenting.InlineComment.TypeHintingMatch
        global $DB;

        $deleted = parent::delete($id);

        if ($deleted) {
            // Deleting of attempts, results and grades is handled in bizexaminer_delete_instance.
            exam_feedback::delete_all(['examid' => $id]);
            $DB->delete_records('bizexaminer_proctor_options', ['examid' => $id]);
        }

        return $deleted;
    }

    /**
     * Loads the associated feedbacks for this exam and sets it into the instance property.
     *
     * @param exam $exam
     * @return void
     */
    protected static function load_feedbacks(exam $exam): void {
        if (!$exam->id) {
            return;
        }

        try {
            $feedbacks = exam_feedback::get_all(['examid' => $exam->id]);
            $exam->feedbacks = $feedbacks;
        } catch (\dml_exception $exception) {
            $exam->feedbacks = [];
            return;
        }
    }

    /**
     * Loads the associated remote proctor options for this exam and
     * sets it into the instance property.
     *
     * @param exam $exam
     * @return void
     */
    protected static function load_remote_proctor_options(exam $exam): void {
        /** @var moodle_database $DB */ // phpcs:ignore moodle.Commenting.InlineComment.TypeHintingMatch
        global $DB;
        if (!$exam->id || !$exam->remoteproctor) {
            return;
        }

        $proctortype = $exam->remoteproctortype;
        try {
            $remoteproctoroptions = $DB->get_records('bizexaminer_proctor_options',
                ['examid' => $exam->id, 'proctortype' => $proctortype]);
        } catch (\dml_exception $exception) {
            $exam->remoteproctoroptions = [];
            return;
        }

        $optionvalues = [];
        foreach ($remoteproctoroptions as $option) {
            $optionvalues[$option->optionkey] = $option->optionvalue;
        }

        $exam->remoteproctoroptions = [$proctortype => $optionvalues];
    }

    /**
     * All proctor options are optional and have a default in the bizExaminer API
     * Therefore no defaults need to be applied.
     *
     * @param exam $exam
     * @return bool
     */
    protected static function save_remote_proctor_options(exam $exam): bool {
        /** @var moodle_database $DB */ // phpcs:ignore moodle.Commenting.InlineComment.TypeHintingMatch
        global $DB;

        if (!$exam->id) {
            return false;
        }

        // Delete options for all proctors for this exam.
        try {
            $DB->delete_records_select(
                'bizexaminer_proctor_options',
                'examid = ?',
                [$exam->id]
            );
        } catch (\dml_exception $exception) {
            return false;
        }

        if (!empty($exam->remoteproctortype) && !empty($exam->remoteproctoroptions)) {
            $newoptions = [];
            foreach ($exam->remoteproctoroptions as $fieldkey => $value) {
                // Values should be sanitized by field type / mod_form.

                $option = new stdClass;
                $option->examid = (int)$exam->id;
                $option->proctortype = $exam->remoteproctortype; // Comes from API, should be safe.
                $option->optionkey = $fieldkey; // Comes from hardcoded values, should be safe.
                $option->optionvalue = $value;

                $newoptions[] = $option;
            }

            try {
                $DB->insert_records('bizexaminer_proctor_options', $newoptions);
            } catch (\dml_exception $exception) {
                return false;
            }

            return true;
        }

        return true;
    }

    /**
     * Save feedback messages into their own table.
     *
     * @param exam $exam
     * @return bool
     */
    protected static function save_feedbacks(exam $exam): bool {
        if (!$exam->id) {
            return false;
        }

        // Delete all existing feedbacks for this exam.
        exam_feedback::delete_all(['examid' => $exam->id]);

        if (!empty($exam->feedbacks)) {
            $saved = 0;
            foreach ($exam->feedbacks as $feedback) {
                $feedback->examid = $exam->id;
                if (exam_feedback::save($feedback)) {
                    $saved ++;
                }
            }

            return $saved === count($exam->feedbacks);
        }
        return true;
    }

}
