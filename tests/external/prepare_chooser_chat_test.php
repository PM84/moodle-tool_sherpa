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

/**
 * Tests for the prepare_chooser_chat external function.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_sherpa\external\prepare_chooser_chat
 */
final class prepare_chooser_chat_test extends \advanced_testcase {
    /**
     * The function returns the chat context and the supporting materials for each activity.
     */
    public function test_execute_returns_context_and_materials(): void {
        $this->resetAfterTest();
        set_config('enabled', 1, 'tool_sherpa');
        // Disable the chat so the AI configuration is not required in the test environment.
        set_config('showchat', 0, 'tool_sherpa');

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);

        $activities = [
            ['modname' => 'wiki', 'title' => 'Wiki'],
            ['modname' => 'quiz', 'title' => 'Quiz'],
        ];

        $result = prepare_chooser_chat::execute($course->id, $activities);
        $result = external_api::clean_returnvalue(prepare_chooser_chat::execute_returns(), $result);

        $context = \core\context\course::instance($course->id);
        $this->assertSame($context->id, $result['contextid']);
        $this->assertFalse($result['chatavailable']);
        $this->assertCount(2, $result['materials']);
        $this->assertSame('wiki', $result['materials'][0]['modname']);
        $this->assertNotEmpty($result['materials'][0]['tutorials']);
        $this->assertNotEmpty($result['materials'][0]['templates']);
    }

    /**
     * The function requires the capability to manage activities.
     */
    public function test_execute_requires_capability(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);

        $this->expectException(\required_capability_exception::class);
        prepare_chooser_chat::execute($course->id, [['modname' => 'wiki', 'title' => 'Wiki']]);
    }
}
