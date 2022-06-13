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
 * Language file for Sol Alerts
 *
 * @package   local_solalerts
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['actions'] = 'Actions';
$string['alert'] = 'Alert';
$string['alerttype'] = 'Alert type';
$string['alerttype_help'] = 'Alert types will display with different coloured text. Use the most appropriate.';

$string['banner'] = 'Banner';

$string['choosealerttype'] = 'Choose alert type';
$string['choosepagetype'] = 'Choose page type';
$string['confirmdelete'] = 'Confirm deletion of "{$a}"';
$string['content'] = 'Alert content';
$string['content_help'] = 'Depending on the content type, the content will be put in different containers.
Just include the alert text for an alert. How it\'s displayed will be done for you.';
$string['contenttype'] = 'Content type';
$string['contenttype_help'] = 'Alerts will be grouped by their type (Banner, Alert, Notices, Dashlinks) and 
will be displayed differently, according to their type. Dashlinks are created in code by other plugins.';
$string['coursefield'] = 'Course field';
$string['coursefield_help'] = 'Specify the coursefield shortname followed by equals followed by the value.
e.g. faculty=FBLDT';
$string['courseroles'] = 'Course roles';

$string['delete'] = 'Delete';
$string['deleted'] = '"{$a}" has been deleted.';
$string['deleteduser'] = 'Deleted user';
$string['displayconditions'] = 'Display conditions';
$string['displayfrom'] = 'Display from';
$string['displayto'] = 'Display to';

$string['edit'] = 'Edit';
$string['editsolalert'] = 'Edit SolAlert';
$string['enabled'] = 'Enabled';

$string['invalidfield'] = 'Specified field ({$a}) does not exist.';
$string['invalidfieldformat'] = 'The entry must be of the format fieldname=value';

$string['lastmodified'] = 'Last modified';

$string['managealerts'] = 'Manage alerts';
$string['modifiedby'] = 'Modified by';

$string['newsaved'] = 'New SolAlert created';
$string['newsolalert'] = 'Create new alert';
$string['notenabled'] = 'Not enabled';
$string['notice'] = 'Notice';

$string['pagetype'] = 'Pagetype';
$string['pagetype_help'] = 'The pagetype is the id of the body element in the html e.g. on the Dashboard, the pagetype is "page-my-index"';
$string['pagetyperequired'] = 'You must specify a pagetype - this is to prevent messages showing on every page.';
$string['pagetypes'] = 'Pagetypes';
$string['pagetypes_desc'] = 'One pagetype per line. This is to help make choosing a pagetype easier.';
$string['pluginname'] =  'Sol Alerts';
$string['privacy:metadata'] = 'The local_solalert Sol Alert plugin does not store any personal data.';

$string['solalerts'] = 'SolAlerts';
$string['systemroles'] = 'System roles';

$string['title'] = 'Title';

$string['updated'] = '"{$a}" has been updated.';
$string['userprofilefield'] = 'User profile field';
$string['userprofilefield_help'] = 'Specify the user profile field shortname followed by equals followed by the value.
e.g. faculty=FBLDT';