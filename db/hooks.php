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
    // Inject an "Ask Sherpa" AI support action next to core help icons (Help-Fragezeichen).
    [
        'hook' => \core\hook\output\before_help_icon_rendered::class,
        'callback' => \tool_sherpa\local\hook_callbacks::class . '::handle_before_help_icon_rendered',
    ],
    // React to language pack updates (Languagestring) by purging cached support answers.
    [
        'hook' => \tool_langimport\hook\after_langpacks_updated::class,
        'callback' => \tool_sherpa\local\hook_callbacks::class . '::handle_after_langpacks_updated',
    ],
    // Declare which AI purposes this plugin uses so local_ai_manager statistics are correct.
    [
        'hook' => \local_ai_manager\hook\purpose_usage::class,
        'callback' => \tool_sherpa\local\hook_callbacks::class . '::handle_purpose_usage',
    ],
];
