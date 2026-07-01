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
 * Language strings for tool_sherpa.
 *
 * @package    tool_sherpa
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['addplacement'] = 'Add placement';
$string['addsource'] = 'Add source';
$string['chatregiontitle'] = 'Ask Sherpa';
$string['chatunavailable'] = 'The AI assistant is not available for you here.';
$string['deleteplacement'] = 'Delete placement';
$string['deleteplacementconfirm'] = 'Are you sure you want to delete the placement "{$a}" and all of its source mappings?';
$string['deletesource'] = 'Delete source';
$string['deletesourceconfirm'] = 'Are you sure you want to delete the source "{$a}" and all of its placement mappings?';
$string['editplacement'] = 'Edit placement';
$string['editsource'] = 'Edit source';
$string['editurl'] = 'Edit URL';
$string['editvalue'] = 'Edit value';
$string['enabled'] = 'Enable Sherpa';
$string['enabled_desc'] = 'If enabled, Sherpa provides individual AI based support throughout the site.';
$string['helpregiontitle'] = 'Help';
$string['linkplacement'] = 'Link placement';
$string['linksource'] = 'Link source';
$string['manage'] = 'Manage Sherpa';
$string['managesourcesandplacements'] = 'Manage sources and placements';
$string['mappingexists'] = 'This source and placement are already mapped.';
$string['materialsregiontitle'] = 'Further materials';
$string['modifiedby'] = 'Modified by';
$string['nomaterials'] = 'No further materials available.';
$string['opensinnewtab'] = '(opens in a new tab)';
$string['placement'] = 'Placement';
$string['placementdeleted'] = 'Placement deleted';
$string['placements'] = 'Placements';
$string['pluginname'] = 'Sherpa';
$string['privacy:metadata'] = 'The Sherpa tool does not store any personal data itself. Support requests are processed and logged by the local_ai_manager subsystem.';
$string['settingsheading'] = 'Sherpa support assistant';
$string['settingsheading_desc'] = 'Sherpa reuses the AI functionality of local_ai_manager and block_ai_chat to provide individual support at various places in the user interface, such as help icons.';
$string['sherpa:manage'] = 'Manage Sherpa sources and placements';
$string['sherpa:usesupport'] = 'Use Sherpa support';
$string['showchat'] = 'Show AI chat';
$string['showchat_desc'] = 'If enabled, the Sherpa modal embeds the AI chat (when AI is available for the user).';
$string['showmaterials'] = 'Show further materials';
$string['showmaterials_desc'] = 'If enabled, the Sherpa modal lists further materials (sources) configured for the help topic.';
$string['sourcedeleted'] = 'Source deleted';
$string['sources'] = 'Sources';
$string['systemprompttemplate'] = 'System prompt template';
$string['systemprompttemplate_default'] = 'You are "Sherpa", a helpful assistant for the Moodle learning platform. You help the user understand the control element "{title}" ({identifier},{component}).

Official help text:
---
{helptext_plain}
---
Further sources (URLs): {source_urls}

Answer in simple, clear language and stay within the given context.';
$string['systemprompttemplate_desc'] = 'Template for the field specific system prompt sent to the AI chat. Placeholders: {title}, {component}, {identifier}, {helptext_plain}, {source_urls}.';
$string['timecreated'] = 'Time created';
$string['timemodified'] = 'Time modified';
$string['type'] = 'Type';
$string['type_formelement'] = 'Form element';
$string['type_langstring'] = 'Language string';
$string['type_path'] = 'Path';
$string['unlink'] = 'Unlink';
$string['unlinkconfirm'] = 'Are you sure you want to unlink "{$a}"?';
$string['url'] = 'URL';
$string['usemodal'] = 'Open help in a modal';
$string['usemodal_desc'] = 'If enabled, clicking a core help icon opens a Sherpa modal instead of the popover. Default: classic popover.';
$string['value'] = 'Value';
