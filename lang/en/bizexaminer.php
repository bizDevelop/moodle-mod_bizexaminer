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
 * Plugin strings are defined here.
 *
 * @package     mod_bizexaminer
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Required strings.
$string['apikeyinstance'] = 'Instance domain';
$string['apikeyinstance_desc'] = 'The domain name of your bizExaminer instance (without https:// or path).';
$string['apikeyorganisation'] = 'API key organisation';
$string['apikeyorganisation_desc'] = 'The API key for the organisation.';
$string['apikeyowner'] = 'API key owner';
$string['apikeyowner_desc'] = 'The API key for the (content) owner.';
$string['attempt_failed'] = 'Not passed';
$string['attempt_heading'] = 'Attempt for {$a}';
$string['attempt_noresults'] = '-';
$string['attempt_pass'] = 'Passed';
$string['attempt_status_aborted'] = 'Aborted';
$string['attempt_status_completed'] = 'Completed';
$string['attempt_status_date_completed'] = 'Completed {$a}';
$string['attempt_status_date_started'] = 'Started {$a}';
$string['attempt_status_pendingresults'] = 'Pending results';
$string['attempt_status_started'] = 'Started';
$string['attempt_viewattempt'] = 'View details';
$string['attempts'] = 'Attempts';
$string['attempts_heading'] = 'Attempts for {$a}';
$string['attempts_no'] = '{$a} attempts';
$string['attempts_table_actions'] = 'Actions';
$string['attempts_table_heading_all'] = 'Summary of all attempts';
$string['attempts_table_heading_yours'] = 'Summary of your previous attempts';
$string['attempts_table_no'] = 'Attempt #';
$string['attempts_table_user'] = 'User';
$string['attempts_view_all'] = 'View all attempts';
$string['bizexaminer:addinstance'] = 'Add a new exam';
$string['bizexaminer:attempt'] = 'Attempt an exam';
$string['bizexaminer:deleteanyattempt'] = 'Delete (any) attempt from a student';
$string['bizexaminer:view'] = 'View an exam';
$string['bizexaminer:viewanyattempt'] = 'View any attempts (by any user) at an exam';
$string['bizexaminer:viewownattempt'] = 'View own attempt at an exam';
$string['callbackapi_action'] = 'Callback API was called with an invalid action.';
$string['callbackapi_differentuser'] = 'You are not loggedin as the user who took the exam.';
$string['callbackapi_invalidattempt'] = 'An invalid attempt id was passed to the callback API.';
$string['callbackapi_invalidexam'] = 'An invalid exam id was passed to the callback API.';
$string['callbackapi_invalidkey'] = 'Invalid key for attempt.';
$string['checktestapi'] = 'Test bizExaminer API credentials';
$string['configureapi'] = 'Configure API credentials';
$string['deletattempt'] = 'Delete';
$string['deleteattemptcheck'] = 'Are you absolutely sure you want to completely delete this attempt?';
$string['deletedattempt'] = 'Attempt was deleted successfully.';
$string['error_api_error'] = 'bizExaminer could not handle the request.';
$string['error_api_invalid_data'] = 'The data sent to bizExaminer was invalid and the exam could not be started.';
$string['error_api_keys'] = 'The API keys for bizExaminer are invalid.';
$string['error_api_return_value'] = 'bizExaminer returned an invalid value.';
$string['error_api_url'] = 'The bizExaminer could not be found at the specified URL.';
$string['error_exam_not_found'] = 'Something went wrong. This exam does not exist (anymore).';
$string['error_general'] = 'Something went wrong. Please try again or contact admin/support.';
$string['error_saving_exam'] = 'Error saving exam.';
$string['exam_access_nomoreattempts'] = 'No more attempts are allowed.';
$string['exam_access_subnetwrong'] = 'This exam is only accessible from certain locations, and this computer is not on the allowed list.';
$string['exam_access_timeclose'] = 'This exam is currently not available.';
$string['exam_access_timeclosed'] = 'This exam is already closed.';
$string['exam_access_timeopen'] = 'The exam will not be available until {$a}';
$string['exam_access_wait'] = 'You must wait before you may re-attempt this exam. You will be allowed to start another attempt after {$a}.';
$string['exam_error_booking'] = 'Could not create a booking with the API.';
$string['exam_error_participant'] = 'Could not create a participant with the API.';
$string['exam_error_save_attempt'] = 'Could not store the attempt.';
$string['exam_error_save_results'] = 'Could not store the results.';
$string['exam_module_invalid'] = 'Please select a valid exam module.';
$string['exam_pendingresults_user'] = 'The user has not finished the exam yet or the results are still being manually reviewed.
You will find the results here, once finished.';
$string['exam_pendingresults_you'] = 'You have not finished the exam yet or your results are still being manually reviewed.
You will find the results here, once finished.';
$string['exam_resumeattempt'] = 'Resume exam';
$string['exam_retakeattempt'] = 'Retake exam';
$string['exam_startattempt'] = 'Start exam';
$string['exam_view_certificate'] = 'View certificate';
$string['grade_current'] = 'Your grade';
$string['grade_infos'] = 'Grading';
$string['grade_pass_out_of'] = '{$a->gradepass} out of {$a->maxgrade}';
$string['gradeattemptfirst'] = 'First attempt';
$string['gradeattemptlast'] = 'Last attempt';
$string['gradeaverage'] = 'Average grade';
$string['gradehighest'] = 'Highest grade';
$string['invalid_attempt_id'] = 'Invalid attempt id passed.';
$string['invalid_exam_id'] = 'Invalid exam id passed.';
$string['modform_access_restrictions'] = 'Extra restrictions';
$string['modform_access_restrictions_delay1st2nd'] = 'Enforced delay between 1st and 2nd attempts';
$string['modform_access_restrictions_delay1st2nd_help'] = 'If enabled, a student must wait for the specified time to elapse before being able to attempt the exam a second time.';
$string['modform_access_restrictions_delaylater'] = 'Enforced delay between later attempts';
$string['modform_access_restrictions_delaylater_help'] = 'If enabled, a student must wait for the specified time to elapse before attempting the exam a third time and any subsequent times.';
$string['modform_access_restrictions_overduehandling'] = 'When time expires';
$string['modform_access_restrictions_overduehandling_autoabandon'] = 'Attempts must be finished before time expires, or they are not counted';
$string['modform_access_restrictions_overduehandling_graceperiod'] = 'There is a grace period when open attempts can be finished.';
$string['modform_access_restrictions_overduehandling_graceperiod_field'] = 'Submission grace period';
$string['modform_access_restrictions_overduehandling_graceperiod_field_help'] = 'If what to do when the time expires is set to \'There is a grace period...\', then this is the default amount of extra time that is allowed.';
$string['modform_access_restrictions_overduehandling_help'] = 'What should happen by default if a student does not submit their attempt before time expires.';
$string['modform_access_restrictions_password'] = 'Require password';
$string['modform_access_restrictions_password_error_length'] = 'Password must be 4-12 characters long.';
$string['modform_access_restrictions_password_help'] = 'If a password is specified, a student must enter it in order to attempt the exam. Must be 4-12 characters long.';
$string['modform_access_restrictions_requiresubnet'] = 'Require network address';
$string['modform_access_restrictions_requiresubnet_help'] = 'Exam access may be restricted to particular subnets on the LAN or Internet by specifying a comma-separated list of partial or full IP address numbers. This can be useful for an invigilated (proctored) exam, to ensure that only people in a certain location can access the exam.';
$string['modform_access_restrictions_timeclose'] = 'Close the exam';
$string['modform_access_restrictions_timeclose_error_beforopen'] = 'You have specified a close date before the open date.';
$string['modform_access_restrictions_timeopen'] = 'Open the exam';
$string['modform_access_restrictions_timeopen_help'] = 'Students can only start their attempt(s) after the open time and they must complete their attempts before the close time.';
$string['modform_add_feedbacks'] = 'Add {no} more feedback fields';
$string['modform_api_credentials'] = 'API credentials';
$string['modform_api_credentials_help'] = 'Select API credentials to connect to bizExaminer and then save. You can configure them in the Plugin Settings.';
$string['modform_api_credentials_invalid'] = 'The credentials configured for this exam are not valid or do not exist.';
$string['modform_api_credentials_save'] = 'Save API credentials';
$string['modform_api_credentials_save_help'] = 'After choosing the API credentials save the form to reload and show available exam modules and remote proctor options.';
$string['modform_attemptsallowed'] = 'Attempts allowed';
$string['modform_exam_module'] = 'Exam module';
$string['modform_exam_module_error'] = 'Error loading the exam module. make sure your API credentials are correct - you can test them at the options screen.';
$string['modform_exam_module_help'] = 'Select an exam module and a content revision.';
$string['modform_exam_module_none'] = 'No exam modules found. Please make sure you have created exams in bizExaminer.
Also make sure your API credentials are correct - you can test them at the options screen.';
$string['modform_examity_courseId'] = 'ID of the course';
$string['modform_examity_courseName'] = 'Name of the course';
$string['modform_examity_examInstructions'] = 'Instructions for the student';
$string['modform_examity_examLevel'] = 'Session type';
$string['modform_examity_examLevel_auto_auth'] = 'Auto-authentication';
$string['modform_examity_examLevel_auto_proctoring_premium'] = 'Automated proctoring premium';
$string['modform_examity_examLevel_auto_proctoring_standard'] = 'Automated proctoring standard';
$string['modform_examity_examLevel_live_auth'] = 'Live authentication';
$string['modform_examity_examLevel_live_proctoring'] = 'Live proctoring';
$string['modform_examity_examLevel_record_review'] = 'Record and review proctoring';
$string['modform_examity_examName'] = 'Name of the exam';
$string['modform_examity_instructorEmail'] = 'Email address of the instructor';
$string['modform_examity_instructorFirstName'] = 'First name of the instructor';
$string['modform_examity_instructorLastName'] = 'Last name of the instructor';
$string['modform_examity_proctorInstructions'] = 'Instructions for the proctor';
$string['modform_examity_settings'] = 'Examity';
$string['modform_examity_v5_courseCode'] = 'Code of the course';
$string['modform_examity_v5_courseName'] = 'Name of the course';
$string['modform_examity_v5_examName'] = 'Name of the exam';
$string['modform_examity_v5_examSecurityLevel'] = 'Exam security level';
$string['modform_examity_v5_examSecurityLevel_auto'] = 'Automated + audit';
$string['modform_examity_v5_examSecurityLevel_automated'] = 'Automated';
$string['modform_examity_v5_examSecurityLevel_automated_practice'] = 'Automated practice';
$string['modform_examity_v5_examSecurityLevel_live_auth'] = 'Live authentication + audit';
$string['modform_examity_v5_examSecurityLevel_live_proctoring'] = 'Live proctoring';
$string['modform_examity_v5_instructorEmail'] = 'Email address of the instructor';
$string['modform_examity_v5_instructorFirstName'] = 'First name of the instructor';
$string['modform_examity_v5_instructorLastName'] = 'Last name of the instructor';
$string['modform_examity_v5_settings'] = 'Examity';
$string['modform_examus_identification'] = 'Identification';
$string['modform_examus_identification_face'] = 'Face';
$string['modform_examus_identification_face_and_passport'] = 'Face and passport';
$string['modform_examus_identification_passport'] = 'Passport';
$string['modform_examus_language'] = 'Constructor UI language';
$string['modform_examus_language_ar'] = 'Arabic';
$string['modform_examus_language_en'] = 'English';
$string['modform_examus_language_es'] = 'Spanish';
$string['modform_examus_language_it'] = 'Italian';
$string['modform_examus_language_ru'] = 'Russian';
$string['modform_examus_proctoring'] = 'Type';
$string['modform_examus_proctoring_offline'] = 'Record and post exam review';
$string['modform_examus_proctoring_online'] = 'Live proctoring';
$string['modform_examus_respondus'] = 'Use Respondus LockDown Browser';
$string['modform_examus_respondus_help'] = 'Use Respondus LockDown Browser';
$string['modform_examus_settings'] = 'Constructor';
$string['modform_examus_userAgreementUrl'] = 'User agreement URL (optional)';
$string['modform_feedbacktext'] = 'Feedback';
$string['modform_grademethod'] = 'Grading method';
$string['modform_grademethod_help'] = 'When multiple attempts are allowed, the following methods are available for calculating the final exam grade:

* Highest grade of all attempts
* Average (mean) grade of all attempts
* First attempt (all other attempts are ignored)
* Last attempt (all other attempts are ignored)';
$string['modform_meazure_allowedResources'] = 'Allowed resources';
$string['modform_meazure_allowedResources_all_websites'] = 'All websites';
$string['modform_meazure_allowedResources_approved_website'] = 'Approved website';
$string['modform_meazure_allowedResources_bathroom_breaks'] = 'Bathroom breaks';
$string['modform_meazure_allowedResources_computer_calculator'] = 'Computer\'s calculator';
$string['modform_meazure_allowedResources_course_website'] = 'Course website';
$string['modform_meazure_allowedResources_ebook_computer'] = 'Ebook (computer)';
$string['modform_meazure_allowedResources_ebook_website'] = 'Ebook (website)';
$string['modform_meazure_allowedResources_excel'] = 'Excel';
$string['modform_meazure_allowedResources_excel_notes'] = 'Notes (Excel)';
$string['modform_meazure_allowedResources_financial_calculator'] = 'Financial calculator';
$string['modform_meazure_allowedResources_formula_sheet'] = 'Formula sheet';
$string['modform_meazure_allowedResources_four_function_calculator'] = 'Four function calculator';
$string['modform_meazure_allowedResources_graphing_calculator'] = 'Graphing calculator';
$string['modform_meazure_allowedResources_handwritten_notes'] = 'Handwritten notes';
$string['modform_meazure_allowedResources_note_cards'] = 'Note cards';
$string['modform_meazure_allowedResources_notepad'] = 'Notepad';
$string['modform_meazure_allowedResources_online_calculator'] = 'Online calculator';
$string['modform_meazure_allowedResources_paint'] = 'Paint';
$string['modform_meazure_allowedResources_pdf_notes'] = 'Notes (PDF)';
$string['modform_meazure_allowedResources_powerpoint'] = 'Powerpoint';
$string['modform_meazure_allowedResources_powerpoint_notes'] = 'Notes (Powerpoint)';
$string['modform_meazure_allowedResources_printed_notes'] = 'Printed notes';
$string['modform_meazure_allowedResources_scientific_calculator'] = 'Scientific calculator';
$string['modform_meazure_allowedResources_scratch1'] = 'Scratch paper (1 sheet)';
$string['modform_meazure_allowedResources_scratch2'] = 'Scratch paper (2 sheets)';
$string['modform_meazure_allowedResources_scratch_more'] = 'Scratch paper (multiple sheets)';
$string['modform_meazure_allowedResources_spss'] = 'SPSS';
$string['modform_meazure_allowedResources_textbook'] = 'Textbook';
$string['modform_meazure_allowedResources_whiteboard'] = 'Whiteboard';
$string['modform_meazure_allowedResources_word'] = 'Word';
$string['modform_meazure_allowedResources_word_notes'] = 'Notes (Word)';
$string['modform_meazure_allowedUrls'] = 'Allowed URLs';
$string['modform_meazure_allowedUrls_add'] = 'Add allowed URL';
$string['modform_meazure_allowedUrls_delete'] = 'Remove allowed URL';
$string['modform_meazure_dontNotifyTestTaker'] = 'Do not notifiy test taker';
$string['modform_meazure_open_on_start'] = 'Open allowed URL {no} on start';
$string['modform_meazure_securityPreset'] = 'Security preset';
$string['modform_meazure_securityPreset_high'] = 'High';
$string['modform_meazure_securityPreset_low'] = 'Low';
$string['modform_meazure_securityPreset_medium'] = 'Medium';
$string['modform_meazure_sessionType'] = 'Type';
$string['modform_meazure_sessionType_live'] = 'Live+';
$string['modform_meazure_sessionType_record'] = 'Record+';
$string['modform_meazure_settings'] = 'Meazure Learning';
$string['modform_meazure_url'] = 'Allowed URL {no}';
$string['modform_mingrade'] = 'Minimum grade';
$string['modform_proctorexam_dontSendEmails'] = 'Do not send participant emails';
$string['modform_proctorexam_examInfo'] = 'General instructions for the exam';
$string['modform_proctorexam_examInfo_help'] = 'They are displayed before the student starts the exam.';
$string['modform_proctorexam_individualInfo'] = 'Individual information for each student.';
$string['modform_proctorexam_individualInfo_help'] = 'A personalized link to start the exam will be appended at the bottom using the the text from below. Alternatively, the <code>##start_exam##</code> placeholder can be used to control positioning of the link.';
$string['modform_proctorexam_mobileCam'] = 'Use mobile camera';
$string['modform_proctorexam_mobileCam_help'] = 'Use mobile camera as additional recording device';
$string['modform_proctorexam_sessionType'] = 'Session type';
$string['modform_proctorexam_sessionType_classroom'] = 'Classroom';
$string['modform_proctorexam_sessionType_live_proctoring'] = 'Live proctoring';
$string['modform_proctorexam_sessionType_record_review'] = 'Record review';
$string['modform_proctorexam_settings'] = 'Proctor Exam';
$string['modform_proctorexam_startExamLinkText'] = '»Start exam« link text';
$string['modform_proctorexam_startExamLinkText_default'] = 'Start exam';
$string['modform_proctorio_allowNewTabs'] = 'Allow new tabs';
$string['modform_proctorio_closeOpenTabs'] = 'Close open tabs';
$string['modform_proctorio_disableClipboard'] = 'Disable clipboard';
$string['modform_proctorio_disableDownloads'] = 'Disable downloads';
$string['modform_proctorio_disablePrinting'] = 'Disable printing';
$string['modform_proctorio_disableRightClick'] = 'Disable rightclick';
$string['modform_proctorio_fullscreenMode'] = 'Force fullscreen';
$string['modform_proctorio_fullscreenMode_lenient'] = 'Lenient';
$string['modform_proctorio_fullscreenMode_moderate'] = 'Moderate';
$string['modform_proctorio_fullscreenMode_no'] = 'no';
$string['modform_proctorio_fullscreenMode_severe'] = 'Severe';
$string['modform_proctorio_recordAudio'] = 'Record audio';
$string['modform_proctorio_recordRoomStart'] = 'Record room on start';
$string['modform_proctorio_recordRoomStart_help'] = 'Require the test taker to perform a room scan before starting the exam';
$string['modform_proctorio_recordScreen'] = 'Record screen';
$string['modform_proctorio_recordVideo'] = 'Record video';
$string['modform_proctorio_settings'] = 'Proctorio';
$string['modform_proctorio_verifyIdMode'] = 'Verify ID';
$string['modform_proctorio_verifyIdMode_auto'] = 'Automatic ID verification';
$string['modform_proctorio_verifyIdMode_live'] = 'Live ID verification';
$string['modform_proctorio_verifyIdMode_no'] = 'no';
$string['modform_remote_proctor'] = 'Use remote proctoring';
$string['modform_remote_proctor_error'] = 'Error retrieving remote proctors. Please make sure your API credentials are correct - you can test them at the options screen.';
$string['modform_remote_proctor_header'] = 'Remote proctoring';
$string['modform_remote_proctor_help'] = 'Choose one of your configured remote proctoring services.';
$string['modform_remote_proctor_invalid'] = 'Please select a valid remote proctor or "no".';
$string['modform_remote_proctor_none'] = 'No remote proctors found. Please make sure you have configured remote proctor accounts.
Also make sure your API credentials are correct - you can test them at the options screen.';
$string['modform_usebecertificate'] = 'Use bizExaminer certificates.';
$string['modform_usebecertificate_help'] = 'Enable this option to show users the certificate you designed/configured in bizExaminer.';
$string['modulename'] = 'bizExaminer exam';
$string['modulename_help'] = 'The bizExaminer Exam activity enables a teacher to create exams that students can take in bizExaminer.

The teacher can allow the exam to be attempted multiple times and a time limit may be set.

Each attempt is marked automatically (or manually, depending on questions configured in bizExaminer), and the grade is recorded in the gradebook.

An exam module must be configured in bizExaminer, remote proctor connections can be reused.';
$string['modulenameplural'] = 'bizExaminer exams';
$string['nocredentials'] = 'Please configure your API credentials.';
$string['overallfeedback'] = 'Overall feedback';
$string['overallfeedback_help'] = 'Overall feedback is text that is shown after an exam has been attempted. By specifying a minimum grade, the text shown can depend on the grade obtained.';
$string['pluginadministration'] = 'bizExaminer administration';
$string['pluginname'] = 'bizExaminer';



$string['privacy:metadata:attempt_results'] = 'Detailed results for each attempt from bizExaminer.';
$string['privacy:metadata:attempt_results:achievedscore'] = 'The points the user got on the attempt.';
$string['privacy:metadata:attempt_results:attemptid'] = 'The id of the attempt.';
$string['privacy:metadata:attempt_results:certificateurl'] = 'The URL to the bizExaminer certificate.';
$string['privacy:metadata:attempt_results:maxscore'] = 'The maximum points the user could have achieved.';
$string['privacy:metadata:attempt_results:pass'] = 'Whether the user failed/passed the exam according to bizExaminer.';
$string['privacy:metadata:attempt_results:questionscorrectcount'] = 'The number of questions the answered (correct).';
$string['privacy:metadata:attempt_results:questionscount'] = 'The number of questions the user was shown.';
$string['privacy:metadata:attempt_results:result'] = 'The percentage the user got on this attempt.';
$string['privacy:metadata:attempt_results:timetaken'] = 'The time in seconds that it took the user to complete the exam.';
$string['privacy:metadata:attempt_results:userid'] = 'The user who attempted the exam.';
$string['privacy:metadata:attempt_results:whenfinished'] = 'The time that the attempt was completed.';
$string['privacy:metadata:attempts'] = 'Details about each attempt on an exam.';
$string['privacy:metadata:attempts:attempt'] = 'The (sequential) attempt number for one user.';
$string['privacy:metadata:attempts:bookingid'] = 'The bookingId in bizExaminer (see external data).';
$string['privacy:metadata:attempts:examid'] = 'The exam that was attempted.';
$string['privacy:metadata:attempts:participantid'] = 'The participantid in bizExaminer (see external data).';
$string['privacy:metadata:attempts:status'] = 'The current state of the attempt.';
$string['privacy:metadata:attempts:timecreated'] = 'The time that the attempt was created and started.';
$string['privacy:metadata:attempts:timemodified'] = 'The time that the attempt was updated.';
$string['privacy:metadata:attempts:userid'] = 'The user who attempted the exam.';
$string['privacy:metadata:attempts:validto'] = 'The time that the attempt is still valid to take in bizExaminer.';
$string['privacy:metadata:bizexaminer'] = 'Data sent to bizExaminer for executing exams.';
$string['privacy:metadata:bizexaminer:email'] = 'The users email address from their moodle profile.';
$string['privacy:metadata:bizexaminer:firstname'] = 'The users first name from their moodle profile.';
$string['privacy:metadata:bizexaminer:lastname'] = 'The users last name from their moodle profile.';
$string['privacy:metadata:grades'] = 'Details about the overall grade for this exam.';
$string['privacy:metadata:grades:examid'] = 'The exam that was graded.';
$string['privacy:metadata:grades:grade'] = 'The overall grade for this exam.';
$string['privacy:metadata:grades:timemodified'] = 'The time that the grade was modified.';
$string['privacy:metadata:grades:timesubmitted'] = 'The time that the grade was submitted (=attempt was submitted).';
$string['privacy:metadata:grades:userid'] = 'The user who was graded.';
$string['reset_delete_attempts'] = 'Exam attempts deleted';
$string['reset_delete_grades'] = 'Exam stored grades deleted';
$string['reset_grades'] = 'Exam gradebook grades reset';
$string['resetform_remove_attempts'] = 'All exam attempts';
$string['results_grade_link'] = '<a href="{$a}">See grades</a>';
$string['results_notification_not_passed'] = 'You have not passed the exam.';
$string['results_notification_passed'] = 'You have passed the exam.';
$string['results_pass'] = 'Result';
$string['results_questionscorrectcount'] = 'Correct questions';
$string['results_questionscount'] = 'Questions';
$string['results_score'] = 'Score';
$string['results_state'] = 'Status';
$string['results_timetaken'] = 'Time taken';
$string['results_user'] = 'User';
$string['results_whenfinished'] = 'Completed on';
$string['results_whenstarted'] = 'Started on';
$string['settings_apicredentials'] = 'API credentials';
$string['settings_apicredentials_actions'] = 'Actions';
$string['settings_apicredentials_actions_delete'] = 'Delete';
$string['settings_apicredentials_actions_delete_disabled'] = 'API credentials cannot be deleted, if they are still used in exams';
$string['settings_apicredentials_actions_test_disabled'] = 'Please save your changes before testing the credentials.';
$string['settings_apicredentials_desc'] = 'Configure your bizExaminer API credentials';
$string['settings_apicredentials_error_invalid'] = 'The API credentials you entered are empty, not valid or contain non-valid characters. Please check them again.';
$string['settings_apicredentials_id'] = 'Id';
$string['settings_apicredentials_infos'] = 'Infos';
$string['settings_apicredentials_instance'] = 'Instance domain';
$string['settings_apicredentials_instance_row'] = 'Instance domain for API credentials set {$a}';
$string['settings_apicredentials_key_organisation'] = 'API key organisation';
$string['settings_apicredentials_key_organisation_row'] = 'API key organisation for API credentials set {$a}';
$string['settings_apicredentials_key_owner'] = 'API key owner';
$string['settings_apicredentials_key_owner_row'] = 'API key owner for API credentials set {$a}';
$string['settings_apicredentials_name'] = 'Name';
$string['settings_apicredentials_name_row'] = 'Name for API credentials set {$a}';
$string['settings_apicredentials_new_label'] = 'New';
$string['settings_apicredentials_used_in'] = 'Used in {$a} exams';
$string['settings_apicredentials_used_in_singular'] = 'Used in {$a} exam';




$string['task_cleanup_abandoned'] = 'Aborting abandoned exam attempts';
$string['testapi'] = 'Test credentials';
$string['testapi_credentials_invalid'] = 'Invalid';
$string['testapi_credentials_valid'] = 'Valid';
$string['testapi_desc'] = 'Test the stored API credential settings.';
$string['testapi_error'] = 'Testing of some API credentials was not successful.';
$string['testapi_success'] = 'Testing the API credentials was successful.';
