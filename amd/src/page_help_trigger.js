// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Trigger for the floating page level help button.
 *
 * A click on the page help button opens the Sherpa modal with the tutorials mapped to the current
 * page's body id.
 *
 * @module     tool_sherpa/page_help_trigger
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as HelpModal from 'tool_sherpa/help_modal';

const SELECTOR = '[data-action="tool-sherpa-page-help"]';

let initialised = false;

/**
 * Initialise the delegated event listener (idempotent).
 */
export const init = () => {
    if (initialised) {
        return;
    }
    initialised = true;

    document.addEventListener('click', (e) => {
        const trigger = e.target.closest(SELECTOR);
        if (!trigger) {
            return;
        }
        e.preventDefault();
        HelpModal.openPageHelp(trigger);
    });
};
