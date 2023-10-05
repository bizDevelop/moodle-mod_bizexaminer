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
 * Plugin administration pages are defined here.
 *
 * @package     mod_bizexaminer
 * @category    settings
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_bizexaminer\admin\admin_setting_configdomain;
use mod_bizexaminer\admin\admin_setting_test_api_button;

defined('MOODLE_INTERNAL') || die();

// phpcs:ignore moodle.Commenting.InlineComment.TypeHintingMatch
/** @var admin_settingpage $settings */

if ($hassiteconfig) {
    // Modules automatically get a section/category and page - which is available under $section.

    // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
    if ($ADMIN->fulltree) {
        // TODO: maybe define default settings for activity modules (#17).
        $instance = new admin_setting_configdomain(
            'mod_bizexaminer/apikeyinstance',
            new lang_string('apikeyinstance', 'mod_bizexaminer'),
            new lang_string('apikeyinstance_desc', 'mod_bizexaminer'),
            ''
        );

        $settings->add($instance);

        $keyowner = new admin_setting_configpasswordunmask(
            'mod_bizexaminer/apikeyowner',
            new lang_string('apikeyowner', 'mod_bizexaminer'),
            new lang_string('apikeyowner_desc', 'mod_bizexaminer'),
            ''
        );

        $settings->add($keyowner);

        $keyorganisation = new admin_setting_configpasswordunmask(
            'mod_bizexaminer/apikeyorganisation',
            new lang_string('apikeyorganisation', 'mod_bizexaminer'),
            new lang_string('apikeyorganisation_desc', 'mod_bizexaminer'),
            ''
        );

        $settings->add($keyorganisation);

        $settings->add(new admin_setting_test_api_button(
            'mod_bizexaminer/testapi',
            '',
            ''
        ));

        // TODO: Allow adding multiple API credentials (#18).
    }

}
