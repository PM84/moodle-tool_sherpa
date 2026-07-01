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
 * Provides supporting materials (tutorials and example templates) for an activity.
 *
 * For the demo there are no real example templates, so the materials are mocked: each activity
 * gets a placeholder tutorial link and a placeholder "course tile" template. In production these
 * could be sourced from the tool_sherpa_source / tool_sherpa_placement / tool_sherpa_mapping
 * tables instead.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class material_provider {
    /** @var string Base URL used for the mocked demo tutorial links. */
    private const DEMO_BASE_URL = 'https://example.org/sherpa-demo/';

    /**
     * Get the mocked supporting materials for an activity.
     *
     * @param string $modname the activity module name (e.g. "wiki")
     * @return array{tutorials: array<int, array{label: string, url: string}>,
     *     templates: array<int, array{title: string, summary: string, image: string}>}
     */
    public static function get_materials(string $modname): array {
        $activityname = self::activity_name($modname);

        return [
            'tutorials' => [
                [
                    'label' => get_string('helpchooser_tutoriallabel', 'tool_sherpa', $activityname),
                    'url' => self::DEMO_BASE_URL . $modname . '/tutorial',
                ],
            ],
            'templates' => [
                [
                    'title' => get_string('helpchooser_templatetitle', 'tool_sherpa', $activityname),
                    'summary' => get_string('helpchooser_templatesummary', 'tool_sherpa', $activityname),
                    // No real template image exists for the demo; the template renders a placeholder.
                    'image' => '',
                ],
            ],
        ];
    }

    /**
     * Resolve the human readable activity name for a module name.
     *
     * @param string $modname the activity module name
     * @return string the localised activity name, or the raw module name as a fallback
     */
    private static function activity_name(string $modname): string {
        $component = 'mod_' . $modname;
        if (get_string_manager()->string_exists('pluginname', $component)) {
            return get_string('pluginname', $component);
        }
        return $modname;
    }
}
