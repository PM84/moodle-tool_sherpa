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
 * Upgrade steps for tool_sherpa.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the tool_sherpa plugin.
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool
 */
function xmldb_tool_sherpa_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026063001) {

        // Drop the obsolete "source" column (and its index) from the placement table. Mappings now
        // live in their own table.
        $placement = new xmldb_table('tool_sherpa_placement');
        $sourcefield = new xmldb_field('source');
        if ($dbman->field_exists($placement, $sourcefield)) {
            $sourceindex = new xmldb_index('source', XMLDB_INDEX_NOTUNIQUE, ['source']);
            if ($dbman->index_exists($placement, $sourceindex)) {
                $dbman->drop_index($placement, $sourceindex);
            }
            $dbman->drop_field($placement, $sourcefield);
        }

        // Add the "value" column to the placement table.
        $valuefield = new xmldb_field('value', XMLDB_TYPE_CHAR, '1333', null, XMLDB_NOTNULL, null, null, 'type');
        if (!$dbman->field_exists($placement, $valuefield)) {
            $dbman->add_field($placement, $valuefield);
        }

        // Rename the legacy plural "sources" table to the singular "source".
        $legacysources = new xmldb_table('tool_sherpa_sources');
        $source = new xmldb_table('tool_sherpa_source');
        if ($dbman->table_exists($legacysources) && !$dbman->table_exists($source)) {
            $dbman->rename_table($legacysources, 'tool_sherpa_source');
        }

        // Create any still missing tables straight from the install definition (e.g. the mapping table,
        // or all tables on installations where they were never created).
        foreach (['tool_sherpa_source', 'tool_sherpa_placement', 'tool_sherpa_mapping'] as $tablename) {
            $table = new xmldb_table($tablename);
            if (!$dbman->table_exists($table)) {
                $dbman->install_one_table_from_xmldb_file(
                    "{$CFG->dirroot}/admin/tool/sherpa/db/install.xml", $tablename);
            }
        }

        // Ensure the standard persistent audit fields exist on the pre-existing tables. Tables that were
        // just created from install.xml above already carry these columns (and their key), so guarding on
        // the first field keeps this idempotent.
        foreach (['tool_sherpa_source', 'tool_sherpa_placement'] as $tablename) {
            $table = new xmldb_table($tablename);
            $usermodified = new xmldb_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            if (!$dbman->field_exists($table, $usermodified)) {
                $dbman->add_field($table, $usermodified);
                $dbman->add_field($table,
                    new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0'));
                $dbman->add_field($table,
                    new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0'));
                $dbman->add_key($table, new xmldb_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']));
            }
        }

        // Make sure the source URL field matches the install definition length.
        $source = new xmldb_table('tool_sherpa_source');
        $urlfield = new xmldb_field('url', XMLDB_TYPE_CHAR, '1333', null, XMLDB_NOTNULL, null, null, 'id');
        if ($dbman->field_exists($source, $urlfield)) {
            $dbman->change_field_precision($source, $urlfield);
        }

        upgrade_plugin_savepoint(true, 2026063001, 'tool', 'sherpa');
    }

    return true;
}
