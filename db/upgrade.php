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
 * Upgrade solalerts
 *
 * @package   local_solalerts
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2023 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade function
 *
 * @param int $version
 * @return bool
 */
function xmldb_local_solalerts_upgrade($version) {
    global $DB;
    $dbman = $DB->get_manager();
    if ($version < 2022060605) {
        $table = new xmldb_table('local_solalerts');
        $field = new xmldb_field('filters', XMLDB_TYPE_TEXT, null, null, false, null, null, 'pagetype');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // No longer require userprofilefield, coursefield, rolesincourse or rolesinsystem as these will all
        // be incorporated in the single filters field as json.
        // But first grab any existing values to store as json.
        $alerts = $DB->get_records('local_solalerts');
        foreach ($alerts as $alert) {
            $filter = [];
            // Userprofilefield: fieldname=value.
            if (!empty(trim($alert->userprofilefield))) {
                [$fieldname, $fieldvalue] = explode('=', $alert->userprofilefield);
                $field = $DB->get_record('user_info_field', ['shortname' => $fieldname]);
                if ($field) {
                    $upf = (object)[
                        'op' => 2, // Equals.
                        'fld' => $field->id,
                        'value' => trim($fieldvalue)
                    ];
                    $filter['userprofilefield'] = $upf;
                }
            }
            // Rolesincourse & Rolesinsystem: A list of comma separated ids.
            // Let's keep this the same so we can target multiple roles.
            if (!empty(trim($alert->rolesincourse))) {
                $filter['rolesincourse'] = $alert->rolesincourse;
            }
            if (!empty(trim($alert->rolesinsystems))) {
                $filter['rolesinsystem'] = $alert->rolesinsystems;
            }

            if (!empty($alert->coursefield)) {
                [$fieldname, $fieldvalue] = explode('=', $alert->coursefield);
                $field = $DB->get_record('customfield_field', ['shortname' => $fieldname]);
                if ($field) {
                    $ccf = (object)[
                        'op' => 2, // Equals.
                        'fld' => $field->id,
                        'value' => trim($fieldvalue)
                    ];
                    $filter['coursecustomfield'] = $ccf;
                }
            }
            $alert->filters = '';
            if (!empty($filter)) {
                $alert->filters = json_encode($filter);
            }
            $alert->timemodified = time();
            $DB->update_record('local_solalerts', $alert);
        }
        $field = new xmldb_field('userprofilefield');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        $field = new xmldb_field('rolesincourse');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        $field = new xmldb_field('rolesinsystems');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        $field = new xmldb_field('coursefield');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2022060605, 'local', 'solalerts');
    }
    if ($version < 2022060607) {
        $table = new xmldb_table('local_solalerts');
        $field = new xmldb_field('sortorder', XMLDB_TYPE_INTEGER, '4', true, false, null, '0', 'enabled');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $index = new xmldb_index('sortorder', XMLDB_INDEX_NOTUNIQUE, ['sortorder']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        upgrade_plugin_savepoint(true, 2022060607, 'local', 'solalerts');
    }
    return true;
}
