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
 * Api client
 *
 * @package     mod_bizexaminer
 * @category    api
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bizexaminer\api;

use core\http_client;
use core_date;
use DateTime;
use mod_bizexaminer\util;

/**
 * A base HTTP implementation of an API Client
 *
 * Works with GuzzleHTTP moodle 4.2
 * @link https://docs.google.com/document/d/1UvvQJOhBZ6d2SpPSz5U5SL6dULwgdW1dnVSXwrDaODQ
 */
class api_client {
    /**
     * Date format to use for date fields
     *
     * @var string
     */
    public const DATE_FORMAT = 'Y-m-d\TH:i:sP';

    /**
     * Path to the API on a domain/instance
     *
     * @var string
     */
    protected const API_PATH = '/api/exmservice';

    /**
     * api_credentials to use to connect to API
     *
     * @var api_credentials
     */
    protected api_credentials $apicredentials;

    /**
     * Creates a new api_client instance
     *
     * @param api_credentials $apicredentials
     */
    public function __construct(api_credentials $apicredentials) {
        $this->apicredentials = $apicredentials;
    }

    /**
     * Gets the api credentials used by this client
     *
     * @return api_credentials
     */
    public function get_credentials(): api_credentials {
        return $this->apicredentials;
    }

    /**
     * Calls getProductParts to get all exam modules and content revisions
     *
     * @return array Array of productParts (see documentation)
     */
    public function get_exam_modules(): array {
        $result = $this->make_call(
            'getProductParts',
            [
                'includeDemos' => 0,
            ]
        );

        $error = $this->handle_api_result_errors($result);
        if (!$error && $result->get_response_code() === api_result::STATUS_OK) {
            if ($result->is_success()) {
                $exammodules = $result->get_response();
                return $exammodules;
            }
        }
        return [];
    }

    /**
     * Calls getRemoteProctoringEnvironments to get all remote proctors
     *
     * @return array Array of remote proctors (see documentation)
     */
    public function get_remote_proctoring_environments(): array {
        $result = $this->make_call('getRemoteProctoringEnvironments');
        $error = $this->handle_api_result_errors($result);
        if (!$error && $result->get_response_code() === api_result::STATUS_OK) {
            if ($result->is_success()) {
                $resultdata = $result->get_response();
                if (isset($resultdata->environments)) {
                    return $resultdata->environments;
                }
            }
        }
        return [];
    }


    /**
     * Calls createBooking to book an exam
     *
     * @param string $exammodule
     * @param string $contentrevisionid
     * @param string $participantid
     * @param string $returnurl
     * @param string $callbackurl
     * @param string|null $remoteproctor
     * @param array $remoteproctorsettings default: []
     * @param string $uilanguage
     * @param DateTime|null $startdate
     * @param DateTime|null $enddate
     * @param string|null $accesscode
     * @return array|false bookingdata (array):
     *                        'bookingId' => (int) the exam booking id
     *                        'url' => (string) the url to start the exam
     */
    public function book_exam(
        string $exammodule,
        string $contentrevisionid,
        string $participantid,
        string $returnurl,
        string $callbackurl,
        ?string $remoteproctor = null,
        array $remoteproctorsettings = [],
        string $uilanguage = '',
        ?DateTime $startdate = null,
        ?DateTime $enddate = null,
        ?string $accesscode = null
    ) {

        if (!$startdate) {
            $startdate = util::create_date(strtotime('now'));
        }
        if (!$enddate) {
            $enddate = util::create_date($startdate->getTimestamp());
            $enddate->modify('+24 hours'); // Default to 24 hours future.
        }

         // Generate random username + password for each booking
         // ... since the directAccessLoginUrl/directAccessExamUrl will be used to log the user in.
        $username = uniqid('beld-');
        $password = generate_password();

        $requestdata = [
            'productPartsId' => $exammodule,
            'participantID' => $participantid,
            'redirectAfterFinishUrl' => $returnurl,
            'callBackUrl' => $callbackurl,
            'contentsRevisionsId' => $contentrevisionid,
            'validFrom' => $startdate->format(self::DATE_FORMAT),
            'validTo' => $enddate->format(self::DATE_FORMAT),
            'timezone' => core_date::get_user_timezone(),
            'attendanceCount' => 1,
            'username' => $username,
            'password' => $password,
            'returnWithAccessUrls' => 1,
            'uiLanguage' => $uilanguage,
            'remoteProctoringEnvironment' => $remoteproctor,
            'remoteProctoringOptions' => $remoteproctorsettings,
        ];

        if (!empty($accesscode)) {
            $requestdata['accessCode'] = $accesscode;
        }

        $result = $this->make_call('createBooking', $requestdata);
        $error = $this->handle_api_result_errors($result);
        if (!$error && $result->get_response_code() === api_result::STATUS_OK) {
            if ($result->is_success()) {
                $response = $result->get_response();
                return [
                    'bookingId' => $response->exmBookingsId,
                    'url' => $response->directAccessExamUrl,
                ];
            }
        }
        return false;
    }

    /**
     * Gets the direct exam access url for a booking.
     *
     * @param int $bookingid
     * @param string $uilanguage
     * @return string|false directAccessExamUrl on success, false on error
     */
    public function get_examination_accessurl(int $bookingid, string $uilanguage = '') {
        $result = $this->make_call('getExaminationAccessUrl', [
            'bookingsId' => $bookingid,
            'directExamAccess' => 1,
            'uiLanguage' => $uilanguage,
        ]);
        $error = $this->handle_api_result_errors($result);
        if (!$error && $result->get_response_code() === api_result::STATUS_OK) {
            if ($result->is_success()) {
                $response = $result->get_response();
                return $response->url;
            }
        }
        return false;
    }


    /**
     * Cralls createParticipant to create a new participant
     *
     * @param string $firstname
     * @param string $lastname
     * @param string $email
     * @return string|false participantId if created, false if not found
     */
    public function create_participant(string $firstname, string $lastname, string $email = '') {
        $result = $this->make_call(
            'createParticipant',
            [
                'firstName' => $firstname,
                'lastName' => $lastname,
                'email' => $email,
                // Required but an empty value is allowed.
                'gender' => '',
            ]
        );

        $error = $this->handle_api_result_errors($result);
        if (!$error && $result->get_response_code() === api_result::STATUS_OK) {
            if ($result->is_success()) {
                $participantid = $result->get_response()->participantID;
                return $participantid;
            }
        }
        return false;
    }

    /**
     * Calls checkParticipant to check for an existing participant based on the search data
     *
     * @param array $searchdata Search for a participant with the following data
     *              'id'|'participantID' => (string)
     *              'email' => (string)
     *              'firstName' => (string)
     *              'lastName' => (string)
     * @return string|false participantId if found, false if not found
     */
    public function check_participant(array $searchdata) {
        $allowedsearchdata = array_intersect_key(
            $searchdata,
            array_flip(['id', 'participantID', 'email', 'firstName', 'lastName'])
        );

        if (isset($allowedsearchdata['id'])) {
            $allowedsearchdata['participantID'] = $allowedsearchdata['id'];
            unset($allowedsearchdata['id']);
        }

        $result = $this->make_call(
            'checkParticipant',
            $allowedsearchdata
        );

        $error = $this->handle_api_result_errors($result);
        if (!$error && $result->get_response_code() === api_result::STATUS_OK) {
            if ($result->is_success()) {
                $participants = $result->get_response();
                if (count($participants) > 0) {
                    return $participants[0]->participantID;
                }
            }
        }
        return false;
    }

    /**
     * Calls getParticipantOverview to get the results for a participant in a booking
     *
     * @param string $participantid
     * @param string $bookingid
     * @return array array of results (see docs)
     */
    public function get_participant_overview(string $participantid, string $bookingid): array {
        $result = $this->make_call('getParticipantOverview', [
            'participantID' => $participantid,
            'exmBookingsId' => $bookingid,
        ]);

        $error = $this->handle_api_result_errors($result);
        if (!$error && $result->get_response_code() === api_result::STATUS_OK) {
            if ($result->is_success()) {
                $results = $result->get_response();
                return $results;
            }
        }
        return [];
    }

    /**
     * Calls getParticipantOverviewWithDetailsAndContent to get the results including content details
     *
     * @param string $participantid
     * @param string $bookingid
     * @return array array of results (see docs)
     */
    public function get_participant_overview_with_details(string $participantid, string $bookingid): array {
        $result = $this->make_call('getParticipantOverviewWithDetailsAndContent', [
            'participantID' => $participantid,
            'exmBookingsId' => $bookingid,
        ]);
        $error = $this->handle_api_result_errors($result);
        if (!$error && $result->get_response_code() === api_result::STATUS_OK) {
            if ($result->is_success()) {
                $results = $result->get_response();
                return $results;
            }
        }
        return [];
    }

    /**
     * Test if credentials are valid by calling a simple function on the API
     *
     * @return bool
     */
    public function test_credentials(): bool {
        $result = $this->make_call('getProductParts');
        $error = $this->handle_api_result_errors($result);
        if (!$error && $result->get_response_code() === api_result::STATUS_OK) {
            if ($result->is_success()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks for common (400, 401, 404, 500) HTTP Errors
     * and error codes from API
     *
     * @param api_result|api_error|null $apiresult
     * @return false|api_error false if no error was matched, otherwise an api_error
     */
    protected function handle_api_result_errors($apiresult) {
        // Somehow null was returned - eg another error happened somewhere.
        if (!$apiresult) {
            $error = new api_error(
                'bizexaminer-api-error',
                'Api returned another error',
                ['result' => $apiresult]
            );
            $this->log_error($error);
            return $error;
        }

        // Exception occurred during request.
        if (api_error::is_a($apiresult)) {
            /** @var api_error $error */
            $error = $apiresult;
            $this->log_error($error);
            return $apiresult;
        }

        if (
            $apiresult->get_response_code() === api_result::STATUS_OK &&
            (isset($apiresult->get_body()->success) && $apiresult->get_body()->success)
        ) {
            return false;
        }

        $error = null;

        if (!empty($apiresult->get_error_code())) {
            switch ($apiresult->get_error_code()) {
                case 'keys_error':
                    $error = new api_error(
                        'bizexaminer-api-not-allowed',
                        'Api returned not allowed',
                        ['result' => $apiresult]
                    );
                    break;
                case 'inputdata_error':
                    $error = new api_error(
                        'bizexaminer-api-error',
                        'Api returned another error',
                        ['result' => $apiresult]
                    );
                    break;
                case 'json-parsing-error':
                    $error = new api_error(
                        'bizexaminer-api-error',
                        'Api returned malformed JSON',
                        ['result' => $apiresult]
                    );
                    break;
            }
        }

        switch ($apiresult->get_response_code()) {
            case api_result::STATUS_UNAUTHORIZED:
                $error = new api_error(
                    'bizexaminer-api-not-allowed',
                    'Api returned not allowed',
                    ['result' => $apiresult]
                );
                break;
            case api_result::STATUS_NOT_FOUND:
                $error = new api_error(
                    'bizexaminer-api-not-found',
                    "Requested Api url was not found.",
                    ['result' => $apiresult]
                );
                break;
            case api_result::STATUS_BAD_REQUEST:
            case api_result::STATUS_ERROR:
            default:
                $error = new api_error(
                    'bizexaminer-api-error',
                    'Api returned another error',
                    ['result' => $apiresult]
                );
                break;
        }

        if ($error) {
            $this->log_error($error);
            return $error;
        }

        return false;
    }

    /**
     * Sends an HTTP(S) request to the API
     *
     * @param string $function  The function to call
     * @param array $data Data to send as body with request
     * @return api_result|api_error
     */
    public function make_call($function, array $data = []) {

        $body = array_merge($data, [ // Overwrite fixed values.
            'function' => $function,
            'key_owner' => $this->apicredentials->get_owner_key(),
            'key_organisation' => $this->apicredentials->get_organisation_key(),
        ]);

        $url = 'https://' . trim($this->apicredentials->get_instance(), '/') . self::API_PATH;

        $httpclient = new http_client();

        try {
            $result = $httpclient->post($url, [
                'headers' => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Accept: application/vnd.bizexaminer.exmservice-v1+json',
                    'User-Agent: Moodle/mod_bizexaminer',
                ],
                'form_params' => $body,
            ]);
        } catch (\Exception $exception) {
            return new api_error($exception->getCode(), $exception->getMessage(), ['trace' => $exception->getTrace()]);
        }

        $apiresult = new api_result($function, $result);

        return $apiresult;
    }

    protected function log_error(api_error $error) {
        util::log('API: ' . $error->get_message(), DEBUG_ALL);
    }
}
