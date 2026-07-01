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

use context;
use context_system;
use core_form\dynamic_form;
use moodle_url;
use tool_sherpa\local\persistent\source;

/**
 * Modal form to create or edit a Sherpa source.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class source_form extends dynamic_form {

    /**
     * Form definition.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'title', get_string('title', 'tool_sherpa'), ['size' => 60]);
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');
        $mform->addRule('title', get_string('maximumchars', '', 255), 'maxlength', 255);

        $mform->addElement('text', 'url', get_string('url', 'tool_sherpa'), ['size' => 60]);
        $mform->setType('url', PARAM_RAW_TRIMMED);
        $mform->addRule('url', null, 'required', null, 'client');
        $mform->addRule('url', get_string('maximumchars', '', 1333), 'maxlength', 1333);
    }

    /**
     * Return the context for the form.
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        return context_system::instance();
    }

    /**
     * Ensure the current user is allowed to use this form.
     */
    protected function check_access_for_dynamic_submission(): void {
        require_capability('tool/sherpa:manage', $this->get_context_for_dynamic_submission());
    }

    /**
     * Process the form submission.
     *
     * @return array
     */
    public function process_dynamic_submission() {
        $data = $this->get_data();

        $source = new source($data->id ?: 0);
        $source->set('title', $data->title);
        $source->set('url', $data->url);
        $source->save();

        return ['id' => $source->get('id')];
    }

    /**
     * Load existing data into the form.
     */
    public function set_data_for_dynamic_submission(): void {
        $id = $this->optional_param('id', 0, PARAM_INT);
        if ($id) {
            $source = new source($id);
            $this->set_data((object) [
                'id' => $source->get('id'),
                'title' => $source->get('title'),
                'url' => $source->get('url'),
            ]);
        }
    }

    /**
     * URL of the page using this form.
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        return new moodle_url('/admin/tool/sherpa/manage.php', ['tab' => 'sources']);
    }
}
