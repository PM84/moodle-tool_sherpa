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
 * Step 2 of the tutorial import: an inline editable preview of the parsed rows.
 *
 * Each parsed source is rendered as an editable group of four fields (title, URL, body IDs and
 * language strings) so the data can be corrected before it is written to the database.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_preview_form extends \moodleform {

    /**
     * Form definition.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'iid', $this->_customdata['iid']);
        $mform->setType('iid', PARAM_INT);

        /** @var \tool_sherpa\local\import\import_row[] $rows */
        $rows = $this->_customdata['rows'];

        foreach ($rows as $i => $row) {
            $group = [
                $mform->createElement('text', 'title', '',
                    ['size' => 25, 'placeholder' => get_string('title', 'tool_sherpa')]),
                $mform->createElement('text', 'url', '',
                    ['size' => 30, 'placeholder' => get_string('url', 'tool_sherpa')]),
                $mform->createElement('text', 'bodyids', '',
                    ['size' => 25, 'placeholder' => get_string('bodyids', 'tool_sherpa')]),
                $mform->createElement('text', 'langstrings', '',
                    ['size' => 25, 'placeholder' => get_string('langstrings', 'tool_sherpa')]),
            ];
            $mform->addGroup($group, "row[$i]", (string) ($i + 1), ' ', true);

            $mform->setType("row[$i][title]", PARAM_TEXT);
            $mform->setType("row[$i][url]", PARAM_RAW_TRIMMED);
            $mform->setType("row[$i][bodyids]", PARAM_RAW_TRIMMED);
            $mform->setType("row[$i][langstrings]", PARAM_RAW_TRIMMED);

            $mform->setDefault("row[$i][title]", $row->title);
            $mform->setDefault("row[$i][url]", $row->url);
            $mform->setDefault("row[$i][bodyids]", implode('; ', $row->bodyids));
            $mform->setDefault("row[$i][langstrings]", implode('; ', $row->langstrings));
        }

        $this->add_action_buttons(true, get_string('confirmimport', 'tool_sherpa'));
    }
}
