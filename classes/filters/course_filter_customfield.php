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
 * Field type based on user field filters
 *
 * @package   local_solalerts
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2023 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_solalerts\filters;

use stdClass;

/**
 * Complex form element for filtering by Course field
 */
class course_filter_customfield {
    /**
     * Field name
     *
     * @var string
     */
    public $name;
    /**
     * Field label
     *
     * @var string
     */
    public $label;
    /**
     * Is this an advanced field type
     *
     * @var bool
     */
    public $advanced;
    /**
     * Constructor
     *
     * @param string $name
     * @param string $label
     * @param bool $advanced
     */
    public function __construct($name, $label, $advanced) {
        $this->name     = $name;
        $this->label    = $label;
        $this->advanced = $advanced;
    }

    /**
     * Returns an array of comparison operators
     * @return array of comparison operators
     */
    public function get_operators() {
        return [
            0 => get_string('contains', 'filters'),
            1 => get_string('doesnotcontain', 'filters'),
            2 => get_string('isequalto', 'filters'),
            3 => get_string('startswith', 'filters'),
            4 => get_string('endswith', 'filters'),
            5 => get_string('isempty', 'filters'),
            6 => get_string('isnotdefined', 'filters'),
            7 => get_string('isdefined', 'filters'),
        ];
    }

    /**
     * Get a menu of available custom fields
     *
     * @return array
     */
    public function get_custom_fields(): array {
        global $DB;
        $sql = "SELECT f.id, cat.name catname, f.name fieldname
        FROM {customfield_category} cat
        JOIN {customfield_field} f ON f.categoryid = cat.id
        WHERE cat.component = 'core_course' AND cat.area = 'course'";
        $fieldrecords = $DB->get_records_sql($sql);
        $fields = [];
        foreach ($fieldrecords as $fieldrecord) {
            $fields[$fieldrecord->id] = $fieldrecord->catname . ': ' . $fieldrecord->fieldname;
        }
        $res = [0 => get_string('anyfield', 'filters')];
        return $res + $fields;
    }

    /**
     * Setup the form element.
     *
     * @param object $mform
     * @return void
     */
    public function setupForm(&$mform) { // phpcs:ignore
        $customfields = $this->get_custom_fields();
        if (empty($customfields)) {
            return;
        }
        $objs = [];
        $objs['field'] = $mform->createElement('select', $this->name . '_fld', null, $customfields);
        $objs['op'] = $mform->createElement('select', $this->name . '_op', null, $this->get_operators());
        $objs['value'] = $mform->createElement('text', $this->name, null);
        $objs['field']->setLabel(get_string('coursefilterfield', 'local_solalerts'));
        $objs['op']->setLabel(get_string('coursefilterlimiter', 'local_solalerts'));
        $objs['value']->setLabel(get_string('valuefor', 'filters', $this->label));
        $grp =& $mform->addElement('group', $this->name . '_grp', $this->label, $objs, '', false);
        $mform->setType($this->name, PARAM_RAW);
        if ($this->advanced) {
            $mform->setAdvanced($this->name . '_grp');
        }
    }

    /**
     * Check the data is formatted correctly
     *
     * @param stdClass $formdata
     * @return ?array|bool Formated formdata
     */
    public function check_data($formdata) {
        $customfields = $this->get_custom_fields();
        if (empty($customfields)) {
            return;
        }
        $field    = $this->name;
        $operator = $field . '_op';
        $fieldid  = $field . '_fld';

        if (property_exists($formdata, $field)) {
            if ($formdata->$operator < 5 && $formdata->$field === '') {
                return false;
            }

            return [
                'value'    => (string)$formdata->$field,
                'operator' => (int)$formdata->$operator,
                'fieldid'  => (int)$formdata->$fieldid,
            ];
        }
    }

    /**
     * Get SQL fragment for filtering
     *
     * @param array $data
     * @return array [SQL, params]
     */
    public function get_sql_filter($data) {
        global $DB;
        static $counter = 0;
        $name = 'ex_coursefield' . $counter++;
        $customfields = $this->get_custom_fields();
        if (empty($customfields)) {
            return;
        }
        $value    = $data['value'];
        $operator = $data['operator'];
        $fieldid  = $data['fieldid'];
        $params = [];
        if (!array_key_exists($fieldid, $customfields)) {
            return ['', []];
        }

        $where = "";
        $op = " IN ";
        if ($operator < 5 && $value === '') {
            return '';
        }
        switch ($operator) {
            case 0: // Contains.
                $where = $DB->sql_like('value', ":$name", false, false);
                $params[$name] = "%$value%";
                break;
            case 1: // Does not contain.
                $where = $DB->sql_like('value', ":$name", false, false, true);
                $params[$name] = "%$value%";
                break;
            case 2: // Equal to.
                $where = $DB->sql_like('value', ":$name", false, false);
                $params[$name] = "$value";
                break;
            case 3: // Starts with.
                $where = $DB->sql_like('value', ":$name", false, false);
                $params[$name] = "$value%";
                break;
            case 4: // Ends with.
                $where = $DB->sql_like('value', ":$name", false, false);
                $params[$name] = "%$value";
                break;
            case 5: // Empty.
                $where = "value = :$name";
                $params[$name] = "";
                break;
            case 6: // Is not defined.
                $op = " NOT IN ";
                break;
            case 7: // Is defined.
                break;
        }

        if ($fieldid) {
            if ($where !== '') {
                $where = " AND $where";
            }
            $where = "fieldid=$fieldid $where";
        }
        if ($where !== '') {
            $where = "WHERE $where";
        }
        return ["id $op (SELECT instanceid FROM {customfield_data} $where) ", $params];
    }

    /**
     * Get display label
     *
     * @param array $data
     * @return string
     */
    public function get_label($data) {
        $operators = $this->get_operators();
        $customfields = $this->get_custom_fields();
        if (empty($customfields)) {
            return '';
        }
        $fieldid = $data['fieldid'];
        $operator = $data['operator'];
        $value = $data['value'];

        if (!array_key_exists($fieldid, $customfields)) {
            return '';
        }

        $a = new stdClass();
        $a->label    = $this->label;
        $a->value    = $value;
        $a->field  = $customfields[$fieldid];
        $a->operator = $operators[$operator];

        switch ($operator) {
            case 0: // Contains.
            case 1: // Doesn't contain.
            case 2: // Equal to.
            case 3: // Starts with.
            case 4: // Ends with.
                return get_string('courselabel', 'local_solalerts', $a);
            case 5: // Empty.
            case 6: // Is not defined.
            case 7: // Is defined.
                return get_string('courselabelnovalue', 'local_solalerts', $a);
        }
        return '';
    }
}
