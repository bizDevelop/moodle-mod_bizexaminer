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
 * Api credentials object
 *
 * @package     mod_bizexaminer
 * @category    api
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 namespace mod_bizexaminer\api;

use mod_bizexaminer\bizexaminer;

/**
 * DTO for API credentials
 */
class api_credentials {

    /**
     * The unique ID generated when saving the api credentials set.
     *
     * @var string
     */
    protected string $id;

    /**
     * Used internally for displaying and selecting API credentials.
     *
     * @var string|null
     */
    protected ?string $name;

    /**
     * Domain instance the API credentials are used on
     *
     * @var string
     */
    protected string $instance;

    /**
     * The API key for the (content) owner
     *
     * @var string
     */
    protected string $ownerkey;

    /**
     * The API key for the organisation
     *
     * @var string
     */
    protected string $organisationkey;

    /**
     * Creates a new api_credentials instance
     *
     * @param string $id The unique ID generated when saving the api credentials set.
     * @param string $name The name of the credential set used internally
     * @param string $instance Domain instance the API credentials are used on
     * @param string $ownerkey The API key for the (content) owner
     * @param string $organisationkey The API key for the organisation
     */
    public function __construct(string $id, string $name, string $instance, string $ownerkey, string $organisationkey) {
        $this->id = $id;
        $this->name = $name;
        $this->instance = $instance;
        $this->ownerkey = $ownerkey;
        $this->organisationkey = $organisationkey;
    }

    /**
     * Get the domain instance the API credentials are used on
     *
     * @return string
     */
    public function get_instance(): string {
        return $this->instance;
    }

    /**
     * Get the API key for the (content) owner
     *
     * @return string
     */
    public function get_owner_key(): string {
        return $this->ownerkey;
    }

    /**
     * Get the API key for the organisation
     *
     * @return string
     */
    public function get_organisation_key(): string {
        return $this->organisationkey;
    }

    /**
     * Get a unique id of this API credentials to use as a reference
     *
     * @return string
     */
    public function get_id(): string {
        return $this->id;
    }

    /**
     * Gets the internally used name of the set.
     *
     * @return string
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * Returns how many times the api credentials are used in exams.
     *
     * @return int
     */
    public function get_exams_used_count(): int {
        global $DB;
        $count = $DB->count_records('bizexaminer', ['apicredentials' => $this->id]);
        return $count;
    }

    /**
     * Get a bizExaminer API client configured with this credentials.
     *
     * @return api_client
     */
    public function get_api_client(): api_client {
        return new api_client($this);
    }

    /**
     * Test if credentials are valid by calling a simple function on the API
     *
     * @return bool
     */
    public function test_credentials(): bool {
        return $this->get_api_client()->test_credentials();
    }

    /**
     * Get a credential set by id
     *
     * @param string $id
     * @return null|api_credentials
     */
    public static function get_by_id(string $id): ?self {
        $credentials = bizexaminer::get_instance()->get_service('settings')->get_raw_credentials();
        if (empty($credentials) || empty($credentials[$id])) {
            return null;
        }

        return self::from_array($id, $credentials[$id]);
    }

    /**
     * Get an array of all api credentials configured.
     * @return self[]
     */
    public static function get_all() : array {
        return bizexaminer::get_instance()->get_service('settings')->get_credentials();
    }

    /**
     * Build a new api credentials set from an array (eg from settings).
     *
     * @param string $id
     * @param array $settingvalues must have keys 'name', 'instance', 'keyowner', 'keyorganisation'
     * @return api_credentials
     */
    public static function from_array(string $id, array $settingvalues): self {
        return new self(
            $id,
            $settingvalues['name'],
            $settingvalues['instance'],
            $settingvalues['keyowner'],
            $settingvalues['keyorganisation']
        );
    }
}
