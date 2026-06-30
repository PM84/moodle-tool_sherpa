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
 * Delegated trigger for the Sherpa help modal.
 *
 * Replaces the default behaviour of the help icons that tool_sherpa rendered: a click or keyboard
 * activation opens the Sherpa modal instead of following the help.php fallback link.
 *
 * @module     tool_sherpa/help_icon_trigger
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as HelpModal from 'tool_sherpa/help_modal';

const SELECTOR = '[data-action="tool-sherpa-help"]';

let initialised = false;

/**
 * Initialise the delegated event listeners (idempotent).
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
        HelpModal.open(trigger);
    });

    // Enter on the link already triggers a click, so only handle Space here.
    document.addEventListener('keydown', (e) => {
        if (e.key !== ' ' && e.key !== 'Spacebar') {
            return;
        }
        const trigger = e.target.closest(SELECTOR);
        if (!trigger) {
            return;
        }
        e.preventDefault();
        HelpModal.open(trigger);
    });
};
