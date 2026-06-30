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

use core\hook\output\before_help_icon_rendered;
use local_ai_manager\hook\purpose_usage;
use moodle_url;
use tool_langimport\hook\after_langpacks_updated;

/**
 * Hook callbacks for tool_sherpa.
 *
 * These callbacks wire Sherpa into the contributed hook points so that individual AI support can
 * be offered at various places (help icons) and so the support cache stays consistent when the
 * underlying language strings change.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Add an "Ask Sherpa" AI support action next to core help icons (Help-Fragezeichen).
     *
     * @param before_help_icon_rendered $hook the help icon rendering hook
     */
    public static function handle_before_help_icon_rendered(before_help_icon_rendered $hook): void {
        global $PAGE;

        if (!get_config('tool_sherpa', 'enabled') || !get_config('tool_sherpa', 'helpiconintegration')) {
            return;
        }

        $context = $PAGE->context ?? \context_system::instance();
        if (!support_manager::is_available($context)) {
            return;
        }

        $helpicon = $hook->get_helpicon();

        // Pass the help string identifier and component so the support page knows the topic.
        $url = new moodle_url('/admin/tool/sherpa/support.php', [
            'identifier' => $helpicon->identifier,
            'component' => $helpicon->component,
            'contextid' => $context->id,
        ]);

        $label = get_string('asksherpa', 'tool_sherpa');
        $icon = \html_writer::tag('i', '', ['class' => 'icon fa fa-robot', 'aria-hidden' => 'true']);
        $button = \html_writer::link($url, $icon, [
            'class' => 'tool-sherpa-help btn btn-link p-0 ms-1',
            'title' => $label,
            'aria-label' => $label,
            'role' => 'button',
            'target' => '_blank',
            'rel' => 'noopener',
        ]);

        $hook->add_after_icon($button);
    }

    /**
     * React to language pack updates by purging cached support answers (Languagestring).
     *
     * When language packs change, previously generated support answers may be based on outdated
     * strings, so the explanations cache is purged.
     *
     * @param after_langpacks_updated $hook the language packs updated hook
     */
    public static function handle_after_langpacks_updated(after_langpacks_updated $hook): void {
        if (empty($hook->updatedlangs)) {
            return;
        }
        // The underlying language strings may have changed, so drop cached explanations.
        \cache::make('tool_sherpa', 'explanations')->purge();
        // TODO: Optionally pre-generate support content for the updated languages.
    }

    /**
     * Declare which AI purposes Sherpa uses so local_ai_manager statistics are correct.
     *
     * @param purpose_usage $hook the purpose usage hook
     */
    public static function handle_purpose_usage(purpose_usage $hook): void {
        $hook->set_component_displayname('tool_sherpa', get_string('pluginname', 'tool_sherpa'));
        $hook->add_purpose_usage_description(
            support_manager::get_purpose(),
            'tool_sherpa',
            get_string('purposeusagedescription', 'tool_sherpa')
        );
    }
}
