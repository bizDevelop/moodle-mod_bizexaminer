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
 * Api result object
 *
 * @package     mod_bizexaminer
 * @category    api
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bizexaminer\local\api;

use Psr\Http\Message\ResponseInterface;
use stdClass;

/**
 * DTO for results from the API
 */
class api_result {

    public const STATUS_OK = 200;
    public const STATUS_UNAUTHORIZED = 401;
    public const STATUS_NOT_FOUND = 404;
    public const STATUS_BAD_REQUEST = 400;
    public const STATUS_ERROR = 500;

    /**
     * The requested function
     *
     * @var string
     */
    protected string $requestfunction;

    /**
     * The HTTP response code as an int
     *
     * @var int|null
     */
    protected ?int $responsecode;

    /**
     * The complete body parsed via json_decode
     *
     * @var stdClass
     */
    protected ?stdClass $body;

    /**
     * Whether the API returned successfully or not
     *
     * @var bool
     */
    protected bool $success;

    /**
     * The actual response data as parsed from JSON body
     *
     * @var mixed|null
     */
    protected $response;

    /**
     * The error code from the API
     *
     * @var string|null
     */
    protected ?string $errorcode;

    /**
     * The error message from the API
     *
     * @var string|null
     */
    protected ?string $errormessage;

    /**
     * The error details with infos to specific fields
     *
     * @var mixed|stdClass
     */
    protected $errordetails;

    /**
     * Creates a new ApiResult instance
     *
     * @param string $requestfunction The requested function
     * @param ResponseInterface $response The HTTP response from Guzzle
     */
    public function __construct(string $requestfunction, ResponseInterface $response) {
        $this->requestfunction = $requestfunction;

        $this->responsecode = $response->getStatusCode();

        $body = json_decode($response->getBody(), false);
        if ($body !== null) {
            $this->body = $body;
        } else if (!$body) {
            $this->body = new stdClass();
            $this->body->success = false;
            $this->body->errorcode = 'json-parsing-error';
            $this->body->errorMessage = 'Error parsing JSON response.';
        }

        $this->success = $this->body->success ?? false;
        if ($this->success) {
            $this->errorcode = null;
            $this->errormessage = null;
            $this->response = $this->body->response ?? null;
        } else {
            $this->errorcode = $this->body->errorcode ?? '';
            $this->errormessage = $this->body->errormessage ?? '';
            $this->errordetails = $this->body->errordetails ?? new stdClass();
            $this->response = null;
        }
    }

    /**
     * Get the requested function
     *
     * @return string
     */
    public function get_function(): string {
        return $this->requestfunction;
    }

    /**
     * Get the HTTP response code as an int
     *
     * @return int|null
     */
    public function get_response_code(): ?int {
        return $this->responsecode;
    }

    /**
     * Get the body as stdClass object parsed via json-decode
     *
     * @return stdClass
     */
    public function get_body(): stdClass {
        return $this->body;
    }

    /**
     * Whether the API returned successfully or not
     *
     * @return bool
     */
    public function is_success(): bool {
        return $this->success;
    }

    /**
     * Gets the actual response data as parsed from JSON body
     *
     * @return mixed|null
     */
    public function get_response() {
        return $this->response;
    }

    /**
     * Gets the error code from the API
     *
     * @return string|null errorcode from API as string or null if it was successful/given
     */
    public function get_error_code(): ?string {
        return $this->errorcode;
    }

    /**
     * Gets the error message from the API
     *
     * @return string|null errormessage from API as string or null if it was successful/given
     */
    public function get_error_message(): ?string {
        return $this->errormessage;
    }

    /**
     * Gets the error details from the API
     *
     * @return mixed|stdClass errormessage from API as string or null if it was successful/given
     */
    public function get_error_details() {
        return $this->errordetails;
    }
}
