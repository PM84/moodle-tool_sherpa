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

/**
 * Admin settings for tool_sherpa.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('tool_sherpa', get_string('pluginname', 'tool_sherpa'));
    $ADMIN->add('tools', $settings);

    $settings->add(new admin_setting_heading(
        'tool_sherpa/settingsheading',
        get_string('settingsheading', 'tool_sherpa'),
        get_string('settingsheading_desc', 'tool_sherpa')
    ));

    // Master switch for the whole plugin.
    $settings->add(new admin_setting_configcheckbox(
        'tool_sherpa/enabled',
        get_string('enabled', 'tool_sherpa'),
        get_string('enabled_desc', 'tool_sherpa'),
        1
    ));

    // Whether to inject the support action next to core help icons.
    $settings->add(new admin_setting_configcheckbox(
        'tool_sherpa/helpiconintegration',
        get_string('helpiconintegration', 'tool_sherpa'),
        get_string('helpiconintegration_desc', 'tool_sherpa'),
        1
    ));

    // The local_ai_manager purpose used to generate support answers.
    $settings->add(new admin_setting_configtext(
        'tool_sherpa/purpose',
        get_string('purpose', 'tool_sherpa'),
        get_string('purpose_desc', 'tool_sherpa'),
        'chat',
        PARAM_ALPHANUMEXT
    ));
}
