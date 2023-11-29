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

use mod_bizexaminer\admin\admin_setting_api_credentials;
use mod_bizexaminer\bizexaminer;

defined('MOODLE_INTERNAL') || die();

// phpcs:ignore moodle.Commenting.InlineComment.TypeHintingMatch
/** @var admin_settingpage $settings */

if ($hassiteconfig) {
    // Modules automatically get a section/category and page - which is available under $section.

    // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
    if ($ADMIN->fulltree) {
        // TODO: maybe define default settings for activity modules (#17).

        // Only register one main setting, name is hardcoded in admin_setting_api_credentials
        // because it's not a reusable setting type.
        // Child settings with name_$ID_$FIELD will be created
        // Moodle will delete all settings from a plugin when a plugin is uninstalled,
        // so no need to clean up ourselves.
        $settings->add(new admin_setting_api_credentials());
    }

}
