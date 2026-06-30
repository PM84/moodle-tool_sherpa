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
use local_ai_manager\ai_manager_utils;

/**
 * Availability helper for Sherpa support.
 *
 * Reuses the availability logic of local_ai_manager so that the AI chat is only offered where the
 * AI tools are actually usable.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class support_manager {
    /** @var string Component name used for local_ai_manager logging and the chat embed. */
    public const COMPONENT = 'tool_sherpa';

    /** @var string local_ai_manager purpose used by the embedded chat. */
    public const PURPOSE = 'chat';

    /** @var array Request level cache of availability results keyed by context id. */
    private static array $availabilitycache = [];

    /**
     * Determine whether Sherpa support is enabled and AI is available for the current user.
     *
     * @param context $context the context in which support is requested
     * @return bool true if the AI is available for the user in this context
     */
    public static function is_available(context $context): bool {
        global $USER;

        if (!get_config('tool_sherpa', 'enabled')) {
            return false;
        }
        if (!has_capability('tool/sherpa:usesupport', $context)) {
            return false;
        }
        if (array_key_exists($context->id, self::$availabilitycache)) {
            return self::$availabilitycache[$context->id];
        }

        // Mirror block_ai_chat: the chat is offered whenever the AI is not fully hidden for the
        // user. When it is "disabled" (e.g. AI usage not yet confirmed) the embedded chat shows the
        // corresponding hint/confirm link, just like block_ai_chat does.
        $aiconfig = ai_manager_utils::get_ai_config($USER, $context->id, null, [self::PURPOSE]);
        $purposeavailable = $aiconfig['purposes'][0]['available'] ?? ai_manager_utils::AVAILABILITY_HIDDEN;
        $available = $aiconfig['availability']['available'] !== ai_manager_utils::AVAILABILITY_HIDDEN
            && $purposeavailable !== ai_manager_utils::AVAILABILITY_HIDDEN;

        self::$availabilitycache[$context->id] = $available;
        return $available;
    }

    /**
     * Determine whether the embedded chat region should be offered.
     *
     * @param context $context the context in which support is requested
     * @return bool true if the chat can be embedded
     */
    public static function chat_available(context $context): bool {
        if (!get_config('tool_sherpa', 'showchat')) {
            return false;
        }
        if (!has_capability('block/ai_chat:view', $context)) {
            return false;
        }
        return self::is_available($context);
    }
}
