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
 * JavaScript for Custom API Credentials settings field.
 *
 * @module      mod_bizexaminer/local/settings/apisettings
 * @category    admin
 * @copyright   2024 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const Selectors = {
  actions: {
    // Inputs for which to watch for changes.
    inputs: 'input[name^="s_mod_bizexaminer_apicredentials_"]',
    // The "Test Credentials" button which should be disabled.
    testButton: '[data-action="mod_bizexaminer/apisettings-test_button"]',
  },
};

/**
 * Listens to changes on API credentials inputs and then disables the "Test Credentials" button.
 *
 * Because the button is just a link to the moodle checks and clicking it will discard any changes.
 * Therefore disable it instead.
 */
export const init = () => {
  document.addEventListener("change", (e) => {
    if (e.target.matches(Selectors.actions.inputs)) {
      const button = document.querySelector(Selectors.actions.testButton);
      button.setAttribute("disabled", "disabled");
      button.setAttribute("aria-disabled", "true");
      button.classList.add("disabled");
      button.setAttribute("title", button.dataset.disabledMessage);
      button.setAttribute("aria-label", button.dataset.disabledMessage);
    }
  });
};
