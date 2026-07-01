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

namespace tool_sherpa\local\callbacks;

use action_link;
use core\context\course as context_course;
use core_course\hook\before_activitychooserbutton_exported;
use moodle_url;
use pix_icon;

/**
 * Adds the "Help, what should I do?" entry to the activity chooser button dropdown.
 *
 * The entry uses a dedicated data-action so the core activity chooser does not handle the click;
 * instead the tool_sherpa trigger opens the Sherpa help chooser.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class before_activitychooserbutton_exported_handler {
    /** @var bool Whether the trigger AMD has already been queued during this request. */
    private static bool $amdloaded = false;

    /**
     * Add the Sherpa help chooser action link to the activity chooser button.
     *
     * @param before_activitychooserbutton_exported $hook the activity chooser button hook
     */
    public static function callback(before_activitychooserbutton_exported $hook): void {
        global $PAGE;

        if (!get_config('tool_sherpa', 'enabled') || !get_config('tool_sherpa', 'enablehelpchooser')) {
            return;
        }

        $section = $hook->get_section();
        $context = context_course::instance($section->course);
        if (
            !has_capability('moodle/course:manageactivities', $context)
            || !has_capability('tool/sherpa:usesupport', $context)
        ) {
            return;
        }

        $attributes = [
            'class' => 'dropdown-item',
            'data-action' => 'sherpa-helpchooser',
            'data-courseid' => $section->course,
            'data-sectionid' => $section->id,
            'data-sectionnum' => $section->sectionnum,
        ];
        if ($hook->get_cm()) {
            $attributes['data-beforemod'] = $hook->get_cm()->id;
        }

        $hook->get_activitychooserbutton()->add_action_link(new action_link(
            new moodle_url('#'),
            get_string('helpchooser_open', 'tool_sherpa'),
            null,
            $attributes,
            new pix_icon('i/help', '')
        ));

        if (!self::$amdloaded) {
            $PAGE->requires->js_call_amd('tool_sherpa/helpchooser_trigger', 'init');
            self::$amdloaded = true;
        }
    }
}
