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

namespace tool_sherpa\local\import;

use tool_sherpa\placement as placementtype;
use tool_sherpa\local\persistent\mapping;
use tool_sherpa\local\persistent\placement;
use tool_sherpa\local\persistent\source;

/**
 * Imports tutorials (sources), their placements and the mappings between them.
 *
 * Existing sources (matched by URL) and placements (matched by type and value) are reused instead
 * of duplicated; mappings that already exist are skipped. The whole import runs in a single
 * database transaction.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class importer {

    /**
     * Import the given rows.
     *
     * @param import_row[] $rows the rows to import
     * @return \stdClass counts: sourcescreated, sourcesreused, placementscreated, placementsreused,
     *     mappingscreated, mappingsskipped
     */
    public function import(array $rows): \stdClass {
        global $DB;

        $summary = (object) [
            'sourcescreated' => 0,
            'sourcesreused' => 0,
            'placementscreated' => 0,
            'placementsreused' => 0,
            'mappingscreated' => 0,
            'mappingsskipped' => 0,
        ];

        $transaction = $DB->start_delegated_transaction();
        try {
            foreach ($rows as $row) {
                $this->import_row($row, $summary);
            }
            $transaction->allow_commit();
        } catch (\Throwable $e) {
            $transaction->rollback($e);
        }

        return $summary;
    }

    /**
     * Import a single row.
     *
     * @param import_row $row the row to import
     * @param \stdClass $summary the running summary counters (modified by reference)
     */
    private function import_row(import_row $row, \stdClass $summary): void {
        $title = trim($row->title);
        $url = trim($row->url);
        if ($row->is_empty() || $url === '' || $title === '') {
            return;
        }

        $sourceid = $this->get_or_create_source($title, $url, $summary);

        foreach ($row->bodyids as $bodyid) {
            $bodyid = trim($bodyid);
            if ($bodyid === '') {
                continue;
            }
            $placementid = $this->get_or_create_placement(placementtype::TYPE_BODYID, $bodyid, $summary);
            $this->get_or_create_mapping($placementid, $sourceid, $summary);
        }

        foreach ($row->langstrings as $langstring) {
            $value = self::convert_langstring($langstring);
            if ($value === null) {
                continue;
            }
            $placementid = $this->get_or_create_placement(placementtype::TYPE_LANGSTRING, $value, $summary);
            $this->get_or_create_mapping($placementid, $sourceid, $summary);
        }
    }

    /**
     * Get the id of the source with the given URL, creating it if necessary.
     *
     * @param string $title the source title
     * @param string $url the source URL
     * @param \stdClass $summary the running summary counters (modified by reference)
     * @return int the source id
     */
    private function get_or_create_source(string $title, string $url, \stdClass $summary): int {
        $existing = source::get_record(['url' => $url]);
        if ($existing) {
            $summary->sourcesreused++;
            return $existing->get('id');
        }

        $source = new source(0, (object) ['title' => $title, 'url' => $url]);
        $source->create();
        $summary->sourcescreated++;
        return $source->get('id');
    }

    /**
     * Get the id of the placement with the given type and value, creating it if necessary.
     *
     * @param string $type the placement type
     * @param string $value the placement value
     * @param \stdClass $summary the running summary counters (modified by reference)
     * @return int the placement id
     */
    private function get_or_create_placement(string $type, string $value, \stdClass $summary): int {
        $existing = placement::get_record(['type' => $type, 'value' => $value]);
        if ($existing) {
            $summary->placementsreused++;
            return $existing->get('id');
        }

        $placement = new placement(0, (object) ['type' => $type, 'value' => $value]);
        $placement->create();
        $summary->placementscreated++;
        return $placement->get('id');
    }

    /**
     * Create the mapping between the placement and the source unless it already exists.
     *
     * @param int $placementid the placement id
     * @param int $sourceid the source id
     * @param \stdClass $summary the running summary counters (modified by reference)
     */
    private function get_or_create_mapping(int $placementid, int $sourceid, \stdClass $summary): void {
        if (mapping::mapping_exists($placementid, $sourceid)) {
            $summary->mappingsskipped++;
            return;
        }

        $mapping = new mapping(0, (object) ['placement' => $placementid, 'source' => $sourceid]);
        $mapping->create();
        $summary->mappingscreated++;
    }

    /**
     * Convert a raw "component:key" language string reference into the placement value syntax.
     *
     * Placements store language strings as "identifier,component" (cf. get_string()), so
     * "core:login" becomes "login,core".
     *
     * @param string $raw the raw "component:key" reference
     * @return string|null the placement value, or null if the reference is malformed
     */
    public static function convert_langstring(string $raw): ?string {
        $raw = trim($raw);
        if ($raw === '' || !str_contains($raw, ':')) {
            return null;
        }

        [$component, $key] = explode(':', $raw, 2);
        $component = trim($component);
        $key = trim($key);
        if ($component === '' || $key === '') {
            return null;
        }

        return $key . ',' . $component;
    }

    /**
     * Split a CSV cell containing a semicolon separated list into its trimmed, non-empty parts.
     *
     * @param string $cell the raw cell content
     * @return string[] the list items
     */
    public static function split_cell(string $cell): array {
        $parts = array_map('trim', explode(';', $cell));
        return array_values(array_filter($parts, static fn($part) => $part !== ''));
    }

    /**
     * Read the parsed rows from a prepared CSV import reader.
     *
     * The first line of the CSV is treated as the header by the reader and is not returned here.
     * The fixed column order is: title, URL, body IDs, language strings.
     *
     * @param \csv_import_reader $cir the initialised reader
     * @return import_row[] the parsed rows
     */
    public static function read_rows(\csv_import_reader $cir): array {
        $rows = [];
        $cir->init();
        while ($fields = $cir->next()) {
            $rows[] = new import_row(
                trim((string) ($fields[0] ?? '')),
                trim((string) ($fields[1] ?? '')),
                self::split_cell((string) ($fields[2] ?? '')),
                self::split_cell((string) ($fields[3] ?? '')),
            );
        }
        $cir->close();

        return $rows;
    }
}
