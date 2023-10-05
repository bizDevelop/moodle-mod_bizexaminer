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

/**
 * DTO for API credentials
 */
class api_credentials {

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
     * @param string $instance Domain instance the API credentials are used on
     * @param string $ownerKey The API key for the (content) owner
     * @param string $organisationKey The API key for the organisation
     */
    public function __construct(string $instance, string $ownerkey, string $organisationkey) {
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
        return md5($this->get_instance() . ':' . $this->get_owner_key() . ':' . $this->get_organisation_key());
    }
}
