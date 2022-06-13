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
 * Solalert persistent
 *
 * @package   local_solalerts
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_solalerts;

use core\persistent;
use lang_string;

class solalert extends persistent {

    const TABLE = 'local_solalerts';
    public const CONTENTTYPE_ALERT = 'alert';
    public const CONTENTTYPE_BANNER = 'banner';
    public const CONTENTTYPE_NOTICE = 'notice';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'title' => [
                'type' => PARAM_TEXT,
                'null' => NULL_NOT_ALLOWED
            ],
            'content' => [
                'type' => PARAM_RAW,
                'null' => NULL_NOT_ALLOWED
            ],
            'contentformat' => [
                'choices' => array(FORMAT_HTML, FORMAT_MOODLE, FORMAT_PLAIN, FORMAT_MARKDOWN),
                'type' => PARAM_INT,
                'default' => FORMAT_HTML
            ],
            'contenttype' => [
                'type' => PARAM_ALPHA,
                'default' => self::CONTENTTYPE_ALERT,
                'choices' => [
                    self::CONTENTTYPE_ALERT,
                    self::CONTENTTYPE_BANNER,
                    self::CONTENTTYPE_NOTICE
                ]
            ],
            'alerttype' => [
                'type' => PARAM_ALPHA,
                'choices' => [
                    '',
                    \core\notification::ERROR,
                    \core\notification::INFO,
                    \core\notification::SUCCESS,
                    \core\notification::WARNING
                ]
            ],
            'pagetype' => [
                'type' => PARAM_RAW,
                'null' => NULL_NOT_ALLOWED,
                'choices' => \local_solalerts\api::pagetypes_menu(true)
            ],
            'userprofilefield' => [
                'type' => PARAM_RAW_TRIMMED,
                'null' => NULL_ALLOWED
            ],
            'coursefield' => [
                'type' => PARAM_RAW_TRIMMED
            ],
            'rolesincourse' => [
                'type' => PARAM_SEQUENCE
            ],
            'rolesinsystems' => [
                'type' => PARAM_SEQUENCE
            ],
            'displayfrom' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED
            ],
            'displayto' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED
            ],
            'enabled' => [
                'type' => PARAM_BOOL,
                'default' => false
            ]
        ];
    }

    // protected function validate_content($value) {
    //     return true;
    // }

    protected function validate_coursefield($value) {
        global $DB;
        if ($value == '') {
            return true;
        }
        if (strpos($value, '=') === false) {
            return new lang_string('invalidfieldformat', 'local_solalerts');
        }
        [$key, $value] = explode('=', $value);
        $sql = "SELECT f.id 
        FROM {customfield_field} f
        JOIN {customfield_category} c ON c.id=f.categoryid
        WHERE f.shortname = :shortname AND c.component='core_course' AND c.area='course'";
        if (!$DB->record_exists_sql($sql, ['shortname' => $key])) {
            return new lang_string('invalidfield', 'local_solalerts', $key);
        }
        return true;
    }

    protected function validate_pagetype($value) {
        $pagetypes = \local_solalerts\api::pagetypes_menu();
        if (!in_array($value, array_keys($pagetypes))) {
            return new lang_string('pagetyperequired', 'local_solalerts');
        }

        return true;
    }

    protected function validate_rolesincourse($value) {
        return true;
        if ($value == '') {
            return true;
        }
        $validroles = \local_solalerts\api::availableroles(CONTEXT_COURSE);
        $validrolekeys = array_keys($validroles);
        $roles = explode(',', $value);
        foreach($roles as $role) {
            if (!is_int($role)) {
                return new lang_string('invalidrole', 'local_solalerts');
            }
            if (!in_array($role, $validrolekeys)) {
                return new lang_string('invalidrole', 'local_solalerts');
            }
        }
        return true;
    }

    protected function validate_rolesinsystems($value) {
        return true;
        if ($value == '') {
            return true;
        }
        $validroles = \local_solalerts\api::availableroles(CONTEXT_SYSTEM);
        $validrolekeys = array_keys($validroles);
        $roles = explode(',', $value);
        foreach($roles as $role) {
            if (!is_int($role)) {
                return new lang_string('invalidrole', 'local_solalerts');
            }
            if (!in_array($role, $validrolekeys)) {
                return new lang_string('invalidrole', 'local_solalerts');
            }
        }
        return true;
    }

    protected function validate_userprofilefield($value) {
        global $DB;
        if ($value == '') {
            return true;
        }
        if (strpos($value, '=') === false) {
            return new lang_string('invalidfieldformat', 'local_solalerts');
        }
        [$key, $value] = explode('=', $value);
        if (!$DB->record_exists('user_info_field', ['shortname' => $key])) {
            return new lang_string('invalidfield', 'local_solalerts', $key);
        }
        return true;
    }
}