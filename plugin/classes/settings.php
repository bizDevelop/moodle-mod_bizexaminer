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
 * A service for plugin settings.
 *
 * @package     mod_bizexaminer
 * @category    settings
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bizexaminer;

use mod_bizexaminer\api\abstract_api_service;
use moodle_exception;
use moodle_url;

/**
 * A service for plugin settings.
 *
 * @package mod_bizexaminer
 */
class settings extends abstract_api_service {
    /**
     * Test/validate the settings
     *
     * @return bool
     */
    public function test_settings(): bool {
        $api = $this->get_api();
        $result = $api->test_credentials();
        return $result;
    }

    /**
     * If credentials are configured/set.
     *
     * @return bool
     */
    public function has_credentials(): bool {
        $instance = get_config('mod_bizexaminer', 'apikeyinstance');
        $keyowner = get_config('mod_bizexaminer', 'apikeyowner');
        $keyorganisation = get_config('mod_bizexaminer', 'apikeyorganisation');

        return !empty($instance) && !empty($keyowner)
            && !empty($keyorganisation);
    }

    /**
     * Get the link to the plugin settings.
     *
     * @return moodle_url
     */
    public function get_link(): moodle_url {
        return new moodle_url('/admin/settings.php', ['section' => 'modsettingbizexaminer']);
    }

    /**
     * Checks if credentials are configured, otherwise show a link to the user to configure them in settings.
     * @throws moodle_exception
     */
    public function check_credentials() {
        if (!self::has_credentials()) {
            throw new \moodle_exception('nocredentials', 'mod_bizexaminer', self::get_link());
        }
    }
}
