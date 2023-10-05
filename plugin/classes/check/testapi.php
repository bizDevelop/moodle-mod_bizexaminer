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
 * A custom moodle check to test the api credentials
 *
 * @package     mod_bizexaminer
 * @category    check
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bizexaminer\check;

use core\check\check;
use core\check\result;
use mod_bizexaminer\bizexaminer;
use mod_bizexaminer\settings;

/**
 * Check which tests if the configured API credentials are valid.
 * Executed/checked in the moodle checks user interface.
 *
 * @package mod_bizexaminer
 */
class testapi extends check {

    /**
     * A link to a place to action this
     *
     * @return core\check\action_link|null
     */
    public function get_action_link(): ?\action_link {
        /** @var settings $settingsservice */
        $settingsservice = bizexaminer::get_instance()->get_service('settings');
        $url = $settingsservice->get_link();
        return new \action_link($url, get_string('configureapi', 'mod_bizexaminer'));
    }

    /**
     * Return the result
     * @return result
     */
    public function get_result(): result {
        /** @var settings $settingsservice */
        $settingsservice = bizexaminer::get_instance()->get_service('settings');
        $testresult = $settingsservice->test_settings();

        if ($testresult) {
            $status = result::OK;
            $summary = get_string('testapi_success', 'mod_bizexaminer');
        } else {
            $status = result::ERROR;
            $summary = get_string('testapi_error', 'mod_bizexaminer');
        }
        $details = get_string('testapi_desc', 'mod_bizexaminer');
        return new result($status, $summary, $details);
    }
}
