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

declare(strict_types=1);

namespace tool_sherpa\local\import;

/**
 * Value object representing a single row of the tutorial import.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_row {

    /**
     * Constructor.
     *
     * @param string $title the source title
     * @param string $url the source URL
     * @param string[] $bodyids the body id placement values
     * @param string[] $langstrings the raw "component:key" language string references
     */
    public function __construct(
        /** @var string The source title. */
        public string $title,
        /** @var string The source URL. */
        public string $url,
        /** @var string[] The body id placement values. */
        public array $bodyids = [],
        /** @var string[] The raw "component:key" language string references. */
        public array $langstrings = [],
    ) {
    }

    /**
     * Whether this row is empty and should be skipped.
     *
     * @return bool true if neither a title nor a URL is set
     */
    public function is_empty(): bool {
        return trim($this->title) === '' && trim($this->url) === '';
    }
}
