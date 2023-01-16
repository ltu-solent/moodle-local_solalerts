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
 * API Helper class
 *
 * @package   local_solalerts
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_solalerts;

use function local_placeholders\get_rolenames_for_ids;

class api {

    /**
     * Gets a list of roles for either the system or course context.
     *
     * @param int $context
     * @return array
     */
    public static function availableroles($contextlevel = CONTEXT_SYSTEM): array {
        global $DB;
        $sql = "SELECT r.id, r.name, r.shortname, r.description, r.sortorder, r.archetype
            FROM {role_context_levels} rcl
            JOIN {role} r ON r.id = rcl.roleid
            WHERE rcl.contextlevel = :contextlevel";
        $roles = $DB->get_records_sql($sql, ['contextlevel' => $contextlevel]);
        $roles = role_fix_names($roles, null, ROLENAME_ORIGINAL, true);
        return $roles;
    }

    public static function pagetypes_menu($valuesonly = false) {
        $pagetypes = explode("\n", get_config('local_solalerts', 'pagetypes'));
        $manager = get_string_manager();
        $menu = [];
        foreach ($pagetypes as $pagetype) {
            $pagetype = trim($pagetype);
            if ($pagetype == '') {
                continue;
            }
            if ($valuesonly) {
                $menu[] = $pagetype;
            } else {
                if ($manager->string_exists($pagetype, 'local_solalerts')) {
                    $menu[$pagetype] = get_string($pagetype, 'local_solalerts');
                } else {
                    $menu[$pagetype] = ucwords(str_replace('-', ' ', preg_replace('/^page-/', '', $pagetype)));
                }
            }
        }
        return $menu;
    }

    public static function userfields_menu() {
        $fields = [
            'idnumber' => get_string('idnumber'),
            'firstname' => get_string('firstname'),
            'lastname' => get_string('lastname'),
        ];
        return $fields;
    }
}
