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
 * A bizExaminer business logic/flow exception for services and callback api.
 *
 * @package     mod_bizexaminer
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bizexaminer;

use mod_bizexaminer\local\api\api_error;
use moodle_exception;

/**
 * A bizExaminer business logic/flow exception for services and callback api.
 *
 * @package mod_bizexaminer
 */
class bizexaminer_exception extends moodle_exception {

    /**
     * Additional debuginfo to output.
     *
     * @var string
     */
    public $debuginfo = '';

    /**
     * An api_error that triggered this exception.
     *
     * @var api_error|null
     */
    public ?api_error $apierror = null;

    /**
     * Add aditional contextual data for debugging to the exception.
     *
     * @param array $data
     */
    public function add_debug_info(array $data): void {
        if (empty($this->debuginfo)) {
            $this->debuginfo = '';
        }

        $additionalinfos = [];
        foreach ($data as $key => $value) {
            $additionalinfos[] = " $key:$value";
        }

        $this->debuginfo .= implode(',', $additionalinfos) . ' ';
    }

    /**
     * Set an api_error that triggered this exception
     * and add it to debug info
     *
     * @param api_error $apierror
     * @return void
     */
    public function set_error(api_error $apierror) {
        $this->apierror = $apierror;
        $this->add_debug_info(['api_error' => json_encode($apierror)]);
    }
}
