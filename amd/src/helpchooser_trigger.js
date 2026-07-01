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
 * Delegated trigger for the Sherpa help chooser.
 *
 * Opens the Sherpa help chooser when the teacher activates the "Help, what should I do?" entry
 * that tool_sherpa added to the activity chooser button dropdown.
 *
 * @module     tool_sherpa/helpchooser_trigger
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {open} from 'tool_sherpa/helpchooser';

const SELECTOR = '[data-action="sherpa-helpchooser"]';

let initialised = false;

/**
 * Read the course position from a trigger element.
 *
 * @param {HTMLElement} trigger The activated trigger element.
 * @return {Object} The course position.
 */
const positionFromTrigger = (trigger) => ({
    courseId: parseInt(trigger.dataset.courseid, 10),
    sectionId: parseInt(trigger.dataset.sectionid, 10),
    beforeMod: trigger.dataset.beforemod ? parseInt(trigger.dataset.beforemod, 10) : 0,
});

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
        open(positionFromTrigger(trigger));
    });
};
