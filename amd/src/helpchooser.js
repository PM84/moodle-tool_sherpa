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
 * The Sherpa help chooser.
 *
 * A tool_sherpa owned variant of the core activity chooser. It reuses the core templates and the
 * core activity chooser AMD modules (repository, exporter, dialoguedom, selectors) and embeds the
 * reactive block_ai_chat conversation (like the Sherpa help icons). The teacher discusses their
 * plan with the assistant; each assistant reply carries a machine-readable marker listing the
 * recommended modnames, which this controller parses to filter the chooser list live.
 *
 * Reused core internals (not a stable public API - covered by the Behat smoke test
 * tests/behat/helpchooser.feature so that core changes surface early):
 * - core_courseformat/local/activitychooser/repository::getSectionModulesData
 * - core_courseformat/local/activitychooser/exporter (getModChooserTemplateData)
 * - core_courseformat/local/activitychooser/dialoguedom (refreshSearchResults, showModuleHelp, ...)
 * - core_courseformat/local/activitychooser/selectors and the chooser templates.
 *
 * Observed block_ai_chat DOM (also covered by the smoke test): the chat output container and the
 * assistant message nodes. block_ai_chat does not expose its reactive store to other plugins, so a
 * MutationObserver on the rendered messages is the supported integration seam.
 *
 * @module     tool_sherpa/helpchooser
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Modal from 'core/modal';
import * as ModalEvents from 'core/modal_events';
import * as Templates from 'core/templates';
import Ajax from 'core/ajax';
import {getString} from 'core/str';
import {getFirst} from 'core/normalise';
import Notification from 'core/notification';
import Pending from 'core/pending';
import {getSectionModulesData} from 'core_courseformat/local/activitychooser/repository';
import Exporter from 'core_courseformat/local/activitychooser/exporter';
import DialogueDom from 'core_courseformat/local/activitychooser/dialoguedom';
import selectors from 'core_courseformat/local/activitychooser/selectors';
import * as ChatEmbed from 'tool_sherpa/chat_embed';

const SHERPA = {
    chooser: '[data-region="sherpa-chooser"]',
    chatContainer: '[data-region="sherpa-chat-container"]',
    status: '[data-region="sherpa-status"]',
};

// block_ai_chat rendered message selectors (see block_ai_chat/templates/components/message).
const CHAT = {
    message: '[data-block_ai_chat-component="message"]',
    content: '[data-block_ai_chat-element="messagecontent"]',
    aiClass: 'ai',
    tempIds: ['loadingspinner', 'temporaryprompt'],
};

// Marker the assistant appends to every reply, e.g. "[[SHERPA_ACTIVITIES: wiki, workshop]]".
const MARKER = /\[\[SHERPA_ACTIVITIES:\s*([^\]]*)\]\]/i;
const MARKER_GLOBAL = /\[\[SHERPA_ACTIVITIES:[^\]]*\]\]/gi;

/**
 * Parse the recommended modnames from an assistant message.
 *
 * @param {String} text The message text.
 * @return {Array|null} The lower-cased modnames, or null when the message carries no marker.
 */
const parseMarker = (text) => {
    const match = MARKER.exec(text);
    if (!match) {
        return null;
    }
    return match[1].split(',').map((name) => name.trim().toLowerCase()).filter(Boolean);
};

/**
 * Open the Sherpa help chooser.
 *
 * @param {Object} position The course position ({courseId, sectionId, beforeMod}).
 */
export const open = async(position) => {
    const pendingPromise = new Pending('tool_sherpa/helpchooser:open');
    try {
        await new HelpChooser(position).show();
    } catch (error) {
        Notification.exception(error);
    }
    pendingPromise.resolve();
};

/**
 * Controller for a single Sherpa help chooser instance.
 *
 * @private
 */
class HelpChooser {
    /**
     * Constructor.
     *
     * @param {Object} position The course position ({courseId, sectionId, beforeMod}).
     */
    constructor(position) {
        this.position = position;
        this.exporter = new Exporter();
        this.mappedModules = new Map();
        this.materials = new Map();
        this.observer = null;
        this.chatContainer = null;
    }

    /**
     * Build and show the modal, wire the events and start the chat.
     *
     * @return {Promise<void>}
     */
    async show() {
        const modules = await getSectionModulesData(
            this.position.courseId,
            this.position.sectionId,
            null,
            this.position.beforeMod,
        );
        modules.forEach((module) => this.mappedModules.set(module.name, module));

        const bodyData = await this.exporter.getModChooserTemplateData(modules);

        this.modal = await Modal.create({
            title: getString('helpchooser_title', 'tool_sherpa'),
            body: Templates.render('tool_sherpa/helpchooser_panel', {}),
            large: true,
            scrollable: true,
            removeOnClose: true,
            show: true,
        });
        await this.modal.getBodyPromise();
        this.body = getFirst(this.modal.getBody());

        // Render the reused core chooser into the Sherpa panel.
        const chooserContainer = this.body.querySelector(SHERPA.chooser);
        const {html, js} = await Templates.renderForPromise('core_courseformat/activitychooser', bodyData);
        await Templates.replaceNodeContents(chooserContainer, html, js);

        // The dialogue argument is unused by ChooserDialogueDOM; pass this controller for clarity.
        this.dialogueDom = new DialogueDom(this, this.modal, this.exporter);
        this.dialogueDom.initBootstrapComponents();
        this.dialogueDom.initActiveTabNavigation();

        this.registerEvents();
        await this.startChat();
    }

    /**
     * Register the modal event listeners.
     */
    registerEvents() {
        this.body.addEventListener('click', (event) => this.handleClick(event));
        this.modal.getRoot().on(ModalEvents.hidden, () => {
            if (this.observer) {
                this.observer.disconnect();
                this.observer = null;
            }
        });
    }

    /**
     * Handle clicks inside the modal body.
     *
     * @param {Event} event The click event.
     */
    async handleClick(event) {
        if (event.target.closest(selectors.actions.optionActions.showSummary)) {
            event.preventDefault();
            await this.showInfo(event.target.closest(selectors.actions.optionActions.showSummary));
            return;
        }
        if (event.target.closest(selectors.actions.closeOption)) {
            event.preventDefault();
            this.dialogueDom.hideModuleHelp();
            return;
        }
        if (event.target.closest(selectors.actions.clearSearch)) {
            event.preventDefault();
            this.dialogueDom.cleanSearchResults();
            return;
        }
        // Favourites are not supported in the Sherpa chooser; swallow the click.
        if (event.target.closest(selectors.actions.optionActions.manageFavourite)) {
            event.preventDefault();
        }
        // The activity link (data-action="add-chooser-option") navigates to the creation page by default.
    }

    /**
     * Prepare and embed the block_ai_chat conversation.
     *
     * @return {Promise<void>}
     */
    async startChat() {
        const statusEl = this.body.querySelector(SHERPA.status);
        this.chatContainer = this.body.querySelector(SHERPA.chatContainer);
        if (!this.chatContainer) {
            return;
        }

        let payload;
        try {
            payload = await this.prepareChat();
        } catch (error) {
            this.showStatus(statusEl, error.message || await getString('helpchooser_unavailable', 'tool_sherpa'));
            return;
        }

        this.materials = new Map((payload.materials || []).map((material) => [material.modname, material]));

        if (!payload.chatavailable) {
            this.showStatus(statusEl, await getString('helpchooser_unavailable', 'tool_sherpa'));
            return;
        }

        // Observe the rendered conversation before it is mounted so the first replies are caught.
        this.observeChat();
        await ChatEmbed.mount(this.chatContainer, payload.contextid);
    }

    /**
     * Call the server to store the chat system prompt and fetch the chat context and materials.
     *
     * @return {Promise<Object>} The prepare payload.
     */
    prepareChat() {
        const activities = [...this.mappedModules.values()].map((module) => ({
            modname: module.name,
            title: module.title,
        }));
        return Ajax.call([{
            methodname: 'tool_sherpa_prepare_chooser_chat',
            args: {courseid: this.position.courseId, activities},
        }])[0];
    }

    /**
     * Watch the embedded chat for new assistant replies and react to their recommendations.
     */
    observeChat() {
        this.observer = new MutationObserver(() => this.scanMessages());
        this.observer.observe(this.chatContainer, {childList: true, subtree: true});
    }

    /**
     * Scan the rendered assistant messages, apply the latest recommendation and hide the markers.
     */
    scanMessages() {
        if (!this.observer || !this.chatContainer) {
            return;
        }
        // Pause observing while we strip the markers from the assistant messages we just read.
        this.observer.disconnect();
        try {
            let latest = null;
            this.chatContainer.querySelectorAll(CHAT.message).forEach((message) => {
                if (!message.classList.contains(CHAT.aiClass)) {
                    return;
                }
                const id = message.getAttribute('data-block_ai_chat-messageid');
                if (CHAT.tempIds.includes(id)) {
                    return;
                }
                const content = message.querySelector(CHAT.content);
                if (!content || !content.textContent) {
                    return;
                }
                const parsed = parseMarker(content.textContent);
                if (parsed !== null) {
                    latest = parsed;
                    this.stripMarker(content);
                }
            });
            if (latest !== null) {
                this.applyRecommended(latest);
            }
        } finally {
            if (this.observer && this.chatContainer) {
                this.observer.observe(this.chatContainer, {childList: true, subtree: true});
            }
        }
    }

    /**
     * Remove the machine-readable marker from a rendered assistant message.
     *
     * @param {HTMLElement} content The message content element.
     */
    stripMarker(content) {
        try {
            if (MARKER.test(content.textContent)) {
                content.innerHTML = content.innerHTML
                    .replace(MARKER_GLOBAL, '')
                    .replace(/<p>\s*<\/p>/gi, '');
            }
        } catch (e) {
            // Stripping is cosmetic; never let it break the filtering.
            return;
        }
    }

    /**
     * Filter the chooser to the recommended activities, reusing the core search results view.
     *
     * @param {Array} modnames The recommended modnames (lower-cased).
     */
    async applyRecommended(modnames) {
        const available = new Map([...this.mappedModules.values()].map((module) => [module.name.toLowerCase(), module]));
        const filtered = modnames
            .filter((modname, index) => modnames.indexOf(modname) === index && available.has(modname))
            .map((modname) => available.get(modname));

        if (filtered.length === 0) {
            this.dialogueDom.cleanSearchResults();
            return;
        }
        const label = await getString('helpchooser_resultslabel', 'tool_sherpa');
        await this.dialogueDom.refreshSearchResults(label, filtered);
        this.dialogueDom.showAllActivitiesTab();
    }

    /**
     * Show the info panel for an activity and inject its supporting materials, if any.
     *
     * @param {HTMLElement} button The clicked "show summary" button.
     */
    async showInfo(button) {
        const option = this.dialogueDom.getClosestChooserOption(button);
        if (!option) {
            return;
        }
        const moduleData = this.mappedModules.get(option.dataset.internal);
        if (!moduleData) {
            return;
        }
        await this.dialogueDom.showModuleHelp(moduleData);
        await this.injectMaterials(option.dataset.internal);
    }

    /**
     * Inject the supporting materials (tutorials and mocked template tiles) into the help region.
     *
     * @param {String} modname The activity module name.
     */
    async injectMaterials(modname) {
        const materials = this.materials.get(modname);
        if (!materials) {
            return;
        }
        const hastutorials = materials.tutorials.length > 0;
        const hastemplates = materials.templates.length > 0;
        if (!hastutorials && !hastemplates) {
            return;
        }
        const help = this.body.querySelector(selectors.regions.help);
        if (!help) {
            return;
        }
        const container = await this.waitForSummaryContent(help);
        if (!container) {
            return;
        }
        const {html, js} = await Templates.renderForPromise('tool_sherpa/activity_materials', {
            tutorials: materials.tutorials,
            templates: materials.templates,
            hastutorials,
            hastemplates,
        });
        Templates.appendNodeContents(container, html, js);
    }

    /**
     * Wait for the core help summary content to be rendered into the help region.
     *
     * @param {HTMLElement} help The help region element.
     * @return {Promise<HTMLElement|null>} Resolves with the summary content container.
     */
    waitForSummaryContent(help) {
        const selector = selectors.regions.chooserSummary.content;
        const existing = help.querySelector(selector);
        if (existing) {
            return Promise.resolve(existing);
        }
        return new Promise((resolve) => {
            const observer = new MutationObserver(() => {
                const found = help.querySelector(selector);
                if (found) {
                    observer.disconnect();
                    resolve(found);
                }
            });
            observer.observe(help, {childList: true, subtree: true});
            // Safety net in case the structure changes in a future core version.
            setTimeout(() => {
                observer.disconnect();
                resolve(help.querySelector(selector));
            }, 2000);
        });
    }

    /**
     * Show a status message in the chat area.
     *
     * @param {HTMLElement} statusEl The status region.
     * @param {String} message The message to show.
     */
    showStatus(statusEl, message) {
        if (statusEl) {
            statusEl.textContent = message;
        }
    }
}
