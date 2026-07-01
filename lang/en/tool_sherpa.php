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
$string['helpchooser_chatsystemprompt'] = 'You are "Sherpa", a friendly didactic assistant embedded in the Moodle activity chooser. A teacher is planning what to do in their course. Discuss their ideas with them, ask brief clarifying questions when helpful, and recommend which of the available Moodle activities fit their goal, explaining why and how they could use each one.

Available activities (modname: name):
{$a->activities}

Only ever recommend activities from this list, using their exact modname.

At the very end of EVERY reply, append one machine-readable line in exactly this format, listing the modnames you currently recommend (most relevant first, or empty if none yet):
[[SHERPA_ACTIVITIES: modname1, modname2]]
Do not mention or explain this line to the teacher.';
$string['helpchooser_demobadge'] = 'Demo';
$string['helpchooser_intro'] = 'Describe in your own words what you want your learners to do. Sherpa discusses ideas with you and suggests suitable activities.';
$string['helpchooser_open'] = 'Help, what should I do?';
$string['helpchooser_resultslabel'] = 'Sherpa suggestions';
$string['helpchooser_templatestitle'] = 'Example templates';
$string['helpchooser_templatesummary'] = 'A ready-made example for the "{$a}" activity.';
$string['helpchooser_templatetitle'] = '{$a} template';
$string['helpchooser_title'] = 'What would you like to do?';
$string['helpchooser_tutoriallabel'] = 'Tutorial: {$a}';
$string['helpchooser_tutorialstitle'] = 'Tutorials';
$string['helpchooser_unavailable'] = 'The AI assistant is not available for you here.';
$string['helpregiontitle'] = 'Help';
$string['linkplacement'] = 'Link placement';
$string['linksource'] = 'Link source';
$string['manage'] = 'Manage Sherpa';
$string['managesourcesandplacements'] = 'Manage sources and placements';
$string['mappingexists'] = 'This source and placement are already mapped.';
$string['materialsregiontitle'] = 'Further materials';
$string['modifiedby'] = 'Modified by';
$string['nomaterials'] = 'No further materials available.';
$string['notitle'] = 'No title';
$string['opensinnewtab'] = '(opens in a new tab)';
$string['placement'] = 'Placement';
$string['placementdeleted'] = 'Placement deleted';
$string['placements'] = 'Placements';
$string['pluginname'] = 'Sherpa';
$string['privacy:metadata'] = 'The Sherpa tool does not store any personal data itself. Support requests are processed and logged by the local_ai_manager subsystem.';
$string['sherpa:manage'] = 'Manage Sherpa sources and placements';
$string['sherpa:usesupport'] = 'Use Sherpa support';
$string['settings:enabled'] = 'Enable Sherpa';
$string['settings:enabled_desc'] = 'If enabled, Sherpa provides custom configurable support throughout the site.';
$string['settings:enablehelpchooser'] = 'Enable "Help, what should I do?"';
$string['settings:enablehelpchooser_desc'] = 'If enabled, teachers see a "Help, what should I do?" option in the activity chooser that opens an AI assisted activity chooser.';
$string['settings:heading'] = 'Sherpa support assistant';
$string['settings:heading_desc'] = 'Sherpa provides individual support at various places in the user interface, such as help icons.';
$string['settings:showchat'] = 'Show AI chat';
$string['settings:showchat_desc'] = 'If enabled, the Sherpa modal embeds the AI chat (when AI is available for the user).';
$string['settings:showmaterials'] = 'Show further materials';
$string['settings:showmaterials_desc'] = 'If enabled, the Sherpa modal lists further materials (sources) configured for the help topic.';
$string['settings:systemprompttemplate'] = 'System prompt template';
$string['settings:systemprompttemplate_default'] = 'You are "Sherpa", a helpful assistant for the Moodle learning platform. You help the user understand the control element "{title}" ({identifier},{component}).

Official help text:
---
{helptext_plain}
---
Further sources (URLs): {source_urls}

Answer in simple, clear language and stay within the given context.';
$string['settings:systemprompttemplate_desc'] = 'Template for the field specific system prompt sent to the AI chat. Placeholders: {title}, {component}, {identifier}, {helptext_plain}, {source_urls}.';
$string['settings:usemodal'] = 'Open help in a modal';
$string['settings:usemodal_desc'] = 'If enabled, clicking a core help icon opens a Sherpa modal instead of the popover. Default: classic popover.';
$string['sourcedeleted'] = 'Source deleted';
$string['sources'] = 'Sources';
$string['timecreated'] = 'Time created';
$string['timemodified'] = 'Time modified';
$string['title'] = 'Title';
$string['type'] = 'Type';
$string['type_formelement'] = 'Form element';
$string['type_langstring'] = 'Language string';
$string['type_path'] = 'Path';
$string['unlink'] = 'Unlink';
$string['unlinkconfirm'] = 'Are you sure you want to unlink "{$a}"?';
$string['url'] = 'URL';
$string['value'] = 'Value';
