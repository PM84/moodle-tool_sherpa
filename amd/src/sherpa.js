// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
/**
 * JavaScript for handling tour creation via button click.
 *
 * @module tool_sherpa/sherpa
 * @copyright 2025 Your Name <your.email@example.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    'jquery',
    'core/ajax',
    'core/str',
    'tool_sherpa/events',
    'tool_sherpa/repository',
    'core/templates',
    'tool_sherpa/tour'
], function($, Ajax, Str, UserTourEvents, tourRepository, Templates, BootstrapTour) {

        // JSON object that holds the information for the tour that is being sent to an endpoint when the teacher hits save
        let tourObject = {
            steps: [
                /*
                template step object
                {
                    title: '',
                    content: '',
                    targettype: '2',
                    targetvalue: '',
                    placement: '',
                    orphan: 'true',
                    backdrop: '',
                    reflex: 'false',
                    config: '',
                } */
            ],
            name: '',
            description: '',
            pathmatch: '',
            enabled: '',
            filter_values: '',
            sortorder: '',
        };

        let stickyTarget = null;
        let sticky = false;

        let currentStepObject = {};

        // Tour queue state for consecutive tour triggering
        let tourQueue = [];           // Array of {id, type, name, sortorder, placementid}
        let currentTourIndex = 0;     // Index of current/next tour in queue
        let shownTourIds = [];        // Array of tour keys already shown (e.g., 'standard_123', 'custom_456')
        let currentCourseId = null;   // Store course ID for queue operations
        let tourEndedListenerAdded = false; // Flag to prevent duplicate listeners
        let manualTourInProgress = false; // Flag to prevent queue auto-start during manual tour

        // Init the tourobject and starts the editor
        const init = function (courseid, customTours) {
            currentCourseId = courseid;
            init_styles();
            initializeEventBindings();
            Object.values(customTours).forEach(tour => {
                // Only show placement buttons for enabled tours
                if (tour.enabled) {
                    setPlacements(tour.placementid, tour.id);
                }
            });
            resetTourObject(courseid);

            // Initialize consecutive tour system
            initTourQueue(courseid);
            setupTourEndedListener();
        };

        /**
         * Initialize the tour queue by fetching pending tours from the server.
         *
         * @param {number} courseid - The course ID
         */
        const initTourQueue = function (courseid) {
            Ajax.call([{
                methodname: 'tool_sherpa_get_pending_tours',
                args: {
                    courseid: courseid,
                    shownids: JSON.stringify(shownTourIds)
                }
            }])[0].done(function (tours) {
                tourQueue = tours;
                currentTourIndex = 0;
            }).fail(function (error) {
                window.console.error('Teacher Tours: Failed to fetch pending tours', error);
                tourQueue = [];
            });
        };

        /**
         * Setup the tour ended event listener for consecutive tour triggering.
         */
        const setupTourEndedListener = function () {
            if (tourEndedListenerAdded) {
                return;
            }
            tourEndedListenerAdded = true;

            document.addEventListener(UserTourEvents.eventTypes.tourEnded, handleTourEnded);
        };

        /**
         * Handle the tour ended event - start the next tour if available.
         *
         * @param {Event} e - The tour ended event
         */
        const handleTourEnded = function (e) {
            // Get the tour that just ended
            const endedTour = e.detail?.tour;

            if (endedTour) {
                // Mark the ended tour as shown
                const endedTourId = endedTour.tourId || endedTour.id;
                if (endedTourId) {
                    // Check if this was a standard or custom tour
                    const config = endedTour.originalConfig || {};
                    if (config.custom_tour_id) {
                        shownTourIds.push('custom_' + config.custom_tour_id);
                    } else {
                        shownTourIds.push('standard_' + endedTourId);
                    }
                }
            }

            // If this was a manually triggered tour, don't auto-start the next one
            if (manualTourInProgress) {
                manualTourInProgress = false;
                return;
            }

            // Small delay to let UI settle before starting next tour
            setTimeout(function () {
                startNextTour();
            }, 500);
        };

        /**
         * Start the next tour in the queue.
         */
        const startNextTour = function () {
            // Find the next tour that hasn't been shown
            while (currentTourIndex < tourQueue.length) {
                const nextTour = tourQueue[currentTourIndex];
                const tourKey = nextTour.type + '_' + nextTour.id;

                currentTourIndex++;

                // Skip if already shown
                if (shownTourIds.includes(tourKey)) {
                    continue;
                }

                // Mark as shown
                shownTourIds.push(tourKey);

                if (nextTour.type === 'custom') {
                    // Start custom tour via our API
                    startCustomTourFromQueue(nextTour.id);
                } else {
                    // Start standard tour using Moodle's tour repository
                    startStandardTourFromQueue(nextTour.id);
                }
                return;
            }

            // No more tours - refresh the queue for next time
            if (currentCourseId) {
                initTourQueue(currentCourseId);
            }
        };

        /**
         * Start a standard tour from the queue.
         *
         * @param {number} tourId - The tour ID
         */
        const startStandardTourFromQueue = function (tourId) {
            // Fetch the tour configuration and start it
            tourRepository.fetchTour(tourId)
                .then(function (response) {
                    if (response && response.hasOwnProperty('tourconfig')) {
                        return Templates.renderForPromise('tool_usertours/tourstep', response.tourconfig)
                            .then(function (result) {
                                startTourWithConfig(tourId, result.html, response.tourconfig);
                                return;
                            });
                    }
                    // No tour config, try next tour
                    window.console.warn('Teacher Tours: No tour config in response, trying next tour');
                    startNextTour();
                    return;
                })
                .catch(function (error) {
                    // If this tour fails, try the next one
                    window.console.error('Teacher Tours: Error fetching/starting tour:', error);
                    startNextTour();
                });
        };

        /**
         * Start a tour with the given configuration.
         *
         * @param {number} tourId - The tour ID
         * @param {string} template - The rendered template HTML
         * @param {object} tourConfig - The tour configuration
         */
        const startTourWithConfig = function (tourId, template, tourConfig) {
            // Sort out the tour name
            tourConfig.tourName = tourConfig.name;
            delete tourConfig.name;

            // Add the template to the configuration
            tourConfig.template = template;

            // Process steps
            tourConfig.steps = tourConfig.steps.map(function (step) {
                if (typeof step.element !== 'undefined') {
                    step.target = step.element;
                    delete step.element;
                }

                if (typeof step.reflex !== 'undefined') {
                    step.moveOnClick = !!step.reflex;
                    delete step.reflex;
                }

                if (typeof step.content !== 'undefined') {
                    step.body = step.content;
                    delete step.content;
                }

                return step;
            });

            // Create and start the tour
            const tour = new BootstrapTour(tourConfig);
            tour.startTour(0);
        };

        /**
         * Start a custom tour from the queue.
         *
         * @param {number} customTourId - The custom tour ID
         */
        const startCustomTourFromQueue = function (customTourId) {
            Ajax.call([{
                methodname: 'tool_sherpa_start_custom_tour',
                args: { customtourid: customTourId }
            }])[0].done(function (response) {
                if (response.success && response.tourid) {
                    // Fetch and start the newly created core tour
                    startStandardTourFromQueue(response.tourid);
                } else {
                    // If creation failed, try the next tour
                    window.console.warn('Teacher Tours: Failed to start custom tour', response.message);
                    startNextTour();
                }
            }).fail(function (error) {
                window.console.error('Teacher Tours: AJAX error starting custom tour', error);
                // If AJAX fails, try the next tour
                startNextTour();
            });
        };

        // Starts the picker at first
        const startEditor = function () {
            // Hide the add sherpa placement button when editing
            $('#start-tour-creation').hide();
            $('#start-sticky-creation').hide();
            $('#step-creation').hide();
            // Show the tour editor interface
            $('#tour-editor').show();
            if (sticky) {
                highlightPlacements();
            } else {
                highlightElements();
            }
        };

        // Show current step indicator
        const showCurrentStepIndicator = function (elementText) {
            $('#current-step-element').text(elementText);
            $('#current-step-indicator').show();
        };

        // Show current step indicator
        const showTourStepsPreview = function () {
            $('.tour-preview').html('');
            tourObject.steps.forEach((step, index) => {
                $('.tour-preview')
                    .append('<div class="tour-step-preview" data-step-index="' + index + '">Step ' + (index + 1) +
                        ' <strong> ' + step.targetvalue + ':</strong> ' + step.title +
                        ' <i class="fa fa-pencil edit-step-icon" style="float: right; cursor: pointer; margin-left: 10px;">' +
                        '</i></div>');
            });
            $('.tour-preview').show();
        };

        // Hide current step indicator
        const hideCurrentStepIndicator = function () {
            $('#current-step-indicator').hide();
        };

        const resetCurrentStepObject = function () {
            currentStepObject = {};
        };

        // Handle tour toggle switches
        const handleTourToggle = function (tourId, enabled, tourType) {
            // Determine which endpoint to call based on tour type
            const methodname = tourType === 'custom'
                ? 'tool_sherpa_toggle_custom_tour_enabled'
                : 'tool_sherpa_toggle_tour_enabled';

            // Make AJAX call to backend to save the state
            Ajax.call([{
                methodname: methodname,
                args: { tourid: tourId, enabled: enabled }
            }])[0].done(function (response) {
                if (response.success) {
                    // Update the UI based on the actual state from server.
                    const tourCard = $(`[data-tour-id="${tourId}"][data-tour-type="${tourType}"]`);
                    const statusElement = tourCard.find('.tour-status');

                    if (response.enabled) {
                        Str.get_string('enabled', 'tool_sherpa')
                            .then(function (enabledText) {
                                statusElement.html('<i class="fa fa-check-circle text-success"></i> ' + enabledText);
                            });
                        tourCard.find('.tour-toggle').prop('checked', true);
                    } else {
                        Str.get_string('disabled', 'tool_sherpa')
                            .then(function (disabledText) {
                                statusElement.html('<i class="fa fa-times-circle text-muted"></i> ' + disabledText);
                            });
                        tourCard.find('.tour-toggle').prop('checked', false);
                    }
                } else {
                    // Revert the toggle if the operation failed
                    const tourCard = $(`[data-tour-id="${tourId}"][data-tour-type="${tourType}"]`);
                    tourCard.find('.tour-toggle').prop('checked', !enabled);
                    alert('Failed to update tour status. Please try again.');
                }
            }).fail(function () {
                // Revert the toggle on error
                const tourCard = $(`[data-tour-id="${tourId}"][data-tour-type="${tourType}"]`);
                tourCard.find('.tour-toggle').prop('checked', !enabled);
                alert('Error updating tour status. Please try again.');
            });
        };

        // Handle tour editing
        const handleTourEdit = function (tourId, tourType) {
            // TODO: Should open the form in the backend to edit the tour
            const tourTypeLabel = tourType === 'custom' ? 'Custom' : 'Standard';
            alert('Edit functionality will be implemented when backend is ready. ' + tourTypeLabel + ' Tour ID: ' + tourId);
        };

        // Handle tour deletion
        const handleTourDelete = function (tourId, tourType) {
            if (confirm('Are you sure you want to delete this tour? This action cannot be undone.')) {
                // Determine which endpoint to call based on tour type
                const methodname = tourType === 'custom'
                    ? 'tool_sherpa_delete_custom_tour'
                    : 'tool_sherpa_delete_tour';

                // Make AJAX call to backend to delete the tour
                Ajax.call([{
                    methodname: methodname,
                    args: { tourid: tourId }
                }])[0].done(function (response) {
                    if (response.success) {
                        // Remove the card from UI with animation
                        $(`[data-tour-id="${tourId}"][data-tour-type="${tourType}"]`).fadeOut(300, function () {
                            $(this).remove();
                            // Check if no standard tours left
                            if ($('.existing-tours .tour-card').length === 0) {
                                $('.existing-tours').hide();
                            }
                            // Check if no custom tours left
                            if ($('.existing-custom-tours .tour-card').length === 0) {
                                $('.existing-custom-tours').hide();
                            }
                        });
                    } else {
                        alert('Failed to delete tour. Please try again.');
                    }
                }).fail(function () {
                    alert('Error deleting tour. Please try again.');
                });
            }
        };

        // Initialise style class for highlights
        const hexToRgba = function (hex, opacity) {
            // Remove '#' if present
            hex = hex.replace(/^#/, '');

            // Parse r, g, b values
            let r = parseInt(hex.substring(0, 2), 16);
            let g = parseInt(hex.substring(2, 4), 16);
            let b = parseInt(hex.substring(4, 6), 16);

            return `rgba(${r}, ${g}, ${b}, ${opacity})`;
        };

        // Highlight the elements
        const highlightElements = function () {
            // Highlight the sections in light green
            // Highlight the mods in blue
            document.querySelectorAll('[id^="section-"]').forEach(section => {
                section.classList.add('section-highlight');
                section.addEventListener('click', tourEventHandlers.sectionClick);
            });
            document.querySelectorAll('[id^="module-"]').forEach(mod => {
                mod.classList.add('module-highlight');
                mod.addEventListener('click', tourEventHandlers.moduleClick);
            });
        };

        const setPlacements = function (placementid, customtourid) {
            Str.get_string('touravailable', 'tool_sherpa').then(function (text) {
                if (placementid.startsWith('section-')) {
                    document.querySelectorAll('[id="' + placementid + '"]').forEach(section => {
                        const button = document.createElement('button');
                        button.dataset.customtourid = customtourid;
                        button.className = 'btn btn-sm btn-outline-primary section-sticky-button';
                        button.textContent = text + ' ';
                        const icon = document.createElement('i');
                        icon.className = 'fa fa-question-circle';
                        button.append(icon);
                        section.style.position = 'relative';
                        section.prepend(button);
                        button.addEventListener('click', tourEventHandlers.stickyStartClick);
                    });
                } else if (placementid === 'page-header') {
                    document.querySelectorAll('[id="page-header"]').forEach(header => {
                        const button = document.createElement('button');
                        button.dataset.customtourid = customtourid;
                        button.className = 'btn btn-sm btn-outline-primary header-sticky-button';
                        button.textContent = text + ' ';
                        const icon = document.createElement('i');
                        icon.className = 'fa fa-question-circle';
                        button.append(icon);
                        header.style.position = 'relative';
                        header.prepend(button);
                        button.addEventListener('click', tourEventHandlers.stickyStartClick);
                    });
                }
            });
        };

        // Highlight the elements
        const highlightPlacements = function () {
            // TODO consider case of multiple tours on one element
            // "Highlight" the section placements and the course header placement
            // onclick remove pseudo elements and handle click in differenct function
            Str.get_string('selectplacement', 'tool_sherpa').then(function (text) {
                document.querySelectorAll('[id^="section-"]').forEach(section => {
                    const button = document.createElement('button');
                    button.className = 'btn btn-sm btn-outline-primary section-sticky-highlight';
                    button.textContent = text + ' ';
                    const icon = document.createElement('i');
                    icon.className = 'fa fa-question-circle';
                    button.append(icon);
                    section.style.position = 'relative';
                    section.prepend(button);
                    button.addEventListener('click', tourEventHandlers.stickySelectClick);
                });
                document.querySelectorAll('[id="page-header"]').forEach(mod => {
                    const button = document.createElement('button');
                    button.className = 'btn btn-sm btn-outline-primary header-sticky-highlight';
                    button.textContent = text + ' ';
                    const icon = document.createElement('i');
                    icon.className = 'fa fa-question-circle';
                    button.append(icon);
                    mod.style.position = 'relative';
                    mod.prepend(button);
                    button.addEventListener('click', tourEventHandlers.stickySelectClick);
                });
            });
        };

        // The second step on creating a step for the tour is creating a text that should show when the step is active
        const startTextEditor = function (editIndex = null) {
            $('#text-editor').show();
            if (editIndex === null) {
                clearTextEditor();
            }
            $('#save-tour').hide();
            $('#step-title').focus();

            // Store edit index for later use
            $('#text-editor').data('edit-index', editIndex);
        };

        const clearTextEditor = function () {
            $('#step-title').val('');
            $('#step-content').val('');
            // $('#step-placement').val('right');
            // $('#step-backdrop').prop('checked', true);
            // $('#step-orphan').prop('checked', false);
            // $('#step-reflex').prop('checked', false);
        };

        const removeHighlighting = function () {
            if (sticky) {
                document.querySelectorAll('.section-sticky-highlight:not(.section-sticky-button-temp)').forEach(section => {
                    section.remove();
                });
                document.querySelectorAll('.header-sticky-highlight:not(.header-sticky-button-temp)').forEach(mod => {
                    mod.remove();
                });
            } else {
                // Remove the highlighting from the elements and their specific event listeners
                document.querySelectorAll('[id^="section-"]').forEach(section => {
                    section.classList.remove('section-highlight');
                    section.removeEventListener('click', tourEventHandlers.sectionClick);
                });
                document.querySelectorAll('[id^="module-"]').forEach(mod => {
                    mod.classList.remove('module-highlight');
                    mod.removeEventListener('click', tourEventHandlers.moduleClick);
                });
            }
        };

        // Send the placement Object to the endpoint
        const savePlacement = function () {
            $('#save-placement').prop('disabled', true).text('Saving...');
            // Send the placement Object to the endpoint
            let argsObj = {};
            if (stickyTarget) {
                placementObject.placementid = stickyTarget;
                placementObject.custom = true;
                argsObj = { tour: placementObject };
            } else {
                placementObject.custom = false;
                argsObj = { tour: placementObject };
            }
            Ajax.call([{
                methodname: 'tool_sherpa_save_placement',
                args: argsObj,
            }])[0].then(function (response) {
                //If ok reset the placementObject, if not show error
                if (!response && !response.status === 'ok') {
                    alert('Error saving tour: ' + (response.message || 'Unknown error'));
                }
                resetTourObject();

                $('#tour-editor').hide();
                $('#start-tour-creation').show();
                $('#start-sticky-creation').show();
                $('#step-creation').show();
                hideCurrentStepIndicator();
                clearTextEditor();
                removeHighlighting();
                $('.tour-preview').html('');
                $('.tour-preview').hide();
                Str.get_string('savetour', 'tool_sherpa')
                    .then(function (text) {
                        $('#save-tour').prop('disabled', false).html('<i class="fa fa-save"></i> ' + text);
                    });
            });
        };

        // Initialize event bindings
        const initializeEventBindings = function () {
            // Bind click event to start tour creation button
            $(document).on('click', '#start-tour-creation', function (e) {
                e.preventDefault();
                startEditor();
            });

            $(document).on('click', '#start-sticky-creation', function (e) {
                e.preventDefault();
                sticky = true;
                startEditor();
            });


            // Bind click event to save tour button
            $(document).on('click', '#save-tour', function (e) {
                e.preventDefault();
                saveTour();
            });

            // Bind click event to save step button
            $(document).on('click', '#save-step', function (e) {
                e.preventDefault();
                saveStep();
                // Hide the text editor
                $('#text-editor').hide();
                $('#save-tour').show();
                // Reset for next step
                showTourStepsPreview();
                hideCurrentStepIndicator();
                resetCurrentStepObject();
                // Restart the editor to pick the next element
                startEditor();
            });

            // Bind click event to edit step icon
            $(document).on('click', '.edit-step-icon', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const stepIndex = $(this).parent().data('step-index');
                editStep(stepIndex);
            });

            // Bind click event to cancel step edit button
            $(document).on('click', '#cancel-step-edit', function (e) {
                e.preventDefault();
                hideCurrentStepIndicator();
                resetCurrentStepObject();
                $('#text-editor').hide();
                startEditor();
            });

            // Bind click event to cancel tour creation button
            $(document).on('click', '#cancel-tour-creation', function (e) {
                e.preventDefault();
                $('#tour-editor').hide();
                $('#start-sticky-creation').show();
                $('#start-tour-creation').show();
                hideCurrentStepIndicator();
                removeHighlighting();
                removeStickyButton();
                resetTourObject();
                $('.tour-preview').html('');
                $('.tour-preview').hide();
            });

            $(document).on('click', '#step-creation', function () {
                startEditor();
            });

            // Bind events for tour management
            $(document).on('change', '.tour-toggle', function () {
                const tourId = $(this).data('tour-id');
                const tourType = $(this).data('tour-type') || 'standard';
                const enabled = $(this).is(':checked');
                handleTourToggle(tourId, enabled, tourType);
            });

            $(document).on('click', '.edit-tour', function (e) {
                e.preventDefault();
                const tourId = $(this).data('tour-id');
                const tourType = $(this).data('tour-type') || 'standard';
                handleTourEdit(tourId, tourType);
            });

            $(document).on('click', '.delete-tour', function (e) {
                e.preventDefault();
                const tourId = $(this).data('tour-id');
                const tourType = $(this).data('tour-type') || 'standard';
                handleTourDelete(tourId, tourType);
            });
        };

        // Return public API
        return {
            sticky: sticky,
            currentStepObject: currentStepObject,
            tourObject: tourObject,
            tourQueue: tourQueue,
            shownTourIds: shownTourIds,
            init: init,
            startEditor: startEditor,
            savePlacement: savePlacement,
            startTextEditor: startTextEditor,
            highlightElements: highlightElements,
            initializeEventBindings: initializeEventBindings,
        };
    }
);
