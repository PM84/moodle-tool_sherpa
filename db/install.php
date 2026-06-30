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
 * Install time setup for tool_sherpa.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Seed a few demo sources, langstring placements and mappings.
 *
 * This makes the "further materials" region of the help modal show content out of the box for a
 * handful of common course settings help icons. The placement value uses the typical Moodle
 * langstring syntax "identifier,component".
 */
function xmldb_tool_sherpa_install(): void {
    global $DB;

    // Demo sources (placeholder tutorial URLs plus the official docs).
    $sourceurls = [
        'https://docs.moodle.org/en/Course_settings',
        'https://example.org/sherpa/tutorials/course-settings',
        'https://example.org/sherpa/faq/course-settings',
    ];
    $sourceids = [];
    foreach ($sourceurls as $url) {
        $sourceids[] = $DB->insert_record('tool_sherpa_source', (object) ['url' => $url]);
    }

    // Langstring placements for some verified core course settings help icons.
    $placementvalues = [
        'idnumbercourse,moodle',
        'coursesummary,moodle',
        'coursevisibility,moodle',
        'fullnamecourse,moodle',
    ];
    foreach ($placementvalues as $value) {
        $placementid = $DB->insert_record('tool_sherpa_placement', (object) [
            'type' => \tool_sherpa\placement::TYPE_LANGSTRING,
            'value' => $value,
        ]);
        foreach ($sourceids as $sourceid) {
            $DB->insert_record('tool_sherpa_mapping', (object) [
                'placement' => $placementid,
                'source' => $sourceid,
            ]);
        }
    }
}
