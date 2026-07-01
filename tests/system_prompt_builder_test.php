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
 * Tests for the system_prompt_builder class.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_sherpa\local\system_prompt_builder
 */
final class system_prompt_builder_test extends \advanced_testcase {
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
     * The template placeholders are substituted from the help field and its sources.
     */
    public function test_build_for_help_substitutes_placeholders(): void {
        global $DB;
        set_config('showmaterials', 1, 'tool_sherpa');
        set_config('systemprompttemplate', '{title}|{component}|{identifier}|{source_urls}', 'tool_sherpa');

        $sourceid = $DB->insert_record('tool_sherpa_source',
            (object) ['title' => 'Example source', 'url' => 'https://example.org/x']);
        $placementid = $DB->insert_record('tool_sherpa_placement', (object) [
            'type' => placement::TYPE_LANGSTRING,
            'value' => 'coursesummary,moodle',
        ]);
        $DB->insert_record('tool_sherpa_mapping', (object) [
            'placement' => $placementid,
            'source' => $sourceid,
        ]);

        $prompt = system_prompt_builder::build_for_help('moodle', 'coursesummary', \core\context\system::instance());

        $this->assertStringContainsString(get_string('coursesummary', 'moodle'), $prompt);
        $this->assertStringContainsString('|moodle|coursesummary|https://example.org/x', $prompt);
    }

    /**
     * Storing and fetching the prompt round-trips per user and context.
     */
    public function test_store_and_fetch_roundtrip(): void {
        $context = \core\context\system::instance();

        $this->assertNull(system_prompt_builder::fetch(7, $context->id));

        system_prompt_builder::store(7, $context, 'a field prompt');

        $this->assertSame('a field prompt', system_prompt_builder::fetch(7, $context->id));
        // A different user does not see the prompt.
        $this->assertNull(system_prompt_builder::fetch(8, $context->id));
    }
}
