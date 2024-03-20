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
 * Scheduled task to fetch results for a specific attempt.
 *
 * @package     mod_bizexaminer
 * @category    tasks
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bizexaminer\task;

use mod_bizexaminer\local\api\exams;
use mod_bizexaminer\bizexaminer;
use mod_bizexaminer\bizexaminer_exception;
use mod_bizexaminer\local\data_objects\attempt;

/**
 * Scheduled task to fetch results for a specific attempt.
 *
 * Scheduled in exams service.
 *
 * @package mod_bizexaminer
 */
class fetch_results extends \core\task\adhoc_task {

    public static function instance(int $attemptid, int $userid) {
        $task = new self();
        $task->set_custom_data((object)[
            'attemptid' => $attemptid,
        ]);
        $task->set_userid($userid);
        return $task;
    }

    public function execute() {
        $data = $this->get_custom_data();
        if (empty($data->attemptid)) {
            mtrace('fetch_results task called without attemptid');
            return;
        }

        $attempt = attempt::get($data->attemptid);
        if (!$attempt) {
            mtrace('fetch_results task called for non-existing attempt: ' . $data->attemptid);
            return;
        }

        /** @var exams $examsservice */
        $examsservice = bizexaminer::get_instance()->get_service('exams', $attempt->get_exam()->get_api_credentials());

        try {
            $examsservice->fetch_results($attempt);
        } catch (bizexaminer_exception $exception) {
            // If any bizexaminer error happens log it with contextual information.
            $exception->add_debug_info(['attemptid' , $attempt->id]);
            mtrace_exception($exception);
        }

    }
}
