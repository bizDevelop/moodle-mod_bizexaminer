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
 * Custom admin settings field for configuring api domain.
 *
 * @package     mod_bizexaminer
 * @category    admin
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bizexaminer\admin;

use admin_setting;
use admin_setting_configpasswordunmask;
use admin_setting_configtext;
use html_table;
use html_table_cell;
use html_writer;
use lang_string;
use mod_bizexaminer\api\api_credentials;
use mod_bizexaminer\bizexaminer;
use mod_bizexaminer\settings;

/**
 * Custom admin setting field for displaying, adding/deleting and editing api credentials.
 *
 * This field actually generates multiple stored options
 * $name = a list of unique credential ids, used as a pointer
 * $name_$id_$key = the value for a key (from CREDENTEIA_KEYS) for the credential with id $id
 *
 * This uses nested admin_setting_config* fields of moodle.
 * That allows us to reuse their styles, their error handling, their validation etc.
 * But also means we have to do "hacky" things and some things ourselves which normally moodle does.
 * But we can keep that code to a minimum.
 * Because moodle does not actually know about these fields (they are not added to the settings page tree in settings.php)
 *
 */
class admin_setting_api_credentials extends \admin_setting {

    /**
     * Nested setting field instances per id.
     *
     * @var array<string,admin_setting[]>
     */
    private $settingfields = [];

    /**
     * Settings service instance to use.
     *
     * @var settings
     */
    private settings $settingsservice;

    public function __construct() {
        parent::__construct(
            'mod_bizexaminer/apicredentials',
            new lang_string('settings_apicredentials', 'mod_bizexaminer'),
            new lang_string('settings_apicredentials_desc', 'mod_bizexaminer'),
            []
        );
        $this->settingsservice = bizexaminer::get_instance()->get_service('settings');
        // Generate instance of all setting fields to use in all methods in this class.
        $this->create_setting_fields();
    }

    /**
     * Create instances of all used settings fields.
     * This is the same workload as doing it directly in settings.php,
     * therefore it should be okay performancewise.
     *
     * @return void
     */
    private function create_setting_fields() {
        $rawids = get_config('mod_bizexaminer', $this->name);
        $ids = explode(':', $rawids);
        $ids[] = 'new';
        $index = 1;
        foreach ($ids as $id) {
            $fields = [];
            $indexlabel = $id === 'new' ? new lang_string('settings_apicredentials_new_label', 'mod_bizexaminer') : $index;

            $fields['name'] = new admin_setting_configtext(
                'mod_bizexaminer/' . $this->settingsservice->build_credential_prop_option_key($id, 'name'),
                new lang_string('settings_apicredentials_name_row', 'mod_bizexaminer', $indexlabel),
                '',
                ''
            );

            $fields['instance'] = new admin_setting_configdomain(
                'mod_bizexaminer/' . $this->settingsservice->build_credential_prop_option_key($id, 'instance'),
                new lang_string('settings_apicredentials_instance_row', 'mod_bizexaminer', $indexlabel),
                '',
                ''
            );

            $fields['keyowner'] = new admin_setting_configpasswordunmask(
                'mod_bizexaminer/' . $this->settingsservice->build_credential_prop_option_key($id, 'keyowner'),
                new lang_string('settings_apicredentials_key_owner_row', 'mod_bizexaminer', $indexlabel),
                '',
                ''
            );

            $fields['keyorganisation'] = new admin_setting_configpasswordunmask(
                'mod_bizexaminer/' . $this->settingsservice->build_credential_prop_option_key($id, 'keyorganisation'),
                new lang_string('settings_apicredentials_key_organisation_row', 'mod_bizexaminer', $indexlabel),
                '',
                ''
            );

            $this->settingfields[$id] = $fields;
            ++$index;
        }
    }

    /**
     * Gets the raw setting from the db and
     * puts them in an array by credential id.
     *
     * @return array<string,string[]>
     */
    public function get_setting() {
        $rawids = $this->config_read($this->name);
        $ids = explode(':', $rawids);

        $credentials = [];

        foreach ($ids as $id) {
            if (empty($id)) {
                continue;
            }
            if (empty($this->settingfields[$id])) {
                continue;
            }
            $credentials[$id] = [
                'name' => $this->settingfields[$id]['name']->get_setting(),
                'instance' => $this->settingfields[$id]['instance']->get_setting(),
                'keyowner' => $this->settingfields[$id]['keyowner']->get_setting(),
                'keyorganisation' => $this->settingfields[$id]['keyorganisation']->get_setting(),
            ];
        }

        return $credentials;
    }

    /**
     * Handles writing of the nested settings, validating them, showing erors etc.
     *
     * @param mixed $data
     * @return string
     */
    public function write_setting($data) {
        $saved = [];
        $errors = [];

        // Since the other fields are nested that moodle does not know anything about,
        // the error is only stored to the whole "apicredentials" field.
        // Therefore on rendering after an error, only the ids are passed
        // (that's the only data stored with _this_ field name).
        // But we want moodle to show errors on all fields.
        $adminroot = admin_get_root();

        // All data for all keys.
        // Just like in core/admin/settings.phph and adminlib.php values are stored in s_$PLUGIN_$KEY.
        // When using data from this array, clean them or pass them to admin_setting fields which will clean them and check type.
        $submitteddata = data_submitted();

        // The sesskey is already checked by moodle core.

        // Handle deleting.
        // Will delete the nested property options.
        // Then below the id is just not saved in the reference array.
        $deleteid = optional_param('bizexaminer_apicredentials_delete', null, PARAM_ALPHANUMEXT);
        if (!empty($deleteid)) {
            $this->delete_credential($deleteid);
        }

        foreach ($this->settingfields as $id => $fields) {
            $savedid = $id;

            // If the id was deleted, skip it to not be included in reference array.
            // Property keys are already deleted above.
            if (!empty($deleteid) && $id === $deleteid) {
                continue;
            }

            // In the new entry check if any values are entered.
            // If no values are entered, the user didn't want to add a new credentialset (but fields still get posted).
            // If there is at least one value, validate them.
            if ($id === 'new') {
                // Create unique id for new entries.
                $savedid = uniqid();

                $hasnewvalue = !empty(array_filter($fields, function ($field) use($submitteddata) {
                    // Submitted fields as s_$PLUGIN_$KEY.
                    $fieldname = $field->get_full_name();
                    return !empty($submitteddata->$fieldname);
                }));

                if (!$hasnewvalue) {
                    continue;
                }
            }

            $saved[] = $savedid;

            // TODO: Maybe test API credentials when saving?

            foreach ($fields as $key => $field) {
                // Submitted fields as s_$PLUGIN_$KEY.
                $fieldname = $field->get_full_name();
                if ($id === 'new') {
                    // Update field name with newly generated id.
                    $field->name = $this->settingsservice->build_credential_prop_option_key($savedid, $key);
                }
                // Require all values.
                if (empty($submitteddata->$fieldname)) {
                    $errors[$fieldname] = get_string('settings_apicredentials_error_invalid', 'mod_bizexaminer');
                } else {
                    // This will validate values with their settings object validate function.
                    // Eg domains are checked. Names and API keys do not require any validation atm.
                    // Will also clean values.
                    $fieldsaved = $field->write_setting($submitteddata->$fieldname);
                    if (!empty($fieldsaved)) {
                        $errors[$fieldname] = $fieldsaved;
                    }
                }

                // Taken from adminlib.php admin_write_settings.
                if (!empty($errors[$fieldname])) {
                    $adminroot->errors[$fieldname] = new \stdClass();
                    $adminroot->errors[$fieldname]->data  = $submitteddata->$fieldname;
                    $adminroot->errors[$fieldname]->id    = $this->get_id();
                    $adminroot->errors[$fieldname]->error = $errors[$fieldname];
                }
            }
        }

        // Only save new ids if all other saves were successfull.
        if (!empty($errors)) {
            return get_string('errorsetting', 'admin');
        }

        $implodedids = implode(':', $saved);
        $saved = $this->config_write($this->name, $implodedids);
        return $saved ? '' : get_string('errorsetting', 'admin');
    }

    public function output_html($data, $query = '') {
        global $OUTPUT;

        // Since the other fields are nested that moodle does not know anything about,
        // the error is only stored to the whole "apicredentials" field.
        // Therefore on rendering after an error, only the ids are passed
        // (that's the only data stored with _this_ field name).
        // Use existing database data to render.
        $dbdata = $this->get_setting();

        $return = '';

        $table = new html_table();
        $table->attributes['class'] = 'admintable generaltable bizexaminer_api_credentials';
        $table->head = [];
        $table->align = [];
        $table->size = [];
        $table->data = [];

        // Escape all get_string calls with s().
        // Attributes of html_writer:: are escaped automatically.

        $table->head[] = html_writer::span(s(get_string('settings_apicredentials_id', 'mod_bizexaminer')), '');
        $table->align[] = 'left';
        $table->size[] = '';

        $table->head[] = html_writer::span(s(get_string('settings_apicredentials_name', 'mod_bizexaminer')), '');
        $table->align[] = 'left';
        $table->size[] = '';

        $table->head[] = html_writer::span(s(get_string('settings_apicredentials_instance', 'mod_bizexaminer')), '');
        $table->align[] = 'left';
        $table->size[] = '';

        $table->head[] = html_writer::span(s(get_string('settings_apicredentials_key_owner', 'mod_bizexaminer')), '');
        $table->align[] = 'left';
        $table->size[] = '';

        $table->head[] = html_writer::span(s(get_string('settings_apicredentials_key_organisation', 'mod_bizexaminer')), '');
        $table->align[] = 'left';
        $table->size[] = '';

        $table->head[] = html_writer::span(s(get_string('settings_apicredentials_infos', 'mod_bizexaminer')), '');
        $table->align[] = 'left';
        $table->size[] = '';

        $table->head[] = html_writer::span(s(get_string('settings_apicredentials_actions', 'mod_bizexaminer')), '');
        $table->align[] = 'left';
        $table->size[] = '';

        // Existing Data.
        $index = 1;
        foreach ($dbdata as $id => $datarow) {
            $credentialset = api_credentials::from_array($id, $datarow);
            $table->data[] = $this->render_row($index, $credentialset);
            ++$index;
        }

        // New Row.
        $table->data[] = $this->render_row($index, new api_credentials('new', '', '', '', ''));

        $return .= $OUTPUT->heading(get_string('settings_apicredentials', 'mod_bizexaminer'), 3);
        $return .= html_writer::table($table);
        $return .= html_writer::tag(
            'a',
            get_string('testapi', 'mod_bizexaminer'),
            [
                'class' => 'btn btn-secondary',
                'href' => (new \moodle_url(
                    '/report/status/index.php',
                    ['detail' => 'mod_bizexaminer_testapi']
                ))->out(),
            ]
        );

        return $return;

    }

    /**
     * Renders a single credential sets row.
     * Mostly uses the admin_setting_classes to do their rendering,
     * which provides us with accessible, moodle-looking fields.
     * We only hide the labels and setting short name via css.
     *
     * @param int $index
     * @param api_credentials $credentials
     * @return array
     */
    private function render_row(int $index, api_credentials $credentials) {
        $row = [];

        $fieldprefix = $this->get_full_name() . '[' . $index . ']';

        // Index + id.
        $id = $credentials->get_id();
        $isnew = $credentials->get_id() === 'new';
        $indexlabel = $isnew ? get_string('settings_apicredentials_new_label', 'mod_bizexaminer') : $index;

        // Make id cell a th with scope row.
        $idcell = new html_table_cell();
        $idcell->header = true;
        $idcell->id = "bizexaminer-api-credentials-row-$index";

        $idcell->text = html_writer::tag(
            'div',
            html_writer::span(s($indexlabel)) .
                html_writer::empty_tag('input',
                    ['type' => 'hidden', 'readonly' => true, 'name' => "{$fieldprefix}[id]", 'value' => $credentials->get_id() ])
        );

        $row[] = $idcell;

        // Name.
        $row[] = $this->settingfields[$id]['name']->output_html($credentials->get_name());

        // Instance.
        $row[] = $this->settingfields[$id]['instance']->output_html($credentials->get_instance());

        // Owner.
        $row[] = $this->settingfields[$id]['keyowner']->output_html($credentials->get_owner_key());

        // Organisation.
        $row[] = $this->settingfields[$id]['keyorganisation']->output_html($credentials->get_organisation_key());

        // Infos.
        $row[] = html_writer::tag(
            'div',
            html_writer::span(
                s($credentials->get_exams_used_count() === 1 ?
                get_string('settings_apicredentials_used_in_singular', 'mod_bizexaminer', $credentials->get_exams_used_count()) :
                get_string('settings_apicredentials_used_in', 'mod_bizexaminer', $credentials->get_exams_used_count())))
        );

        // Actions.
        $actions = [];

        // Delete button.
        if ($credentials->get_id() !== 'new') {
            $deletebuttonargs = [
                'class' => 'btn btn-secondary',
                'type' => 'submit',
                'value' => $credentials->get_id(),
                'name' => 'bizexaminer_apicredentials_delete',
            ];

            // If API credentials are still used, show disabled delete button.
            if ($credentials->get_exams_used_count() > 0) {
                $actions[] = \html_writer::tag(
                    'button',
                    get_string('settings_apicredentials_actions_delete', 'mod_bizexaminer'),
                    array_merge($deletebuttonargs,
                        ['disabled' => true,
                            'title' => get_string('settings_apicredentials_actions_delete_disabled', 'mod_bizexaminer')])
                );

            } else {
                $actions[] = \html_writer::tag(
                    'button',
                    get_string('settings_apicredentials_actions_delete', 'mod_bizexaminer'),
                    $deletebuttonargs
                );
            }
        }

        $row[] = html_writer::tag(
            'div',
            implode('', $actions)
        );

        return $row;
    }

    private function delete_credential(string $id): void {
        // Delete old options from database.
        // Id will not be stored in id array in main option (see above).
        foreach (settings::CREDENTIAL_KEYS as $key) {
            unset_config($this->settingsservice->build_credential_prop_option_key($id, $key), 'mod_bizexaminer');
        }
    }

}
