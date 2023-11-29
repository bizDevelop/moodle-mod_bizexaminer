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
 * Common helper and utils for the plugin.
 *
 * @package     mod_bizexaminer
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bizexaminer;

use coding_exception;
use context_module;
use core\check\performance\debugging;
use core\task\adhoc_task;
use DateTime;
use mod_bizexaminer\data_objects\exam;
use stdClass;
use Throwable;

/**
 * Helpers and utils for the plugin.
 *
 * @package mod_bizexaminer
 */
abstract class util {

    /**
     * Gets the current context module context
     *
     * @param null|stdClass|cm_info $coursemodule {@see get_coursemodule}
     * @return null|context_module
     */
    public static function get_cm_context($coursemodule): ?context_module {
        if (!$coursemodule) {
            return null;
        }
        return context_module::instance($coursemodule->id);
    }

    /**
     * Get the coursemodule for an examid instance.
     *
     * @param int $examid
     * @param null|int $courseid
     * @return false|stdClass
     */
    public static function get_coursemodule(int $examid, ?int $courseid = null) {
        if (!$courseid) {
            $exam = exam::get($examid);
            if ($examid) {
                $courseid = $exam->course;
            }
        }

        if (!$courseid) {
            return false;
        }

        $cm = get_coursemodule_from_instance('bizexaminer', $examid, $courseid);
        return $cm;
    }

    /**
     * Unschedules an adhoc task if it's not necessary anymore.
     *
     * No real way to unschedule a task except from deleting directly from DB or calling this function.
     *
     * @see \core\task\manager::get_queued_adhoc_task_record
     *
     * @param \core\task\adhoc_task $task
     * @return bool
     */
    public static function unschedule_adhoc_task($task): bool {
        /** @var moodle_database $DB */ // phpcs:ignore moodle.Commenting.InlineComment.TypeHintingMatch
        global $DB;
        try {
            if ($task->get_id()) {
                $DB->delete_records('task_adhoc', ['id' => $task->get_id()]);
            } else {
                $sql = 'classname = ? AND component = ? AND ' .
                $DB->sql_compare_text('customdata', \core_text::strlen($task->get_custom_data_as_string()) + 1) . ' = ?';
                $params = [
                    self::get_canonical_class_name($task),
                    $task->get_component(),
                    $task->get_custom_data_as_string(),
                ];
                if ($task->get_userid()) {
                    $params[] = $task->get_userid();
                    $sql .= " AND userid = ? ";
                }
                $DB->delete_records_select('task_adhoc', $sql, $params);
            }
        } catch (\dml_exception $ex) {
            return false;
        }
        return true;
    }

    /**
     * Returns detailed information about specified exception.
     *
     * @param Throwable $exception any sort of exception or throwable.
     * @param string $level The level on which to log the exception. Any of moodles DEBUG_* levels
     * @return void
     */
    public static function log_exception(Throwable $exception, string $level = DEBUG_NORMAL): void {
        // Taken form default_exception_handler.
        $info = get_exception_info($exception);
        $logerrmsg = $info->message . ' Debug: ' . $info->debuginfo . "\n" . format_backtrace($info->backtrace, true);
        self::log($logerrmsg, $level);
    }

    /**
     * Log a string via moodles debugging()
     *
     * @see debugging
     *
     * @param string $message
     * @param string $level
     */
    public static function log(string $message, string $level = DEBUG_NORMAL): void {
        $logmessage = "[bizExaminer]: " . $message;
        debugging($logmessage, $level);
    }

    /**
     * Get the system/users/course language and return it as a two-character symbol.
     *
     * @return string
     */
    public static function get_lang(): string {
        $lang = current_language();
        $lang = substr($lang, 0, 2);
        return $lang;
    }

    /**
     * Creates a DateTime object from a timestamp, keeping the servers timezone
     *
     * @param null|int $timestamp
     * @return null|DateTime
     */
    public static function create_date(?int $timestamp): ?DateTime {
        if (!$timestamp) {
            return null;
        }
        $date = new DateTime();
        $date->setTimestamp($timestamp);
        return $date;
    }

    /**
     * Gets class name for use in database table. Always begins with a \.
     *
     * Copied from \core\task\manager::get_canonical_class_name
     * @param string|task_base $taskorstring Task object or a string
     */
    protected static function get_canonical_class_name($taskorstring) {
        if (is_string($taskorstring)) {
            $classname = $taskorstring;
        } else {
            $classname = get_class($taskorstring);
        }
        if (strpos($classname, '\\') !== 0) {
            $classname = '\\' . $classname;
        }
        return $classname;
    }
}
