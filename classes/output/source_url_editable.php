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

namespace tool_sherpa\output;

use context_system;
use core\output\inplace_editable;
use core_external\external_api;
use tool_sherpa\local\persistent\source;

/**
 * Inline editable component for a source URL.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class source_url_editable extends inplace_editable {

    /**
     * Class constructor.
     *
     * @param source $source The source persistent.
     */
    public function __construct(source $source) {
        $editable = has_capability('tool/sherpa:manage', context_system::instance());
        $url = $source->get('url');

        parent::__construct(
            'tool_sherpa',
            'sourceurl',
            $source->get('id'),
            $editable,
            s($url),
            $url,
            get_string('editurl', 'tool_sherpa')
        );
    }

    /**
     * Update the source URL and return self, called from the inplace_editable callback.
     *
     * @param int $sourceid
     * @param string $value
     * @return self
     */
    public static function update(int $sourceid, string $value): self {
        external_api::validate_context(context_system::instance());
        require_capability('tool/sherpa:manage', context_system::instance());

        $source = new source($sourceid);

        $value = trim($value);
        if ($value !== '') {
            $source->set('url', $value);
            $source->update();
        }

        return new self($source);
    }
}
