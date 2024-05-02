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
 * Library of interface functions and constants.
 *
 * @package     mod_bizexaminer
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_bizexaminer\bizexaminer;
use mod_bizexaminer\local\data_objects\attempt;
use mod_bizexaminer\local\data_objects\exam_grade;
use mod_bizexaminer\local\data_objects\exam;
use mod_bizexaminer\local\gradebook\grading;
use mod_bizexaminer\local\mod_form\mod_form_helper;

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 *
 * @see course_modinfo::get_array_of_activities()
 *
 * @param object $coursemodule
 * @return cached_cm_info info
 */
function bizexaminer_get_coursemodule_info($coursemodule) {
    $exam = exam::get($coursemodule->instance);
    if (!$exam) {
        return null;
    }

    $info = new cached_cm_info();
    $info->name = $exam->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $info->content = format_module_intro('bizexaminer', $exam->get_activity_module(), $coursemodule->id, false);
    }

    // The icon will be filtered if it will be the default module icon.
    $info->customdata['filtericon'] = true;

    return $info;
}

/**
 * Whether the activity is branded.
 * This information is used, for instance, to decide if a filter should be applied to the icon or not.
 *
 * @return bool True if the activity is branded, false otherwise.
 */
function bizexaminer_is_branded(): bool {
    return true;
}

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function bizexaminer_supports($feature) {
    switch ($feature) {
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_ASSESSMENT;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_bizexaminer into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $data An object from the form.
 * @param mod_bizexaminer_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function bizexaminer_add_instance($data, $mform = null) {
    $exam = new exam();

    $formhandler = new mod_form_helper();
    $formhandler->process_options($exam, $data);
    $saved = exam::save($exam);

    if ($saved) {
        return $exam->id;
    } else {
        return new moodle_exception('error_saving_exam', 'mod_bizexaminer');
    }

}

/**
 * Updates an instance of the mod_bizexaminer in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $data An object from the form in mod_form.php.
 * @param mod_bizexaminer_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function bizexaminer_update_instance($data, $mform = null) {
    $exam = exam::get($data->instance);
    $originalexam = clone $exam;

    $formhandler = new mod_form_helper();
    $formhandler->process_options($exam, $data);

    $updated = exam::save($exam);

    if ($updated) {
        // If any grade related settings change, recalculate all grades for this exam.
        // Changing grade (=grade type or scale) is not possible in UI - but still.
        // Grademethod changing (=which attempt to count) can change.
        // Changing of gradepass should be handled by moodle core.
        // Grading as a whole can't be disabled if its already enabled once and there are grades already.
        if ($exam->grade !== $originalexam->grade || $exam->grademethod !== $originalexam->grademethod) {
            $gradingservice = bizexaminer::get_instance()->get_service('grading');
            if (grading::has_grading($exam->grade)) { // If new setting is no grading just ignore.
                $regraded = $gradingservice->save_grades($exam->id);
            }

        }
    }

    // Sync any changes to gradebook api - maybe because rescaling or feedback messages change or anything else.
    bizexaminer_update_grades($exam->get_activity_module()); // Update for all users = without userid.

    if (isset($regraded)) {
        return $updated && $regraded;
    }
    return $updated;
}

/**
 * Removes an instance of the mod_bizexaminer from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function bizexaminer_delete_instance($id) {
    $exam = exam::get($id);

    /** @var exams $examsservice */
    $examsservice = bizexaminer::get_instance()->get_service('exams', $exam->get_api_credentials());

    // Delete attempts, results, grades, gradebook api.
    $examsservice->delete_all_attempts($exam);
    // Delete grades from gradebook API.
    if ($exam) {
        bizexaminer_grade_item_delete($exam->get_activity_module());
    }

    // Finally delete exam - also deletes remote proctor options.
    $deleted = exam::delete($id);

    return $deleted;
}

// TODO: Additional/missing lib.php functions/callbacks (#16).

/**
 * Add cron related service status checks
 *
 * @return array of check objects
 */
function mod_bizexaminer_status_checks(): array {
    return [new \mod_bizexaminer\check\testapi()];
}

/**
 * This function extends the settings navigation block for the site.
 *
 * It is safe to rely on PAGE here as we will only ever be within the module
 * context when this is called
 *
 * @param settings_navigation $settings
 * @param navigation_node $examnode
 * @return void
 */
function bizexaminer_extend_settings_navigation(settings_navigation $settings, navigation_node $examnode) {
    global $CFG;

    if (has_capability('mod/bizexaminer:viewanyattempt', $settings->get_page()->cm->context)) {
        $url = new moodle_url('/mod/bizexaminer/attempts.php',
                ['examid' => $settings->get_page()->cm->instance]);
        $examnode->add_node(navigation_node::create(get_string('attempts', 'mod_bizexaminer'), $url,
                navigation_node::TYPE_SETTING,
                null, 'bizexaminer_attempts', new pix_icon('i/report', '')));
    }
}

/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the exam.
 *
 * @param mixed $mform the course reset form that is being built.
 * @return void
 */
function bizexaminer_reset_course_form_definition($mform) {
    $mform->addElement('header', 'bizexaminerheader', get_string('pluginname', 'mod_bizexaminer'));
    $mform->addElement('advcheckbox', 'reset_exam_attempts',
            get_string('resetform_remove_attempts', 'mod_bizexaminer'));
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * This function will remove all attempts for an exam in the database
 * and clean up any related data.
 *
 * @param stdClass $data the data submitted from the reset course.
 * @return array
 */
function bizexaminer_reset_userdata($data) {
    /** @var moodle_database $DB */ // phpcs:ignore moodle.Commenting.InlineComment.TypeHintingMatch
    global $DB;
    $componentstr = get_string('modulenameplural', 'mod_bizexaminer');
    $status = [];
    if (!empty($data->reset_exam_attempts)) {
        // Use raw DB call because objects are not needed here.
        $exams = $DB->get_records(exam::TABLE, ['course' => $data->courseid]);

        foreach ($exams as $exam) {
            // Delete all attempts and their results and grades from our custom grades table.
            $deletedattempts = attempt::delete_all(['examid' => $exam->id]); // Handles deleting results.
            $status[] = [
                'component' => $componentstr,
                'item' => get_string('reset_delete_attempts', 'mod_bizexaminer'),
                'error' => !$deletedattempts,
            ];

            $deletedgrades = exam_grade::delete_all(['examid' => $exam->id]);
            $status[] = [
                'component' => $componentstr,
                'item' => get_string('reset_delete_grades', 'mod_bizexaminer'),
                'error' => !$deletedgrades,
            ];

            // Delete grades from gradebook API.
            $resetgrades = bizexaminer_grade_item_delete($exam, 'reset');
            $status[] = [
                'component' => $componentstr,
                'item' => get_string('reset_grades', 'mod_bizexaminer'),
                'error' => $resetgrades === GRADE_UPDATE_FAILED,
            ];
        }
    }
    return $status;
}

/**
 * Course reset form defaults.
 * @param  object $course
 * @return array
 */
function bizexaminer_reset_course_form_defaults($course) {
    return ['reset_exam_attempts' => 1];
}

/**
 * Callback which updates the grades for the supplied user
 * by getting results from results table and then calling bizexaminer_grade_item_update
 *
 * @see https://docs.moodle.org/dev/Gradebook_API#.7B.24modname.7D_update_grades.28.24modinstance.2C_.24userid.3D0.2C_.24nullifnone.3Dtrue.29
 *
 * @uses bizexaminer_grade_item_update
 *
 * @param stdClass $actvitiymodule the activity module settings
 * @param int $userid A user ID or 0 for all users
 * @param bool $nullifnone Whether to create a null rawgrade for if a single user is specified and they don't have a grade yet
 * @return void
 */
function bizexaminer_update_grades($actvitiymodule, $userid = null, $nullifnone = true) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    /** @var grading $gradingservice */
    $gradingservice = bizexaminer::get_instance()->get_service('grading');

    // 1. If the exam does not have grading enabled (=set to "none")
    // pass only the activity module to bizexaminer_grade_item_update
    // so settings like the grading scale being used can be saved
    // but do not save a grade.
    // Has to be done because of ??? core plugins do it this way.
    if ($actvitiymodule->grade === GRADE_TYPE_NONE) {
        bizexaminer_grade_item_update($actvitiymodule);
        return;
    }

    // 2. If a userid is supplied try to get attempt results and map them to grades
    if ($userid) {
        $usergrade = $gradingservice->get_user_grade($actvitiymodule->id, $userid);
        if ($usergrade) {
            // 2a. Store users grade in gradebook
            bizexaminer_grade_item_update($actvitiymodule, $usergrade);
        } else if ($nullifnone) {
            // 2b. If a userid is passed and $nullifnone is passed and no results could be found
            // create a null rawgrade.
            $grade = new stdClass;
            $grade->userid = $userid;
            $grade->rawgrade = null;
            bizexaminer_grade_item_update($actvitiymodule, $grade);
        }
    } else {
        // 3. No userid specified = all users: Get results for all users and update them. Array must be indexed by userid!
        $grades = $gradingservice->get_users_grade($actvitiymodule->id);
        if ($grades) {
            // 3a. Store all users grades in gradebook
            bizexaminer_grade_item_update($actvitiymodule, $grades);
        } else {
            // 3b. Default to empty, don't know why but all core plugins do it this way
            bizexaminer_grade_item_update($actvitiymodule);
        }

    }
}

/**
 * Delete a grade item for a given exam
 * Does not recalculate, but removes the complete grade item for this whole activity module
 * Should not be used for deleting single users grades.
 *
 * @param stdClass $actvitiymodule
 * @return int
 */
function bizexaminer_grade_item_delete($actvitiymodule) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    return grade_update('mod/bizexaminer', $actvitiymodule->course, 'mod', 'bizexaminer', $actvitiymodule->id, 0,
            null, ['deleted' => 1]);
}

/**
 * Create or update the grade item for given exam
 *
 * This callback should create or updated the grade itm for a given activity module instance
 * by calling grade_update.
 * It can update both the activity grade item information and grades for users if they are supplied via $grades
 *
 * $grades also accepts 'reset' to assist in course reset functionality.
 *
 * @uses grade_update
 *
 * Called by bizexaminer_update_grades
 * or maybe after adding/updating an exam instance
 * or when resetting the gradebook {@see quiz_set_grade}
 * or maybe with _set_grade method? {@see quiz}
 *

 *
 * Code inspired by examples in documentation (forum activity module) and quiz module
 *
 * @param stdClass $actvitiymodule object with extra cmidnumber
 * @param mixed $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function bizexaminer_grade_item_update($actvitiymodule, $grades = null) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    // Set base parameters for grade_update.
    $params = [
        'itemname' => $actvitiymodule->name,
        'idnumber' => $actvitiymodule->cmidnumber ?? null, // May not be always present according to core quiz method.
    ];

    // Set gradetype and grade value.
    if ($actvitiymodule->grade === 0) {
        $params['gradetype'] = GRADE_TYPE_NONE;
    } else {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax'] = $actvitiymodule->grade;
        $params['grademin'] = 0;
    }

    // Handle course resetting.
    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    // TODO: how does scale work?

    // Parameter $itemnumber = 0, if module has more than one grade for a user, this should be changed.
    return grade_update('mod/bizexaminer', $actvitiymodule->course, 'mod', 'bizexaminer', $actvitiymodule->id, 0, $grades, $params);
}
