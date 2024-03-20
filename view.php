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
 * The view page for a single exam.
 *
 * @package     mod_bizexaminer
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_bizexaminer\bizexaminer;
use mod_bizexaminer\local\callback_api\callback_api;
use mod_bizexaminer\local\data_objects\attempt;
use mod_bizexaminer\local\data_objects\exam;
use mod_bizexaminer\local\gradebook\grading;
use mod_bizexaminer\util;

require(__DIR__.'/../../config.php');
require_once($CFG->dirroot.'/mod/bizexaminer/lib.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or ...
$examid = optional_param('examid',  0, PARAM_INT);  // Exam ID.

if ($id) {
    if (!$cm = get_coursemodule_from_id('bizexaminer', $id)) {
        throw new \moodle_exception('invalidcoursemodule');
    }
    if (!$course = get_course($cm->course)) {
        throw new \moodle_exception('coursemisconf');
    }
    if (!$exam = exam::get($cm->instance)) {
        throw new \moodle_exception('invalid_exam_id', 'mod_bizexaminer');
    }
} else {
    if (!$exam = exam::get($examid)) {
        throw new \moodle_exception('invalid_exam_id', 'mod_bizexaminer');
    }
    if (!$course = get_course($exam->course)) {
        throw new \moodle_exception('invalidcourseid');
    }
    if (!$cm = get_coursemodule_from_instance('bizexaminer', $exam->id, $course->id)) {
        throw new \moodle_exception('invalidcoursemodule');
    }
}

// Check login and get context.
require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/bizexaminer:view', $context);

// Cache some other capabilities we use several times.
$canattempt = has_capability('mod/bizexaminer:attempt', $context);

$event = \mod_bizexaminer\event\course_module_viewed::create([
    'context' => $context,
    'objectid' => $exam->id,
]);
$event->add_record_snapshot('bizexaminer', $exam->get_activity_module());
$event->trigger();

// Initialize $PAGE.
$PAGE->set_cacheable(false); // Prevent caching because of dynamic nature of this page.
$PAGE->set_url('/mod/bizexaminer/view.php', ['id' => $cm->id]);
$title = $course->shortname . ': ' . format_string($exam->name);
$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);
if (html_is_blank($exam->intro)) {
    $PAGE->activityheader->set_description('');
}
$PAGE->add_body_class('limitedwidth');

/** @var mod_bizexaminer\output\renderer $renderer */
$renderer = $PAGE->get_renderer('mod_bizexaminer');
$output = ''; // Do not output anything before it's not clear that a redirect might be needed.

$pendingattempts = attempt::get_all(['examid' => $exam->id, 'userid' => $USER->id, 'status' => attempt::STATUS_PENDING_RESULTS]);
$haspendingattempt = !empty($pendingattempts);;
$runningattempts = attempt::get_all(
    ['examid' => $exam->id, 'userid' => $USER->id, 'status' => attempt::STATUS_STARTED], 'timemodified DESC');
$hasrunningattempt = !empty($runningattempts);
$previousattempts = attempt::get_all(['examid' => $exam->id, 'userid' => $USER->id], 'attempt ASC, timemodified ASC');
$haspreviousattempts = !empty($previousattempts);

/** @var grading $gradingservice */
$gradingservice = bizexaminer::get_instance()->get_service('grading');

// If errors happened previously (eg on callback api) they are added to session notice and moodle shows them.

// Show success failed/pass notice.
if ($previousattempts) {
    // If grading is enabled, show failed/pass notice based on grading configuration.
    if (grading::has_grading($exam->grade)) {
        $gradebookgrade = $gradingservice->get_gradebook_grade($exam, $USER->id);
        if ($gradebookgrade && $gradebookgrade->grade !== null) {
            $pass = (float)$gradebookgrade->grade >= (float)$gradebookgrade->gradepass;
            $output .= $renderer->pass_fail($pass);
        }
    } else {
        // Else show failed/pass notifice based on bizExaminer attempt result.
        // Check on the highest result, if this is failed -> all attempts are failed.
        $highestattempt = $gradingservice->get_attempt_for_grading($exam, $USER->id);
        if ($highestattempt) {
            $highestattemptresults = $highestattempt->get_results();
            if ($highestattemptresults) {
                $output .= $renderer->pass_fail($highestattemptresults->pass);
            }
        }
    }
}

// Show notice about pending results.
if ($haspendingattempt) {
    $firstattemptkey = array_key_first($pendingattempts);
    $attempt = $pendingattempts[$firstattemptkey];
    // Show pending message.
    $output .= $renderer->pending_results($attempt);
}

// Show start/retake button.
// Still show retake button if user has pending.
// The limit of max attempts is important - which is checked in can_access.
if ($canattempt) {
    /** @var exams $examsservice */
    $examsservice = bizexaminer::get_instance()->get_service('exams', $exam->get_api_credentials());
    $canaccess = $examsservice->can_access($exam, $USER->id);

    if ($canaccess !== true) {
        $output .= $renderer->show_access_restriction($canaccess);
    } else {
        if ($hasrunningattempt) {
            $firstattemptkey = array_key_first($runningattempts);
            $attempt = $runningattempts[$firstattemptkey];

            if ($attempt->is_valid()) {
                $output .= $renderer->resume_button($attempt);
            } else {
                // If the booking in bizExaminer is not valid anymore, change the attempt to aborted.
                $attempt->status = attempt::STATUS_ABORTED;
                $attempt->timemodified = util::create_date(time());
                attempt::save($attempt);
                redirect(new moodle_url('/mod/bizexaminer/view.php', ['examid' => $exam->id]));
            }
        } else {
            /** @var callback_api $callbackapi */
            $callbackapi = bizexaminer::get_instance()->get_service('callbackapi');
            $starturl = $callbackapi->make_url(callback_api::ACTIONS['startexam'], ['examid' => $exam->id, 'sesskey' => sesskey()]);

            // Show start link.
            if (attempt::count(['examid' => $exam->id, 'userid' => $USER->id, 'status' => attempt::STATUS_COMPLETED])) {
                $output .= $renderer->retake_attempt($starturl);
            } else {
                // Show start link.
                $output .= $renderer->start_attempt($starturl);
            }
        }
    }
}

// Show previous attempts table, depending on capability.
if (has_capability('mod/bizexaminer:viewownattempt', $context)) {
    if (!empty($previousattempts)) {
        $output .= $renderer->my_attempts_table($previousattempts);
    }
}

if (has_capability('mod/bizexaminer:attempt', $context)) {
    // Show general grading info about this exam and current grade.
    if (grading::has_grading($exam->grade)) {
        $output .= $renderer->grade_info($exam, true);
    }

    // Show overall feedback for user.
    if ($haspreviousattempts && !empty($exam->feedbacks)) {
        $output .= $renderer->overall_feedback($exam);
    }
}



// Show certificate to user.
if ($exam->usebecertificate) {
    // Use the highest available grade/attempt by default to get information for the certificate.
    $attempt = $gradingservice->get_attempt_for_grading($exam, $USER->id, grading::GRADEHIGHEST);
    if ($attempt && $attempt->hasresults) {
        $attemptresults = $attempt->get_results();
        // Only show if passed in bizExaminer, because only then certificate is available (see #81).
        if ($attemptresults->pass && $attemptresults->certificateurl) {
            $output .= $renderer->certificate($attemptresults);
        }
    }
}

if (has_capability('mod/bizexaminer:viewanyattempt', $context)) {
    $output .= $renderer->teacher_view($exam);
}

echo $OUTPUT->header();
echo $output;
// Finish the page.
echo $OUTPUT->footer();
