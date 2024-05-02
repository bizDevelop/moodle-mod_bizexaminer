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
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bizexaminer;

use mod_bizexaminer\local\api\api_credentials;
use moodle_exception;
use moodle_url;

/**
 * A service for plugin settings.
 *
 * @package mod_bizexaminer
 */
class settings {

    /**
     * The main name of the main setting which stores the referenced ids.
     * @var string
     */
    private const OPTION_NAME = 'apicredentials';

    /**
     * All keys which a single api credential set has and which defines which single nested options are stored.
     * @var string[]
     */
    public const CREDENTIAL_KEYS = [
        'name', // Internal, for easier organisation.
        'keyowner',
        'keyorganisation',
        'instance',
    ];

    /**
     * Build the key for a single api credentials id and field.
     *
     * @param string $id
     * @param string $prop
     * @return string
     */
    public function build_credential_prop_option_key(string $id, string $prop): string {
        return  self::OPTION_NAME . '_' . $id . '_' . $prop;
    }

    /**
     * In the frontend, in courses, in callback api
     * we can't use admin_setting_api_credentials, since admin_setting/adminlib is not loaded.
     * Therefore functionality to retrieve all values has to be duplicated here.
     * Manual version of admin_setting_api_credentials::get_setting
     *
     * @return array
     */
    public function get_raw_credentials(): array {
        $dbvalue = get_config('mod_bizexaminer', self::OPTION_NAME);
        $ids = $dbvalue ? explode(':', $dbvalue) : [];
        $keysnumber = count(self::CREDENTIAL_KEYS);
        $credentials = [];
        foreach ($ids as $id) {
            $credential = [];
            foreach (self::CREDENTIAL_KEYS as $key) {
                $value = get_config('mod_bizexaminer', $this->build_credential_prop_option_key($id, $key));
                if ($value !== null) {
                    $credential[$key] = $value;
                }
            }
            if (count($credential) === $keysnumber) {
                $credentials[$id] = $credential;
            }
        }
        return $credentials;
    }

    /**
     * Get all the configured api credentials as api_credentials object
     *
     * @return api_credentials[]
     */
    public function get_credentials(): array {
        $rawvalues = $this->get_raw_credentials();
        $credentials = [];
        foreach ($rawvalues as $id => $rawvalue) {
            $credentials[] = api_credentials::from_array($id, $rawvalue);
        }
        return $credentials;
    }

    /**
     * Test/validate the settings
     *
     * @return array
     */
    public function test_settings(): array {
        $results = [];
        foreach ($this->get_credentials() as $credentials) {
            $results[] = [
                'credentials' => $credentials,
                'result' => $credentials->test_credentials(),
            ];
        }
        return $results;
    }

    /**
     * If credentials are configured/set.
     *
     * @return bool
     */
    public function has_credentials(): bool {
        return !empty($this->get_raw_credentials());
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
    public function check_has_credentials() {
        if (!self::has_credentials()) {
            throw new \moodle_exception('nocredentials', 'mod_bizexaminer', self::get_link());
        }
    }
}
