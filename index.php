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
 * Plugin administration pages are defined here.
 *
 * @package     mod_bizexaminer
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once($CFG->dirroot.'/mod/bizexaminer/lib.php');

$id = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
require_course_login($course);

$coursecontext = \core\context\course::instance($course->id);
$event = \mod_bizexaminer\event\course_module_instance_list_viewed::create([
    'context' => $coursecontext,
]);
$event->add_record_snapshot('course', $course);
$event->trigger();

$modulenameplural = get_string('modulenameplural', 'mod_bizexaminer');
$modulename = get_string('modulename', 'mod_bizexaminer');

$PAGE->set_url(new moodle_url('/mod/bizexaminer/index.php', ['id' => $course->id]));
$PAGE->navbar->add($modulenameplural);
$PAGE->set_title(format_string($course->shortname.': ' . $modulenameplural));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($coursecontext);

/** @var mod_bizexaminer\output\renderer $renderer */
$renderer = $PAGE->get_renderer('mod_bizexaminer');
$output = ''; // Do not output anything before it's not clear that a redirect might be needed.

// Get all the appropriate data.
$exams = get_all_instances_in_course('bizexaminer', $course);

$output .= $renderer->exams_list($course, $exams);

echo $OUTPUT->header();
echo $output;
// Finish the page.
echo $OUTPUT->footer();
