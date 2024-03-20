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
 * Mod form processor for creating/updating module instances
 *
 * @package     mod_bizexaminer
 * @category    mod_form
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bizexaminer\local\mod_form;

use coding_exception;
use mod_bizexaminer\local\api\exam_modules;
use mod_bizexaminer\local\api\remote_proctors;
use mod_bizexaminer\bizexaminer;
use mod_bizexaminer\local\data_objects\exam;
use mod_bizexaminer\local\data_objects\exam_feedback;
use mod_bizexaminer\local\gradebook\grading;
use mod_bizexaminer\util;
use moodle_exception;
use stdClass;

/**
 * Service for mod_form functionality.
 *
 * @package mod_bizexaminer
 */
class mod_form_helper {

    /**
     * Process submit of the mod_form
     *
     * @param exam $exam
     * @param stdClass $data
     * @throws moodle_exception
     */
    public function process_options(exam $exam, stdClass $data) {
        $exam->id = !empty($data->instance) ? (int)$data->instance : null;
        $exam->course = (int)$data->course;

        if (!empty($data->name)) {
            $exam->name = trim($data->name);
        }

        if (!empty($data->intro)) {
            $exam->intro = trim($data->intro);
        }

        $exam->introformat = $data->introformat;

        if (empty($exam->timecreated)) {
            $exam->timecreated = util::create_date(time());
        }

        $exam->timemodified = util::create_date(time());

        // API Credentials.
        $exam->apicredentials = $data->api_credentials;

        // Exam module.
        /** @var exam_modules $exammodulesservice */
        $exammodulesservice = bizexaminer::get_instance()->get_service('exammodules', $exam->get_api_credentials());
        $exammoduleids = $exammodulesservice->explode_exam_module_ids($data->exam_module);
        if ($exammoduleids) {
            $exam->productid = (int)$exammoduleids['product'];
            $exam->productpartsid = (int)$exammoduleids['productpart'];
            $exam->contentrevision = (int)$exammoduleids['contentrevision'];
        } else {
            throw new \moodle_exception('exam_module_invalid', 'mod_bizexaminer');
        }

        $exam->usebecertificate = (int)$data->usebecertificate === 1;

        // Remote proctors - not required.
        $remoteproctor = remote_proctor_select::parse_remote_proctor_value($data->remote_proctor);
        if ($remoteproctor && count($remoteproctor) === 2) {
            $exam->remoteproctor = $remoteproctor[1];
            $exam->remoteproctortype = $remoteproctor[0];

            if (empty($data->remote_proctor_options)) {
                $exam->remoteproctoroptions = [];
            } else {
                // All defined fields for all proctor types.
                $allremoteproctorfields = remote_proctors::get_remote_proctor_setting_fields();
                // The defined fields for the selected proctor type.
                $remoteproctorfields = array_key_exists($exam->remoteproctortype, $allremoteproctorfields) ?
                $allremoteproctorfields[$exam->remoteproctortype] : [];
                // The values for the selected remote proctor.
                $values = array_key_exists($exam->remoteproctortype, $data->remote_proctor_options) ?
                $data->remote_proctor_options[$exam->remoteproctortype] : [];

                $allowedvalues = array_intersect_key($values, $remoteproctorfields);
                $exam->remoteproctoroptions = $allowedvalues;
            }
        } else {
            $exam->remoteproctor = null;
            $exam->remoteproctortype = null;
            $exam->remoteproctoroptions = [];
        }

        // Grading options.
        $exam->maxattempts = $data->maxattempts;
        $validgrademethods = [grading::GRADEHIGHEST, grading::GRADEAVERAGE, grading::GRADEFIRST, grading::GRADELAST];
        $exam->grademethod = in_array($data->grademethod, $validgrademethods) ? $data->grademethod : null;
        $exam->grade = $data->grade;

        // Feedback messages.
        if (!empty($data->feedbacktext)) {
            $exam->feedbacks = []; // Delete all previous.
            $haszerofeedback = false;
            foreach ($data->feedbacktext as $i => $feedbacktext) {
                $feedback = new exam_feedback();
                $feedback->feedbacktext = $feedbacktext['text']; // IMPORTANT: Needs to be escaped when outputting.
                $feedback->feedbacktextformat = $feedbacktext['format'];
                $feedback->mingrade = $data->feedbackmingrade[$i] ?? 0;

                if (empty($feedback->feedbacktext)) {
                    continue;
                }

                // There can only be one 0 feedback message.
                // That is the case when no grading is enabled and there are empty feedback boxes.
                if ($haszerofeedback && $feedback->mingrade === 0) {
                    continue;
                }

                if ($feedback->mingrade === 0 && !$haszerofeedback) {
                    $haszerofeedback = true;
                }

                $exam->feedbacks[] = $feedback;
            }
        }

        // Access restrictions.
        $exam->timeopen = util::create_date($data->timeopen);
        $exam->timeclose = util::create_date($data->timeclose);
        $exam->overduehandling = $data->overduehandling ?? null;
        $exam->graceperiod = $data->graceperiod ?? null;
        $exam->password = $data->exam_password; // Sanitized to PARAM_TEXT and validate in self::validation.
        $exam->subnet = $data->subnet; // Sanitized to PARAM_TEXT, not validate (see self::validation).
        $exam->delayattempt1 = $data->delayattempt1; // Sanitized to PARAM_FLOAT by duration element type.
        $exam->delayattempt2 = $data->delayattempt2;// Sanitized to PARAM_FLOAT by duration element type.
    }

    /**
     * Load existing values into the form
     *
     * @param mixed $defaultvalues - via reference
     */
    public function load_values(&$defaultvalues) {
        if (empty($defaultvalues['id'])) {
            return;
        }

        $exam = exam::get($defaultvalues['id']);
        if (!$exam) {
            return;
        }

        // Build selected exam module id from id parts.
        $exammoduleid = $exam->get_exam_module_id();
        if ($exammoduleid) {
            $defaultvalues['exam_module'] = $exammoduleid;
        }

        // Build remote proctor value from id and type
        // because input returns only the id on saving.
        if ($exam->remoteproctor) {
            $proctor = $exam->get_remote_proctor();
            if ($proctor) {
                $defaultvalues['remote_proctor'] = remote_proctor_select::build_remote_proctor_value(
                    $exam->remoteproctor, $exam->remoteproctortype);

                // Build remote proctor options from stored table.
                // All proctor options are optional and have a default in the bizExaminer API
                // therefore no defaults need to be applied.
                $proctortype = $proctor['type'];
                $defaultvalues['remote_proctor_options'] = $exam->remoteproctoroptions;
            }
        }

        // Password field - different in form to stop browsers that remember
        // passwords from getting confused.
        if (isset($defaultvalues['password'])) {
            $defaultvalues['exam_password'] = $defaultvalues['password'];
            unset($defaultvalues['password']);
        }

        // No validation for subnet atm - should be a comma-separated list of ip addresses
        // but even moodle core (quiz, chat) does not validate IPs.

        $defaultvalues['api_credentials'] = $exam->apicredentials;

    }

    /**
     * Validate feedback texts
     *
     * @param mixed $data
     * @param mixed $errors Errors returned to the user - via reference
     */
    public function validate_feedbacks($data, &$errors) {
        // TODO: validate - ensure they are unique and in range of grade?
    }

    /**
     * Load existing feedback values into the repeater.
     *
     * @param array $defaultvalues
     * @param exam_feedback[] $feedbacks
     */
    public function load_feedback_values(&$defaultvalues, array $feedbacks) {
        if (empty($feedbacks)) {
            return;
        }
        $key = 0; // Data object arrays are indexed by id.
        foreach ($feedbacks as $feedback) {
            $defaultvalues['feedbacktext['.$key.']']['text'] = $feedback->feedbacktext;
            $defaultvalues['feedbacktext['.$key.']']['format'] = $feedback->feedbacktextformat;
            $defaultvalues['feedbackmingrade['.$key.']'] = $feedback->mingrade;
            $defaultvalues['feedbackid['.$key.']'] = $feedback->id;

            if ($defaultvalues['grade'] == 0) {
                // When an exam is un-graded, there can only be one lot of
                // feedback. If the exam previously had a maximum grade and
                // several lots of feedback, we must now avoid putting text
                // into input boxes that are disabled, but which the
                // validation will insist are blank.
                break;
            }

            $key++;
        }
    }
}
