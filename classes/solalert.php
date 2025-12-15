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

/**
 * Solalert persisten object
 */
class solalert extends persistent {
    /**
     * Table used by this class
     */
    const TABLE = 'local_solalerts';
    /**
     * Alert content type
     */
    public const CONTENTTYPE_ALERT = 'alert';
    /**
     * Banner content type
     */
    public const CONTENTTYPE_BANNER = 'banner';
    /**
     * Notice content type
     */
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
                'null' => NULL_NOT_ALLOWED,
            ],
            'content' => [
                'type' => PARAM_RAW,
                'null' => NULL_NOT_ALLOWED,
            ],
            'contentformat' => [
                'choices' => [FORMAT_HTML, FORMAT_MOODLE, FORMAT_PLAIN, FORMAT_MARKDOWN],
                'type' => PARAM_INT,
                'default' => FORMAT_HTML,
            ],
            'contenttype' => [
                'type' => PARAM_ALPHA,
                'default' => self::CONTENTTYPE_ALERT,
                'choices' => [
                    self::CONTENTTYPE_ALERT,
                    self::CONTENTTYPE_BANNER,
                    self::CONTENTTYPE_NOTICE,
                ],
            ],
            'alerttype' => [
                'type' => PARAM_ALPHA,
                'choices' => [
                    '',
                    \core\notification::ERROR,
                    \core\notification::INFO,
                    \core\notification::SUCCESS,
                    \core\notification::WARNING,
                ],
            ],
            'pagetype' => [
                'type' => PARAM_RAW,
                'null' => NULL_NOT_ALLOWED,
                'choices' => \local_solalerts\api::pagetypes_menu(true),
            ],
            'filters' => [
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
            ],
            'displayfrom' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
            ],
            'displayto' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
            ],
            'enabled' => [
                'type' => PARAM_BOOL,
                'default' => false,
            ],
            'sortorder' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
        ];
    }

    /**
     * Validate the page type
     *
     * @param string $value
     * @return bool|lang_string
     */
    protected function validate_pagetype($value) {
        $pagetypes = \local_solalerts\api::pagetypes_menu();
        if (!in_array($value, array_keys($pagetypes))) {
            return new lang_string('pagetyperequired', 'local_solalerts');
        }

        return true;
    }

    /**
     * Validate roles in course
     *
     * @param string $value
     * @return bool|lang_string
     */
    protected function validate_rolesincourse($value) {
        return true; // Why are we not validating?
        if ($value == '') {
            return true;
        }
        $validroles = \local_solalerts\api::availableroles(CONTEXT_COURSE);
        $validrolekeys = array_keys($validroles);
        $roles = explode(',', $value);
        foreach ($roles as $role) {
            if (!is_int($role)) {
                return new lang_string('invalidrole', 'local_solalerts');
            }
            if (!in_array($role, $validrolekeys)) {
                return new lang_string('invalidrole', 'local_solalerts');
            }
        }
        return true;
    }

    /**
     * Validate rolesinsystem
     *
     * @param string $value
     * @return bool|lang_string
     */
    protected function validate_rolesinsystem($value) {
        return true; // Why are we not validating?
        if ($value == '') {
            return true;
        }
        $validroles = \local_solalerts\api::availableroles(CONTEXT_SYSTEM);
        $validrolekeys = array_keys($validroles);
        $roles = explode(',', $value);
        foreach ($roles as $role) {
            if (!is_int($role)) {
                return new lang_string('invalidrole', 'local_solalerts');
            }
            if (!in_array($role, $validrolekeys)) {
                return new lang_string('invalidrole', 'local_solalerts');
            }
        }
        return true;
    }

    /**
     * Don't thin I need this
     *
     * @return string decoded json object
     */
    protected function get_userprofilefield() {
        $decode = json_decode($this->raw_get('userprofilefield'));
        return $decode;
    }
}
