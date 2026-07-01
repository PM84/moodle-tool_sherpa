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
use tool_sherpa\placement;

/**
 * Tests for the get_help_modal_content external function.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_sherpa\external\get_help_modal_content
 */
final class get_help_modal_content_test extends \advanced_testcase {
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
     * The function returns the mapped sources and the help title.
     */
    public function test_execute_returns_sources(): void {
        global $DB;
        $this->setAdminUser();
        set_config('showmaterials', 1, 'tool_sherpa');
        // Disable the chat so the AI config is not required in the test environment.
        set_config('showchat', 0, 'tool_sherpa');

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

        $context = \core\context\system::instance();
        $result = get_help_modal_content::execute('moodle', 'coursesummary', $context->id);
        $result = external_api::clean_returnvalue(get_help_modal_content::execute_returns(), $result);

        $this->assertSame(get_string('coursesummary', 'moodle'), $result['title']);
        $this->assertFalse($result['chatavailable']);
        $this->assertCount(1, $result['sources']);
        $this->assertSame('https://example.org/x', $result['sources'][0]['url']);
        $this->assertSame('Example source', $result['sources'][0]['title']);
    }

    /**
     * An unknown help identifier is rejected.
     */
    public function test_execute_unknown_identifier_throws(): void {
        $this->setAdminUser();

        $context = \core\context\system::instance();
        $this->expectException(\invalid_parameter_exception::class);
        get_help_modal_content::execute('moodle', 'tool_sherpa_no_such_help_id', $context->id);
    }
}
