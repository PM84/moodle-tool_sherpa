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

namespace tool_sherpa\form;

/**
 * Step 1 of the tutorial import: upload a CSV file.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_upload_form extends \moodleform {

    /**
     * Form definition.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement(
            'filepicker',
            'csvfile',
            get_string('csvfile', 'tool_sherpa'),
            null,
            ['accepted_types' => ['.csv', '.txt']]
        );
        $mform->addRule('csvfile', null, 'required', null, 'client');
        $mform->addHelpButton('csvfile', 'csvfile', 'tool_sherpa');

        $this->add_action_buttons(true, get_string('continue'));
    }
}
