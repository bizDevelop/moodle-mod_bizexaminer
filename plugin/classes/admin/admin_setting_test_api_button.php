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
 * Custom admin settings field for an action button to test api credentials
 *
 * @package     mod_bizexaminer
 * @category    admin
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bizexaminer\admin;

/**
 * No setting - just heading and text.
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_test_api_button extends \admin_setting_heading {

    /**
     * not a setting, just text
     *
     * @param string $name
     * @param string $heading heading
     * @param string $information text in box
     */
    public function __construct($name, $heading, $information) {
        $this->nosave = true;
        parent::__construct($name, $heading, $information, '');
    }

    /**
     * Returns an HTML string
     *
     * @param mixed $data array or string depending on setting
     * @param string $query
     * @return string Returns an HTML string
     */
    public function output_html($data, $query='') {
        return \html_writer::tag(
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
    }
}
