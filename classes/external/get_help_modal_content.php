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
 * External function returning the lazy loaded content for a help modal.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_help_modal_content extends external_api {
    /**
     * Describe the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'component' => new external_value(PARAM_COMPONENT, 'Frankenstyle component of the help string'),
            'identifier' => new external_value(PARAM_RAW, 'Help string identifier'),
            'contextid' => new external_value(PARAM_INT, 'Context id of the current page'),
        ]);
    }

    /**
     * Return the modal content and store the chat system prompt.
     *
     * @param string $component the help string component
     * @param string $identifier the help string identifier
     * @param int $contextid the context id of the current page
     * @return array the modal payload
     */
    public static function execute(string $component, string $identifier, int $contextid): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'component' => $component,
            'identifier' => $identifier,
            'contextid' => $contextid,
        ]);

        $context = \core\context_helper::instance_by_id($params['contextid']);
        self::validate_context($context);
        require_capability('tool/sherpa:usesupport', $context);

        // Make sure the identifier really is a help string and not arbitrary input.
        if (!get_string_manager()->string_exists($params['identifier'], $params['component'])) {
            throw new \invalid_parameter_exception('Unknown help identifier');
        }

        return help_modal_manager::get_modal_payload($params['component'], $params['identifier'], $context);
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
