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

require_once("$CFG->libdir/tablelib.php");

use context_system;
use core_user;
use html_writer;
use lang_string;
use moodle_url;
use table_sql;

class solalerts_table extends table_sql {

    private $pagetypes = [];
    private $systemroles = [];
    private $courseroles = [];
    public function __construct($uniqueid) {
        parent::__construct($uniqueid);
        $this->useridfield = 'modifiedby';
        $this->pagetypes = \local_solalerts\api::pagetypes_menu();
        $this->systemroles = \local_solalerts\api::availableroles(CONTEXT_SYSTEM);
        $this->courseroles = \local_solalerts\api::availableroles(CONTEXT_COURSE);
        $columns = [
            'id',
            'title',
            'contenttype',
            'displayconditions',
            'enabled',
            'usermodified',
            'timemodified',
            'actions'
        ];

        $columnheadings = [
            'id',
            new lang_string('title', 'local_solalerts'),
            new lang_string('contenttype', 'local_solalerts'),
            new lang_string('displayconditions', 'local_solalerts'),
            new lang_string('enabled', 'local_solalerts'),
            new lang_string('modifiedby', 'local_solalerts'),
            new lang_string('lastmodified', 'local_solalerts'),
            new lang_string('actions', 'local_solalerts'),
        ];

        $this->define_columns($columns);
        $this->define_headers($columnheadings);
        $this->no_sorting('actions');
        $this->no_sorting('displayconditions');
        $this->sortable(true, 'id');
        $this->collapsible(false);

        $this->define_baseurl(new moodle_url("/local/solalerts/index.php"));
        $where = '1=1';
        $this->set_sql('*', "{local_solalerts}", $where);
    }

    public function col_contenttype($col) {
        $contenttype = $col->contenttype;
        $html = $contenttype;
        if ($contenttype == \local_solalerts\solalert::CONTENTTYPE_ALERT) {
            $html .= '<br /><small>' . $col->alerttype . '</small>';
        }
        return $html;
    }

    public function col_displayconditions($col) {
        $items = [];
        if ($col->pagetype != '') {
            $pagetype = $col->pagetype;
            if (isset($this->pagetypes[$pagetype])) {
                $pagetype = $this->pagetypes[$pagetype];
            }
            $items[] = get_string('pagetype', 'local_solalerts') . ': ' . $pagetype;
        }
        if ($col->userprofilefield != '') {
            $items[] = get_string('userprofilefield', 'local_solalerts') . ': ' . $col->userprofilefield;
        }
        if ($col->coursefield != '') {
            $items[] = get_string('coursefield', 'local_solalerts') . ': ' . $col->coursefield;
        }
        if ($col->rolesincourse != '') {
            $roles = explode(',', $col->rolesincourse);
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
        if ($col->rolesinsystems != '') {
            $roles = explode(',', $col->rolesinsystems);
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

    public function col_enabled($col) {
        return ($col->enabled) ? new lang_string('enabled', 'local_solalerts')
            : new lang_string('notenabled', 'local_solalerts');
    }

    public function col_usermodified($col) {
        $modifiedby = core_user::get_user($col->usermodified);
        if (!$modifiedby || $modifiedby->deleted) {
            return get_string('deleteduser', 'local_solalerts');
        }
        return fullname($modifiedby);
    }

    public function col_timemodified($col) {
        return userdate($col->timemodified, get_string('strftimedatetimeshort', 'core_langconfig'));
    }

    public function col_actions($col) {
        $params = ['action' => 'edit', 'id' => $col->id];
        $edit = new moodle_url('/local/solalerts/edit.php', $params);
        $html = html_writer::link($edit, get_string('edit'));

        $params['action'] = 'delete';
        $delete = new moodle_url('/local/solalerts/edit.php', $params);
        $html .= " " . html_writer::link($delete, get_string('delete'));
        return $html;
    }
}
