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
 * abstract api service
 *
 * @package     mod_bizexaminer
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bizexaminer\local\api;

/**
 * An abstract API service which has access to an api client
 *
 * @package mod_bizexaminer
 */
abstract class abstract_api_service {
    /**
     * API Client of this instance to use
     * @var api_client
     */
    protected api_client $api;

    /**
     * Create a new service instance
     * @param api_client $apiclient API Client to use in this instance
     */
    public function __construct(api_client $apiclient) {
        $this->api = $apiclient;
    }

    /**
     * Get the API client instance to use
     * @return api_client
     */
    protected function get_api(): api_client {
        return $this->api;
    }
}
