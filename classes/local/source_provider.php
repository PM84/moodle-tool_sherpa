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
        $sql = "SELECT s.id, s.title, s.url
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

    /**
     * Get the sources mapped to the body id placements matching the current page.
     *
     * A body id placement value is either an exact body id (e.g. "page-login-index") or a pattern
     * containing a single "*" wildcard that matches any suffix (e.g. "page-course-view-*"). The
     * matching is performed in PHP to stay database independent.
     *
     * @param string $bodyid the HTML body id of the current page
     * @return array list of source records (id, url), empty if materials are disabled or none match
     */
    public static function get_sources_for_bodyid(string $bodyid): array {
        global $DB;

        if (!get_config('tool_sherpa', 'showmaterials')) {
            return [];
        }

        $bodyid = trim($bodyid);
        if ($bodyid === '') {
            return [];
        }

        $placements = $DB->get_records('tool_sherpa_placement', ['type' => placement::TYPE_BODYID], '', 'id, value');

        $matchingids = [];
        foreach ($placements as $placement) {
            if (self::bodyid_matches($bodyid, (string) $placement->value)) {
                $matchingids[] = (int) $placement->id;
            }
        }

        if (empty($matchingids)) {
            return [];
        }

        [$insql, $inparams] = $DB->get_in_or_equal($matchingids, SQL_PARAMS_NAMED);
        $sql = "SELECT DISTINCT s.id, s.title, s.url
                  FROM {tool_sherpa_source} s
                  JOIN {tool_sherpa_mapping} m ON m.source = s.id
                 WHERE m.placement {$insql}
              ORDER BY s.id ASC";

        return $DB->get_records_sql($sql, $inparams);
    }

    /**
     * Whether a concrete body id matches a body id placement pattern.
     *
     * The pattern may contain a single "*" wildcard standing for any (possibly empty) suffix.
     *
     * @param string $bodyid the concrete body id of the current page
     * @param string $pattern the placement value (exact body id or "*" pattern)
     * @return bool true if the pattern matches the body id
     */
    private static function bodyid_matches(string $bodyid, string $pattern): bool {
        $pattern = trim($pattern);
        if ($pattern === '') {
            return false;
        }

        if (!str_contains($pattern, '*')) {
            return $pattern === $bodyid;
        }

        $regex = '/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/';
        return (bool) preg_match($regex, $bodyid);
    }
}
