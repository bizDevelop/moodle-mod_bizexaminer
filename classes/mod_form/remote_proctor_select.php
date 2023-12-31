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
require_once($CFG->libdir . '/form/autocomplete.php');

use mod_bizexaminer\api\api_credentials;
use mod_bizexaminer\api\remote_proctors;
use mod_bizexaminer\bizexaminer;

/**
 * Form field type for choosing a remote proctor.
 *
 * @package     mod_bizexaminer
 */
class remote_proctor_select extends \MoodleQuickForm_select {

    /**
     * The constructor and therefore get_remote_proctors may be called multiple times
     * But any error about no options should be added to session notifications only once.
     * @var bool
     */
    private static bool $fetchoptionserroradded = false;

    /**
     * The api credentials selected for this exam.
     * If none are selected, no exam modules will be fetched.
     * If they are selected, those are used to fetch exam modules.
     *
     * @var null|api_credentials
     */
    private ?api_credentials $apicredentials = null;

     /**
      * Constructor
      *
      * @param string $elementname Select name attribute
      * @param mixed $elementlabel Label(s) for the select
      * @param mixed $attributes Either a typical HTML attribute string or an associative array
      * @param api_credentials|null $apicredentials API Credentials used to fetch remote proctors
      */
    public function __construct(
        $elementname = null, $elementlabel = null, $attributes = null, ?api_credentials $apicredentials = null) {
        $this->apicredentials = $apicredentials;
        // This comment is from the MoodleQuickForm_autocomplete class (sic!):
        // Even if the constructor gets called twice we do not really want 2x options (crazy forms!).
        $this->_options = [];
        parent::__construct($elementname, $elementlabel, [], $attributes, true);
        $this->loadArray($this->get_remote_proctors());
    }

    /**
     * Fetch remote proctor connections from bizExaminer and map them to options format.
     *
     * @return string[]
     */
    public function get_remote_proctors(): array {
        $options = [
            '' => get_string('choosedots'),
        ];

        if (!$this->apicredentials) {
            return $options;
        }

        /** @var remote_proctors $remoteproctorsservice */
        $remoteproctorsservice = bizexaminer::get_instance()->get_service('remoteproctors', $this->apicredentials);
        $remoteproctors = $remoteproctorsservice->get_remote_proctors();

        foreach ($remoteproctors as $id => $proctor) {
            $name = $proctor['name'];
            $description = $proctor['description'];
            $proctortype = $remoteproctorsservice->map_proctor_type_label($proctor['type']);
            // Put proctor type in value so it can be read out for conditionally hiding/displaying
            // the fields for this proctor.
            $value = self::build_remote_proctor_value($id, $proctor['type']);
            $options[$value] = "{$proctortype}: {$name} ({$description})";
        }

        if (empty($options) && !self::$fetchoptionserroradded) {
            \core\notification::error(get_string('modform_remote_proctor_none', 'mod_bizexaminer'));
            self::$fetchoptionserroradded = true;
        }

        return $options;
    }

    /**
     * Build the key of the remote proctor value which includes type and id to be parsed on frontend/backend.
     *
     * Type is required for conditional rendering of sub-fields.
     *
     * @param string $id
     * @param string $type
     * @return string
     */
    public static function build_remote_proctor_value(string $id, string $type): string {
        return "{$type}_-_{$id}";
    }

    /**
     * Parse a combined value of remote proctor type and id.
     *
     * @param string $value
     * @return null|string[]
     */
    public static function parse_remote_proctor_value(string $value = '') {
        return empty($value) || !str_contains($value, '_-_') ? null : explode('_-_', $value);
    }

    /**
     * Check that the remote proctor exists
     *
     * @param string $value Submitted value.
     * @return string|null Validation error message or null.
     */
    public function validateSubmitValue($value) { // phpcs:disable moodle.NamingConventions.ValidFunctionName.LowercaseMethod
        if (!$this->apicredentials) {
            return get_string('modform_remote_proctor_invalid', 'mod_bizexaminer');
        }

        if (!empty($value)) {
            /** @var remote_proctors $remoteproctorsservice */
            $remoteproctorsservice = bizexaminer::get_instance()->get_service('remoteproctors', $this->apicredentials);
            if (str_contains($value, '_-_')) {
                // Frontend stores it with {$proctorType}_-_{$proctorAccountName} for conditional hiding/displaying.
                $value = substr($value, strpos($value, '_-_') + 3);
            }
            $remoteproctoreexists = $remoteproctorsservice->has_remote_proctor((string)$value);
            if (!$remoteproctoreexists) {
                return get_string('modform_remote_proctor_invalid', 'mod_bizexaminer');
            }
        }
    }
}
