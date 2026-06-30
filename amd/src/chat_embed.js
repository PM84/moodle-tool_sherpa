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
 * Embeds the reactive block_ai_chat main component into the Sherpa modal.
 *
 * Mirrors the embedding pattern used by mod_aichat: render a container with a unique main element
 * and initialise block_ai_chat/reactive_init in embedded mode using the tool_sherpa component, so
 * the field specific system prompt can be injected via the local_ai_manager before_request hook.
 *
 * @module     tool_sherpa/chat_embed
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as Templates from 'core/templates';
import * as ReactiveInit from 'block_ai_chat/reactive_init';

let counter = 0;

/**
 * Render the embed container and start the reactive chat in embedded mode.
 *
 * @param {HTMLElement} container The element to render the chat into.
 * @param {Number} contextid The context id used for the chat.
 */
export const mount = async(container, contextid) => {
    counter++;
    const uniqueid = 'tool_sherpa_chat_' + Date.now() + '_' + counter;

    const {html, js} = await Templates.renderForPromise('tool_sherpa/chat_embed', {uniqueid});
    Templates.replaceNodeContents(container, html, js);

    const selector = '[data-block_aichat-element="mainelement"][data-id="' + uniqueid + '"]';
    await ReactiveInit.init(contextid, selector, null, 'tool_sherpa');
};
