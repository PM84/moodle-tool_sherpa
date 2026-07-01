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

namespace tool_sherpa;

/**
 * Class representing a Sherpa placement.
 *
 * @package    tool_sherpa
 * @copyright  2026 MoodleMootDACH Team 8 - The one and only
 * @author     Dr. Peter Mayer
 * @author     Melanie Treitinger
 * @author     Kathleen Aermes
 * @author     Nikolai Jahreis
 * @author     Alexander Bias
 * @author     Joscha Sauerland
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class placement {
    /** @var string Placement attached to a form element. */
    public const TYPE_FORMELEMENT = 'formelement';

    /** @var string Placement attached to a language string. */
    public const TYPE_LANGSTRING = 'langstring';

    /** @var string Placement attached to a path. */
    public const TYPE_PATH = 'path';

    /** @var string Placement attached to a page, identified by its HTML body id (supports a "*" wildcard). */
    public const TYPE_BODYID = 'bodyid';

    /**
     * Returns the list of all available placement types.
     *
     * @return string[] The available placement types.
     */
    public static function get_types(): array {
        return [
            self::TYPE_FORMELEMENT,
            self::TYPE_LANGSTRING,
            self::TYPE_PATH,
            self::TYPE_BODYID,
        ];
    }
}
