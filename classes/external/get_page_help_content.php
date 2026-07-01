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

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use tool_sherpa\local\help_modal_manager;

/**
 * External function returning the page level help content for a body id.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_page_help_content extends external_api {
    /**
     * Describe the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'bodyid' => new external_value(PARAM_RAW, 'HTML body id of the current page'),
            'contextid' => new external_value(PARAM_INT, 'Context id of the current page'),
        ]);
    }

    /**
     * Return the page help content and store the chat system prompt.
     *
     * @param string $bodyid the HTML body id of the current page
     * @param int $contextid the context id of the current page
     * @return array the modal payload
     */
    public static function execute(string $bodyid, int $contextid): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'bodyid' => $bodyid,
            'contextid' => $contextid,
        ]);

        $context = \core\context_helper::instance_by_id($params['contextid']);
        self::validate_context($context);
        require_capability('tool/sherpa:usesupport', $context);

        return help_modal_manager::get_page_help_payload($params['bodyid'], $context);
    }

    /**
     * Describe the return value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'title' => new external_value(PARAM_TEXT, 'Help title'),
            'chatavailable' => new external_value(PARAM_BOOL, 'Whether the chat region is available'),
            'sources' => new external_multiple_structure(new external_single_structure([
                'url' => new external_value(PARAM_URL, 'Source URL'),
            ])),
        ]);
    }
}
