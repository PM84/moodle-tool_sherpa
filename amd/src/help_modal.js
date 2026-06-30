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
 * Builds and populates the Sherpa help modal.
 *
 * One modal instance is reused for the whole page and refilled on each open. Region 1 (help text)
 * is copied from the hidden template next to the trigger; regions 2 (materials) and 3 (chat) are
 * lazy loaded from the server.
 *
 * @module     tool_sherpa/help_modal
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as Templates from 'core/templates';
import Modal from 'core/modal';
import Ajax from 'core/ajax';
import {exception as displayException} from 'core/notification';
import Pending from 'core/pending';
import * as ChatEmbed from 'tool_sherpa/chat_embed';

let modal = null;

/**
 * Fetch the lazy loaded modal content from the server.
 *
 * @param {String} component The help string component.
 * @param {String} identifier The help string identifier.
 * @param {Number} contextid The context id.
 * @returns {Promise<Object>} The modal payload.
 */
const fetchContent = (component, identifier, contextid) => {
    return Ajax.call([{
        methodname: 'tool_sherpa_get_help_modal_content',
        args: {component, identifier, contextid},
    }])[0];
};

/**
 * Copy the server rendered help text from the trigger's hidden template into the help region.
 *
 * @param {HTMLElement} root The modal root element.
 * @param {HTMLElement} trigger The trigger element that was activated.
 */
const renderHelpRegion = (root, trigger) => {
    const region = root.querySelector('[data-region="help"]');
    if (!region) {
        return;
    }
    region.replaceChildren();
    const wrapper = trigger.closest('.tool-sherpa-help-wrapper');
    const template = wrapper ? wrapper.querySelector('template[data-region="tool-sherpa-helptext"]') : null;
    if (template && template.content) {
        region.appendChild(template.content.cloneNode(true));
    }
};

/**
 * Render the materials region from the fetched sources.
 *
 * @param {HTMLElement} root The modal root element.
 * @param {Array} sources The source objects.
 */
const renderMaterials = async(root, sources) => {
    const region = root.querySelector('[data-region="materials"]');
    const list = root.querySelector('[data-region="materials-list"]');
    if (!region || !list) {
        return;
    }
    if (!sources || sources.length === 0) {
        region.hidden = true;
        return;
    }
    list.replaceChildren();
    for (const source of sources) {
        const {html, js} = await Templates.renderForPromise('tool_sherpa/material_item', source);
        Templates.appendNodeContents(list, html, js);
    }
    region.hidden = false;
};

/**
 * Mount the reactive block_ai_chat embed into the chat region.
 *
 * @param {HTMLElement} root The modal root element.
 * @param {Number} contextid The context id.
 */
const mountChat = async(root, contextid) => {
    const region = root.querySelector('[data-region="chat"]');
    const container = root.querySelector('[data-region="chat-container"]');
    if (!region || !container) {
        return;
    }
    region.hidden = false;
    await ChatEmbed.mount(container, contextid);
};

/**
 * Open the help modal for the given trigger element.
 *
 * @param {HTMLElement} trigger The activated help trigger.
 */
export const open = async(trigger) => {
    const pending = new Pending('tool_sherpa/help_modal:open');
    try {
        const data = trigger.dataset;
        const title = data.title || '';
        const contextid = parseInt(data.contextid, 10);
        const showmaterials = data.showmaterials === '1';
        const chatavailable = data.chatavailable === '1';

        const body = await Templates.render('tool_sherpa/help_modal_body', {});
        if (!modal) {
            modal = await Modal.create({title, body, large: true, removeOnClose: false});
        } else {
            modal.setTitle(title);
            modal.setBody(body);
        }

        const root = modal.getRoot()[0];
        renderHelpRegion(root, trigger);
        await modal.show();

        // Lazy load materials and chat from the server.
        if (showmaterials || chatavailable) {
            const content = await fetchContent(data.component, data.identifier, contextid);
            if (showmaterials) {
                await renderMaterials(root, content.sources);
            }
            if (chatavailable && content.chatavailable) {
                await mountChat(root, contextid);
            }
        }
    } catch (error) {
        displayException(error);
    } finally {
        pending.resolve();
    }
};
