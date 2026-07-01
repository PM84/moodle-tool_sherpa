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
 * Tests for the conversational chooser chat prompt builder.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_sherpa\local\chooser_chat_prompt_builder
 */
final class chooser_chat_prompt_builder_test extends \advanced_testcase {
    /**
     * The prompt lists the available activities and instructs the assistant to emit the marker.
     */
    public function test_build_includes_activities_and_marker(): void {
        $prompt = chooser_chat_prompt_builder::build([
            ['modname' => 'wiki', 'title' => 'Wiki'],
            ['modname' => 'quiz', 'title' => 'Quiz'],
        ]);

        $this->assertStringContainsString('wiki: Wiki', $prompt);
        $this->assertStringContainsString('quiz: Quiz', $prompt);
        $this->assertStringContainsString('[[SHERPA_ACTIVITIES:', $prompt);
    }

    /**
     * Activities with an empty modname are skipped.
     */
    public function test_build_skips_empty_modnames(): void {
        $prompt = chooser_chat_prompt_builder::build([
            ['modname' => '', 'title' => 'Nameless'],
            ['modname' => 'forum', 'title' => 'Forum'],
        ]);

        $this->assertStringNotContainsString('Nameless', $prompt);
        $this->assertStringContainsString('forum: Forum', $prompt);
    }
}
