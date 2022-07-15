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
 * Lib file for callbacks
 *
 * @package   local_solalert
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_solalerts\api;
use local_solalerts\solalert;

defined('MOODLE_INTERNAL') || die();

function local_solalerts_solentzone_alerts($alerts) {
    global $COURSE, $DB, $PAGE, $USER;
    $pagetype = 'page-' . $PAGE->pagetype;
    $validpagetypes = \local_solalerts\api::pagetypes_menu();
    $sas = $DB->get_records('local_solalerts', ['contenttype' => solalert::CONTENTTYPE_ALERT, 'enabled' => true]);
    foreach ($sas as $sa) {
        $validpagetype = $validtimeframe = $validuser = $validcoursefield = $validuserfield = true;
        if ($sa->pagetype != '' && $pagetype != $sa->pagetype) {
            continue;
        }
        if (!isset($validpagetypes[$sa->pagetype])) {
            continue;
        }
        if ($sa->displayfrom > 0 && $sa->displayfrom > time()) {
            $validtimeframe = false;
        }
        if ($sa->displayto > 0 && $sa->displayto < time()) {
            $validtimeframe = false;
        }
        if (!$validtimeframe) {
            continue;
        }
        if ($sa->coursefield != '') {
            if (strpos($pagetype, 'page-course-view') === false) {
                continue;
            }
            $handler = \core_customfield\handler::get_handler('core_course', 'course');
            $datas = $handler->get_instance_data($COURSE->id);
            [$fieldname, $fieldvalue] = explode('=', $sa->coursefield);
            $hasmatch = false;
            foreach ($datas as $data) {
                if ($data->get_field()->get('shortname') != $fieldname) {
                    continue;
                }
                if ($data->get_value() != $fieldvalue) {
                    continue;
                }
                $hasmatch = true;
                break;
            }
            if (!$hasmatch) {
                continue;
            }
        }

        if ($sa->userprofilefield != '') {
            [$fieldname, $fieldvalue] = explode('=', $sa->userprofilefield);
            if (!isset($USER->profile[$fieldname])) {
                continue;
            }
            if ($USER->profile[$fieldname] != $fieldvalue) {
                continue;
            }
        }
        // Perhaps check capability rather than role.
        if ($sa->rolesincourse != '') {
            $rolesincourse = explode(',', $sa->rolesincourse);
            // We need to allow checking parent context, as the person may be viewing an activity,
            // and the parent is the course.
            $userroles = get_user_roles($PAGE->context, $USER->id);
            $hasroles = array_filter($userroles, function($role) use ($rolesincourse) {
                return in_array($role->roleid, $rolesincourse);
            });
            if (count($hasroles) == 0) {
                continue;
            }
        }
        if ($sa->rolesinsystems != '') {
            $rolesinsystems = explode(',', $sa->rolesinsystems);
            $userroles = get_user_roles(context_system::instance(), $USER->id, false);
            $hasroles = array_filter($userroles, function($role) use ($rolesinsystems) {
                return in_array($role->roleid, $rolesinsystems);
            });
            if (count($hasroles) == 0) {
                continue;
            }
        }
        $display = ($validpagetype && $validtimeframe && $validcoursefield && $validuser);
        if ($display) {
            $alerts[] = new \core\output\notification(clean_text($sa->content, FORMAT_PLAIN), $sa->alerttype);
        }
    }
    return $alerts;
}

function local_solalerts_solentzone_banners($banners) {
    return $banners;
}