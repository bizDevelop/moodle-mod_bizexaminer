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
 * Plugin upgrade steps are defined here.
 *
 * @package     mod_bizexaminer
 * @category    db
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute mod_bizexaminer upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_bizexaminer_upgrade($oldversion) {
    /** @var moodle_database $DB */ // phpcs:ignore moodle.Commenting.InlineComment.TypeHintingMatch
    global $DB;

    $dbman = $DB->get_manager();

    // For further information please read {@link https://docs.moodle.org/dev/Upgrade_API}.
    //
    // You will also have to create the db/install.xml file by using the XMLDB Editor.
    // Documentation for the XMLDB Editor can be found at {@link https://docs.moodle.org/dev/XMLDB_editor}.

    // Fix id/index for grades.
    if ($oldversion < 2023100403) {

        // Define field id to be added to bizexaminer_grades.
        $table = new xmldb_table('bizexaminer_grades');

        // Drop previous foreign-unique key for user.
        $key = new xmldb_key('user', XMLDB_KEY_FOREIGN_UNIQUE, ['userid'], 'user', ['id']);
        $dbman->drop_key($table, $key);

        // Add new user foreign (withohut unique) key.
        $key = new xmldb_key('user', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $dbman->add_key($table, $key);

        // Bizexaminer savepoint reached.
        upgrade_mod_savepoint(true, 2023100403, 'bizexaminer');
    }

    // Multiple API Keys update.
    if ($oldversion < 2023110200) {
        // Define field api_credentials to be added to bizexaminer.
        $table = new xmldb_table('bizexaminer');
        $field = new xmldb_field('apicredentials', XMLDB_TYPE_CHAR, '15', null, null, null, null, 'usebecertificate');

        // Conditionally launch add field api_credentials.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $oldapiinstance = get_config('mod_bizexaminer', 'apikeyinstance');
        $oldapikeyorganisation = get_config('mod_bizexaminer', 'apikeyorganisation');
        $oldapikeyowner = get_config('mod_bizexaminer', 'apikeyowner');

        if (!empty($oldapiinstance) || !empty($oldapikeyorganisation) || !empty($oldapikeyowner)) {
            $newapicredentialsid = uniqid();
            set_config('apicredentials_' . $newapicredentialsid . '_name', 'Main', 'mod_bizexaminer');
            set_config('apicredentials_' . $newapicredentialsid . '_instance', $oldapiinstance, 'mod_bizexaminer');
            set_config('apicredentials_' . $newapicredentialsid . '_keyorganisation', $oldapikeyorganisation, 'mod_bizexaminer');
            set_config('apicredentials_' . $newapicredentialsid . '_keyowner', $oldapikeyowner, 'mod_bizexaminer');

            set_config('apicredentials', $newapicredentialsid, 'mod_bizexaminer');

            unset_config('apikeyinstance', 'mod_bizexaminer');
            unset_config('apikeyorganisation', 'mod_bizexaminer');
            unset_config('apikeyowner', 'mod_bizexaminer');

            // Set new API Credentials ID for all existing exams.
            $DB->set_field('bizexaminer', 'apicredentials', $newapicredentialsid);
        }

        // Bizexaminer savepoint reached.
        upgrade_mod_savepoint(true, 2023110200, 'bizexaminer');
    }

    return true;
}
