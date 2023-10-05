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
 * Callback API for redirects/http callbacks from bizExaminer
 *
 * Does not require a login check because it's a callback that may be called from the bizExaminer api without user interaction
 * Only require login for startExam and examReturn
 *
 * @package     mod_bizexaminer
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_bizexaminer\bizexaminer;
use mod_bizexaminer\callback_api\callback_api;

// Disable moodle specific debug messages and any errors in output,
// comment out when debugging or better look into error log!
// Also prevents notice about missing $PAGE->set_url - which is not needed on this page, which mostly redirects.
define('NO_DEBUG_DISPLAY', true);

// phpcs:ignore moodle.Files.RequireLogin.Missing -- can be called without user, checks capabilities in callback_api class
require(__DIR__.'/../../config.php');
require_once("lib.php");
require_once($CFG->dirroot.'/mod/bizexaminer/lib.php');

// Use a custom exception handler which just logs exceptions and stops.
set_exception_handler(callback_api::get_exception_handler());

// Prevent caching of API.
@header('Cache-Control: no-store, no-cache, must-revalidate');
@header('Expires: Mon, 20 Aug 1969 09:23:00 GMT');
@header_remove('Last-Modified');

$action = required_param('cbaction', PARAM_ALPHANUMEXT);

$PAGE->set_context(null); // Don't know which attempt or exam is handled yet.

/** @var callback_api $callbackapi */
$callbackapi = bizexaminer::get_instance()->get_service('callbackapi');
if (!$callbackapi->has_action($action)) {
    throw new \moodle_exception('callbackapi_action', 'mod_bizexaminer');
}
$callbackapi->handle($action);
