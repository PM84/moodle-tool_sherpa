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
 * Management interface for Sherpa sources, placements and mappings.
 *
 * Reuses core building blocks: modal forms (core_form/modalform), the report builder
 * dynamic table reload event, and core notifications/ajax for deletions.
 *
 * @module      tool_sherpa/management
 * @copyright   2026 ISB Bayern
 * @author      Dr. Peter Mayer
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalForm from 'core_form/modalform';
import Notification from 'core/notification';
import Pending from 'core/pending';
import {getString} from 'core/str';
import {prefetchStrings} from 'core/prefetch';
import {add as addToast} from 'core/toast';
import {dispatchEvent} from 'core/event_dispatcher';
import {call as fetchMany} from 'core/ajax';
import reportEvents from 'core_reportbuilder/local/events';
import reportSelectors from 'core_reportbuilder/local/selectors';

const SELECTORS = {
    sourceCreate: '[data-action="source-create"]',
    sourceEdit: '[data-action="source-edit"]',
    sourceDelete: '[data-action="source-delete"]',
    placementCreate: '[data-action="placement-create"]',
    placementEdit: '[data-action="placement-edit"]',
    placementDelete: '[data-action="placement-delete"]',
    mappingAdd: '[data-action="mapping-add"]',
    mappingDelete: '[data-action="mapping-delete"]',
};

/**
 * Reload the dynamic table that contains the given element.
 *
 * @param {HTMLElement} element
 */
const reloadTable = element => {
    const reportElement = element.closest(reportSelectors.regions.report);
    if (reportElement) {
        dispatchEvent(reportEvents.tableReload, {preservePagination: true}, reportElement);
    }
};

/**
 * Module URL of the fireworks-js UMD build (https://fireworks.js.org/).
 *
 * The ".js" suffix is mandatory: RequireJS uses ids containing a URL scheme verbatim and does not
 * append it, and the CDN only serves the file with the correct JavaScript MIME type when requested
 * with its real ".js" extension.
 *
 * @var {String}
 */
const FIREWORKS_URL = 'https://cdn.jsdelivr.net/npm/fireworks-js@2/dist/index.umd.js';

/** @var {Promise|null} Cached loader promise so the library is fetched only once. */
let fireworksLoader = null;

/**
 * Lazy load the fireworks-js library through RequireJS (which Moodle uses).
 *
 * The library ships as a UMD bundle that registers itself as an anonymous AMD module. It must
 * therefore be pulled in via require() and not a raw <script> tag - otherwise RequireJS aborts
 * the next module load with a "Mismatched anonymous define()" error.
 *
 * @return {Promise} Resolves with the Fireworks class.
 */
const loadFireworks = () => {
    if (fireworksLoader === null) {
        fireworksLoader = new Promise((resolve, reject) => {
            window.require([FIREWORKS_URL], module => resolve(module.Fireworks || module), reject);
        });
    }
    return fireworksLoader;
};

/**
 * Fire a short, decorative fireworks show in a full screen, non-interactive layer.
 */
const launchFireworks = () => {
    loadFireworks().then(Fireworks => {
        const container = document.createElement('div');
        container.className = 'tool_sherpa-fireworks';
        document.body.appendChild(container);

        const fireworks = new Fireworks(container);
        fireworks.start();

        setTimeout(() => {
            fireworks.stop();
            container.remove();
        }, 5000);
        return;
    }).catch(() => {
        // The fireworks are purely decorative, so silently ignore any loading errors.
        return;
    });
};

/**
 * Open a modal form and reload the table once it is submitted.
 *
 * @param {HTMLElement} triggerElement
 * @param {String} formClass
 * @param {Promise|String} title
 * @param {Object} args
 * @param {Function} [onSubmitted] Optional callback run after a successful submission.
 */
const openModal = (triggerElement, formClass, title, args, onSubmitted) => {
    const modal = new ModalForm({
        formClass,
        args,
        modalConfig: {title},
        returnFocus: triggerElement,
    });

    modal.addEventListener(modal.events.FORM_SUBMITTED, () => {
        reloadTable(triggerElement);
        if (onSubmitted) {
            onSubmitted();
        }
    });
    modal.show();
};

/**
 * Confirm and run a deletion web service, then reload the table.
 *
 * @param {HTMLElement} triggerElement
 * @param {String} methodname
 * @param {Number} id
 * @param {Promise|String} title
 * @param {Promise|String} message
 * @param {Promise|String} toast
 */
const confirmDelete = (triggerElement, methodname, id, title, message, toast) => {
    Notification.saveCancelPromise(title, message, getString('delete', 'core'), {triggerElement})
        .then(() => {
            const pendingPromise = new Pending('tool_sherpa/delete');
            return fetchMany([{methodname, args: {id}}])[0]
                .then(() => addToast(toast))
                .then(() => {
                    reloadTable(triggerElement);
                    return pendingPromise.resolve();
                })
                .catch(Notification.exception);
        })
        .catch(() => {
            return;
        });
};

/**
 * Initialise the management interface.
 */
export const init = () => {
    prefetchStrings('tool_sherpa', [
        'addsource', 'editsource', 'deletesource', 'sourcedeleted',
        'addplacement', 'editplacement', 'deleteplacement', 'placementdeleted',
        'linksource', 'linkplacement',
        'unlink', 'unlinkconfirm',
    ]);
    prefetchStrings('core', ['delete']);

    document.addEventListener('click', event => {
        const sourceCreate = event.target.closest(SELECTORS.sourceCreate);
        if (sourceCreate) {
            event.preventDefault();
            openModal(sourceCreate, 'tool_sherpa\\form\\source_form', getString('addsource', 'tool_sherpa'), {},
                launchFireworks);
            return;
        }

        const sourceEdit = event.target.closest(SELECTORS.sourceEdit);
        if (sourceEdit) {
            event.preventDefault();
            openModal(sourceEdit, 'tool_sherpa\\form\\source_form', getString('editsource', 'tool_sherpa'),
                {id: sourceEdit.dataset.sourceId});
            return;
        }

        const sourceDelete = event.target.closest(SELECTORS.sourceDelete);
        if (sourceDelete) {
            event.preventDefault();
            confirmDelete(sourceDelete, 'tool_sherpa_source_delete', sourceDelete.dataset.sourceId,
                getString('deletesource', 'tool_sherpa'),
                getString('deletesourceconfirm', 'tool_sherpa', sourceDelete.dataset.sourceName),
                getString('sourcedeleted', 'tool_sherpa'));
            return;
        }

        const placementCreate = event.target.closest(SELECTORS.placementCreate);
        if (placementCreate) {
            event.preventDefault();
            openModal(placementCreate, 'tool_sherpa\\form\\placement_form', getString('addplacement', 'tool_sherpa'), {},
                launchFireworks);
            return;
        }

        const placementEdit = event.target.closest(SELECTORS.placementEdit);
        if (placementEdit) {
            event.preventDefault();
            openModal(placementEdit, 'tool_sherpa\\form\\placement_form', getString('editplacement', 'tool_sherpa'),
                {id: placementEdit.dataset.placementId});
            return;
        }

        const placementDelete = event.target.closest(SELECTORS.placementDelete);
        if (placementDelete) {
            event.preventDefault();
            confirmDelete(placementDelete, 'tool_sherpa_placement_delete', placementDelete.dataset.placementId,
                getString('deleteplacement', 'tool_sherpa'),
                getString('deleteplacementconfirm', 'tool_sherpa', placementDelete.dataset.placementName),
                getString('placementdeleted', 'tool_sherpa'));
            return;
        }

        const mappingAdd = event.target.closest(SELECTORS.mappingAdd);
        if (mappingAdd) {
            event.preventDefault();
            if (mappingAdd.dataset.sourceId) {
                openModal(mappingAdd, 'tool_sherpa\\form\\mapping_form', getString('linkplacement', 'tool_sherpa'),
                    {sourceid: mappingAdd.dataset.sourceId});
            } else {
                openModal(mappingAdd, 'tool_sherpa\\form\\mapping_form', getString('linksource', 'tool_sherpa'),
                    {placementid: mappingAdd.dataset.placementId});
            }
            return;
        }

        const mappingDelete = event.target.closest(SELECTORS.mappingDelete);
        if (mappingDelete) {
            event.preventDefault();
            confirmDelete(mappingDelete, 'tool_sherpa_mapping_delete', mappingDelete.dataset.mappingId,
                getString('unlink', 'tool_sherpa'),
                getString('unlinkconfirm', 'tool_sherpa', mappingDelete.dataset.mappingLabel),
                getString('unlink', 'tool_sherpa'));
        }
    });
};
