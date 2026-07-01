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

/**
 * CSV import wizard for Sherpa tutorials (sources, placements and their mappings).
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_sherpa\form\import_preview_form;
use tool_sherpa\form\import_upload_form;
use tool_sherpa\local\import\import_row;
use tool_sherpa\local\import\importer;

require(__DIR__ . '/../../../config.php');
require_once("{$CFG->libdir}/adminlib.php");
require_once("{$CFG->libdir}/csvlib.class.php");

admin_externalpage_setup('tool_sherpa_import');

$context = context_system::instance();
require_capability('tool/sherpa:manage', $context);

$pageurl = new moodle_url('/admin/tool/sherpa/import.php');
$manageurl = new moodle_url('/admin/tool/sherpa/manage.php', ['tab' => 'sources']);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('importheading', 'tool_sherpa'));

$iid = optional_param('iid', 0, PARAM_INT);

if ($iid) {
    // Step 2: preview / confirm.
    $cir = new csv_import_reader($iid, 'tool_sherpa');
    $rows = importer::read_rows($cir);

    $previewform = new import_preview_form($pageurl, ['iid' => $iid, 'rows' => $rows]);

    if ($previewform->is_cancelled()) {
        $cir->cleanup();
        redirect($pageurl);
    } else if ($data = $previewform->get_data()) {
        $importrows = [];
        foreach (($data->row ?? []) as $row) {
            $importrows[] = new import_row(
                (string) ($row['title'] ?? ''),
                (string) ($row['url'] ?? ''),
                importer::split_cell((string) ($row['bodyids'] ?? '')),
                importer::split_cell((string) ($row['langstrings'] ?? '')),
            );
        }

        $summary = (new importer())->import($importrows);
        $cir->cleanup();

        echo $OUTPUT->notification(get_string('importsummary', 'tool_sherpa', $summary),
            \core\output\notification::NOTIFY_SUCCESS);
        echo $OUTPUT->continue_button($manageurl);
    } else {
        echo html_writer::div(get_string('importpreviewintro', 'tool_sherpa'), 'mb-3');
        $previewform->display();
    }
} else {
    // Step 1: upload.
    $uploadform = new import_upload_form($pageurl);

    if ($uploadform->is_cancelled()) {
        redirect($manageurl);
    } else if ($data = $uploadform->get_data()) {
        $content = $uploadform->get_file_content('csvfile');

        $newiid = csv_import_reader::get_new_iid('tool_sherpa');
        $cir = new csv_import_reader($newiid, 'tool_sherpa');
        $readcount = $cir->load_csv_content($content, 'UTF-8', 'semicolon', null, '"');

        if ($readcount === false || $readcount === null) {
            echo $OUTPUT->notification($cir->get_error(), \core\output\notification::NOTIFY_ERROR);
            $cir->cleanup();
            $uploadform->display();
        } else {
            $rows = importer::read_rows($cir);
            if (empty($rows)) {
                echo $OUTPUT->notification(get_string('importnothing', 'tool_sherpa'),
                    \core\output\notification::NOTIFY_ERROR);
                $cir->cleanup();
                $uploadform->display();
            } else {
                $previewform = new import_preview_form($pageurl, ['iid' => $newiid, 'rows' => $rows]);
                echo html_writer::div(get_string('importpreviewintro', 'tool_sherpa'), 'mb-3');
                $previewform->display();
            }
        }
    } else {
        echo html_writer::div(get_string('importheading_desc', 'tool_sherpa'), 'mb-3');
        $uploadform->display();
    }
}

echo $OUTPUT->footer();
