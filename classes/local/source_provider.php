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

use tool_sherpa\placement;

/**
 * Resolves the support sources mapped to a placement.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class source_provider {
    /**
     * Get the sources mapped to the langstring placement of a help icon.
     *
     * The placement value uses the typical Moodle langstring syntax "identifier,component"
     * (cf. get_string($identifier, $component)).
     *
     * @param string $component the help string component
     * @param string $identifier the help string identifier
     * @return array list of source records (id, url), empty if materials are disabled or none mapped
     */
    public static function get_sources_for_help(string $component, string $identifier): array {
        global $DB;

        if (!get_config('tool_sherpa', 'showmaterials')) {
            return [];
        }

        $value = $identifier . ',' . $component;
        $sql = "SELECT s.id, s.url
                  FROM {tool_sherpa_source} s
                  JOIN {tool_sherpa_mapping} m ON m.source = s.id
                  JOIN {tool_sherpa_placement} p ON p.id = m.placement
                 WHERE p.type = :type AND p.value = :value
              ORDER BY s.id ASC";

        return $DB->get_records_sql($sql, [
            'type' => placement::TYPE_LANGSTRING,
            'value' => $value,
        ]);
    }
}
