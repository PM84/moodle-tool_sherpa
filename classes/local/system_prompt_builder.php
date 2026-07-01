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

use context;

/**
 * Builds and stores the field specific system prompt for the embedded chat.
 *
 * The prompt is built server side from the help text and the mapped sources and stored in a short
 * lived application cache (keyed by user and context). The local_ai_manager before_request hook
 * callback reads it and injects it as a system message - no persona is created.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class system_prompt_builder {
    /**
     * Build the cache key for a user and context.
     *
     * @param int $userid the user id
     * @param int $contextid the context id
     * @return string the cache key
     */
    private static function cache_key(int $userid, int $contextid): string {
        return $userid . '_' . $contextid;
    }

    /**
     * Build the system prompt string for a help field.
     *
     * @param string $component the help string component
     * @param string $identifier the help string identifier
     * @param context $context the context the request is performed in
     * @return string the assembled system prompt
     */
    public static function build_for_help(string $component, string $identifier, context $context): string {
        $title = get_string($identifier, $component);
        $helphtml = get_string($identifier . '_help', $component);
        $helpplain = trim(html_to_text(format_text($helphtml, FORMAT_MARKDOWN, ['context' => $context])));

        $urls = [];
        foreach (source_provider::get_sources_for_help($component, $identifier) as $source) {
            $urls[] = $source->url;
        }

        $template = (string) get_config('tool_sherpa', 'systemprompttemplate');
        if (trim($template) === '') {
            $template = get_string('systemprompttemplate_default', 'tool_sherpa');
        }

        return strtr($template, [
            '{title}' => $title,
            '{component}' => $component,
            '{identifier}' => $identifier,
            '{helptext_plain}' => $helpplain,
            '{source_urls}' => implode(', ', $urls),
        ]);
    }

    /**
     * Build the system prompt string for a page (body id) placement.
     *
     * @param string $title the page help title
     * @param string[] $urls the URLs of the mapped sources
     * @return string the assembled system prompt
     */
    public static function build_for_page(string $title, array $urls): string {
        return get_string('pagehelp_chatsystemprompt', 'tool_sherpa', (object) [
            'title' => $title,
            'urls' => implode(', ', $urls),
        ]);
    }

    /**
     * Store the system prompt for later injection by the before_request hook.
     *
     * @param int $userid the user id the prompt belongs to
     * @param context $context the context the chat is embedded in
     * @param string $prompt the system prompt to store
     */
    public static function store(int $userid, context $context, string $prompt): void {
        \cache::make('tool_sherpa', 'activeprompt')->set(self::cache_key($userid, $context->id), $prompt);
    }

    /**
     * Fetch the stored system prompt for a user and context.
     *
     * @param int $userid the user id
     * @param int $contextid the context id
     * @return string|null the stored prompt, or null if none is stored
     */
    public static function fetch(int $userid, int $contextid): ?string {
        $value = \cache::make('tool_sherpa', 'activeprompt')->get(self::cache_key($userid, $contextid));
        return (is_string($value) && $value !== '') ? $value : null;
    }
}
