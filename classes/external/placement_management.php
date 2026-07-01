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
 * External API for sherpa placement management.
 * @package    tool_sherpa
 * @copyright  2026 MoodleMootDACH Team 8 - The one and only
 * @author     Dr. Peter Mayer
 * @author     Melanie Treitinger
 * @author     Kathleen Aermes
 * @author     Nikolai Jahreis
 * @author     Alexander Bias
 * @author     Joscha Sauerland
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_sherpa\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;

/**
 * External API class for sherpa placement management.
 * @package    tool_sherpa
 * @copyright  2026 MoodleMootDACH Team 8 - The one and only
 * @author     Dr. Peter Mayer
 * @author     Melanie Treitinger
 * @author     Kathleen Aermes
 * @author     Nikolai Jahreis
 * @author     Alexander Bias
 * @author     Joscha Sauerland
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class placement_management extends external_api {
    /**
     * Parameters for sherpa_placement.
     *
     * @return external_function_parameters
     */
    public static function sherpa_placement_parameters(): external_function_parameters {
        return new external_function_parameters([
            'placement' => new external_single_structure([
                    'type' => new external_value(PARAM_TEXT, 'Placement type'),
                    'value' => new external_value(PARAM_TEXT, 'Placement type identifier value'),
            ]),
        ]);
    }

    /**
     * Save a placement (create or update).
     *
     * @param array $placement Placement data from frontend
     *
     * @return array Result
     */
    public static function save_placement(array $placement): array {
        global $DB, $CFG;

        $params = self::validate_parameters(self::save_placement_parameters(), [
            'placement' => $placement,
        ]);

        $placementdata = $params['placement'];

        // Prepare tour data for database.
        $placementsave = new \stdClass();
        $placementsave->type = $placementdata['type'];
        $placementsave->value = $placementdata['value'];

        // Insert placement into database.
        $placementid = $DB->insert_record('tool_sherpa_placement', $placementsave);

        if (!$placementid) {
            return [
                'success' => false,
                'placementid' => 0,
                'message' => 'Failed to create placement',
            ];
        }

        return [
            'success' => true,
            'placementid' => $placementid,
            'message' => 'Placement created successfully',
        ];
    }

    /**
     * Return definition for save_placement.
     *
     * @return external_single_structure
     */
    public static function save_tour_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'placementid' => new external_value(PARAM_INT, 'Placement ID'),
            'message' => new external_value(PARAM_TEXT, 'Response message'),
        ]);
    }

    /**
     * Parameters for get_placement.
     *
     * @return external_function_parameters
     */
    public static function get_placement_parameters(): external_function_parameters {
        return new external_function_parameters([
            'placementid' => new external_value(PARAM_INT, 'Placement ID'),
        ]);
    }

    /**
     * Get a placement by ID.
     *
     * @param int $placementid Placement ID
     *
     * @return array Placement data
     */
    public static function get_placment(int $placementid): array {
        global $DB;
        $params = self::validate_parameters(self::get_tour_parameters(), [
            'placementid' => $placementid,
        ]);

        $placement = $DB->get_record('tool_sherpa_placement', ['id' => $placementid]);
        if (!$placement) {
            throw new \moodle_exception('placementnotfound', 'tool_sherpa');
        }

        return [
            'id' => $placement['id'],
            'type' => $placement['type'],
            'value' => $placement['value'],
        ];
    }

    /**
     * Return definition for get_placment.
     *
     * @return external_single_structure
     */
    public static function get_placment_returns(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Placement ID'),
            'type' => new external_value(PARAM_INT, 'Placement type'),
            'value' => new external_value(PARAM_TEXT, 'Placement value'),
        ]);
    }
}
/*     
     * Get all tours for a course.
     *
     * @param int $courseid     Course ID
     * @param bool $enabledonly Only return enabled tours
     *
     * @return array Tours data
     
    public static function get_course_tours(int $courseid, bool $enabledonly = false): array
    {

        $params = self::validate_parameters(self::get_course_tours_parameters(), [
            'courseid' => $courseid,
            'enabledonly' => $enabledonly,
        ]);

        // Check course context.
        $context = context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('moodle/course:manageactivities', $context);

        $tours = manager::get_course_tours($params['courseid'], $params['enabledonly']);

        $result = [];
        foreach ($tours as $tour) {
            $placementdata = manager::format_tour_for_frontend($tour);
            $result[] = [
                'id' => $placementdata['id'],
                'courseid' => $placementdata['courseid'],
                'name' => $placementdata['name'],
                'description' => $placementdata['description'],
                'steps' => json_encode($placementdata['steps']),
                'enabled' => $placementdata['enabled'],
            ];
        }

        return $result;
    } */

    /**
     * Return definition for get_course_tours.
     *
     * @return external_multiple_structure
     
    public static function get_course_tours_returns(): external_multiple_structure
    {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Tour ID'),
                'courseid' => new external_value(PARAM_INT, 'Course ID'),
                'name' => new external_value(PARAM_TEXT, 'Tour name'),
                'description' => new external_value(PARAM_TEXT, 'Tour description'),
                'steps' => new external_value(PARAM_RAW, 'JSON encoded steps'),
                'enabled' => new external_value(PARAM_BOOL, 'Enabled status'),
            ])
        );
    }*/

    /**
     * Parameters for toggle_tour_enabled.
     *
     * @return external_function_parameters
     
    public static function toggle_tour_enabled_parameters(): external_function_parameters
    {
        return new external_function_parameters([
            'tourid' => new external_value(PARAM_INT, 'Tour ID'),
            'enabled' => new external_value(PARAM_BOOL, 'Enabled status'),
        ]);
    }

    /**
     * Toggle tour enabled/disabled status.
     *
     * @param int $tourid   Tour ID
     * @param bool $enabled Whether tour should be enabled
     *
     * @return array Result with success status
     
    public static function toggle_tour_enabled(int $tourid, bool $enabled): array
    {
        $params = self::validate_parameters(self::toggle_tour_enabled_parameters(), [
            'tourid' => $tourid,
            'enabled' => $enabled,
        ]);

        // Get the tour to check permissions
        $tour = manager::get_tour($params['tourid']);
        if (!$tour) {
            throw new \moodle_exception('tournotfound', 'block_teacher_tours');
        }

        // Get tour data for context check
        $placementdata = manager::format_tour_for_frontend($tour);

        // Check course context.
        $context = context_course::instance($placementdata['courseid']);
        self::validate_context($context);
        require_capability('moodle/course:manageactivities', $context);

        // Toggle the enabled status
        $success = manager::set_tour_enabled($params['tourid'], $params['enabled']);

        return [
            'success' => $success,
            'enabled' => $params['enabled'],
        ];
    }

    /**
     * Return definition for toggle_tour_enabled.
     *
     * @return external_single_structure
     
    public static function toggle_tour_enabled_returns(): external_single_structure
    {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'enabled' => new external_value(PARAM_BOOL, 'New enabled status'),
        ]);
    }
*/