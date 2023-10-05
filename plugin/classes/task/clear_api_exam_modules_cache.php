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
 * Task to clear/purge the exam modules fetched from API.
 *
 * @package     mod_bizexaminer
 * @category    tasks
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bizexaminer\task;

use cache;

/**
 * Adhoc task to clear/purge the exam modules fetched from api
 * Will be scheduled for a short amount of time in the future after getting the exam modules
 * because the cache ttl flag shouldnt be used
 * see https://docs.moodle.org/dev/Cache_AP
 *
 * @package mod_bizexaminer
 */
class clear_api_exam_modules_cache extends \core\task\adhoc_task {

    public function execute() {
        $exammodulescache = cache::make('mod_bizexaminer', 'exam_modules');
        $exammodulescache->purge();
    }
}
