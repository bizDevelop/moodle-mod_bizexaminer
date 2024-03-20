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
 * Custom admin settings field for configuring api domain
 *
 * @package     mod_bizexaminer
 * @category    admin
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bizexaminer\local\admin;

/**
 * Used to validate a textfield used for domain names, wildcard domain names and IP addresses/ranges (both IPv4 and IPv6 format).
 *
 * based on admin_setting_configmixedhostiplist but extends admin_setting_configtext and not admin_setting_configtextarea
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_configdomain extends \admin_setting_configtext {

    /**
     * Validate the contents of the textarea as either IP addresses, domain name or wildcard domain name (RFC 4592).
     * Used to validate a new line separated list of entries collected from a textarea control.
     *
     * This setting provides support for internationalised domain names (IDNs), however, such UTF-8 names will be converted to
     * their ascii-compatible encoding (punycode) on save, and converted back to their UTF-8 representation when fetched
     * via the get_setting() method, which has been overriden.
     *
     * @param string $data A list of FQDNs, DNS wildcard format domains, and IP addresses, separated by new lines.
     * @return mixed bool true for success or string:error on failure
     */
    public function validate($data) {
        if (empty($data)) {
            return true;
        }

        $valid = false;

            // Validate each string entry against the supported formats.
        if (\core\ip_utils::is_ip_address($data) || \core\ip_utils::is_ipv6_range($data)
                    || \core\ip_utils::is_ipv4_range($data) || \core\ip_utils::is_domain_name($data)
                    || \core\ip_utils::is_domain_matching_pattern($data)) {
                    $valid = true;
            // Test if PHP can get an IP from a domain.
            if (!filter_var(gethostbyname($data), FILTER_VALIDATE_IP )) {
                $valid = false;
            }
        }

        return $valid ? true : get_string('validateerrorlist', 'admin', $data);

    }
}
