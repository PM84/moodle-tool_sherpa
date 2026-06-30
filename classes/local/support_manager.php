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
use core\exception\moodle_exception;
use local_ai_manager\ai_manager_utils;
use local_ai_manager\manager;

/**
 * Wrapper that reuses local_ai_manager and block_ai_chat to provide individual AI support.
 *
 * This class is the single entry point Sherpa uses to talk to the AI subsystem. It mirrors the
 * availability logic used by block_ai_chat and delegates the actual request to local_ai_manager.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class support_manager {
    /** @var string The component name used for AI manager logging. */
    public const COMPONENT = 'tool_sherpa';

    /** @var string Default purpose used for generating support answers. */
    private const DEFAULT_PURPOSE = 'singleprompt';

    /** @var array Request level cache of availability results keyed by context id. */
    private static array $availabilitycache = [];

    /**
     * Get the configured local_ai_manager purpose to use for support requests.
     *
     * @return string the purpose name
     */
    public static function get_purpose(): string {
        $purpose = get_config('tool_sherpa', 'purpose');
        return !empty($purpose) ? $purpose : self::DEFAULT_PURPOSE;
    }

    /**
     * Determine whether Sherpa support is enabled and AI is available for the current user.
     *
     * Reuses the availability logic of block_ai_chat / local_ai_manager so that Sherpa only
     * appears where the AI tools are actually usable.
     *
     * @param context $context the context in which support is requested
     * @return bool true if support can be offered
     */
    public static function is_available(context $context): bool {
        global $USER;

        if (!get_config('tool_sherpa', 'enabled')) {
            return false;
        }

        if (array_key_exists($context->id, self::$availabilitycache)) {
            return self::$availabilitycache[$context->id];
        }

        if (!has_capability('tool/sherpa:usesupport', $context)) {
            self::$availabilitycache[$context->id] = false;
            return false;
        }

        // Reuse the local_ai_manager availability check, exactly as block_ai_chat does.
        $aiconfig = ai_manager_utils::get_ai_config($USER, $context->id, null, [self::get_purpose()]);
        $available = $aiconfig['availability']['available'] === ai_manager_utils::AVAILABILITY_AVAILABLE;

        self::$availabilitycache[$context->id] = $available;
        return $available;
    }

    /**
     * Generate an individual support answer for the given question.
     *
     * The result is cached per purpose, context and question. Cached answers are purged when
     * language packs are updated (see hook_callbacks::handle_after_langpacks_updated).
     *
     * @param string $question the user question or help topic to explain
     * @param context $context the context the request is performed in
     * @return string the generated support answer in Markdown
     * @throws moodle_exception if support is unavailable or the AI request fails
     */
    public static function get_support_answer(string $question, context $context): string {
        if (!self::is_available($context)) {
            throw new moodle_exception('error_supportunavailable', 'tool_sherpa');
        }

        $cache = \cache::make('tool_sherpa', 'explanations');
        $cachekey = sha1(self::get_purpose() . '|' . $context->id . '|' . $question);
        $cached = $cache->get($cachekey);
        if ($cached !== false) {
            return $cached;
        }

        $manager = new manager(self::get_purpose());
        $response = $manager->perform_request(self::build_prompt($question), self::COMPONENT, $context->id);

        if ($response->get_code() !== 200) {
            throw new moodle_exception('error_airequestfailed', 'tool_sherpa', '', null, $response->get_errormessage());
        }

        $answer = $response->get_content();
        $cache->set($cachekey, $answer);
        return $answer;
    }

    /**
     * Build the full prompt sent to the AI model.
     *
     * @param string $question the help topic or user question
     * @return string the assembled prompt text
     */
    private static function build_prompt(string $question): string {
        // TODO: Refine the system prompt or reuse a block_ai_chat persona for individual support.
        return get_string('supportpromptprefix', 'tool_sherpa') . "\n\n" . $question;
    }
}
