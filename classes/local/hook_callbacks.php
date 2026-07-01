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
use core\hook\output\before_footer_html_generation;
use local_ai_manager\hook\before_request;

/**
 * Hook callbacks for tool_sherpa.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /** @var bool Whether the trigger AMD has already been queued during this request. */
    private static bool $amdloaded = false;

    /**
     * Replace the core help icon popover with the Sherpa help modal trigger.
     *
     * @param before_help_icon_rendered $hook the help icon rendering hook
     */
    public static function handle_before_help_icon_rendered(before_help_icon_rendered $hook): void {
        global $PAGE;

        if (!get_config('tool_sherpa', 'enabled') || !get_config('tool_sherpa', 'usemodal')) {
            return;
        }

        $context = $PAGE->context ?? \context_system::instance();
        if (!has_capability('tool/sherpa:usesupport', $context)) {
            return;
        }

        $helpicon = $hook->get_helpicon();
        $templatecontext = $hook->get_templatecontext();
        $templatecontext->component = $helpicon->component;
        $templatecontext->identifier = $helpicon->identifier;
        $templatecontext->contextid = $context->id;
        $templatecontext->chatavailable = support_manager::chat_available($context);
        $templatecontext->showmaterials = (bool) get_config('tool_sherpa', 'showmaterials');

        $hook->set_replacement($hook->renderer->render_from_template('tool_sherpa/help_icon', $templatecontext));

        if (!self::$amdloaded) {
            $PAGE->requires->js_call_amd('tool_sherpa/help_icon_trigger', 'init');
            self::$amdloaded = true;
        }
    }

    /**
     * Inject a page level help button when tutorials are mapped to the current page's body id.
     *
     * @param before_footer_html_generation $hook the footer generation hook
     */
    public static function inject_page_help(before_footer_html_generation $hook): void {
        global $PAGE, $OUTPUT;

        if (!get_config('tool_sherpa', 'enabled') || !get_config('tool_sherpa', 'enablepagehelp')) {
            return;
        }

        $context = $PAGE->context ?? \context_system::instance();
        if (!has_capability('tool/sherpa:usesupport', $context)) {
            return;
        }

        $bodyid = (string) $PAGE->bodyid;
        if ($bodyid === '') {
            return;
        }

        if (empty(source_provider::get_sources_for_bodyid($bodyid))) {
            return;
        }

        $templatecontext = (object) [
            'bodyid' => $bodyid,
            'contextid' => $context->id,
            'title' => get_string('pagehelp_title', 'tool_sherpa'),
            'label' => get_string('pagehelp_buttonlabel', 'tool_sherpa'),
            'chatavailable' => support_manager::chat_available($context),
            'showmaterials' => (bool) get_config('tool_sherpa', 'showmaterials'),
        ];

        $hook->add_html($OUTPUT->render_from_template('tool_sherpa/page_help_button', $templatecontext));
        $PAGE->requires->js_call_amd('tool_sherpa/page_help_trigger', 'init');
    }

    /**
     * Inject the field specific system prompt into a Sherpa chat request.
     *
     * Fires for every AI request, so it returns early unless the request originates from a Sherpa
     * chat embed (component) using the chat purpose. The prompt is read from the short lived cache
     * that was populated when the help modal was opened.
     *
     * @param before_request $hook the AI request hook
     */
    public static function inject_system_prompt(before_request $hook): void {
        global $USER;

        if (
            $hook->get_component() !== support_manager::COMPONENT
            || $hook->get_purpose()->get_plugin_name() !== support_manager::PURPOSE
        ) {
            return;
        }

        $prompt = system_prompt_builder::fetch($USER->id, $hook->get_context()->id);
        if ($prompt !== null) {
            $hook->prepend_system_message($prompt);
        }
    }
}
