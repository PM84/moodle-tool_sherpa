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
 * External (AJAX) service definitions for tool_sherpa.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'tool_sherpa_source_delete' => [
        'classname'   => 'tool_sherpa\external\source_delete',
        'description' => 'Delete a Sherpa source and its mappings.',
        'type'        => 'write',
        'ajax'        => true,
    ],
    'tool_sherpa_placement_delete' => [
        'classname'   => 'tool_sherpa\external\placement_delete',
        'description' => 'Delete a Sherpa placement and its mappings.',
        'type'        => 'write',
        'ajax'        => true,
    ],
    'tool_sherpa_mapping_delete' => [
        'classname'   => 'tool_sherpa\external\mapping_delete',
        'description' => 'Remove a mapping between a Sherpa source and placement.',
        'type'        => 'write',
        'ajax'        => true,
    ],
    'tool_sherpa_get_help_modal_content' => [
        'classname'   => 'tool_sherpa\external\get_help_modal_content',
        'methodname'  => 'execute',
        'description' => 'Get the lazy loaded content (materials, chat availability) for a help modal '
            . 'and store the field specific chat system prompt.',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'tool/sherpa:usesupport',
    ],
    'tool_sherpa_prepare_chooser_chat' => [
        'classname'   => 'tool_sherpa\external\prepare_chooser_chat',
        'methodname'  => 'execute',
        'description' => 'Store the help chooser chat system prompt and return the chat context and materials.',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'tool/sherpa:usesupport',
    ],
];
