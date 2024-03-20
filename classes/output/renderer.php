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
 * A renderer for the view.php file
 *
 * @package     mod_bizexaminer
 * @category    output
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bizexaminer\output;


use core_user;
use html_table;
use html_writer;
use mod_bizexaminer\bizexaminer;
use mod_bizexaminer\local\callback_api\callback_api;
use mod_bizexaminer\local\data_objects\attempt;
use mod_bizexaminer\local\data_objects\attempt_results;
use mod_bizexaminer\local\data_objects\exam;
use mod_bizexaminer\local\gradebook\grading;
use moodle_url;
use plugin_renderer_base;
use single_button;

/**
 * A renderer implementation for bizexaminer plugin.
 *
 * Mostly only handles rendering, only in certain cases does data fetching and permission checks.
 * Check source of method before using it.
 *
 * @package mod_bizexaminer
 */
class renderer extends plugin_renderer_base {

    /**
     * Displays a list of all exams in a course.
     *
     * @param stdClass $course
     * @param stdClass[] $exams from get_all_instances_in_courses
     * @return string
     */
    public function exams_list($course, array $exams): string {
        if (empty($exams)) {
            notice(
                get_string('thereareno', 'moodle', get_string('modulenameplural', 'mod_bizexaminer')),
                new moodle_url('/course/view.php', ['id' => $course->id])
            );
        }

        $coursecontext = \core\context\course::instance($course->id);
        $canviewexam = has_capability('mod/bizexaminer:view', $coursecontext);
        $canviewanyattempts = has_capability('mod/bizexaminer:viewanyattempt', $coursecontext);

        $table = new html_table();
        $table->attributes['class'] = 'generaltable mod_index';
        $table->head = [];
        $table->align = [];
        $table->size = [];
        $table->data = [];

        // 1. Grouping by section or week.
        if (course_format_uses_sections($course->format)) {
            $table->head[] = get_string('sectionname', 'format_'.$course->format);
            $table->align[] = 'center';
            $table->size[] = '';
        }

        // 2. Name
        $table->head[] = get_string('name');
        $table->align[] = 'left';
        $table->size[] = '';

        // 3. Attempts
        if ($canviewanyattempts) {
            $table->head[] = get_string('attempts', 'mod_bizexaminer');
            $table->align = 'center';
            $table->size[] = '';
        }

        // Group exams by section and only show heading for each section.
        $currentsection = '';

        foreach ($exams as $exam) {
            $row = [];
            // 1. Section
            if ($exam->section !== $currentsection) {
                if ($exam->section) {
                    $row[] = get_section_name($course, $exam->section);
                } else {
                    $row[] = '';
                }
                $currentsection = $exam->section;
            } else {
                $row[] = '';
            }

            // 2. Name
            $examurl = new moodle_url('/mod/bizexaminer/view.php', ['id' => $exam->coursemodule]);
            if ($canviewexam) {
                $row[] = html_writer::link(
                    $examurl,
                    format_string($exam->name, true),
                    ['class' => !$exam->visible ? 'dimmed' : '' ]
                );
            } else {
                $row[] = format_string($exam->name, true);
            }

            // 3. Attempts
            if ($canviewanyattempts) {
                $row[] = get_string('attempts_no', 'mod_bizexaminer', attempt::count(['examid' => $exam->id]));
            }

            $table->data[] = $row;
        }

        $output = '';
        $output .= $this->output->heading(get_string('modulenameplural', 'mod_bizexaminer'));
        $output .= html_writer::table($table);
        return $output;
    }

    /**
     * Show start attempt button
     *
     * @param string|moodle_url $starturl
     * @param ?string $buttonlabel defaults to 'exam_startattempt'
     * @return string
     */
    public function start_attempt($starturl, $buttonlabel = null): string {
        if (!$buttonlabel) {
            $buttonlabel = get_string('exam_startattempt', 'mod_bizexaminer');
        }
        $button = new single_button(
            new moodle_url($starturl),
            $buttonlabel,
            'get',
            single_button::BUTTON_PRIMARY
        );
        return $this->render($button);
    }

    /**
     * Show retake attempt button
     * @param mixed $starturl
     * @return string
     */
    public function retake_attempt($starturl): string {
        return $this->start_attempt($starturl, get_string('exam_retakeattempt', 'mod_bizexaminer'));
    }

    /**
     * Show resumse attempt button
     * @param attempt $attempt
     * @return string
     */
    public function resume_button(attempt $attempt): string {
        $examurl = $attempt->get_exam_url();
        if (!$examurl) {
            return '';
        }
        $button = new single_button(
            $examurl,
            get_string('exam_resumeattempt', 'mod_bizexaminer'),
            'get',
            single_button::BUTTON_PRIMARY
        );
        return $this->render($button);
    }

    /**
     * Show any errors happened during last request/callback.
     * @param array $errors
     * @return string
     */
    public function show_errors(array $errors = []): string {
        // At the moment only show a general error to the user and no specific ones.
        return $this->output->notification(
            get_string('error_general', 'mod_bizexaminer'),
            'error',
            false
        );
    }

    /**
     * Show message about access restrictions for the exam.
     * @param string $message
     * @return string
     */
    public function show_access_restriction(string $message): string {
        return $this->output->notification(
            $message,
            'warning',
            false
        );
    }

    /**
     * Displays the results of an exam attempt
     *
     * @param exam $exam
     * @param attempt $attempt
     * @param attempt_results|null $results
     * @return string
     */
    public function attempt_details(exam $exam, attempt $attempt, ?attempt_results $results): string {
        global $USER;
        $output = '';

        $output .= html_writer::start_tag('table', [
            'class' => 'generaltable generalbox examattemptresults',
        ]);
        $output .= html_writer::start_tag('tbody');

        $resultdatas = [
            [
                'title' => get_string('results_whenstarted', 'mod_bizexaminer'),
                'content' => userdate($attempt->timecreated->getTimestamp()),
            ],
            [
                'title' => get_string('results_state', 'mod_bizexaminer'),
                'content' => attempt::attempt_status_label($attempt->status),
            ],
        ];

        if ((int)$USER->id !== $attempt->userid) {
            $resultdatas[] = [
                'title' => get_string('results_user', 'mod_bizexaminer'),
                'content' => $this->get_attempt_user($attempt),
            ];
        }

        if ($attempt->hasresults && $results->whenfinished) {
            $resultdatas = array_merge($resultdatas, [
                [
                    'title' => get_string('results_whenfinished', 'mod_bizexaminer'),
                    'content' => userdate($results->whenfinished->getTimestamp()),
                ],
                [
                    'title' => get_string('results_timetaken', 'mod_bizexaminer'),
                    'content' => format_time($results->timetaken),
                ],
                [
                    'title' => get_string('results_score', 'mod_bizexaminer'),
                    'content' => format_float($results->result) . '%',
                ],
            ]);

            // If no moodle grading, show pass/fail from bizExaminer. See #81.
            if (!grading::has_grading($exam->grade)) {
                $resultdatas[] = [
                    'title' => get_string('results_pass', 'mod_bizexaminer'),
                    'content' => $results->pass ? get_string('attempt_pass', 'mod_bizexaminer') :
                        get_string('attempt_failed', 'mod_bizexaminer'),
                ];
            }

            $resultdatas = array_merge($resultdatas, [
                [
                    'title' => get_string('results_questionscount', 'mod_bizexaminer'),
                    'content' => s($results->questionscount),
                ],
                [
                    'title' => get_string('results_questionscorrectcount', 'mod_bizexaminer'),
                    'content' => s($results->questionscorrectcount),
                ],
            ]);
        }

        foreach ($resultdatas as $resultdata) {
            $output .= html_writer::tag('tr',
            html_writer::tag('th', $resultdata['title'], ['class' => 'cell', 'scope' => 'row']) .
                html_writer::tag('td', $resultdata['content'], ['class' => 'cell'])
            );
        }

        $output .= html_writer::end_tag('tbody');
        $output .= html_writer::end_tag('table');
        return $output;
    }

    /**
     * Display message about pending results.
     *
     * @param attempt $attempt
     * @return string
     */
    public function pending_results(attempt $attempt): string {
        global $USER;
        if ($attempt->userid === (int) $USER->id) {
            return $this->output->notification(
                get_string('exam_pendingresults_you', 'mod_bizexaminer'),
                'info',
                false
            );
        } else {
            return $this->output->notification(
                get_string('exam_pendingresults_user', 'mod_bizexaminer'),
                'info',
                false
            );
        }
    }

    /**
     * Display a message about whether the user has passed or failed at the exam.
     * The bool $pass can come from the gradebook or from attempt_result/bizExaminer.
     *
     * @param bool $pass
     * @return string
     */
    public function pass_fail(bool $pass): string {
        if ($pass) {
            return $this->output->notification(
                get_string('results_notification_passed', 'mod_bizexaminer'),
                'success',
                false
            );
        } else {
            return $this->output->notification(
                get_string('results_notification_not_passed', 'mod_bizexaminer'),
                'info',
                false
            );
        }
    }

    /**
     * Display the previous attempts for the curent user.
     *
     * @param attempt[] $attempts
     * @return string
     */
    public function my_attempts_table(array $attempts): string {
        $table = new html_table();
        $table->attributes['class'] = 'generaltable examattemptssummary';
        $table->head = [];
        $table->align = [];
        $table->size = [];
        $table->data = [];

        $table->head[] = get_string('attempts_table_no', 'mod_bizexaminer');
        $table->align[] = 'center';
        $table->size[] = '';

        $table->head[] = get_string('results_state', 'mod_bizexaminer');
        $table->align[] = 'left';
        $table->size[] = '';

        $table->head[] = get_string('results_score', 'mod_bizexaminer');
        $table->align[] = 'left';
        $table->size[] = '';

        $table->head[] = get_string('attempts_table_actions', 'mod_bizexaminer');
        $table->align[] = 'left';
        $table->size[] = '';

        foreach ($attempts as $attempt) {
            $row = [];

            $row[] = $attempt->attempt ?? '';
            $row[] = $this->get_attempt_status($attempt);
            $row[] = $this->get_attempt_score($attempt);
            $row[] = $this->get_attempt_actions($attempt);

            $table->data[] = $row;
        }

        $output = '';
        $output .= $this->output->heading(get_string('attempts_table_heading_yours', 'mod_bizexaminer'), 3);
        $output .= html_writer::table($table);
        return $output;
    }

    /**
     * Display all attempts from all users, for teachers.
     *
     * @param attempt[] $attempts
     * @return string
     */
    public function all_attempts_table(array $attempts): string {
        $table = new html_table();
        $table->attributes['class'] = 'generaltable examattemptssummary';
        $table->head = [];
        $table->align = [];
        $table->size = [];
        $table->data = [];

        $table->head[] = get_string('attempts_table_user', 'mod_bizexaminer');
        $table->align[] = 'left';
        $table->size[] = '';

        $table->head[] = get_string('attempts_table_no', 'mod_bizexaminer');
        $table->align[] = 'center';
        $table->size[] = '';

        $table->head[] = get_string('results_state', 'mod_bizexaminer');
        $table->align[] = 'left';
        $table->size[] = '';

        $table->head[] = get_string('results_score', 'mod_bizexaminer');
        $table->align[] = 'left';
        $table->size[] = '';

        $table->head[] = get_string('attempts_table_actions', 'mod_bizexaminer');
        $table->align[] = 'left';
        $table->size[] = '';

        foreach ($attempts as $attempt) {
            $row = [];

            $row[] = $this->get_attempt_user($attempt);
            $row[] = $attempt->attempt ?? '';
            $row[] = $this->get_attempt_status($attempt);
            $row[] = $this->get_attempt_score($attempt);
            $row[] = $this->get_attempt_actions($attempt);

            $table->data[] = $row;
        }

        $output = '';
        $output .= $this->output->heading(get_string('attempts_table_heading_all', 'mod_bizexaminer'), 3);
        $output .= html_writer::table($table);
        return $output;
    }

    /**
     * Display grading info for this exam
     * Including grade method, grade pass info, link to gradebook
     *
     * @param exam $exam
     * @param bool $showusergrade Whether to show the current users current grade
     */
    public function grade_info(exam $exam, $showusergrade = true): string {
        if (!grading::has_grading($exam->grade)) {
            return '';
        }
        global $USER;
        $output = '';
        $output .= $this->output->heading(get_string('grade_infos', 'mod_bizexaminer'), 3);
        /** @var grading $gradingservice */
        $gradingservice = bizexaminer::get_instance()->get_service('grading');
        $grade = $gradingservice->get_gradebook_grade($exam, $USER->id);

        $output .= html_writer::start_tag('table', [
            'class' => 'generaltable generalbox examattemptresults',
        ]);
        $output .= html_writer::start_tag('tbody');

        $rows = [];

        if ($showusergrade && $grade) {
            $rows[] = [
                'title' => get_string('grade_current', 'mod_bizexaminer'),
                'content' => s($grade->str_long_grade),
            ];
        }

        if ($grade && !empty($grade->gradepass) && grade_floats_different($grade->gradepass, 0)) {
            $a = new \stdClass();
            $a->gradepass = grading::format_grade($grade->gradepass);
            $a->maxgrade = grading::format_grade($grade->grademax);
            $rows[] = [
                'title' => get_string('gradepass', 'grades'),
                'content' => get_string('grade_pass_out_of', 'mod_bizexaminer', $a),
            ];
        }

        $rows[] = [
            'title' => '',
            'content' => get_string('results_grade_link', 'mod_bizexaminer',
                (new moodle_url('/grade/report/user/index.php', ['id' => $exam->course]))->out()),
        ];

        foreach ($rows as $row) {
            $output .= html_writer::tag('tr',
            html_writer::tag('th', $row['title'], ['class' => 'cell', 'scope' => 'row']) .
                html_writer::tag('td', $row['content'], ['class' => 'cell'])
            );
        }

        $output .= html_writer::end_tag('tbody');
        $output .= html_writer::end_tag('table');

        return $output;
    }

    /**
     * Display the feedback text message regarding the users overall grade.
     *
     * @param exam $exam
     * @return string
     */
    public function overall_feedback(exam $exam): string {
        global $USER;
        $output = '';
        $output .= $this->output->heading(get_string('overallfeedback', 'mod_bizexaminer'), 3);

        /** @var grading $gradingservice */
        $gradingservice = bizexaminer::get_instance()->get_service('grading');
        $grade = $gradingservice->get_gradebook_grade($exam, $USER->id);
        if (grading::has_grading($exam->grade) && $grade && !empty($grade->grade)) {
            $feedback = $gradingservice->get_feedback_for_grade($grade->grade, $exam->id);
        } else {
            $feedback = $gradingservice->get_feedback_for_grade(0, $exam->id);
        }
        if ($feedback) {
            $output .= html_writer::div(format_text($feedback->feedbacktext, $feedback->feedbacktextformat), 'bizexaminerfeedback');
        }

        return $output;
    }

    /**
     * Displays a link to the certificate of the attempt from bizExaminer.
     *
     * @param attempt_results $results
     * @return string
     */
    public function certificate(attempt_results $results): string {
        if (!$results->certificateurl) {
            return '';
        }
        $button = new single_button(
            new moodle_url($results->certificateurl),
            get_string('exam_view_certificate', 'mod_bizexaminer'),
            'get',
            single_button::BUTTON_PRIMARY
        );
        return $this->render($button);
    }

    /**
     * Displays an overview for teachers.
     *
     * @param exam $exam
     * @return string
     */
    public function teacher_view(exam $exam): string {
        $button = new single_button(
            new moodle_url('/mod/bizexaminer/attempts.php',
                ['examid' => $exam->id]),
            get_string('attempts_view_all', 'mod_bizexaminer'),
            'get',
            single_button::BUTTON_PRIMARY
        );
        return $this->render($button);
    }

    /**
     * Displays the status of an attempt
     *
     * @param mixed $attempt
     * @return string
     */
    protected function get_attempt_status($attempt): string {
        $statuslabel = attempt::attempt_status_label($attempt->status);
        if ($attempt->status === attempt::STATUS_STARTED) {
            $statuslabel .= html_writer::tag('span', get_string('attempt_status_date_started', 'mod_bizexaminer',
                userdate($attempt->timecreated->getTimestamp())), ['class' => 'statedetails']);
        } else if ($attempt->status === attempt::STATUS_COMPLETED || $attempt->status === attempt::STATUS_PENDING_RESULTS) {
            $statuslabel .= html_writer::tag('span', get_string('attempt_status_date_completed', 'mod_bizexaminer',
                userdate($attempt->timemodified->getTimestamp())), ['class' => 'statedetails']);
        }
        return $statuslabel;
    }

    /**
     * Get the score of an attempt based on bizExminer result (percentage).
     *
     * @param mixed $attempt
     * @return string
     */
    protected function get_attempt_score($attempt): string {
        if ($attempt->status === attempt::STATUS_COMPLETED && $attempt->hasresults) {
            $results = $attempt->get_results();
            return format_float($results->result) . '%';
        } else {
            return get_string('attempt_noresults', 'mod_bizexaminer');
        }
    }

    /**
     * Get actions for an attempt
     * depending on user rule (teacher or user).
     *
     * @param attempt $attempt
     * @return string
     */
    protected function get_attempt_actions(attempt $attempt): string {
        global $USER;
        /** @var callback_api $callbackapi */
        $callbackapi = bizexaminer::get_instance()->get_service('callbackapi');
        /** @var exams $examsservice */
        $examsservice = bizexaminer::get_instance()->get_service('exams', $attempt->get_exam()->get_api_credentials());
        $exam = $attempt->get_exam();

        $actions = [];
        if ($attempt->status === attempt::STATUS_STARTED &&
            has_capability('mod/bizexaminer:attempt', $this->page->context) && $attempt->userid === (int)$USER->id &&
            $examsservice->can_access($exam, $USER->id) === true) {
            $examurl = $attempt->get_exam_url();
            if ($examurl) {
                $actions[] = html_writer::link($examurl, get_string('exam_resumeattempt', 'mod_bizexaminer'));
            }
        }

        if (
            has_capability('mod/bizexaminer:viewanyattempt', $this->page->context)
                || ($attempt->userid === (int) $USER->id && has_capability('mod/bizexaminer:viewownattempt', $this->page->context))
        ) {
            $actions[] = html_writer::link(
                new moodle_url('/mod/bizexaminer/attempt.php', ['attemptid' => $attempt->id]),
                get_string('attempt_viewattempt', 'mod_bizexaminer')
            );
        }

        if (has_capability('mod/bizexaminer:deleteanyattempt', $this->page->context)) {
            $deleteurl = $callbackapi->make_url(
                callback_api::ACTIONS['deleteattempt'],
                ['attemptid' => $attempt->id, 'sesskey' => sesskey()]
            );
            $buttonid = 'deleteattempt-' . $attempt->id;
            $actions[] = html_writer::link($deleteurl, get_string('deletattempt', 'mod_bizexaminer'),
                ['class' => 'text-danger', 'id' => $buttonid]);
            $this->page->requires->event_handler('#' . $buttonid, 'click', 'M.util.show_confirm_dialog',
                ['message' => get_string('deleteattemptcheck', 'mod_bizexaminer')]);
        }

         return implode("<br />", $actions);;
    }

    /**
     * Gets a link to the user profile of an attempt.
     *
     * @param mixed $attempt
     * @return string
     */
    protected function get_attempt_user($attempt): string {
        $name = fullname(core_user::get_user($attempt->userid), has_capability('moodle/site:viewfullnames', $this->page->context));

        if ($this->page->course->id == SITEID) {
            $profileurl = new moodle_url('/user/profile.php', ['id' => $attempt->userid]);
        } else {
            $profileurl = new moodle_url('/user/view.php',
                    ['id' => $attempt->userid, 'course' => $this->page->course->id]);
        }
        return html_writer::link($profileurl, $name);
    }

}
