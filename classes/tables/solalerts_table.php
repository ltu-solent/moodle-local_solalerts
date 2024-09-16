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
 * Solalerts table
 *
 * @package   local_solalerts
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_solalerts\tables;

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->libdir/tablelib.php");

use context_system;
use core_text;
use core_user;
use html_writer;
use lang_string;
use local_solalerts\filters\course_filter_customfield;
use moodle_url;
use table_sql;
use user_filter_cohort;
use user_filter_profilefield;
use user_filter_text;

/**
 * Solalerts table for listing all available solalerts
 */
class solalerts_table extends table_sql {

    /**
     * Pagetypes menu
     *
     * @var array
     */
    private $pagetypes = [];
    /**
     * System roles menu
     *
     * @var array
     */
    private $systemroles = [];
    /**
     * Course roles menu
     *
     * @var array
     */
    private $courseroles = [];
    /**
     * Constructor
     *
     * @param string $uniqueid
     */
    public function __construct($uniqueid) {
        parent::__construct($uniqueid);
        $this->useridfield = 'modifiedby';
        $this->pagetypes = \local_solalerts\api::pagetypes_menu();
        $this->systemroles = \local_solalerts\api::availableroles(CONTEXT_SYSTEM);
        $this->courseroles = \local_solalerts\api::availableroles(CONTEXT_COURSE);
        $columns = [
            'id',
            'title',
            'content',
            'contenttype',
            'displayconditions',
            'enabled',
            'sortorder',
            'usermodified',
            'timemodified',
            'actions',
        ];

        $columnheadings = [
            'id',
            new lang_string('title', 'local_solalerts'),
            new lang_string('content', 'local_solalerts'),
            new lang_string('contenttype', 'local_solalerts'),
            new lang_string('displayconditions', 'local_solalerts'),
            new lang_string('enabled', 'local_solalerts'),
            new lang_string('sortorder', 'local_solalerts'),
            new lang_string('modifiedby', 'local_solalerts'),
            new lang_string('lastmodified', 'local_solalerts'),
            new lang_string('actions', 'local_solalerts'),
        ];

        $this->define_columns($columns);
        $this->define_headers($columnheadings);
        $this->no_sorting('actions');
        $this->no_sorting('displayconditions');
        $this->sortable(true, 'id', SORT_DESC);
        $this->collapsible(false);
        $this->column_style('sortorder', 'text-align', 'center');

        $this->define_baseurl(new moodle_url("/local/solalerts/index.php"));
        $where = '1=1';
        $this->set_sql('*', "{local_solalerts}", $where);
    }

    /**
     * Actions column
     *
     * @param stdClass $col
     * @return string HTML formatted column data
     */
    public function col_actions($col) {
        $params = ['action' => 'edit', 'id' => $col->id];
        $edit = new moodle_url('/local/solalerts/edit.php', $params);
        $html = html_writer::link($edit, get_string('edit'));

        $params['action'] = 'delete';
        $delete = new moodle_url('/local/solalerts/edit.php', $params);
        $html .= " " . html_writer::link($delete, get_string('delete'));
        return $html;
    }

    /**
     * Output some of the alert content as a taster, but shortened
     *
     * @param stdClass $col
     * @return string HTML formatted column data
     */
    public function col_content($col) {
        return shorten_text(format_text($col->content, FORMAT_HTML), 150);
    }

    /**
     * Content type column
     *
     * @param stdClass $col
     * @return string HTML formatted column data
     */
    public function col_contenttype($col) {
        $contenttype = $col->contenttype;
        $html = ucfirst($contenttype);
        if ($contenttype == \local_solalerts\solalert::CONTENTTYPE_ALERT) {
            $html .= '<br /><small>' . ucfirst($col->alerttype) . '</small>';
        }
        return $html;
    }

    /**
     * Display conditions column
     *
     * @param stdClass $col
     * @return string HTML formatted column data
     */
    public function col_displayconditions($col) {
        $items = [];
        $filters = json_decode($col->filters);
        if ($col->pagetype != '') {
            $pagetype = $col->pagetype;
            if (isset($this->pagetypes[$pagetype])) {
                $pagetype = $this->pagetypes[$pagetype];
            }
            $items[] = get_string('pagetype', 'local_solalerts') . ': ' . $pagetype;
        }
        if (isset($filters->userprofilefield->value) && $filters->userprofilefield->value != '') {
            $profilefield = new user_filter_profilefield('userprofilefield', get_string('profilefields', 'admin'), false);
            $fielddata = [
                'profile' => $filters->userprofilefield->fld,
                'value' => $filters->userprofilefield->value,
                'operator' => $filters->userprofilefield->op,
            ];
            $item = $profilefield->get_label($fielddata);
            if (!empty($item)) {
                $items[] = $item;
            }
        }
        if (isset($filters->coursecustomfield->value) && $filters->coursecustomfield->value != '') {
            $customfield = new course_filter_customfield('coursecustomfield', get_string('coursefield', 'local_solalerts'), false);
            $fielddata = [
                'fieldid' => $filters->coursecustomfield->fld,
                'value' => $filters->coursecustomfield->value,
                'operator' => $filters->coursecustomfield->op,
            ];
            $item = $customfield->get_label($fielddata);
            if (!empty($item)) {
                $items[] = $item;
            }
        }
        if (isset($filters->institution->value) && $filters->institution->value != '') {
            $institution = new user_filter_text('institution', get_string('institution'), false, 'institution');
            $fielddata = [
                'value' => $filters->institution->value,
                'operator' => $filters->institution->op,
            ];
            $item = $institution->get_label($fielddata);
            if (!empty($item)) {
                $items[] = $item;
            }
        }
        if (isset($filters->department->value) && $filters->department->value != '') {
            $department = new user_filter_text('department', get_string('department'), false, 'department');
            $fielddata = [
                'value' => $filters->department->value,
                'operator' => $filters->department->op,
            ];
            $item = $department->get_label($fielddata);
            if (!empty($item)) {
                $items[] = $item;
            }
        }
        if (isset($filters->cohort->value) && $filters->cohort->value != '') {
            $cohort = new user_filter_cohort(false);
            $fielddata = [
                'value' => $filters->cohort->value,
                'operator' => $filters->cohort->op,
            ];
            $item = $cohort->get_label($fielddata);
            if (!empty($item)) {
                $items[] = $item;
            }
        }
        if (isset($filters->rolesincourse) && $filters->rolesincourse != '') {
            $roles = explode(',', $filters->rolesincourse);
            $rolenames = [];
            foreach ($roles as $role) {
                if (isset($this->courseroles[$role])) {
                    $rolenames[] = $this->courseroles[$role];
                }
            }
            if (count($rolenames) > 0) {
                $items[] = get_string('courseroles', 'local_solalerts') . ': ' . join(', ', $rolenames);
            }
        }
        if (isset($filters->rolesinsystem) && $filters->rolesinsystem != '') {
            $roles = explode(',', $filters->rolesinsystem);
            $rolenames = [];
            foreach ($roles as $role) {
                if (isset($this->systemroles[$role])) {
                    $rolenames[] = $this->systemroles[$role];
                }
            }
            if (count($rolenames) > 0) {
                $items[] = get_string('systemroles', 'local_solalerts') . ': ' . join(', ', $rolenames);
            }
        }
        if ($col->displayfrom > 0) {
            $items[] = get_string('displayfrom', 'local_solalerts') . ': ' . userdate($col->displayfrom);
        }
        if ($col->displayto > 0) {
            $items[] = get_string('displayto', 'local_solalerts') . ': ' . userdate($col->displayto);
        }
        return html_writer::alist($items);
    }

    /**
     * Enabled column
     *
     * @param stdClass $col
     * @return string HTML formatted column data
     */
    public function col_enabled($col) {
        return ($col->enabled) ? new lang_string('enabled', 'local_solalerts')
            : new lang_string('notenabled', 'local_solalerts');
    }

    /**
     * Time modified column
     *
     * @param stdClass $col
     * @return string HTML formatted column data
     */
    public function col_timemodified($col) {
        return userdate($col->timemodified, get_string('strftimedatetimeshort', 'core_langconfig'));
    }

    /**
     * Title column
     *
     * @param stdClass $col
     * @return string HTML formatted column data
     */
    public function col_title($col) {
        $params = ['action' => 'edit', 'id' => $col->id];
        $edit = new moodle_url('/local/solalerts/edit.php', $params);
        $html = html_writer::link($edit, $col->title, ['title' => get_string('edittitle', 'local_solalerts', $col->title)]);
        return $html;
    }

    /**
     * User modified column
     *
     * @param stdClass $col
     * @return string HTML formatted column data
     */
    public function col_usermodified($col) {
        $modifiedby = core_user::get_user($col->usermodified);
        if (!$modifiedby || $modifiedby->deleted) {
            return get_string('deleteduser', 'local_solalerts');
        }
        return fullname($modifiedby);
    }
}
