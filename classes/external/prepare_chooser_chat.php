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

namespace tool_sherpa\external;

use core\context\course as context_course;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use tool_sherpa\local\chooser_chat_prompt_builder;
use tool_sherpa\local\material_provider;
use tool_sherpa\local\support_manager;
use tool_sherpa\local\system_prompt_builder;

/**
 * Prepares the help chooser chat.
 *
 * Stores the conversational system prompt (carrying the available activities as context) so the
 * local_ai_manager before_request hook injects it into the embedded chat, and returns the chat
 * context, its availability and the mocked supporting materials for each available activity.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class prepare_chooser_chat extends external_api {
    /**
     * Describe the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id the chooser was opened in'),
            'activities' => new external_multiple_structure(
                new external_single_structure([
                    'modname' => new external_value(PARAM_ALPHANUMEXT, 'Activity module name'),
                    'title' => new external_value(PARAM_TEXT, 'Activity display name'),
                ]),
                'The activities available in the chooser'
            ),
        ]);
    }

    /**
     * Store the chat system prompt and return the chat context and materials.
     *
     * @param int $courseid the course id
     * @param array $activities the available activities
     * @return array the chat preparation payload
     */
    public static function execute(int $courseid, array $activities): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'activities' => $activities,
        ]);

        $context = context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('moodle/course:manageactivities', $context);
        require_capability('tool/sherpa:usesupport', $context);

        $chatavailable = support_manager::chat_available($context);
        if ($chatavailable) {
            $prompt = chooser_chat_prompt_builder::build($params['activities']);
            system_prompt_builder::store($USER->id, $context, $prompt);
        }

        $materials = [];
        foreach ($params['activities'] as $activity) {
            $activitymaterials = material_provider::get_materials($activity['modname']);
            $materials[] = [
                'modname' => $activity['modname'],
                'tutorials' => $activitymaterials['tutorials'],
                'templates' => $activitymaterials['templates'],
            ];
        }

        return [
            'contextid' => $context->id,
            'chatavailable' => $chatavailable,
            'materials' => $materials,
        ];
    }

    /**
     * Describe the return value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'contextid' => new external_value(PARAM_INT, 'Context id to embed the chat in'),
            'chatavailable' => new external_value(PARAM_BOOL, 'Whether the AI chat is available for the user'),
            'materials' => new external_multiple_structure(new external_single_structure([
                'modname' => new external_value(PARAM_ALPHANUMEXT, 'Activity module name'),
                'tutorials' => new external_multiple_structure(new external_single_structure([
                    'label' => new external_value(PARAM_TEXT, 'Tutorial label'),
                    'url' => new external_value(PARAM_URL, 'Tutorial URL'),
                ])),
                'templates' => new external_multiple_structure(new external_single_structure([
                    'title' => new external_value(PARAM_TEXT, 'Template title'),
                    'summary' => new external_value(PARAM_TEXT, 'Template summary'),
                    'image' => new external_value(PARAM_URL, 'Template image URL', VALUE_OPTIONAL, ''),
                ])),
            ])),
        ]);
    }
}
