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

namespace tool_sherpa\local\import;

use tool_sherpa\placement;

/**
 * Tests for the tutorial importer.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_sherpa\local\import\importer
 */
final class importer_test extends \advanced_testcase {
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
     * A "component:key" reference is converted to the "key,component" placement value.
     *
     * @covers \tool_sherpa\local\import\importer::convert_langstring
     */
    public function test_convert_langstring(): void {
        $this->assertSame('login,core', importer::convert_langstring('core:login'));
        $this->assertSame('addnewcourse,core_course', importer::convert_langstring('core_course:addnewcourse'));
        $this->assertSame('login,core', importer::convert_langstring('  core : login '));
        $this->assertNull(importer::convert_langstring(''));
        $this->assertNull(importer::convert_langstring('nocolon'));
        $this->assertNull(importer::convert_langstring('core:'));
    }

    /**
     * A semicolon separated cell is split into trimmed, non-empty parts.
     *
     * @covers \tool_sherpa\local\import\importer::split_cell
     */
    public function test_split_cell(): void {
        $this->assertSame(['a', 'b', 'c'], importer::split_cell('a; b;c ;'));
        $this->assertSame([], importer::split_cell('  '));
    }

    /**
     * A row is imported as a source with the mapped body id and langstring placements.
     *
     * @covers \tool_sherpa\local\import\importer::import
     */
    public function test_import_creates_source_placements_and_mappings(): void {
        global $DB;

        $rows = [
            new import_row('Login tutorial', 'https://example.org/login',
                ['page-login-index'], ['core:login']),
        ];

        $summary = (new importer())->import($rows);

        $this->assertSame(1, $summary->sourcescreated);
        $this->assertSame(2, $summary->placementscreated);
        $this->assertSame(2, $summary->mappingscreated);
        $this->assertSame(0, $summary->mappingsskipped);

        $this->assertEquals(1, $DB->count_records('tool_sherpa_source', ['url' => 'https://example.org/login']));
        $this->assertEquals(1, $DB->count_records('tool_sherpa_placement',
            ['type' => placement::TYPE_BODYID, 'value' => 'page-login-index']));
        $this->assertEquals(1, $DB->count_records('tool_sherpa_placement',
            ['type' => placement::TYPE_LANGSTRING, 'value' => 'login,core']));
        $this->assertEquals(2, $DB->count_records('tool_sherpa_mapping'));
    }

    /**
     * Placements shared across rows are reused instead of duplicated.
     *
     * @covers \tool_sherpa\local\import\importer::import
     */
    public function test_import_reuses_shared_placements(): void {
        global $DB;

        $rows = [
            new import_row('Text editor', 'https://example.org/editor', [], ['core:texteditor']),
            new import_row('Insert links', 'https://example.org/links', [], ['core:texteditor']),
        ];

        $summary = (new importer())->import($rows);

        $this->assertSame(2, $summary->sourcescreated);
        $this->assertSame(1, $summary->placementscreated);
        $this->assertSame(1, $summary->placementsreused);
        $this->assertSame(2, $summary->mappingscreated);
        $this->assertEquals(1, $DB->count_records('tool_sherpa_placement',
            ['type' => placement::TYPE_LANGSTRING, 'value' => 'texteditor,core']));
        $this->assertEquals(2, $DB->count_records('tool_sherpa_mapping'));
    }

    /**
     * Re-importing the same data reuses existing records and skips existing mappings.
     *
     * @covers \tool_sherpa\local\import\importer::import
     */
    public function test_import_is_idempotent(): void {
        global $DB;

        $rows = [
            new import_row('Login tutorial', 'https://example.org/login',
                ['page-login-index'], ['core:login']),
        ];

        (new importer())->import($rows);
        $summary = (new importer())->import($rows);

        $this->assertSame(0, $summary->sourcescreated);
        $this->assertSame(1, $summary->sourcesreused);
        $this->assertSame(0, $summary->placementscreated);
        $this->assertSame(2, $summary->placementsreused);
        $this->assertSame(0, $summary->mappingscreated);
        $this->assertSame(2, $summary->mappingsskipped);

        $this->assertEquals(1, $DB->count_records('tool_sherpa_source'));
        $this->assertEquals(2, $DB->count_records('tool_sherpa_placement'));
        $this->assertEquals(2, $DB->count_records('tool_sherpa_mapping'));
    }

    /**
     * Empty and incomplete rows are skipped.
     *
     * @covers \tool_sherpa\local\import\importer::import
     */
    public function test_import_skips_empty_and_incomplete_rows(): void {
        global $DB;

        $rows = [
            new import_row('', '', [], []),
            new import_row('No url', '', ['page-x'], []),
            new import_row('', 'https://example.org/nourl', ['page-y'], []),
        ];

        $summary = (new importer())->import($rows);

        $this->assertSame(0, $summary->sourcescreated);
        $this->assertEquals(0, $DB->count_records('tool_sherpa_source'));
    }
}
