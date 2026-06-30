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
use tool_sherpa\local\persistent\placement;

/**
 * Inline editable component for a placement value.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class placement_value_editable extends inplace_editable {

    /**
     * Class constructor.
     *
     * @param placement $placement The placement persistent.
     */
    public function __construct(placement $placement) {
        $editable = has_capability('tool/sherpa:manage', context_system::instance());
        $value = $placement->get('value');

        parent::__construct(
            'tool_sherpa',
            'placementvalue',
            $placement->get('id'),
            $editable,
            s($value),
            $value,
            get_string('editvalue', 'tool_sherpa')
        );
    }

    /**
     * Update the placement value and return self, called from the inplace_editable callback.
     *
     * @param int $placementid
     * @param string $value
     * @return self
     */
    public static function update(int $placementid, string $value): self {
        external_api::validate_context(context_system::instance());
        require_capability('tool/sherpa:manage', context_system::instance());

        $placement = new placement($placementid);

        $value = trim($value);
        if ($value !== '') {
            $placement->set('value', $value);
            $placement->update();
        }

        return new self($placement);
    }
}
