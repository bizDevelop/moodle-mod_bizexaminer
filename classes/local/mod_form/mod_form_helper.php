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
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bizexaminer\local\mod_form;

use mod_bizexaminer\local\api\exam_modules;
use mod_bizexaminer\local\api\remote_proctors;
use mod_bizexaminer\bizexaminer;
use mod_bizexaminer\local\data_objects\exam;
use mod_bizexaminer\local\data_objects\exam_feedback;
use mod_bizexaminer\local\gradebook\grading;
use mod_bizexaminer\util;
use mod_bizexaminer_mod_form;
use moodle_exception;
use MoodleQuickForm;
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
                // Only allow settings for the selected proctor (eg remove configs from previous selected proctors).
                $values = array_key_exists($exam->remoteproctortype, $data->remote_proctor_options) ?
                $data->remote_proctor_options[$exam->remoteproctortype] : [];
                // Only allow the defined fields for the selected proctor.
                $remoteproctorfields = array_key_exists($exam->remoteproctortype, $allremoteproctorfields) ?
                $allremoteproctorfields[$exam->remoteproctortype] : [];
                $allowedvalues = array_intersect_key($values, $remoteproctorfields);
                // Check nested repeater values.
                foreach ($remoteproctorfields as $field => $fieldconfig) {
                    if (!array_key_exists($field, $allowedvalues)) {
                        continue;
                    }
                    if ($fieldconfig['type'] === 'repeater') {
                        $allowedvalues[$field] = array_intersect_key($allowedvalues[$field], $fieldconfig['fields']);
                    }
                }

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

    /**
     * Build settings for all remote proctors according to config from mod_bizexaminer\local\api\remote_proctors
     *
     * Form and modform are required toa dd fields directly to form.
     *
     * @param mod_bizexaminer_mod_form $form
     * @param MoodleQuickForm $mform
     * @param null|exam $exam
     * @return void
     */
    public function add_remote_proctor_fields(mod_bizexaminer_mod_form $form, MoodleQuickForm $mform, ?exam $exam = null) {
        foreach (remote_proctors::get_remote_proctor_setting_fields() as $proctor => $proctorfields) {
            foreach ($proctorfields as $fieldname => $proctorfield) {
                $fieldprefix = "remote_proctor_options[{$proctor}]";
                $element = $this->create_remote_proctoring_setting_field(
                    $proctor, $fieldprefix, $fieldname, $proctorfield, $form, $mform, $exam);

                if ($element) {
                    $mform->addElement($element);
                    $fullfieldname = "{$fieldprefix}[{$fieldname}]";
                    $labelstringidentifier = "modform_{$proctor}_{$fieldname}";
                    if (!empty($proctorfield['help_text'])) {
                        $mform->addHelpButton($fullfieldname, $labelstringidentifier, 'mod_bizexaminer');
                    }
                }
            }
        }
    }

    /**
     * Creates a field from the field config of a remote proctor.
     * Builds the field but does not directly add it to the mform (except for repeaters).
     *
     * Paramters are required to correctly build element and state of element
     * especially for repeaters.
     *
     * @param string $proctor
     * @param string $fieldprefix
     * @param string $fieldname
     * @param array $field
     * @param mod_bizexaminer_mod_form $form
     * @param MoodleQuickForm $mform
     * @param null|exam $exam
     * @return object|null The created element.
     */
    private function create_remote_proctoring_setting_field(
        string $proctor, string $fieldprefix, string $fieldname, array $field,
        mod_bizexaminer_mod_form $form, MoodleQuickForm $mform, ?exam $exam = null) {
        $fullfieldname = "{$fieldprefix}[{$fieldname}]";
        $labelstringidentifier = "modform_{$proctor}_{$fieldname}";
        $element = null;
        switch ($field['type']) {
            case 'text':
                $element = $mform->createElement(
                    'text',
                    $fullfieldname,
                    get_string($labelstringidentifier, 'mod_bizexaminer'),
                );
                $mform->setType($fullfieldname, $field['sanitizetype'] ?? PARAM_NOTAGS);
                break;
            case 'multiselect';
            case 'select':
                $element = $mform->createElement(
                    'select',
                    $fullfieldname,
                    get_string($labelstringidentifier, 'mod_bizexaminer'),
                    $field['options']
                );
                if (isset($field['multiple']) && $field['multiple']) {
                    $element->setMultiple(true);
                }
                break;
            case 'repeater':
                $repeatedfields = [];
                $repeatedfieldoptions = [];
                foreach ($field['fields'] as $subfieldname => $subfield) {
                    $fullsubfieldname = "{$fullfieldname}[{$subfieldname}]";
                    $repeatedelement = $this->create_remote_proctoring_setting_field(
                        $proctor, $fullfieldname, $subfieldname, $subfield, $form, $mform, $exam);
                    if ($repeatedelement) {
                        $repeatedfields[] = $repeatedelement;
                        $repeatedfieldoptions[$fullsubfieldname] = [
                            'type' => $repeatedfield['sanitizetype'] ?? PARAM_TEXT,
                        ];
                    }
                }

                // For helper fields outside the repeater, use a dash-separated string here.
                // Also important for delete button: Delete button name is built different by moodle
                // (by appending -hidden to it) so the default array form will not work.
                $helperfieldsname = str_replace(['[', ']'], '_', $fullfieldname);

                // To hide a static element, it needs to be in a group,
                // see https://tracker.moodle.org/browse/MDL-66251 .
                // Create a label element, which is shown with the default 0 elements and add-button.
                // A static inside group looks funky,therefore just use an empty group with a label.
                // Do not put repeater fields inside a group, because group is always displayed inline.
                $mform->addGroup([], $helperfieldsname, get_string($labelstringidentifier, 'mod_bizexaminer'), '', false);

                $deletebuttonname = $helperfieldsname . '_delete';
                $repeatedfields[] = $mform->createElement(
                    'submit', $deletebuttonname, $field['deletelabel'] ?? get_string('delete'), [], false);

                $repeatscount = 0;
                if (isset($exam->remoteproctoroptions[$proctor][$fieldname])) {
                    $firstrepeatedfield = array_key_first($exam->remoteproctoroptions[$proctor][$fieldname]);
                    if ($firstrepeatedfield) {
                        $repeatscount = count($exam->remoteproctoroptions[$proctor][$fieldname][$firstrepeatedfield]);
                    }
                }

                $form->repeat_elements(
                    $repeatedfields,
                    $repeatscount,
                    $repeatedfieldoptions,
                    $fullfieldname . '[_repeats]',
                    $fullfieldname . '[_add_fields]',
                    1,
                    $field['addlabel'],
                    true, // Do not close header.
                    $deletebuttonname
                );
                break;
            case 'switch':
                $element = $mform->createElement(
                    'selectyesno',
                    $fullfieldname, // Get's appended to array of group name $group[$field].
                    get_string($labelstringidentifier, 'mod_bizexaminer'),
                );
        }

        return $element;
    }

    /**
     * Hides all the remote proctoring setting fields if no remote_proctor is selected, none aravailable
     * or no api credentials are selected.
     *
     * Used in mod_form::definition_after_data which can change definitions after data has been read.
     *
     * @param MoodleQuickForm $mform
     * @param null|array $remoteproctoroptions Null means none available (no api credentials,...) -> hide them
     * @return void
     */
    public function hide_remote_proctoring_fields(MoodleQuickForm $mform, array $remoteproctoroptions = []) {
        // When selected API credentials are here, get real proctor options
        // and show/hide them depending on the selected proctor.
        foreach (remote_proctors::get_remote_proctor_setting_fields() as $proctor => $proctorfields) {
            // Hide proctor fields if no remote proctor is selected
            // or if any other remote proctors than those beloning to this proctor type
            // are selected.
            // Build select options per proctor for remote proctor settings to depend upon
            // because the syntax does not allow wildcard checking.
            $otherproctoroptions = array_reduce(array_keys($remoteproctoroptions), function($otheroptions, $option) use ($proctor) {
                $optionproctor = explode('_-_', $option)[0];
                if ($optionproctor && $optionproctor !== $proctor) {
                    $otheroptions[] = $option;
                }
                return $otheroptions;
            }, [0, '']); // 0 for default value

            foreach ($proctorfields as $fieldname => $proctorfield) {
                $fullfieldname = "remote_proctor_options[{$proctor}][{$fieldname}]";

                // Store function in an anonymous function to reuse for both if-cases below.
                $hidefield = function($mform, $hidefieldname) use ($remoteproctoroptions, $otherproctoroptions) {
                    // If no remote proctors -> no remote proctor select -> remove all fields.
                    if (empty($remoteproctoroptions) || !$mform->elementExists('remote_proctor')) {
                        if ($mform->elementExists($hidefieldname)) {
                            $mform->removeElement($hidefieldname);
                        }
                        return;
                    }

                    $mform->hideIf(
                        $hidefieldname,
                        'remote_proctor',
                        'in',
                        $otherproctoroptions
                    );
                };

                // Repeaters need special handling for removing/hiding
                // because their field names have an additional level with indexes.
                if ($proctorfield['type'] === 'repeater') {
                    // Hide/remove all fields inside the repeater.
                    $repeatscountfield = $mform->getElement($fullfieldname . '[_repeats]');
                    $repeatscount = 1;
                    if ($repeatscountfield && $repeatscountfield->getValue()) {
                        $repeatscount = (int) $repeatscountfield->getValue();
                    }

                    $hidefield($mform, $fullfieldname . '[_add_fields]');

                    // See comment in create_remote_proctoring_setting_field.
                    $helperfieldsname = str_replace(['[', ']'], '_', $fullfieldname);
                    $hidefield($mform, $helperfieldsname); // Hide repeater "label" group.

                    $deletebuttonname = $helperfieldsname . '_delete';

                    foreach ($proctorfield['fields'] as $repeatedfieldname => $repeatedfieldargs) {
                        $repeatedfullfieldname = $fullfieldname . "[{$repeatedfieldname}]";
                        for ($i = 0; $i < $repeatscount; $i++) {
                            $hidefield($mform, $repeatedfullfieldname . "[{$i}]");
                            $hidefield($mform, $deletebuttonname . "[{$i}]");
                        }
                    }
                } else {
                    $hidefield($mform, $fullfieldname);
                }
            }
        }
    }
}
