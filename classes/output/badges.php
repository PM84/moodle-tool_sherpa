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

declare(strict_types=1);

namespace tool_sherpa\output;

use html_writer;
use moodle_url;

/**
 * Helpers to render the mapping columns (badges + link control) of the management tables.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class badges {

    /**
     * Render the placements mapped to the given source, plus a control to link another placement.
     *
     * @param int $sourceid
     * @return string
     */
    public static function placements_for_source(int $sourceid): string {
        global $DB, $PAGE;
        $output = $PAGE->get_renderer('core');

        $sql = "SELECT m.id AS mappingid, p.type, p.value
                  FROM {tool_sherpa_mapping} m
                  JOIN {tool_sherpa_placement} p ON p.id = m.placement
                 WHERE m.source = :sourceid
              ORDER BY p.type ASC, p.value ASC";
        $records = $DB->get_records_sql($sql, ['sourceid' => $sourceid]);

        $badges = '';
        foreach ($records as $record) {
            $badges .= $output->render_from_template('tool_sherpa/placement_badge', [
                'type' => get_string('type_' . $record->type, 'tool_sherpa'),
                'value' => $record->value,
                'unlink' => true,
                'mappingid' => $record->mappingid,
            ]);
        }

        $badges .= self::link_control(['data-action' => 'mapping-add', 'data-source-id' => $sourceid],
            get_string('linkplacement', 'tool_sherpa'));

        return html_writer::div($badges, 'tool_sherpa-mappings');
    }

    /**
     * Render the sources mapped to the given placement, plus a control to link another source.
     *
     * @param int $placementid
     * @return string
     */
    public static function sources_for_placement(int $placementid): string {
        global $DB, $PAGE;
        $output = $PAGE->get_renderer('core');

        $sql = "SELECT m.id AS mappingid, s.url
                  FROM {tool_sherpa_mapping} m
                  JOIN {tool_sherpa_source} s ON s.id = m.source
                 WHERE m.placement = :placementid
              ORDER BY s.url ASC";
        $records = $DB->get_records_sql($sql, ['placementid' => $placementid]);

        $badges = '';
        foreach ($records as $record) {
            $badges .= $output->render_from_template('tool_sherpa/source_badge', [
                'url' => $record->url,
                'unlink' => true,
                'mappingid' => $record->mappingid,
            ]);
        }

        $badges .= self::link_control(['data-action' => 'mapping-add', 'data-placement-id' => $placementid],
            get_string('linksource', 'tool_sherpa'));

        return html_writer::div($badges, 'tool_sherpa-mappings');
    }

    /**
     * Build the "+" control used to link a new mapping.
     *
     * @param array $attributes Data attributes carrying the anchor id and action.
     * @param string $label Accessible label / tooltip.
     * @return string
     */
    private static function link_control(array $attributes, string $label): string {
        global $PAGE;
        $output = $PAGE->get_renderer('core');

        $attributes += [
            'role' => 'button',
            'class' => 'tool_sherpa-mapping-add',
            'title' => $label,
            'aria-label' => $label,
        ];

        return html_writer::link(new moodle_url('#'), $output->pix_icon('t/add', $label), $attributes);
    }
}
