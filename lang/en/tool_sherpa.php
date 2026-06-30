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

$string['asksherpa'] = 'Ask Sherpa';
$string['enabled'] = 'Enable Sherpa';
$string['enabled_desc'] = 'If enabled, Sherpa provides individual AI based support throughout the site.';
$string['error_airequestfailed'] = 'The AI support request could not be completed.';
$string['error_supportunavailable'] = 'AI support is currently not available for you in this context.';
$string['helpiconintegration'] = 'Help icon integration';
$string['helpiconintegration_desc'] = 'If enabled, an "Ask Sherpa" action is shown next to core help icons.';
$string['nohelpcontext'] = 'No help topic was provided, so Sherpa cannot offer specific support.';
$string['pluginname'] = 'Sherpa';
$string['privacy:metadata'] = 'The Sherpa tool does not store any personal data itself. Support requests are processed and logged by the local_ai_manager subsystem.';
$string['purpose'] = 'AI purpose';
$string['purpose_desc'] = 'The local_ai_manager purpose used to generate support answers (e.g. singleprompt).';
$string['purposeusagedescription'] = 'Used to generate individual support answers for help topics.';
$string['settingsheading'] = 'Sherpa support assistant';
$string['settingsheading_desc'] = 'Sherpa reuses the AI functionality of local_ai_manager and block_ai_chat to provide individual support at various places in the user interface, such as help icons.';
$string['sherpa:usesupport'] = 'Use Sherpa support';
$string['sherpa:editsupport'] = 'Edit Sherpa support materials';
$string['supportintro'] = 'Sherpa can help you understand this topic. Here is an explanation:';
$string['supportpromptprefix'] = 'You are Sherpa, a friendly assistant for a Moodle learning platform. Explain the following help topic to the user in simple, clear language.';
