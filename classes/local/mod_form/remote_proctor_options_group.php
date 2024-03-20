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
 * Grouping of options for a single remote proctor type
 *
 * @package     mod_bizexaminer
 * @category    mod_form
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bizexaminer\local\mod_form;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/form/group.php');

/**
 * Form field grouping multiple remote proctor options
 */
class remote_proctor_options_group extends \MoodleQuickForm_group {

    /**
     * Subfields to show
     * @var array
     */
    private array $proctorfields = [];

    /**
     * The type of proctor
     * @var string
     */
    private string $proctor;

     /**
      * Constructor
      *
      * @param string $elementname Select name attribute
      * @param string $proctor proctor type
      * @param array $proctorfields fields config for this proctor {@see remote_proctors::class}
      */
    public function __construct(string $elementname, string $proctor, array $proctorfields) {
        parent::__construct($elementname, get_string("modform_{$proctor}_settings", 'mod_bizexaminer'));
        $this->proctorfields = $proctorfields;
        $this->proctor = $proctor;
    }

    /**
     * This will create all the elements based on the field configuration.
     * @see remote_proctors::get_remote_proctor_setting_fields
     *
     * @access private
     *
     */
    // phpcs:disable moodle.NamingConventions.ValidFunctionName.LowercaseMethod, PSR2.Methods.MethodDeclaration.Underscore, Squiz.Scope.MethodScope.Missing
    function _createElements() {
        global $OUTPUT;

        $this->_elements = [];

        foreach ($this->proctorfields as $fieldname => $proctorfield) {
            $labelname = "modform_{$this->proctor}_{$fieldname}";
            $element = null;
            switch ($proctorfield['type']) {
                case 'text':
                    $element = $this->createFormElement(
                        'text',
                        $fieldname, // Get's appended to array of group name $group[$field].
                        $proctorfield['label'],
                    );
                    $this->_mform->setType($this->getName() . "[$fieldname]", PARAM_NOTAGS);
                    break;
                case 'select':
                    $element = $this->createFormElement(
                        'select',
                        $fieldname, // Get's appended to array of group name $group[$field].
                        $proctorfield['label'],
                        $proctorfield['options']
                    );
                    break;
                case 'switch':
                    $element = $this->createFormElement(
                        'selectyesno',
                        $fieldname, // Get's appended to array of group name $group[$field].
                        $proctorfield['label'],
                    );

            }
            if ($element) {
                if (!empty($proctorfield['help_text'])) {
                    $element->_helpbutton = $OUTPUT->help_icon($labelname, 'mod_bizexaminer');
                }
                $this->_elements[] = $element;
            }
        }

    }
}
