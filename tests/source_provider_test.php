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
 * Tests for the source_provider class.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_sherpa\local\source_provider
 */
final class source_provider_test extends \advanced_testcase {
    /**
     * Reset state and clear the install seed data so tests start from a known empty state.
     */
    protected function setUp(): void {
        global $DB;
        parent::setUp();
        $this->resetAfterTest();
        $DB->delete_records('tool_sherpa_mapping');
        $DB->delete_records('tool_sherpa_placement');
        $DB->delete_records('tool_sherpa_source');
    }

    /**
     * Insert a source mapped to a langstring placement.
     *
     * @param string $value the placement value ("identifier,component")
     * @param string $url the source url
     */
    private function map_source(string $value, string $url): void {
        global $DB;
        $sourceid = $DB->insert_record('tool_sherpa_source', (object) ['title' => 'Example source', 'url' => $url]);
        $placementid = $DB->insert_record('tool_sherpa_placement', (object) [
            'type' => placement::TYPE_LANGSTRING,
            'value' => $value,
        ]);
        $DB->insert_record('tool_sherpa_mapping', (object) [
            'placement' => $placementid,
            'source' => $sourceid,
        ]);
    }

    /**
     * Mapped sources are returned for the matching langstring placement.
     */
    public function test_get_sources_for_help_returns_mapped_sources(): void {
        set_config('showmaterials', 1, 'tool_sherpa');
        $this->map_source('coursevisibility,moodle', 'https://example.org/a');

        $sources = source_provider::get_sources_for_help('moodle', 'coursevisibility');

        $this->assertCount(1, $sources);
        $source = reset($sources);
        $this->assertEquals('https://example.org/a', $source->url);
    }

    /**
     * No sources are returned for an unmapped help field.
     */
    public function test_get_sources_for_help_no_match(): void {
        set_config('showmaterials', 1, 'tool_sherpa');
        $this->map_source('coursevisibility,moodle', 'https://example.org/a');

        $this->assertSame([], source_provider::get_sources_for_help('moodle', 'idnumbercourse'));
    }

    /**
     * No sources are returned when the materials region is disabled.
     */
    public function test_get_sources_for_help_disabled(): void {
        set_config('showmaterials', 0, 'tool_sherpa');
        $this->map_source('coursevisibility,moodle', 'https://example.org/a');

        $this->assertSame([], source_provider::get_sources_for_help('moodle', 'coursevisibility'));
    }
}
