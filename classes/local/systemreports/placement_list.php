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
use html_writer;
use lang_string;
use moodle_url;
use pix_icon;
use stdClass;
use core_reportbuilder\system_report;
use core_reportbuilder\local\filters\{select, text};
use core_reportbuilder\local\report\{action, column, filter};
use tool_sherpa\placement as placementtype;
use tool_sherpa\local\persistent\placement;
use tool_sherpa\output\badges;
use tool_sherpa\output\placement_value_editable;

/**
 * System report listing the Sherpa placements.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class placement_list extends system_report {

    /**
     * Initialise the report.
     */
    protected function initialise(): void {
        $alias = 'shp';
        $this->set_main_table('tool_sherpa_placement', $alias);

        // Fields required by row actions (placeholder replacement).
        $this->add_base_fields("{$alias}.id, {$alias}.type, {$alias}.value");

        // Both entities are annotated so the filters are grouped into "Sources" and "Placements" sections.
        $this->annotate_entity('source', new lang_string('sources', 'tool_sherpa'));
        $this->annotate_entity('placement', new lang_string('placements', 'tool_sherpa'));

        $this->add_columns();
        $this->add_filters();
        $this->add_actions();

        $this->set_initial_sort_column('placement:type', SORT_ASC);
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

        // Type column (Bootstrap badge, edited via the modal only).
        $this->add_column((new column(
            'type',
            new lang_string('type', 'tool_sherpa'),
            'placement'
        ))
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$alias}.type")
            ->set_is_sortable(true, ["{$alias}.type"])
            ->add_callback(static function($value): string {
                if ((string) $value === '') {
                    return '';
                }
                return html_writer::span(get_string('type_' . $value, 'tool_sherpa'), 'badge bg-secondary text-white');
            })
        );

        // Value column (inline editable).
        $this->add_column((new column(
            'value',
            new lang_string('value', 'tool_sherpa'),
            'placement'
        ))
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$alias}.id, {$alias}.value")
            ->set_is_sortable(true, ["{$alias}.value"])
            ->add_callback(static function($value, stdClass $row): string {
                global $PAGE;
                return (new placement_value_editable(new placement(0, $row)))->render($PAGE->get_renderer('core'));
            })
        );

        // Mapped sources column (badges + link control).
        $this->add_column((new column(
            'sources',
            new lang_string('sources', 'tool_sherpa'),
            'source'
        ))
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$alias}.id")
            ->set_is_sortable(false)
            ->add_callback(static function($value, stdClass $row): string {
                return badges::sources_for_placement((int) $row->id);
            })
        );
    }

    /**
     * Add filters to the report.
     */
    protected function add_filters(): void {
        global $DB;
        $alias = $this->get_main_table_alias();

        // Section "Sources": filter by the url of the mapped sources (correlated subquery).
        $urlconcat = $DB->sql_group_concat('s.url', ', ');
        $this->add_filter((new filter(
            text::class,
            'url',
            new lang_string('url', 'tool_sherpa'),
            'source',
            "(SELECT {$urlconcat}
                FROM {tool_sherpa_mapping} m
                JOIN {tool_sherpa_source} s ON s.id = m.source
               WHERE m.placement = {$alias}.id)"
        )));

        // Section "Placements": filter by type (own field, dropdown) and value (own field).
        $this->add_filter((new filter(
            select::class,
            'type',
            new lang_string('type', 'tool_sherpa'),
            'placement',
            "{$alias}.type"
        ))
            ->set_options_callback(static function(): array {
                $options = [];
                foreach (placementtype::get_types() as $type) {
                    $options[$type] = get_string('type_' . $type, 'tool_sherpa');
                }
                return $options;
            })
        );

        $this->add_filter((new filter(
            text::class,
            'value',
            new lang_string('value', 'tool_sherpa'),
            'placement',
            "{$alias}.value"
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
            ['data-action' => 'placement-edit', 'data-placement-id' => ':id'],
            false,
            new lang_string('editplacement', 'tool_sherpa')
        )));

        // Delete action.
        $this->add_action((new action(
            new moodle_url('#'),
            new pix_icon('t/delete', ''),
            [
                'data-action' => 'placement-delete',
                'data-placement-id' => ':id',
                'data-placement-name' => ':value',
                'class' => 'text-danger',
            ],
            false,
            new lang_string('deleteplacement', 'tool_sherpa')
        )));
    }
}
