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

/**
 * Tests for the mocked material_provider class.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_sherpa\local\material_provider
 */
final class material_provider_test extends \advanced_testcase {
    /**
     * The materials have the expected structure for a known activity.
     */
    public function test_get_materials_structure(): void {
        $materials = material_provider::get_materials('wiki');

        $this->assertArrayHasKey('tutorials', $materials);
        $this->assertArrayHasKey('templates', $materials);

        $this->assertNotEmpty($materials['tutorials']);
        $this->assertArrayHasKey('label', $materials['tutorials'][0]);
        $this->assertArrayHasKey('url', $materials['tutorials'][0]);
        $this->assertStringContainsString('wiki', $materials['tutorials'][0]['url']);

        $this->assertNotEmpty($materials['templates']);
        $this->assertArrayHasKey('title', $materials['templates'][0]);
        $this->assertArrayHasKey('summary', $materials['templates'][0]);
        $this->assertArrayHasKey('image', $materials['templates'][0]);
    }

    /**
     * Unknown activity names still yield mocked materials.
     */
    public function test_get_materials_unknown_modname(): void {
        $materials = material_provider::get_materials('doesnotexist');

        $this->assertNotEmpty($materials['tutorials']);
        $this->assertNotEmpty($materials['templates']);
    }
}
