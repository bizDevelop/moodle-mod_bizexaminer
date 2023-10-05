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
 * Api error
 *
 * @package     mod_bizexaminer
 * @category    api
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bizexaminer\api;

/**
 * DTO for API errors
 */
class api_error {
    /**
     * An internal error code
     * @var string
     */
    protected string $errorcode;

    /**
     * A message about what went wrong
     * @var string
     */
    protected string $message;

    /**
     * Any additional contextual data
     * @var null|array
     */
    protected ?array $data = null;

    /**
     * Create a new error instance
     * @param string $errorcode An internal error code
     * @param string $message A message about what went wrong
     * @param null|array $data Any additional contextual data
     */
    public function __construct(string $errorcode, string $message, ?array $data = null) {
        $this->errorcode = $errorcode;
        $this->message = $message;
        $this->data = $data;
    }

    /**
     * Get the message about what went wrong
     * @return string
     */
    public function get_message(): string {
        return $this->message;
    }

    /**
     * Get the internal error code
     * @return string
     */
    public function get_error_code(): string {
        return $this->errorcode;
    }

    /**
     * Get any additional contextual data
     * @return null|array
     */
    public function get_data(): ?array {
        return $this->data;
    }

    /**
     * Checks if the object passed is an api_error instance
     * @param mixed $mayberror
     * @return bool
     */
    public static function is_a($mayberror): bool {
        return is_a($mayberror, self::class);
    }
}
