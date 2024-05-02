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
 * The view page for a single attempt.
 *
 * @package     mod_bizexaminer
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_bizexaminer\local\data_objects\attempt;
use mod_bizexaminer\local\data_objects\exam;
use mod_bizexaminer\local\gradebook\grading;
use mod_bizexaminer\util;

require(__DIR__.'/../../config.php');
require_once($CFG->dirroot.'/mod/bizexaminer/lib.php');

$examid = required_param('examid', PARAM_INT);

$exam = exam::get($examid);
if (!$exam) {
    throw new \moodle_exception('invalid_exam_id', 'mod_bizexaminer');
}

$course = get_course($exam->course);
$coursemodule = util::get_coursemodule($exam->id, $exam->course);
$context = context_module::instance($coursemodule->id);

// Check login and get context.
require_login($course, false, $coursemodule);
// Require any capability of those two.
if (!has_any_capability(['mod/bizexaminer:viewanyattempt', 'mod/bizexaminer:viewownattempt'], $context)) {
    throw new required_capability_exception($context, 'mod/bizexaminer:viewownattempt', 'nopermissions', '');
}

// Initialize $PAGE.
$PAGE->set_cacheable(false); // Prevent caching because of dynamic nature of this page.
$PAGE->set_url('/mod/bizexaminer/attempts.php', ['examid' => $exam->id]);
$title = $course->shortname . ': ' . format_string($exam->name);
$PAGE->set_title($title);
$PAGE->set_heading(get_string('attempts_heading', 'mod_bizexaminer', format_string($exam->name)));
/** @var mod_bizexaminer\output\renderer $renderer */
$renderer = $PAGE->get_renderer('mod_bizexaminer');
$output = ''; // Do not output anything before it's not clear that a redirect might be needed.

// If errors happened previously (eg on callback api) they are added to session notice and moodle shows them.

// Depending on capability show all attempts for all users or only own attempts
// Similar to view.php.

if (has_capability('mod/bizexaminer:viewanyattempt', $context)) {
    $attempts = attempt::get_all(['examid' => $exam->id], 'timemodified ASC');
    $output .= $renderer->all_attempts_table($attempts);
} else if (has_capability('mod/bizexaminer:viewownattempt', $context)) {
    $attempts = attempt::get_all(['examid' => $exam->id, 'userid' => $USER->id], 'attempt ASC, timemodified ASC');
    $output .= $renderer->my_attempts_table($attempts);
}

echo $OUTPUT->header();
echo $output;
// Finish the page.
echo $OUTPUT->footer();
