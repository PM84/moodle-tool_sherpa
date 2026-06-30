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
 * Management page for Sherpa sources, placements and their mappings.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_reportbuilder\system_report_factory;
use core_reportbuilder\output\report_action;
use tool_sherpa\local\systemreports\placement_list;
use tool_sherpa\local\systemreports\source_list;

require(__DIR__ . '/../../../config.php');
require_once("{$CFG->libdir}/adminlib.php");

admin_externalpage_setup('tool_sherpa_manage');

$tab = optional_param('tab', 'sources', PARAM_ALPHA);
if (!in_array($tab, ['sources', 'placements'], true)) {
    $tab = 'sources';
}

$PAGE->requires->js_call_amd('tool_sherpa/management', 'init');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manage', 'tool_sherpa'));

$tabs = [
    new tabobject('sources',
        new moodle_url('/admin/tool/sherpa/manage.php', ['tab' => 'sources']),
        get_string('sources', 'tool_sherpa')),
    new tabobject('placements',
        new moodle_url('/admin/tool/sherpa/manage.php', ['tab' => 'placements']),
        get_string('placements', 'tool_sherpa')),
];
echo $OUTPUT->tabtree($tabs, $tab);

if ($tab === 'placements') {
    $report = system_report_factory::create(placement_list::class, context_system::instance());
    $report->set_report_action(new report_action(
        get_string('addplacement', 'tool_sherpa'),
        ['class' => 'btn btn-primary my-auto', 'data-action' => 'placement-create'],
    ));
} else {
    $report = system_report_factory::create(source_list::class, context_system::instance());
    $report->set_report_action(new report_action(
        get_string('addsource', 'tool_sherpa'),
        ['class' => 'btn btn-primary my-auto', 'data-action' => 'source-create'],
    ));
}

echo $report->output();

echo $OUTPUT->footer();
