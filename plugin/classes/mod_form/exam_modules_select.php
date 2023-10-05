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
 * Select field with selectgroups for exam modules
 *
 * @package     mod_bizexaminer
 * @category    mod_form
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bizexaminer\mod_form;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/form/selectgroups.php');

use mod_bizexaminer\api\exam_modules;
use mod_bizexaminer\bizexaminer;

/**
 * Form field type for choosing an exam module.
 *
 * TODO: Add autocomplete/search to selectgroups (#2).
 * Moodles default autocomplete field does not support optgroups
 */
class exam_modules_select extends \MoodleQuickForm_selectgroups {

    /**
     * The constructor and therefore get_exam_modules may be called multiple times
     * But any error about no options should be added to session notifications only once.
     * @var bool
     */
    private static bool $fetchoptionserroradded = false;

     /**
      * Constructor
      *
      * @param string $elementName Select name attribute
      * @param mixed $elementLabel Label(s) for the select
      * @param mixed $attributes Either a typical HTML attribute string or an associative array
      */
    public function __construct($elementname = null, $elementlabel = null, $attributes = null) {
        // This comment is from the MoodleQuickForm_autocomplete class (sic!).
        // Even if the constructor gets called twice we do not really want 2x options (crazy forms!).
        $this->_optGroups = [];
        parent::__construct($elementname, $elementlabel, [], $attributes, false);
        $this->loadArrayOptGroups(self::get_exam_modules());
    }

    /**
     * Get the exam modules from API and map them to the correct format.
     *
     * @return array
     */
    private static function get_exam_modules(): array {
        /** @var exam_modules $exammodulesservice */
        $exammodulesservice = bizexaminer::get_instance()->get_service('exammodules');
        $exammodules = $exammodulesservice->get_exam_modules();

        $optiongroups = [
            '' => [
                'text' => get_string('choosedots'),
            ],
        ];

        foreach ($exammodules as $id => $exammodule) {
            $optiongrouplabel = $exammodule['name'];
            $options = [];
            foreach ($exammodule['modules'] as $moduleid => $module) {
                $fullid = $module['fullid']; // Includes exam id, exam module id AND content revision id.
                $options[$fullid] = $module['name'];
            }
            $optiongroups[$optiongrouplabel] = $options;
        }

        if (empty($options) && !self::$fetchoptionserroradded) {
            \core\notification::error(get_string('modform_exam_module_none', 'mod_bizexaminer'));
            self::$fetchoptionserroradded = true;
        }

        return $optiongroups;
    }

    /**
     * Check that the exam module exists
     *
     * @param string $value Submitted value.
     * @return string|null Validation error message or null if valid.
     */
    // phpcs:disable moodle.NamingConventions.ValidFunctionName.LowercaseMethod -- name form QuickForm
    public function validateSubmitValue($value) {
        if ($value !== null) {
            /** @var exam_modules $exammodulesservice */
            $exammodulesservice = bizexaminer::get_instance()->get_service('exammodules');
            $exammoduleexists = $exammodulesservice->has_exam_module_content_revision((string)$value);
            if (!$exammoduleexists) {
                return get_string('exam_module_invalid', 'mod_bizexaminer');
            }
        }
    }
}
