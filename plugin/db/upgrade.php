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

    return true;
}
