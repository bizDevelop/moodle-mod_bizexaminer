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
 * Scheduled task to cleanup abandoned attempts.
 *
 * @package     mod_bizexaminer
 * @category    tasks
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bizexaminer\task;

use mod_bizexaminer\local\data_objects\attempt;
use mod_bizexaminer\util;
use moodle_database;

/**
 * Cleanup abandoned attempts.
 *
 */
class clean_abandoned_attempts extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('task_cleanup_abandoned', 'mod_bizexaminer');
    }

    /**
     *
     * Abort off any overdue attempts.
     */
    public function execute() {
        /** @var moodle_database $DB */ // phpcs:ignore moodle.Commenting.InlineComment.TypeHintingMatch
        global $DB;

        $abandoneddate = strtotime('-1 month');

        mtrace('Looking for overdue exam attempts (older than ' . $abandoneddate . ')');

        // Get all attempts modified earlier than 1 month ago.
        $overdueattempts = $DB->get_records_sql("
            SELECT att.id, att.userid FROM {bizexaminer_attempts} att
            WHERE att.timemodified <= ?
            AND att.status IN ('started','pending_results')
        ", [$abandoneddate]);

        mtrace('Considering ' . count($overdueattempts) . ' attempts for aborting.');

        $aborted = 0;

        foreach ($overdueattempts as $attempt) {
            $newattemptdata = new \stdClass();
            $newattemptdata->id = $attempt->id;
            $newattemptdata->timemodified = util::create_date(time())->getTimestamp();
            $newattemptdata->status = attempt::STATUS_ABORTED;
            // Set the attempt status to aborted.
            $updated = $DB->update_record(attempt::TABLE, $newattemptdata);
            if ($updated) {
                // Unschedule results fetch cron.
                $crontask = fetch_results::instance($attempt->id, $attempt->userid);
                util::unschedule_adhoc_task($crontask);
                $aborted++;
            }
        }

        mtrace('Aborted ' . $aborted . ' exam attempts.');
    }
}
