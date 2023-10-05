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
 * @category    api
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bizexaminer\api;

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
     * @param string|null $proctorType
     * @return string
     */
    public function map_proctor_type_label(?string $proctortype) {
        switch ($proctortype) {
            case 'proctorio':
                return 'Proctorio';
            case 'examity':
                return 'Examity';
            case 'examus':
                return 'Constructor';
            case 'proctorexam':
                return 'ProctorExam';
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
     * Gets the settings per remote proctor to use
     * can be used for moodle plugin settings field format
     *
     * @see mod_bizexaminer\mod_form\remote_proctor_options_group
     *
     * @return array
     */
    public static function get_remote_proctor_setting_fields(): array {
        $settings = [
            'proctorexam' => [ // Key needs to be the same as 'type' from api.
                'sessionType' => [
                    'label' => get_string('modform_proctorexam_sessionType', 'mod_bizexaminer', null, true),
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
                    'label' => get_string('modform_proctorexam_mobileCam', 'mod_bizexaminer', null, true),
                    'help_text' => get_string('modform_proctorexam_mobileCam_help', 'mod_bizexaminer', null, true),
                    'type' => 'switch',
                    'default' => 0,
                ],
                'dontSendEmails' => [
                    'label' => get_string('modform_proctorexam_dontSendEmails', 'mod_bizexaminer', null, true),
                    'type' => 'switch',
                    'default' => 0,
                ],
                'examInfo' => [
                    'label' => get_string('modform_proctorexam_examInfo', 'mod_bizexaminer', null, true),
                    'help_text' => get_string('modform_proctorexam_examInfo_help', 'mod_bizexaminer', null, true),
                    'type' => 'text',
                ],
                'individualInfo' => [
                    'label' => get_string('modform_proctorexam_individualInfo', 'mod_bizexaminer', null, true),
                    'help_text' => get_string('modform_proctorexam_individualInfo_help', 'mod_bizexaminer', null, true),
                    'type' => 'text',
                ],
                'startExamLinkText' => [
                    'label' => get_string('modform_proctorexam_startExamLinkText', 'mod_bizexaminer'),
                    'default' => get_string('modform_proctorexam_startExamLinkText_default', 'mod_bizexaminer'),
                    'type' => 'text',
                ],
            ],
            'examity' => [
                'courseId' => [
                    'label' => get_string('modform_examity_courseId', 'mod_bizexaminer', null, true),
                    'type' => 'text',
                ],
                'courseName' => [
                    'label' => get_string('modform_examity_courseName', 'mod_bizexaminer', null, true),
                    'type' => 'text',
                ],
                'instructorFirstName' => [
                    'label' => get_string('modform_examity_instructorFirstName', 'mod_bizexaminer', null, true),
                    'type' => 'text',
                ],
                'instructorLastName' => [
                    'label' => get_string('modform_examity_instructorLastName', 'mod_bizexaminer', null, true),
                    'type' => 'text',
                ],
                'instructorEmail' => [
                    'label' => get_string('modform_examity_instructorEmail', 'mod_bizexaminer', null, true),
                    'type' => 'email',
                ],
                'examName' => [
                    'label' => get_string('modform_examity_examName', 'mod_bizexaminer', null, true),
                    'type' => 'text',
                ],
                'examLevel' => [
                    'label' => get_string('modform_examity_examLevel', 'mod_bizexaminer', null, true),
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
                    'label' => get_string('modform_examity_examInstructions', 'mod_bizexaminer', null, true),
                    'type' => 'text',
                ],
                'proctorInstructions' => [
                    'label' => get_string('modform_examity_proctorInstructions', 'mod_bizexaminer', null, true),
                    'type' => 'text',
                ],
            ],
            'examus' => [
                'language' => [
                    'label' => get_string('modform_examus_language', 'mod_bizexaminer', null, true),
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
                    'label' => get_string('modform_examus_proctoring', 'mod_bizexaminer', null, true),
                    'type' => 'select',
                    'default' => 'online',
                    'options' => [
                        'online' => get_string('modform_examus_proctoring_online', 'mod_bizexaminer', null, true),
                        'offline' => get_string('modform_examus_proctoring_offline', 'mod_bizexaminer', null, true),
                    ],
                ],
                'identification' => [
                    'label' => get_string('modform_examus_identification', 'mod_bizexaminer', null, true),
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
                    'label' => get_string('modform_examus_respondus', 'mod_bizexaminer', null, true),
                    'help_text' => get_string('modform_examus_respondus_help', 'mod_bizexaminer', null, true),
                    'type' => 'switch',
                    'default' => 0,
                ],
                'userAgreementUrl' => [
                    'label' => get_string('modform_examus_userAgreementUrl', 'mod_bizexaminer', null, true),
                    'type' => 'text',
                    'default' => '',
                ],
            ],
            'proctorio' => [
                'recordVideo' => [
                    'label' => get_string('modform_proctorio_recordVideo', 'mod_bizexaminer', null, true),
                    'type' => 'switch',
                    'default' => 0,
                ],
                'recordAudio' => [
                    'label' => get_string('modform_proctorio_recordAudio', 'mod_bizexaminer', null, true),
                    'type' => 'switch',
                    'default' => 0,
                ],
                'recordScreen' => [
                    'label' => get_string('modform_proctorio_recordScreen', 'mod_bizexaminer', null, true),
                    'type' => 'switch',
                    'default' => 0,
                ],
                'recordRoomStart' => [
                    'label' => get_string('modform_proctorio_recordRoomStart', 'mod_bizexaminer', null, true),
                    'help_text' => get_string('modform_proctorio_recordRoomStart_help', 'mod_bizexaminer', null, true),
                    'type' => 'switch',
                    'default' => 0,
                ],
                'verifyIdMode' => [
                    'label' => get_string('modform_proctorio_verifyIdMode', 'mod_bizexaminer', null, true),
                    'type' => 'select',
                    'options' => [
                        '' => get_string('modform_proctorio_verifyIdMode_no', 'mod_bizexaminer', null, true),
                        'auto' => get_string('modform_proctorio_verifyIdMode_auto', 'mod_bizexaminer', null, true),
                        'live' => get_string('modform_proctorio_verifyIdMode_live', 'mod_bizexaminer', null, true),
                    ],
                ],
                'closeOpenTabs' => [
                    'label' => get_string('modform_proctorio_closeOpenTabs', 'mod_bizexaminer', null, true),
                    'type' => 'switch',
                    'default' => 0,
                ],
                'allowNewTabs' => [
                    'label' => get_string('modform_proctorio_allowNewTabs', 'mod_bizexaminer', null, true),
                    'type' => 'switch',
                    'default' => 0,
                ],
                'fullscreenMode' => [
                    'label' => get_string('modform_proctorio_fullscreenMode', 'mod_bizexaminer', null, true),
                    'type' => 'select',
                    'options' => [
                        '' => get_string('modform_proctorio_fullscreenMode_no', 'mod_bizexaminer', null, true),
                        'lenient' => get_string('modform_proctorio_fullscreenMode_lenient', 'mod_bizexaminer', null, true),
                        'moderate' => get_string('modform_proctorio_fullscreenMode_moderate', 'mod_bizexaminer', null, true),
                        'severe' => get_string('modform_proctorio_fullscreenMode_severe', 'mod_bizexaminer', null, true),
                    ],
                ],
                'disableClipboard' => [
                    'label' => get_string('modform_proctorio_disableClipboard', 'mod_bizexaminer', null, true),
                    'type' => 'switch',
                    'default' => 0,
                ],
                'disableRightClick' => [
                    'label' => get_string('modform_proctorio_disableRightClick', 'mod_bizexaminer', null, true),
                    'type' => 'switch',
                    'default' => 0,
                ],
                'disableDownloads' => [
                    'label' => get_string('modform_proctorio_disableDownloads', 'mod_bizexaminer', null, true),
                    'type' => 'switch',
                    'default' => 0,
                ],
                'disablePrinting' => [
                    'label' => get_string('modform_proctorio_disablePrinting', 'mod_bizexaminer', null, true),
                    'type' => 'switch',
                    'default' => 0,
                ],
            ],
        ];

        return $settings;
    }
}
