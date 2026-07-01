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
use tool_sherpa\local\persistent\mapping;

/**
 * Modal form to link a Sherpa source with a placement.
 *
 * The form works in two directions, depending on the anchor passed as argument:
 * - "sourceid" given: pick a placement to link to that source.
 * - "placementid" given: pick a source to link to that placement.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mapping_form extends dynamic_form {

    /**
     * Whether the form is anchored on a source (and therefore picks a placement).
     *
     * @return bool
     */
    protected function is_source_anchor(): bool {
        return (bool) $this->optional_param('sourceid', 0, PARAM_INT);
    }

    /**
     * Form definition.
     */
    public function definition() {
        global $DB;

        $mform = $this->_form;

        $sourceid = $this->optional_param('sourceid', 0, PARAM_INT);
        $placementid = $this->optional_param('placementid', 0, PARAM_INT);

        $mform->addElement('hidden', 'sourceid', $sourceid);
        $mform->setType('sourceid', PARAM_INT);
        $mform->addElement('hidden', 'placementid', $placementid);
        $mform->setType('placementid', PARAM_INT);

        if ($this->is_source_anchor()) {
            // Anchored on a source: pick a not-yet-linked placement.
            $sql = "SELECT p.id, p.type, p.value
                      FROM {tool_sherpa_placement} p
                     WHERE p.id NOT IN (
                           SELECT m.placement FROM {tool_sherpa_mapping} m WHERE m.source = :sourceid)
                  ORDER BY p.type ASC, p.value ASC";
            $options = ['' => ''];
            foreach ($DB->get_records_sql($sql, ['sourceid' => $sourceid]) as $record) {
                $options[$record->id] = get_string('type_' . $record->type, 'tool_sherpa') . ': ' . $record->value;
            }
            $mform->addElement('autocomplete', 'placement', get_string('placement', 'tool_sherpa'), $options,
                ['noselectionstring' => get_string('choosedots')]);
            $mform->addRule('placement', null, 'required', null, 'client');
        } else {
            // Anchored on a placement: pick a not-yet-linked source.
            $sql = "SELECT s.id, s.url
                      FROM {tool_sherpa_source} s
                     WHERE s.id NOT IN (
                           SELECT m.source FROM {tool_sherpa_mapping} m WHERE m.placement = :placementid)
                  ORDER BY s.url ASC";
            $options = ['' => ''];
            foreach ($DB->get_records_sql($sql, ['placementid' => $placementid]) as $record) {
                $options[$record->id] = $record->url;
            }
            $mform->addElement('autocomplete', 'source', get_string('url', 'tool_sherpa'), $options,
                ['noselectionstring' => get_string('choosedots')]);
            $mform->addRule('source', null, 'required', null, 'client');
        }
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
     * Resolve the source and placement ids from submitted data.
     *
     * @param \stdClass $data
     * @return array [$placementid, $sourceid]
     */
    protected function resolve_ids(\stdClass $data): array {
        if (!empty($data->sourceid)) {
            return [(int) ($data->placement ?? 0), (int) $data->sourceid];
        }
        return [(int) $data->placementid, (int) ($data->source ?? 0)];
    }

    /**
     * Validate the submitted data.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files): array {
        $errors = [];

        [$placementid, $sourceid] = $this->resolve_ids((object) $data);

        if (empty($placementid) || empty($sourceid)) {
            $errors[$this->is_source_anchor() ? 'placement' : 'source'] = get_string('required');
        } else if (mapping::mapping_exists($placementid, $sourceid)) {
            $errors[$this->is_source_anchor() ? 'placement' : 'source'] = get_string('mappingexists', 'tool_sherpa');
        }

        return $errors;
    }

    /**
     * Process the form submission.
     *
     * @return array
     */
    public function process_dynamic_submission() {
        $data = $this->get_data();
        [$placementid, $sourceid] = $this->resolve_ids($data);

        $mapping = new mapping(0);
        $mapping->set('placement', $placementid);
        $mapping->set('source', $sourceid);
        $mapping->save();

        return ['id' => $mapping->get('id')];
    }

    /**
     * Load existing data into the form (nothing to preload).
     */
    public function set_data_for_dynamic_submission(): void {
        $this->set_data((object) [
            'sourceid' => $this->optional_param('sourceid', 0, PARAM_INT),
            'placementid' => $this->optional_param('placementid', 0, PARAM_INT),
        ]);
    }

    /**
     * URL of the page using this form.
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        return new moodle_url('/admin/tool/sherpa/manage.php');
    }
}
