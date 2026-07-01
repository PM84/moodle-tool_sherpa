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

/**
 * Builds the conversational system prompt for the embedded help chooser chat.
 *
 * The prompt gives the block_ai_chat conversation its context: the activities that are actually
 * available in the chooser. It also instructs the assistant to append a machine-readable marker
 * listing the modnames it currently recommends. The client parses that marker from each reply to
 * update the filtered activity list live while the teacher and the assistant discuss ideas.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class chooser_chat_prompt_builder {
    /**
     * Build the conversational system prompt from the available activities.
     *
     * @param array $activities list of available activities, each ['modname' => string, 'title' => string]
     * @return string the assembled system prompt
     */
    public static function build(array $activities): string {
        $lines = [];
        foreach ($activities as $activity) {
            $modname = clean_param($activity['modname'] ?? '', PARAM_ALPHANUMEXT);
            if ($modname === '') {
                continue;
            }
            $title = trim((string) ($activity['title'] ?? $modname));
            $lines[] = '- ' . $modname . ': ' . $title;
        }

        return get_string('helpchooser_chatsystemprompt', 'tool_sherpa', (object) [
            'activities' => implode("\n", $lines),
        ]);
    }
}
