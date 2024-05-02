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
 * Remote proctors service.
 *
 * @package     mod_bizexaminer
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bizexaminer\local\api;

use cache;
use mod_bizexaminer\task\clear_api_remote_proctors_cache;

/**
 * Service for getting remote proctors
 */
class remote_proctors extends abstract_api_service {

    /**
     * Get remote proctor environments from the Api, uses a cache via transients
     *
     * @return array $remoteproctors (array):
     *               'name' => (string)
     *               'description' => (string)
     *               'type' => (string)
     */
    public function get_remote_proctors(): array {
        $cache = cache::make('mod_bizexaminer', 'remote_proctors');
        $cachekey = "remote_proctors_{$this->api->get_credentials()->get_id()}";

        $returnproctors = $cache->get($cachekey);

        if (!$returnproctors) {
            $returnproctors = [];
            $apiclient = $this->get_api();
            $proctors = $apiclient->get_remote_proctoring_environments();

            if (!$proctors) {
                return $returnproctors;
            }

            foreach ($proctors as $proctor) {
                $id = $proctor->name;
                $returnproctors[$id] = [
                    'name' => $proctor->name,
                    'description' => $proctor->description ?? '',
                    'type' => $proctor->type,
                ];
            }

            // Save with a relative short amount of expiration
            // this is mostly cached so when viewing settings page, saving, validating (mulitple times within minutes)
            // it gets the same values from local
            // but it needs to be short, so new exam modules created in bizExaminer show here soon.
            $cache->set($cachekey, $returnproctors);

            // Trigger adhoc task to clear cache in near future
            // because TTl of cache shouldnt be used according to docs.
            $task = new clear_api_remote_proctors_cache();
            $task->set_next_run_time(time() + MINSECS * 5);
            \core\task\manager::reschedule_or_queue_adhoc_task($task);
        }

        return $returnproctors;
    }

    /**
     * Gets a remote proctor based on its unique id.
     *
     * @param string $id
     * @return array|false
     */
    public function get_remote_proctor(string $id) {
        $remoteproctors = $this->get_remote_proctors();
        if (isset($remoteproctors[$id])) {
            return $remoteproctors[$id];
        }
        return false;
    }

    /**
     * Returns a readable name for a proctor type
     *
     * @param string|null $proctortype
     * @return string
     */
    public function map_proctor_type_label(?string $proctortype) {
        switch ($proctortype) {
            case 'proctorio':
                return 'Proctorio';
            case 'examity':
            case 'examity_v5':
                return 'Examity';
            case 'examus':
                return 'Constructor';
            case 'proctorexam':
                return 'ProctorExam';
            case 'meazure':
                return 'Meazure Learning';
        }
        return '';
    }

    /**
     * Checks if a remote proctor exists for a set of api credentials
     *
     * @param string $id id (unique) of remote proctor connection
     * @return bool
     */
    public function has_remote_proctor(string $id): bool {
        $allremoteproctors = $this->get_remote_proctors();
        if (!isset($allremoteproctors[$id])) {
            return false;
        }

        return true;
    }

    /**
     * Do some reformatting of options
     * because of differences in storing the settings and how the API expects them.
     *
     * @param string|null $proctortype
     * @param array|null $remoteproctoroptions
     * @return array
     */
    public static function build_remote_proctor_options_for_api(
        ?string $proctortype = null, ?array $remoteproctoroptions = []): array {
        if (!$proctortype || empty($remoteproctoroptions)) {
            return [];
        }

        if (!array_key_exists($proctortype, $remoteproctoroptions)) {
            return [];
        }

        $options = $remoteproctoroptions[$proctortype];
        if ($proctortype === 'meazure') {
            $allowedurls = [];
            if (!empty($options['allowedUrls']['url'])) {
                foreach ($options['allowedUrls']['url'] as $i => $url) {
                    $openonstart = false;
                    if (!empty($options['allowedUrls']['open_on_start'][$i]) &&
                        (int)$options['allowedUrls']['open_on_start'][$i] === 1) {
                        $openonstart = true;
                    };
                    if (!empty($url)) {
                        $allowedurls[] = [
                            'url' => $url,
                            'open_on_start' => $openonstart,
                        ];
                    }
                }
            }
            $options['allowedUrls'] = $allowedurls;
        }

        return $options;
    }

    /**
     * Gets the settings per remote proctor to use
     * can be used for moodle plugin settings field format
     *
     * @see mod_bizexaminer\local\mod_form\mod_form_helper::add_remote_proctor_fields
     *
     * @return array
     */
    public static function get_remote_proctor_setting_fields(): array {
        $settings = [
            'proctorexam' => [ // Key needs to be the same as 'type' from api.
                'sessionType' => [
                    'type' => 'select',
                    'default' => 'record_review',
                    'options' => [
                        'classroom' => get_string(
                            'modform_proctorexam_sessionType_classroom', 'mod_bizexaminer', null, true),
                        'record_review' => get_string(
                            'modform_proctorexam_sessionType_record_review', 'mod_bizexaminer', null, true),
                        'live_proctoring' => get_string(
                            'modform_proctorexam_sessionType_live_proctoring', 'mod_bizexaminer', null, true),
                    ],
                ],
                'mobileCam' => [
                    'help_text' => true,
                    'type' => 'switch',
                    'default' => 0,
                ],
                'dontSendEmails' => [
                    'type' => 'switch',
                    'default' => 0,
                ],
                'examInfo' => [
                    'help_text' => true,
                    'type' => 'text',
                ],
                'individualInfo' => [
                    'help_text' => true,
                    'type' => 'text',
                ],
                'startExamLinkText' => [
                    'default' => get_string('modform_proctorexam_startExamLinkText_default', 'mod_bizexaminer'),
                    'type' => 'text',
                ],
            ],
            'examity' => [
                'courseId' => [
                    'type' => 'text',
                ],
                'courseName' => [
                    'type' => 'text',
                ],
                'instructorFirstName' => [
                    'type' => 'text',
                ],
                'instructorLastName' => [
                    'type' => 'text',
                ],
                'instructorEmail' => [
                    'type' => 'email',
                ],
                'examName' => [
                    'type' => 'text',
                ],
                'examLevel' => [
                    'type' => 'select',
                    'value_type' => 'int',
                    'options' => [
                        1 => get_string('modform_examity_examLevel_live_auth', 'mod_bizexaminer', null, true),
                        2 => get_string('modform_examity_examLevel_auto_proctoring_premium', 'mod_bizexaminer', null, true),
                        3 => get_string('modform_examity_examLevel_record_review', 'mod_bizexaminer', null, true),
                        4 => get_string('modform_examity_examLevel_live_proctoring', 'mod_bizexaminer', null, true),
                        5 => get_string('modform_examity_examLevel_auto_auth', 'mod_bizexaminer', null, true),
                        6 => get_string('modform_examity_examLevel_auto_proctoring_standard', 'mod_bizexaminer', null, true),
                    ],
                ],
                'examInstructions' => [
                    'type' => 'text',
                ],
                'proctorInstructions' => [
                    'type' => 'text',
                ],
            ],
            'examity_v5' => [
                'courseCode' => [
                    'type' => 'text',
                ],
                'courseName' => [
                    'type' => 'text',
                ],
                'instructorFirstName' => [
                    'type' => 'text',
                ],
                'instructorLastName' => [
                    'type' => 'text',
                ],
                'instructorEmail' => [
                    'type' => 'email',
                ],
                'examName' => [
                    'type' => 'text',
                ],
                'examSecurityLevel' => [
                    'type' => 'select',
                    'value_type' => 'int',
                    'options' => [
                        2 => get_string('modform_examity_v5_examSecurityLevel_auto', 'mod_bizexaminer', null, true),
                        4 => get_string('modform_examity_v5_examSecurityLevel_live_proctoring', 'mod_bizexaminer', null, true),
                        10 => get_string('modform_examity_v5_examSecurityLevel_live_auth', 'mod_bizexaminer', null, true),
                        11 => get_string('modform_examity_v5_examSecurityLevel_automated', 'mod_bizexaminer', null, true),
                        12 => get_string('modform_examity_v5_examSecurityLevel_automated_practice', 'mod_bizexaminer', null, true),
                    ],
                ],
            ],
            'examus' => [
                'language' => [
                    'type' => 'select',
                    'default' => 'en',
                    'options' => [
                        'en' => get_string('modform_examus_language_en', 'mod_bizexaminer', null, true),
                        'ru' => get_string('modform_examus_language_ru', 'mod_bizexaminer', null, true),
                        'es' => get_string('modform_examus_language_es', 'mod_bizexaminer', null, true),
                        'it' => get_string('modform_examus_language_it', 'mod_bizexaminer', null, true),
                        'ar' => get_string('modform_examus_language_ar', 'mod_bizexaminer', null, true),
                    ],
                ],
                'proctoring' => [
                    'type' => 'select',
                    'default' => 'online',
                    'options' => [
                        'online' => get_string('modform_examus_proctoring_online', 'mod_bizexaminer', null, true),
                        'offline' => get_string('modform_examus_proctoring_offline', 'mod_bizexaminer', null, true),
                    ],
                ],
                'identification' => [
                    'type' => 'select',
                    'default' => 'face',
                    'options' => [
                        'face' => get_string('modform_examus_identification_face', 'mod_bizexaminer', null, true),
                        'passport' => get_string('modform_examus_identification_passport', 'mod_bizexaminer', null, true),
                        'face_and_passport' => get_string(
                            'modform_examus_identification_face_and_passport', 'mod_bizexaminer', null, true),
                    ],
                ],
                'respondus' => [
                    'help_text' => true,
                    'type' => 'switch',
                    'default' => 0,
                ],
                'userAgreementUrl' => [
                    'type' => 'text',
                    'default' => '',
                ],
            ],
            'proctorio' => [
                'recordVideo' => [
                    'type' => 'switch',
                    'default' => 0,
                ],
                'recordAudio' => [
                    'type' => 'switch',
                    'default' => 0,
                ],
                'recordScreen' => [
                    'type' => 'switch',
                    'default' => 0,
                ],
                'recordRoomStart' => [
                    'help_text' => true,
                    'type' => 'switch',
                    'default' => 0,
                ],
                'verifyIdMode' => [
                    'type' => 'select',
                    'options' => [
                        '' => get_string('modform_proctorio_verifyIdMode_no', 'mod_bizexaminer', null, true),
                        'auto' => get_string('modform_proctorio_verifyIdMode_auto', 'mod_bizexaminer', null, true),
                        'live' => get_string('modform_proctorio_verifyIdMode_live', 'mod_bizexaminer', null, true),
                    ],
                ],
                'closeOpenTabs' => [
                    'type' => 'switch',
                    'default' => 0,
                ],
                'allowNewTabs' => [
                    'type' => 'switch',
                    'default' => 0,
                ],
                'fullscreenMode' => [
                    'type' => 'select',
                    'options' => [
                        '' => get_string('modform_proctorio_fullscreenMode_no', 'mod_bizexaminer', null, true),
                        'lenient' => get_string('modform_proctorio_fullscreenMode_lenient', 'mod_bizexaminer', null, true),
                        'moderate' => get_string('modform_proctorio_fullscreenMode_moderate', 'mod_bizexaminer', null, true),
                        'severe' => get_string('modform_proctorio_fullscreenMode_severe', 'mod_bizexaminer', null, true),
                    ],
                ],
                'disableClipboard' => [
                    'type' => 'switch',
                    'default' => 0,
                ],
                'disableRightClick' => [
                    'type' => 'switch',
                    'default' => 0,
                ],
                'disableDownloads' => [
                    'type' => 'switch',
                    'default' => 0,
                ],
                'disablePrinting' => [
                    'type' => 'switch',
                    'default' => 0,
                ],
            ],
            'meazure' => [
                'sessionType' => [
                    'type' => 'select',
                    'default' => 'live+',
                    'options' => [
                        'live+' => get_string(
                            'modform_meazure_sessionType_live', 'mod_bizexaminer', null, true),
                        'record+' => get_string(
                            'modform_meazure_sessionType_record', 'mod_bizexaminer', null, true),
                    ],
                ],
                'dontNotifyTestTaker' => [
                    'type' => 'switch',
                    'default' => 0,
                ],
                'securityPreset' => [
                    'type' => 'select',
                    'default' => 'low',
                    'options' => [
                        'low' => get_string(
                            'modform_meazure_securityPreset_low', 'mod_bizexaminer', null, true),
                        'medium' => get_string(
                            'modform_meazure_securityPreset_medium', 'mod_bizexaminer', null, true),
                        'high' => get_string(
                            'modform_meazure_securityPreset_high', 'mod_bizexaminer', null, true),
                    ],
                ],
                'allowedResources' => [
                    'type' => 'select',
                    'multiple' => true,
                    'default' => [],
                    'options' => [
                        'all_websites' => get_string(
                            'modform_meazure_allowedResources_all_websites', 'mod_bizexaminer', null, true),
                        'approved_website' => get_string(
                            'modform_meazure_allowedResources_approved_website', 'mod_bizexaminer', null, true),
                        'bathroom_breaks' => get_string(
                            'modform_meazure_allowedResources_bathroom_breaks', 'mod_bizexaminer', null, true),
                        'computer_calculator' => get_string(
                            'modform_meazure_allowedResources_computer_calculator', 'mod_bizexaminer', null, true),
                        'course_website' => get_string(
                            'modform_meazure_allowedResources_course_website', 'mod_bizexaminer', null, true),
                        'ebook_computer' => get_string(
                            'modform_meazure_allowedResources_ebook_computer', 'mod_bizexaminer', null, true),
                        'ebook_website'     => get_string(
                            'modform_meazure_allowedResources_ebook_website', 'mod_bizexaminer', null, true),
                        'excel' => get_string(
                            'modform_meazure_allowedResources_excel', 'mod_bizexaminer', null, true),
                        'excel_notes' => get_string(
                            'modform_meazure_allowedResources_excel_notes', 'mod_bizexaminer', null, true),
                        'financial_calculator' => get_string(
                            'modform_meazure_allowedResources_financial_calculator', 'mod_bizexaminer', null, true),
                        'formula_sheet' => get_string(
                            'modform_meazure_allowedResources_formula_sheet', 'mod_bizexaminer', null, true),
                        'four_function_calculator' => get_string(
                            'modform_meazure_allowedResources_four_function_calculator', 'mod_bizexaminer', null, true),
                        'graphing_calculator' => get_string(
                            'modform_meazure_allowedResources_graphing_calculator', 'mod_bizexaminer', null, true),
                        'handwritten_notes' => get_string(
                            'modform_meazure_allowedResources_handwritten_notes', 'mod_bizexaminer', null, true),
                        'note_cards' => get_string(
                            'modform_meazure_allowedResources_note_cards', 'mod_bizexaminer', null, true),
                        'notepad' => get_string(
                            'modform_meazure_allowedResources_notepad', 'mod_bizexaminer', null, true),
                        'online_calculator' => get_string(
                            'modform_meazure_allowedResources_online_calculator', 'mod_bizexaminer', null, true),
                        'paint' => get_string(
                            'modform_meazure_allowedResources_paint', 'mod_bizexaminer', null, true),
                        'pdf_notes' => get_string(
                            'modform_meazure_allowedResources_pdf_notes', 'mod_bizexaminer', null, true),
                        'powerpoint' => get_string(
                            'modform_meazure_allowedResources_powerpoint', 'mod_bizexaminer', null, true),
                        'powerpoint_notes' => get_string(
                            'modform_meazure_allowedResources_powerpoint_notes', 'mod_bizexaminer', null, true),
                        'printed_notes' => get_string(
                            'modform_meazure_allowedResources_printed_notes', 'mod_bizexaminer', null, true),
                        'scientific_calculator' => get_string(
                            'modform_meazure_allowedResources_scientific_calculator', 'mod_bizexaminer', null, true),
                        'scratch1' => get_string(
                            'modform_meazure_allowedResources_scratch1', 'mod_bizexaminer', null, true),
                        'scratch2' => get_string(
                            'modform_meazure_allowedResources_scratch2', 'mod_bizexaminer', null, true),
                        'scratch_more' => get_string(
                            'modform_meazure_allowedResources_scratch_more', 'mod_bizexaminer', null, true),
                        'spss' => get_string(
                            'modform_meazure_allowedResources_spss', 'mod_bizexaminer', null, true),
                        'textbook' => get_string(
                            'modform_meazure_allowedResources_textbook', 'mod_bizexaminer', null, true),
                        'whiteboard' => get_string(
                            'modform_meazure_allowedResources_whiteboard', 'mod_bizexaminer', null, true),
                        'word' => get_string(
                            'modform_meazure_allowedResources_word', 'mod_bizexaminer', null, true),
                        'word_notes' => get_string(
                            'modform_meazure_allowedResources_word_notes', 'mod_bizexaminer', null, true),
                    ],
                ],
                'allowedUrls' => [
                    'type' => 'repeater',
                    'addlabel' => get_string('modform_meazure_allowedUrls_add', 'mod_bizexaminer', null, true),
                    'deletelabel' => get_string('modform_meazure_allowedUrls_delete', 'mod_bizexaminer', null, true),
                    'default' => '',
                    'fields' => [
                        'url' => [
                            'type' => 'text',
                            'default' => '',
                            'sanitizetype' => PARAM_URL,
                        ],
                        'open_on_start' => [
                            'type' => 'switch',
                            'default' => 0,
                            'sanitizetype' => PARAM_BOOL,
                        ],
                    ],
                ],
            ],
        ];

        return $settings;
    }
}
