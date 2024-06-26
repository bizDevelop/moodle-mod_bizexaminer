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
 * The main mod_bizexaminer configuration form.
 *
 * @package     mod_bizexaminer
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_grades\component_gradeitems;
use mod_bizexaminer\local\api\api_credentials;
use mod_bizexaminer\bizexaminer;
use mod_bizexaminer\local\data_objects\exam;
use mod_bizexaminer\local\data_objects\exam_feedback;
use mod_bizexaminer\local\gradebook\grading;
use mod_bizexaminer\local\mod_form\exam_modules_select;
use mod_bizexaminer\local\mod_form\mod_form_helper;
use mod_bizexaminer\local\mod_form\remote_proctor_select;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/course/moodleform_mod.php');

bizexaminer::get_instance()->get_service('settings')->check_has_credentials();

/**
 * Module instance settings form.
 *
 * Sanitizing/Security:
 * Text fields have a PARAM_NOTAGS/PARAM_TEXT type applied,
 * most remote proctor options are selects/switches and don't need sanitizing according to moodle docs
 * because core already handles that.
 *
 * @package     mod_bizexaminer
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_bizexaminer_mod_form extends moodleform_mod {

    /**
     * The maximum value for "max attempts" setting.
     */
    private const MAX_ATTEMPT_OPTION = 10;

    /**
     * A service for generating options, processing and validating, communicating with datalayer.
     *
     * @var mod_form_helper
     */
    private mod_form_helper $modformhelper;

    /**
     * Cache feedbacks.
     *
     * @var null|array
     */
    private ?array $feedbacks = null;

    /**
     * The currently edited or created exam instance.
     * @var null|exam
     */
    private ?exam $exam = null;

    /**
     * Create a new mod_form instance.
     *
     * @param mixed $current
     * @param mixed $section
     * @param mixed $cm
     * @param mixed $course
     * @return void
     */
    public function __construct($current, $section, $cm, $course) {
        $this->modformhelper = new mod_form_helper();
        $this->exam = new exam();
        if (!empty($cm->instance)) {
            $exam = exam::get((int)$cm->instance);
            if ($exam) {
                $this->exam = $exam;
            }
        }
        parent::__construct($current, $section, $cm, $course);
    }
    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        if (!$this->feedbacks) {
            $this->load_feedbacks();
        }

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Adding the standard "intro" and "introformat" fields.
        $this->standard_intro_elements();
        $this->add_exam_fields();
        $this->add_remote_proctor_fields();
        $this->add_grading_fields();
        $this->add_feedback_fields();
        $this->add_access_restriction_fields();
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Add exam module related fields.
     *
     * @return void
     */
    private function add_exam_fields() {
        $mform = $this->_form;
        // BizExaminer Settings.
        $mform->addElement('header', 'bizexaminer', get_string('pluginname', 'mod_bizexaminer'));

        $credentialsoptions = [
            '' => get_string('choosedots'),
        ];
        foreach (api_credentials::get_all() as $set) {
            $credentialsoptions[$set->get_id()] = $set->get_name();
        }
        $mform->addElement('select', 'api_credentials',
            get_string('modform_api_credentials', 'mod_bizexaminer'), $credentialsoptions);
        $mform->addHelpButton('api_credentials', 'modform_api_credentials', 'mod_bizexaminer');
        // Select field type checks for allowed options by default; additionally require a value.
        $mform->addRule('api_credentials', null, 'required', null, 'client');

        // When the save_api_credentials submit form is clicked,
        // We try to get the submitted api credentials and set them in the exam instance in this class.
        // So it can be used below to show api-dependent fields.
        if ($this->optional_param('save_api_credentials', 0, PARAM_TEXT)) {
            $submittedapicredentials = $this->optional_param('api_credentials', '', PARAM_RAW);
            // Test if those api credentials exist, if not just don't select them.
            if (api_credentials::get_by_id($submittedapicredentials)) {
                $this->exam->apicredentials = $submittedapicredentials;
            }
        }

        // Allways show the user a button to click when the API credentials are changed.
        // To disable the api-dependent fields, they are disabledIf different than the previous chosen api credentials.
        $mform->addElement('static', 'save_api_credentials_description', '',
            get_string('modform_api_credentials_save_help', 'mod_bizexaminer'));
        // Submit the form, but do not trigger saving of the form / activity. Must be before adding field.
        // See https://moodledev.io/docs/apis/subsystems/form/advanced/no-submit-button .
        $mform->registerNoSubmitButton('save_api_credentials');
        $mform->addElement('submit', 'save_api_credentials',
            get_string('modform_api_credentials_save', 'mod_bizexaminer'), [], false);
        $mform->disabledIf('save_api_credentials', 'api_credentials', 'noitemselected');
        $mform->disabledIf('save_api_credentials', 'api_credentials', 'eq', '');
        if ($this->exam->apicredentials) {
            // Disable button, if api credentials value is not changed.
            $mform->disabledIf('save_api_credentials', 'api_credentials', 'eq', $this->exam->apicredentials);
        }

        // Always add api-credentials dependent fields, so form knows which values to validate/save.
        // But if no apicredentials are selected, hide them full - even after selecting one on the client.
        // The user must first save the api credentials, so that the form is reload and the
        // list of exam modules can be fetched server side.

        // Do not pass api credentials, instead let define_after_data handle it.
        // It will pass the most current value (currently submitted or previously saved).
        $mform->addElement(new exam_modules_select(
                'exam_module', get_string('modform_exam_module', 'mod_bizexaminer'), []
        ));
        $mform->addHelpButton('exam_module', 'modform_exam_module', 'mod_bizexaminer');
        // Select field type checks for allowed options by default; additionally require a value.
        $mform->addRule('exam_module', null, 'required', null, 'client');

        $mform->addElement('selectyesno', 'usebecertificate', get_string('modform_usebecertificate', 'mod_bizexaminer'));
        $mform->addHelpButton('usebecertificate', 'modform_usebecertificate', 'mod_bizexaminer');

        if ($this->exam->apicredentials) {
            // Disable api-dependent fields if no api credentials are selected.
            $mform->disabledIf('exam_module', 'api_credentials', 'noitemselected');
            $mform->disabledIf('exam_module', 'api_credentials', 'eq', '');
            $mform->disabledIf('usebecertificate', 'api_credentials', 'noitemselected');
            $mform->disabledIf('usebecertificate', 'api_credentials', 'eq', '');

            // Disable api-dependent fields if different api credentials than previously selected are chosen.
            // User has to submit save_api_credentials button first.
            $mform->disabledIf('exam_module', 'api_credentials', 'neq', $this->exam->apicredentials);
            $mform->disabledIf('usebecertificate', 'api_credentials', 'neq', $this->exam->apicredentials);
        } else {
            // If no api credentials are selected, we want to always hide the fields
            // so the user is forced to save the api credentials.
            $mform->hideIf('exam_module', 'api_credentials', 'noitemselected');
            $mform->hideIf('exam_module', 'api_credentials', 'eq', '');
            $mform->hideIf('exam_module', 'api_credentials', 'neq', '');
            $mform->hideIf('usebecertificate', 'api_credentials', 'noitemselected');
            $mform->hideIf('usebecertificate', 'api_credentials', 'eq', '');
            $mform->hideIf('usebecertificate', 'api_credentials', 'neq', '');
        }
    }

    /**
     * Add fields for remote proctoring.
     *
     * @return void
     */
    private function add_remote_proctor_fields() {
        $mform = $this->_form;

        $mform->addElement(
                'header', 'bizexaminer_remocte_proctor_header', get_string('modform_remote_proctor_header', 'mod_bizexaminer'));

        // Do not pass api credentials, instead let define_after_data handle it.
        // It will pass the most current value (currently submitted or previously saved).
        $mform->addElement(new remote_proctor_select(
            'remote_proctor', get_string('modform_remote_proctor', 'mod_bizexaminer'), []
        ));
        $mform->addHelpButton('remote_proctor', 'modform_remote_proctor', 'mod_bizexaminer');

        if ($this->exam) {
            if ($this->exam->apicredentials) {
                // Disable api-dependent fields if no api credentials are selected.
                $mform->disabledIf('remote_proctor', 'api_credentials', 'noitemselected');
                $mform->disabledIf('remote_proctor', 'api_credentials', 'eq', '');

                // Disable api-dependent fields if different api credentials than previously selected are chosen.
                // User has to submit save_api_credentials button first.
                $mform->disabledIf('remote_proctor', 'api_credentials', 'neq', $this->exam->apicredentials);
            } else {
                // If no api credentials are selected, we want to always hide the fields
                // so the user is forced to save the api credentials.
                $mform->hideIf('remote_proctor', 'api_credentials', 'noitemselected');
                $mform->hideIf('remote_proctor', 'api_credentials', 'eq', '');
                $mform->hideIf('remote_proctor', 'api_credentials', 'neq', '');
            }
        }

        // If no proctor select, don't show any proctor settings.
        if (!$mform->elementExists('remote_proctor')) {
            return;
        }

        $this->modformhelper->add_remote_proctor_fields($this, $mform, $this->exam);
    }

    /**
     * Add fields for grading.
     *
     * @return void
     */
    private function add_grading_fields() {
        $mform = $this->_form;
        // Add standard grading elements.
        $this->standard_grading_coursemodule_elements();

        // Grading options.
        // Number of maxattempts.
        $attemptoptions = ['0' => get_string('unlimited')];
        for ($i = 1; $i <= self::MAX_ATTEMPT_OPTION; $i++) {
            $attemptoptions[$i] = $i;
        }
        $mform->addElement('select', 'maxattempts', get_string('modform_attemptsallowed', 'mod_bizexaminer'),
                $attemptoptions);

        // Grading method.
        $mform->addElement('select', 'grademethod', get_string('modform_grademethod', 'mod_bizexaminer'),
                grading::get_grademethod_options());
        $mform->addHelpButton('grademethod', 'modform_grademethod', 'mod_bizexaminer');
        $mform->hideIf('grademethod', 'attempts', 'eq', 1);
    }

    /**
     * Add fields for feedback texts.
     *
     * @return void
     */
    private function add_feedback_fields() {
        $mform = $this->_form;
        $mform->addElement('header', 'overallfeedbackheading', get_string('overallfeedback', 'mod_bizexaminer'));
        $mform->addHelpButton('overallfeedbackheading', 'overallfeedback', 'mod_bizexaminer');

        $repeatedfields = [];

        $repeatedfields[] = $mform->createElement('editor', 'feedbacktext',
            get_string('modform_feedbacktext', 'mod_bizexaminer'), ['rows' => 3]);
        // Needs to be RAW according to docs and examples in intro/quiz.
        // Instead use format_text when outputting.
        $mform->setType('feedbacktext', PARAM_RAW);

        $repeatedfields[] = $mform->createElement('float', 'feedbackmingrade',
            get_string('modform_mingrade', 'mod_bizexaminer'), ['size' => 10]);

        $repeatedfields[] = $mform->createElement('hidden', 'feedbackid', 0);
        $mform->setType('feedbackid', PARAM_INT);

        $repeatedfields[] = $mform->createElement('submit', 'delete', get_string('delete'), [], false);

        $numfeedbacks = max(count($this->feedbacks), 1);

        $repeatelementsno = $this->repeat_elements(
            $repeatedfields,
            $numfeedbacks,
            [
                'feedbacktext' => [
                    'type' => PARAM_RAW,
                ],
                'feedbackmingrade' => [
                    'type' => PARAM_FLOAT,
                ],
            ],
            'feedback_repeats',
            'feedbackboundary_add_fields',
            3,
            get_string('modform_add_feedbacks', 'mod_bizexaminer'),
            true,
            'delete'
        );

        // Add the disabledif rules. We cannot do this using the $repeatoptions parameter to
        // repeat_elements because we don't want to dissable the first feedbacktext.
        $gradefieldname = component_gradeitems::get_field_name_for_itemnumber("mod_{$this->_modname}", 0, 'grade');
        // For first=0 element, only disable mingrade, but keep feedbacktext - to use when no grading is used.
        $mform->hideIf('feedbackmingrade[0]', $gradefieldname.'[modgrade_type]', 'eq', 'none');
        for ($i = 1; $i < $repeatelementsno; $i++) {
            $mform->disabledIf('feedbackmingrade[' . $i . ']', $gradefieldname.'[modgrade_type]', 'eq', 'none');
            // Editor does not support hiding.
            $mform->disabledIf('feedbacktext[' . $i . ']', $gradefieldname.'[modgrade_type]', 'eq', 'none');
        }
    }

    /**
     * Add fields related to restricting access to the exam.
     *
     * @return void
     */
    private function add_access_restriction_fields() {
        $mform = $this->_form;

        // Acess Restrictions.
        $mform->addElement('header', 'access_restrictions', get_string('modform_access_restrictions', 'mod_bizexaminer'));

        // Open and close dates.
        $mform->addElement('date_time_selector', 'timeopen', get_string('modform_access_restrictions_timeopen', 'mod_bizexaminer'),
        ['optional' => true]);
        $mform->addHelpButton('timeopen', 'modform_access_restrictions_timeopen', 'mod_bizexaminer');

        $mform->addElement('date_time_selector', 'timeclose',
            get_string('modform_access_restrictions_timeclose', 'mod_bizexaminer'),
        ['optional' => true]);

        // What to do with overdue attempts.
        $mform->addElement(
            'select', 'overduehandling',
            get_string('modform_access_restrictions_overduehandling', 'mod_bizexaminer'),
            [
                exam::OVERDUE_CANCEL =>
                    get_string('modform_access_restrictions_overduehandling_autoabandon', 'mod_bizexaminer'),
                exam::OVERDUE_GRACEPERIOD =>
                    get_string('modform_access_restrictions_overduehandling_graceperiod', 'mod_bizexaminer'),
            ]
        );
        $mform->addHelpButton('overduehandling', 'modform_access_restrictions_overduehandling', 'mod_bizexaminer');
        $mform->hideIf('overduehandling', 'timeclose[enabled]', 'notchecked');

        // Grace period time.
        $mform->addElement(
            'duration', 'graceperiod',
            get_string('modform_access_restrictions_overduehandling_graceperiod_field', 'mod_bizexaminer'),
            []
        );
        $mform->addHelpButton('graceperiod', 'modform_access_restrictions_overduehandling_graceperiod_field', 'mod_bizexaminer');
        $mform->hideIf('graceperiod', 'overduehandling', 'neq', exam::OVERDUE_GRACEPERIOD);
        $mform->hideIf('graceperiod', 'timeclose[enabled]', 'notchecked');

        // Require password to begin exam attempt.
        // Change name to exam_password to prevent browser from using autocomplete.
        // Length restriction comes from bizExaminer access code restriction.
        $mform->addElement('passwordunmask', 'exam_password',
            get_string('modform_access_restrictions_password', 'mod_bizexaminer'));
        $mform->setType('exam_password', PARAM_TEXT);
        $mform->addHelpButton('exam_password', 'modform_access_restrictions_password', 'mod_bizexaminer');

        // IP address.
        $mform->addElement('text', 'subnet', get_string('modform_access_restrictions_requiresubnet', 'mod_bizexaminer'));
        $mform->setType('subnet', PARAM_TEXT);
        $mform->addHelpButton('subnet', 'modform_access_restrictions_requiresubnet', 'mod_bizexaminer');

        // Enforced time delay between exam attempts.
        $mform->addElement('duration', 'delayattempt1', get_string('modform_access_restrictions_delay1st2nd', 'mod_bizexaminer'),
                ['optional' => true]);
        $mform->addHelpButton('delayattempt1', 'modform_access_restrictions_delay1st2nd', 'mod_bizexaminer');

        $mform->hideIf('delayattempt1', 'maxattempts', 'eq', 1);

        $mform->addElement('duration', 'delayattempt2', get_string('modform_access_restrictions_delaylater', 'mod_bizexaminer'),
        ['optional' => true]);
        $mform->addHelpButton('delayattempt2', 'modform_access_restrictions_delaylater', 'mod_bizexaminer');
        $mform->hideIf('delayattempt2', 'maxattempts', 'eq', 1);
        $mform->hideIf('delayattempt2', 'maxattempts', 'eq', 2);
    }

    /**
     * Allows module to modify data returned by get_moduleinfo_data() or prepare_new_moduleinfo_data() before calling set_data()
     * This method is also called in the bulk activity completion form.
     *
     * Only available on moodleform_mod.
     *
     * @param array $defaultvalues passed by reference
     */
    function data_preprocessing(&$defaultvalues) { // phpcs:ignore Squiz.Scope.MethodScope.Missing
        parent::data_preprocessing($defaultvalues);

        $this->modformhelper->load_values($defaultvalues);
        $this->modformhelper->load_feedback_values($defaultvalues, $this->feedbacks);
    }

    /**
     * This method is called after definition(), data submission and set_data().
     * All form setup that is dependent on form values should go in here.
     *
     * Get current submitted API credentials and set those in api-dependent fields (exams, remote proctors)
     * So those fields can load the values from the correct/new api credentials and
     * validate the selected value against those.
     * Important for when changing API credentials in an existing exam.
     *
     * This is used to give api-dependent fields the correct API credentials.
     * On editing, it will show give the previously selected API credentials,
     * on selecting/changing it will give the new submitted ones.
     */
    public function definition_after_data() {
        $mform = $this->_form;
        $apicredentialsfield = $mform->getElement('api_credentials');
        $apicredentialsvalue = $apicredentialsfield->getValue();

        if (!empty($apicredentialsvalue) && !empty($apicredentialsvalue[0])) {
            // Test if those api credentials exist, if not just don't select them.
            $apicredentials = api_credentials::get_by_id($apicredentialsvalue[0]);
            if ($apicredentials && $apicredentials->are_valid()) {
                if ($mform->elementExists('exam_module')) {
                    /** @var exam_modules_select $examselectfield */
                    $examselectfield = $mform->getElement('exam_module');
                    $examselectfield->set_api_credentials($apicredentials);
                }

                // Element could not exist, if there are not remote proctors configured for an account.
                if ($mform->elementExists('remote_proctor')) {
                    /** @var remote_proctor_select $remoteproctorselectfield */
                    $remoteproctorselectfield = $mform->getElement('remote_proctor');
                    $remoteproctorselectfield->set_api_credentials($apicredentials);

                    // If not remote proctors, hide select.
                    $remoteproctoroptions = $remoteproctorselectfield->get_remote_proctors();
                    if (empty($remoteproctoroptions)) {
                        $mform->removeElement('bizexaminer_remocte_proctor_header');
                        $mform->removeElement('remote_proctor');
                    }
                    $this->modformhelper->hide_remote_proctoring_fields($mform, $remoteproctoroptions);
                }
            } else {
                if ($mform->elementExists('remote_proctor')) {
                    $mform->removeElement('bizexaminer_remocte_proctor_header');
                    $mform->removeElement('remote_proctor');
                }
                $this->modformhelper->hide_remote_proctoring_fields($mform, []);
            }
        }
    }

    /**
     * Verify and validate form input.
     *
     * @param mixed $data
     * @param mixed $files
     * @return void
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Check open and close times are consistent.
        if ($data['timeopen'] != 0 && $data['timeclose'] != 0 &&
                $data['timeclose'] < $data['timeopen']) {
            $errors['timeclose'] = get_string('modform_access_restrictions_timeclose_error_beforopen', 'mod_bizexaminer');
        }

        // Check password length fits bizExaminer accessCode requirements.
        if (!empty($data['exam_password'])) {
            if (strlen($data['exam_password']) < 4 || strlen($data['exam_password']) > 12) {
                $errors['exam_password'] = get_string('modform_access_restrictions_password_error_length', 'mod_bizexaminer');
            }
        }

        // Check if api credentials exist.
        $apicredentials = api_credentials::get_by_id($data['api_credentials'] ?? '');
        if (!$apicredentials || !$apicredentials->test_credentials()) {
            $errors['api_credentials'] = get_string('modform_api_credentials_invalid', 'mod_bizexaminer');
        }

        $this->modformhelper->validate_feedbacks($data, $errors);

        return $errors;
    }

    /**
     * Load existing feedbacks from the database.
     *
     * @return void
     */
    private function load_feedbacks() {
        if ($this->_instance) {
            $this->feedbacks = exam_feedback::get_all(['examid' => $this->_instance], 'mingrade ASC');
        } else {
            $this->feedbacks = [];
        }
    }
}

// Register a custom form element.
MoodleQuickForm::registerElementType(
    // The custom element is named `course_competency_rule`.
    // This is the element name used in the `addElement()` function.
    'bizexaminer_exam_modules_select',

    // This is where it's definition is defined.
    // This does not currently support class auto-loading.
    "$CFG->dirroot/mod/bizexaminer/classes/mod_form/exam_modules_select.php",

    // The class name of the element.
    exam_modules_select::class
);
MoodleQuickForm::registerElementType(
    // The custom element is named `course_competency_rule`.
    // This is the element name used in the `addElement()` function.
    'bizexaminer_remote_proctor_select',

    // This is where it's definition is defined.
    // This does not currently support class auto-loading.
    "$CFG->dirroot/mod/bizexaminer/classes/mod_form/remote_proctor_select.php",

    // The class name of the element.
    remote_proctor_select::class
);
