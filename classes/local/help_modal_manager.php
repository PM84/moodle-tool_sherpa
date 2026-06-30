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

namespace tool_sherpa\local;

use context;

/**
 * Aggregates the lazy loaded content for the help modal.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class help_modal_manager {
    /**
     * Build the modal payload (title, materials, chat availability) and store the chat system prompt.
     *
     * @param string $component the help string component
     * @param string $identifier the help string identifier
     * @param context $context the context of the current page
     * @return array the payload for the external function
     */
    public static function get_modal_payload(string $component, string $identifier, context $context): array {
        global $USER;

        $sources = [];
        foreach (source_provider::get_sources_for_help($component, $identifier) as $source) {
            $sources[] = ['url' => $source->url];
        }

        $chatavailable = support_manager::chat_available($context);
        if ($chatavailable) {
            // Build and store the field specific system prompt; the before_request hook injects it.
            $prompt = system_prompt_builder::build_for_help($component, $identifier, $context);
            system_prompt_builder::store($USER->id, $context, $prompt);
        }

        return [
            'title' => get_string($identifier, $component),
            'chatavailable' => $chatavailable,
            'sources' => $sources,
        ];
    }
}
