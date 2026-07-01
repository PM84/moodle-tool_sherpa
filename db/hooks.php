<?php
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
 * Hook callback registrations for tool_sherpa.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$callbacks = [
    // Replace the core help icon popover with the Sherpa help modal trigger.
    [
        'hook' => \core\hook\output\before_help_icon_rendered::class,
        'callback' => \tool_sherpa\local\hook_callbacks::class . '::handle_before_help_icon_rendered',
    ],
    // Inject the field specific system prompt into the AI chat request (no persona needed).
    [
        'hook' => \local_ai_manager\hook\before_request::class,
        'callback' => \tool_sherpa\local\hook_callbacks::class . '::inject_system_prompt',
        'priority' => 500,
    ],
    // Add the "Help, what should I do?" entry to the activity chooser button dropdown.
    [
        'hook' => \core_course\hook\before_activitychooserbutton_exported::class,
        'callback' => \tool_sherpa\local\callbacks\before_activitychooserbutton_exported_handler::class . '::callback',
    ],
];
