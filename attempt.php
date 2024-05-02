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

use mod_bizexaminer\bizexaminer;
use mod_bizexaminer\local\data_objects\attempt;
use mod_bizexaminer\local\gradebook\grading;
use mod_bizexaminer\util;

require(__DIR__.'/../../config.php');
require_once($CFG->dirroot.'/mod/bizexaminer/lib.php');

$attemptid = required_param('attemptid', PARAM_INT);

$attempt = attempt::get($attemptid);
if (!$attempt) {
    throw new \moodle_exception('invalid_attempt_id', 'mod_bizexaminer');
}

$exam = $attempt->get_exam();
if (!$exam) {
    throw new \moodle_exception('invalid_exam_id', 'mod_bizexaminer');
}
$course = get_course($exam->course);
$coursemodule = util::get_coursemodule($exam->id, $exam->course);
$context = context_module::instance($coursemodule->id);

// Check login and get context.
require_login($course, false, $coursemodule);

if ((int)$USER->id === $attempt->userid) {
    require_capability('mod/bizexaminer:viewownattempt', $context);
} else {
    require_capability('mod/bizexaminer:viewanyattempt', $context);
}

// Cache some other capabilities we use several times.
$canattempt = has_capability('mod/bizexaminer:attempt', $context);

// Initialize $PAGE.
$PAGE->set_cacheable(false); // Prevent caching because of dynamic nature of this page.
$PAGE->set_url('/mod/bizexaminer/attempt.php', ['attemptid' => $attempt->id]);
$title = $course->shortname . ': ' . format_string($exam->name);
$PAGE->set_title($title);
$PAGE->set_heading(get_string('attempt_heading', 'mod_bizexaminer', format_string($exam->name)));
/** @var mod_bizexaminer\output\renderer $renderer */
$renderer = $PAGE->get_renderer('mod_bizexaminer');
$output = ''; // Do not output anything before it's not clear that a redirect might be needed.

// If errors happened previously (eg on callback api) they are added to session notice and moodle shows them.

if (!$attempt->hasresults) {
    // Try to fetch results now.
    /** @var exams $examsservice */
    $examsservice = bizexaminer::get_instance()->get_service('exams', $exam->get_api_credentials());
    $resultsfetched = $examsservice->fetch_results($attempt);
    if ($resultsfetched) {
        $attempt = attempt::get($attempt->id); // Refresh instance.
    }
}

$results = null;
if ($attempt->hasresults) {
    $results = $attempt->get_results();
}
$output .= $renderer->attempt_details($exam, $attempt, $results);

/** @var exams $examsservice */
$examsservice = bizexaminer::get_instance()->get_service('exams', $exam->get_api_credentials());
$canaccess = $examsservice->can_access($exam, $USER->id);

// Only show resuming if user is the same as the student (eg no teachers) of the attempt and has the capability.
if ($attempt->status === attempt::STATUS_STARTED &&
    $attempt->userid === (int)$USER->id && has_capability('mod/bizexaminer:attempt', $context)) {
    if ($canaccess !== true) {
        $output .= $renderer->show_access_restriction($canaccess);
    } else {
        if ($attempt->is_valid()) {
            $output .= $renderer->resume_button($attempt);
        } else {
            // If the booking in bizExaminer is not valid anymore, change the attempt to aborted.
            $attempt->status = attempt::STATUS_ABORTED;
            $attempt->timemodified = util::create_date(time());
            attempt::save($attempt);
        }

    }
}
if ($attempt->status === attempt::STATUS_PENDING_RESULTS ||
    (!$attempt->hasresults && $attempt->status !== attempt::STATUS_ABORTED)) {
    $output .= $renderer->pending_results($attempt);
}

if ($attempt->status === attempt::STATUS_COMPLETED) {
    if (!$attempt->hasresults || !$results) {
        $output .= $renderer->pending_results($attempt);
    } else {
        // Show attempt certificate.
        // Only show if passed in bizExaminer, because only then certificate is available (see #81).
        if ($exam->usebecertificate && $results->pass && $results->certificateurl) {
            $output .= $renderer->certificate($results);
        }

    }
}

echo $OUTPUT->header();
echo $output;
// Finish the page.
echo $OUTPUT->footer();
