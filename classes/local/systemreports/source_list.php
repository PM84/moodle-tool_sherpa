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

namespace tool_sherpa\local\systemreports;

use context_system;
use lang_string;
use moodle_url;
use pix_icon;
use stdClass;
use core_reportbuilder\system_report;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\report\{action, column, filter};
use tool_sherpa\local\persistent\source;
use tool_sherpa\output\badges;
use tool_sherpa\output\source_url_editable;

/**
 * System report listing the Sherpa sources.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class source_list extends system_report {

    /**
     * Initialise the report.
     */
    protected function initialise(): void {
        $alias = 'shs';
        $this->set_main_table('tool_sherpa_source', $alias);

        // Fields required by row actions (placeholder replacement).
        $this->add_base_fields("{$alias}.id, {$alias}.url");

        // Both entities are annotated so the filters are grouped into "Sources" and "Placements" sections.
        $this->annotate_entity('source', new lang_string('sources', 'tool_sherpa'));
        $this->annotate_entity('placement', new lang_string('placements', 'tool_sherpa'));

        $this->add_columns();
        $this->add_filters();
        $this->add_actions();

        $this->set_initial_sort_column('source:url', SORT_ASC);
        $this->set_downloadable(false);
    }

    /**
     * Ensure we can view the report.
     *
     * @return bool
     */
    protected function can_view(): bool {
        return has_capability('tool/sherpa:manage', context_system::instance());
    }

    /**
     * Add columns to the report.
     */
    protected function add_columns(): void {
        $alias = $this->get_main_table_alias();

        // URL column (inline editable).
        $this->add_column((new column(
            'url',
            new lang_string('url', 'tool_sherpa'),
            'source'
        ))
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$alias}.id, {$alias}.url")
            ->set_is_sortable(true, ["{$alias}.url"])
            ->add_callback(static function($value, stdClass $row): string {
                global $PAGE;
                return (new source_url_editable(new source(0, $row)))->render($PAGE->get_renderer('core'));
            })
        );

        // Mapped placements column (badges + link control).
        $this->add_column((new column(
            'placements',
            new lang_string('placements', 'tool_sherpa'),
            'source'
        ))
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$alias}.id")
            ->set_is_sortable(false)
            ->add_callback(static function($value, stdClass $row): string {
                return badges::placements_for_source((int) $row->id);
            })
        );
    }

    /**
     * Add filters to the report.
     */
    protected function add_filters(): void {
        global $DB;
        $alias = $this->get_main_table_alias();

        // Section "Sources": filter by url (own field).
        $this->add_filter((new filter(
            text::class,
            'url',
            new lang_string('url', 'tool_sherpa'),
            'source',
            "{$alias}.url"
        )));

        // Section "Placements": filter by type/value of the mapped placements (correlated subqueries).
        $typeconcat = $DB->sql_group_concat('p.type', ', ');
        $this->add_filter((new filter(
            text::class,
            'type',
            new lang_string('type', 'tool_sherpa'),
            'placement',
            "(SELECT {$typeconcat}
                FROM {tool_sherpa_mapping} m
                JOIN {tool_sherpa_placement} p ON p.id = m.placement
               WHERE m.source = {$alias}.id)"
        )));

        $valueconcat = $DB->sql_group_concat('p.value', ', ');
        $this->add_filter((new filter(
            text::class,
            'value',
            new lang_string('value', 'tool_sherpa'),
            'placement',
            "(SELECT {$valueconcat}
                FROM {tool_sherpa_mapping} m
                JOIN {tool_sherpa_placement} p ON p.id = m.placement
               WHERE m.source = {$alias}.id)"
        )));
    }

    /**
     * Add row actions to the report.
     */
    protected function add_actions(): void {
        // Edit action (re-opens the modal).
        $this->add_action((new action(
            new moodle_url('#'),
            new pix_icon('t/edit', ''),
            ['data-action' => 'source-edit', 'data-source-id' => ':id'],
            false,
            new lang_string('editsource', 'tool_sherpa')
        )));

        // Delete action.
        $this->add_action((new action(
            new moodle_url('#'),
            new pix_icon('t/delete', ''),
            [
                'data-action' => 'source-delete',
                'data-source-id' => ':id',
                'data-source-name' => ':url',
                'class' => 'text-danger',
            ],
            false,
            new lang_string('deletesource', 'tool_sherpa')
        )));
    }
}
