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

$systemcontext = context_system::instance();

// The "Manage sources and placements" page should be accessible for users with either the
// moodle/site:configview or the tool/sherpa:manage capability (in addition to full admins).
// As a settings/external page can only be guarded by a single capability, we pick the appropriate
// one for the current user and pass it to the page later (cf. local_staticpage).
$hasmanage = has_capability('tool/sherpa:manage', $systemcontext);
if ($hasmanage) {
    $managecapability = 'tool/sherpa:manage';
} else {
    $managecapability = 'moodle/site:configview';
}

// Show the Sherpa section for full admins as well as for users allowed to manage or view it.
if ($hassiteconfig || $hasmanage || has_capability('moodle/site:configview', $systemcontext)) {
    // Own category, listed under "Plugins > Admin tools" (the "tools" node).
    $ADMIN->add('tools', new admin_category(
        'tool_sherpa',
        get_string('pluginname', 'tool_sherpa')
    ));

    // Settings page (full admins only).
    if ($hassiteconfig) {
        $settings = new admin_settingpage('tool_sherpa_settings', get_string('settings', 'core'));

        $settings->add(new admin_setting_heading(
            'tool_sherpa/settingsheading',
            get_string('settings:heading', 'tool_sherpa'),
            get_string('settings:heading_desc', 'tool_sherpa')
        ));

        // Master switch for the whole plugin.
        $settings->add(new admin_setting_configcheckbox(
            'tool_sherpa/enabled',
            get_string('settings:enabled', 'tool_sherpa'),
            get_string('settings:enabled_desc', 'tool_sherpa'),
            0
        ));

        // Replace the core help popover with the Sherpa modal. Default off (classic popover).
        $settings->add(new admin_setting_configcheckbox(
            'tool_sherpa/usemodal',
            get_string('settings:usemodal', 'tool_sherpa'),
            get_string('settings:usemodal_desc', 'tool_sherpa'),
            0
        ));
        $settings->hide_if('tool_sherpa/usemodal', 'tool_sherpa/enabled');

        // Show the "further materials" region in the modal.
        $settings->add(new admin_setting_configcheckbox(
            'tool_sherpa/showmaterials',
            get_string('settings:showmaterials', 'tool_sherpa'),
            get_string('settings:showmaterials_desc', 'tool_sherpa'),
            0
        ));
        $settings->hide_if('tool_sherpa/showmaterials', 'tool_sherpa/enabled');

        // Offer a page level help button on pages that have tutorials mapped to their body id. Default off.
        $settings->add(new admin_setting_configcheckbox(
                'tool_sherpa/enablepagehelp',
                get_string('settings:enablepagehelp', 'tool_sherpa'),
                get_string('settings:enablepagehelp_desc', 'tool_sherpa'),
                0
        ));
        $settings->hide_if('tool_sherpa/enablepagehelp', 'tool_sherpa/enabled');

        // Show the embedded AI chat region in the modal.
        $settings->add(new admin_setting_configcheckbox(
            'tool_sherpa/showchat',
            get_string('settings:showchat', 'tool_sherpa'),
            get_string('settings:showchat_desc', 'tool_sherpa'),
            0
        ));
        $settings->hide_if('tool_sherpa/showchat', 'tool_sherpa/enabled');

        // Offer the "Help, what should I do?" entry in the activity chooser. Default off.
        $settings->add(new admin_setting_configcheckbox(
            'tool_sherpa/enablehelpchooser',
            get_string('settings:enablehelpchooser', 'tool_sherpa'),
            get_string('settings:enablehelpchooser_desc', 'tool_sherpa'),
            0
        ));
        $settings->hide_if('tool_sherpa/enablehelpchooser', 'tool_sherpa/showchat');

        // Template used to build the field specific system prompt for the chat.
        $settings->add(new admin_setting_configtextarea(
            'tool_sherpa/systemprompttemplate',
            get_string('settings:systemprompttemplate', 'tool_sherpa'),
            get_string('settings:systemprompttemplate_desc', 'tool_sherpa'),
            get_string('settings:systemprompttemplate_default', 'tool_sherpa'),
            PARAM_RAW
        ));
        $settings->hide_if('tool_sherpa/systemprompttemplate', 'tool_sherpa/showchat');

        $ADMIN->add('tool_sherpa', $settings);
    }

    // Management page for sources, placements and their mappings.
    $ADMIN->add('tool_sherpa', new admin_externalpage(
        'tool_sherpa_manage',
        get_string('managesourcesandplacements', 'tool_sherpa'),
        new moodle_url('/admin/tool/sherpa/manage.php'),
        $managecapability
    ));

    // CSV import wizard for tutorials (writes data, hence guarded by the manage capability).
    $ADMIN->add('tool_sherpa', new admin_externalpage(
        'tool_sherpa_import',
        get_string('importtutorials', 'tool_sherpa'),
        new moodle_url('/admin/tool/sherpa/import.php'),
        'tool/sherpa:manage'
    ));
}
