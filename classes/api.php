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

use context_system;
use local_solalerts\filters\course_filter_customfield;
use user_filter_cohort;
use user_filter_profilefield;
use user_filter_text;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/user/filters/profilefield.php');
/**
 * Helper class
 */
class api {

    /**
     * Contains
     */
    const TEXT_FILTER_CONTAINS = 0;
    /**
     * Does not contain
     */
    const TEXT_FILTER_DOESNOTCONTAIN = 1;
    /**
     * Is equals to
     */
    const TEXT_FILTER_ISEQUALSTO = 2;
    /**
     * Start with
     */
    const TEXT_FILTER_STARTSWITH = 3;
    /**
     * Ends with
     */
    const TEXT_FILTER_ENDSWITH = 4;
    /**
     * Is empty
     */
    const TEXT_FILTER_ISEMPTY = 5;
    /**
     * Is not defined
     */
    const TEXT_FILTER_ISNOTDEFINED = 6;
    /**
     * Is defined
     */
    const TEXT_FILTER_ISDEFINED = 7;
    /**
     * Gets a list of roles for either the system or course context.
     *
     * @param int $contextlevel
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

    /**
     * List of pagetypes for select menu
     *
     * @param boolean $valuesonly
     * @return array
     */
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

    /**
     * List of userfields for select
     *
     * @return array
     */
    public static function userfields_menu() {
        $fields = [
            'idnumber' => get_string('idnumber'),
            'firstname' => get_string('firstname'),
            'lastname' => get_string('lastname'),
        ];
        return $fields;
    }

    /**
     * Can this alert be displayed to this user in this context?
     *
     * @param stdClass $sa Solalert object
     * @param string $pagetype The id of the page
     * @param \context $pagecontext
     * @param integer $courseid
     * @return boolean
     */
    public static function can_display($sa, $pagetype, $pagecontext, $courseid = 0) {
        global $COURSE, $DB, $USER;
        $pagetype = (strpos($pagetype, 'page-') === 0) ? $pagetype : 'page-' . $pagetype;
        $validpagetypes = self::pagetypes_menu();
        $validpagetype = $validtimeframe = $validuser = $validcoursefield = $validuserfield = true;
        $filters = json_decode($sa->filters);
        if ($sa->enabled == false) {
            return false;
        }
        if ($sa->pagetype != '' && $pagetype != $sa->pagetype) {
            return false;
        }
        if (!isset($validpagetypes[$sa->pagetype])) {
            return false;
        }
        if ($sa->displayfrom > 0 && $sa->displayfrom > time()) {
            $validtimeframe = false;
        }
        if ($sa->displayto > 0 && $sa->displayto < time()) {
            $validtimeframe = false;
        }
        if (!$validtimeframe) {
            return false;
        }

        if (isset($filters->coursecustomfield->value) && $filters->coursecustomfield->value != '') {
            if (strpos($pagetype, 'page-course-view') === false) {
                return false;
            }
            $customfield = new course_filter_customfield('coursecustomfield', get_string('coursefield', 'local_solalerts'), false);
            $fielddata = [
                'fieldid' => $filters->coursecustomfield->fld,
                'value' => $filters->coursecustomfield->value,
                'operator' => $filters->coursecustomfield->op,
            ];
            [$sql, $params] = $customfield->get_sql_filter($fielddata);
            if (empty($sql)) {
                // There's nothing to search for.
                return false;
            }
            $sql = "SELECT id FROM {course} WHERE " . $sql . " AND id = :courseid";
            $params['courseid'] = $courseid;
            $datas = $DB->get_records_sql($sql, $params);
            if (!isset($datas[$courseid])) {
                $validcoursefield = false;
                return false;
            }
        }

        if (isset($filters->userprofilefield->value) && $filters->userprofilefield->value != '') {
            $profilefield = new user_filter_profilefield('userprofilefield', get_string('profilefields', 'admin'), false);
            $fielddata = [
                'profile' => $filters->userprofilefield->fld,
                'value' => $filters->userprofilefield->value,
                'operator' => $filters->userprofilefield->op,
            ];
            [$sql, $params] = $profilefield->get_sql_filter($fielddata);
            if (empty($sql)) {
                return false;
            }
            $sql = "SELECT id FROM {user} WHERE " . $sql . " AND id = :userid";
            $params['userid'] = $USER->id;
            $datas = $DB->get_records_sql($sql, $params);
            if (!isset($datas[$USER->id])) {
                return false;
            }
        }

        if (isset($filters->rolesincourse) && $filters->rolesincourse != '') {
            $rolesincourse = explode(',', $filters->rolesincourse);
            // We need to allow checking parent context, as the person may be viewing an activity,
            // and the parent is the course.
            $userroles = get_user_roles($pagecontext, $USER->id);
            $hasroles = array_filter($userroles, function($role) use ($rolesincourse) {
                return in_array($role->roleid, $rolesincourse);
            });
            if (count($hasroles) == 0) {
                return false;
            }
        }

        if (isset($filters->rolesinsystem) && $filters->rolesinsystem != '') {
            $rolesinsystem = explode(',', $filters->rolesinsystem);
            $userroles = get_user_roles(context_system::instance(), $USER->id, false);
            $hasroles = array_filter($userroles, function($role) use ($rolesinsystem) {
                return in_array($role->roleid, $rolesinsystem);
            });
            if (count($hasroles) == 0) {
                return false;
            }
        }

        if (isset($filters->department->value) && !empty($filters->department->value)) {
            $department = new user_filter_text('department', get_string('department'), false, 'department');
            $fielddata = [
                'operator' => $filters->department->op,
                'value' => $filters->department->value,
            ];
            [$sql, $params] = $department->get_sql_filter($fielddata);
            if (empty($sql)) {
                return false;
            }
            $sql = "SELECT id FROM {user} WHERE " . $sql . " AND id = :userid";
            $params['userid'] = $USER->id;
            $datas = $DB->get_records_sql($sql, $params);
            if (!isset($datas[$USER->id])) {
                return false;
            }
        }

        if (isset($filters->institution->value) && !empty($filters->institution->value)) {
            $institution = new user_filter_text('institution', get_string('institution'), false, 'institution');
            $fielddata = [
                'operator' => $filters->institution->op,
                'value' => $filters->institution->value,
            ];
            [$sql, $params] = $institution->get_sql_filter($fielddata);
            if (empty($sql)) {
                return false;
            }
            $sql = "SELECT id FROM {user} WHERE " . $sql . " AND id = :userid";
            $params['userid'] = $USER->id;
            $datas = $DB->get_records_sql($sql, $params);
            if (!isset($datas[$USER->id])) {
                return false;
            }
        }

        if (isset($filters->cohort->value) && !empty($filters->cohort->value)) {
            $cohort = new user_filter_cohort('cohort', get_string('cohort', 'local_solalerts'), false, 'cohort');
            $fielddata = [
                'operator' => $filters->cohort->op,
                'value' => $filters->cohort->value,
            ];
            [$sql, $params] = $cohort->get_sql_filter($fielddata);
            if (empty($sql)) {
                return false;
            }
            $sql = "SELECT id FROM {user} WHERE " . $sql . " AND id = :userid";
            $params['userid'] = $USER->id;
            $datas = $DB->get_records_sql($sql, $params);
            if (!isset($datas[$USER->id])) {
                return false;
            }
        }

        $display = ($validpagetype && $validtimeframe && $validcoursefield && $validuser);
        return $display;
    }
}
