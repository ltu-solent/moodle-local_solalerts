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
 * Solent Alert form for persistent class
 *
 * @package   local_solalerts
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_solalerts\forms;

use context_system;
use core\form\persistent as persistentform;
use lang_string;
use local_solalerts\api;
use local_solalerts\filters\course_filter_customfield;
use local_solalerts\solalert;
use stdClass;
use user_filter_cohort;
use user_filter_profilefield;
use user_filter_text;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/user/filters/profilefield.php');

/**
 * Solalert Class
 */
class solalert_form extends persistentform {

    /**
     * Name of persistent class
     *
     * @var string
     */
    protected static $persistentclass = solalert::class;
    /**
     * Defined filter fields
     *
     * @var array
     */
    protected $_fields;

    /**
     * Solalert object editing form definition.
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;
        $required = new lang_string('required');
        $mform->addElement('text', 'title', new lang_string('title', 'local_solalerts'));
        $mform->addRule('title', $required, 'required', null, 'client');

        $mform->addElement('editor', 'content', new lang_string('content', 'local_solalerts'), null, $this->get_editor_options());
        $mform->setType('content', PARAM_RAW);
        $mform->addRule('content', $required, 'required', null, 'client');
        $mform->addHelpButton('content', 'content', 'local_solalerts');

        $choices = [
            solalert::CONTENTTYPE_ALERT => new lang_string('alert', 'local_solalerts'),
            solalert::CONTENTTYPE_NOTICE => new lang_string('notice', 'local_solalerts')
        ];
        $mform->addElement('select', 'contenttype', new lang_string('contenttype', 'local_solalerts'), $choices);
        $mform->addRule('contenttype', $required, 'required', null, 'client');
        $mform->addHelpButton('contenttype', 'contenttype', 'local_solalerts');

        $choices = [
            '' => new lang_string('choosealerttype', 'local_solalerts'),
            \core\notification::ERROR => new lang_string('error'),
            \core\notification::INFO => new lang_string('info'),
            \core\notification::SUCCESS => new lang_string('success'),
            \core\notification::WARNING => new lang_string('warning')
        ];
        $mform->addElement('select', 'alerttype', new lang_string('alerttype', 'local_solalerts'), $choices);
        $mform->hideIf('alerttype', 'contenttype', 'neq', solalert::CONTENTTYPE_ALERT);
        $mform->addHelpButton('alerttype', 'alerttype', 'local_solalerts');

        $mform->addElement('advcheckbox', 'enabled', new lang_string('enabled', 'local_solalerts'));

        $mform->addElement('text', 'sortorder', new lang_string('sortorder', 'local_solalerts'));

        $mform->addElement('header', 'displayconditionheader',
            new lang_string('displayconditions', 'local_solalerts')
        );
        $mform->addElement('html', get_string('displayconditions_desc', 'local_solalerts'));

        $choices = ['' => new lang_string('choosepagetype', 'local_solalerts')]
            + \local_solalerts\api::pagetypes_menu();
        $mform->addElement('select', 'pagetype', new lang_string('pagetype', 'local_solalerts'), $choices);
        $mform->addHelpButton('pagetype', 'pagetype', 'local_solalerts');
        $mform->addRule('pagetype', $required, 'required', null, 'client');

        $mform->addElement('date_time_selector', 'displayfrom', new lang_string('displayfrom', 'local_solalerts'),
            ['optional' => true]);
        $mform->addElement('date_time_selector', 'displayto', new lang_string('displayto', 'local_solalerts'),
            ['optional' => true]);

        $userfilters = [
            'userprofilefield' => 1,
            'department' => 1,
            'institution' => 1,
            'cohort' => 1,
            'coursecustomfield' => 1
        ];

        $this->_fields = [];
        foreach ($userfilters as $userfilter => $value) {
            if ($field = $this->get_field($userfilter, false)) {
                $this->_fields[$userfilter] = $field;
                $field->setupForm($mform);
            }
        }

        $choices = api::availableroles(CONTEXT_COURSE);
        $select = $mform->addElement('select', 'rolesincourse', new lang_string('courseroles', 'local_solalerts'), $choices);
        $select->setMultiple(true);

        $choices = api::availableroles(CONTEXT_SYSTEM);
        $select = $mform->addElement('select', 'rolesinsystem', new lang_string('systemroles', 'local_solalerts'), $choices);
        $select->setMultiple(true);

        $mform->addElement('hidden', 'usermodified');
        $mform->addElement('hidden', 'timemodified');

        $this->add_action_buttons();
    }

    /**
     * Options used by the text editor
     *
     * @return array
     */
    public function get_editor_options() {
        return ['maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean' => true, 'context' => context_system::instance()];
    }

    /**
     * Convert multipart and multioption into a storable object for the database.
     *
     * @param stdClass $data
     * @return object
     */
    protected static function convert_fields(stdClass $data) {
        $data = parent::convert_fields($data);
        // Multiselects are stored as commas-separated strings so we need to convert
        // the form array to the csv format.
        $filters = (object)[
            'rolesincourse' => implode(',', $data->rolesincourse),
            'rolesinsystem' => implode(',', $data->rolesinsystem),
            'userprofilefield' => (object)[
                'op' => $data->userprofilefield_op,
                'fld' => $data->userprofilefield_fld,
                'value' => $data->userprofilefield
            ],
            'coursecustomfield' => (object)[
                'op' => $data->coursecustomfield_op,
                'fld' => $data->coursecustomfield_fld,
                'value' => $data->coursecustomfield
            ],
            'department' => (object)[
                'op' => $data->department_op,
                'value' => $data->department
            ],
            'institution' => (object)[
                'op' => $data->institution_op,
                'value' => $data->institution
            ],
            'cohort' => (object)[
                'op' => $data->cohort_op,
                'value' => $data->cohort
            ]
        ];
        $data->filters = json_encode($filters);
        return $data;
    }

    /**
     * Get the data from the database and format it for form output.
     *
     * @return stdClass
     */
    protected function get_default_data() {
        $data = parent::get_default_data();
        $getfilters = $this->get_persistent()->get('filters');
        $filters = null;
        if ($getfilters) {
            $filters = json_decode($getfilters);
        }
        $data->rolesincourse = $filters->rolesincourse ?? [];
        $data->rolesinsystem = $filters->rolesinsystem ?? [];

        $profiledata = $filters->userprofilefield ?? null;
        if ($profiledata) {
            $data->userprofilefield = $profiledata->value;
            $data->userprofilefield_op = $profiledata->op;
            $data->userprofilefield_fld = $profiledata->fld;
        }
        $coursedata = $filters->coursecustomfield ?? null;
        if ($coursedata) {
            $data->coursecustomfield = $coursedata->value;
            $data->coursecustomfield_op = $coursedata->op;
            $data->coursecustomfield_fld = $coursedata->fld;
        }
        $department = $filters->department ?? null;
        if ($department) {
            $data->department = $department->value;
            $data->department_op = $department->op;
        }
        $institution = $filters->institution ?? null;
        if ($institution) {
            $data->institution = $institution->value;
            $data->institution_op = $institution->op;
        }
        $cohort = $filters->cohort ?? null;
        if ($cohort) {
            $data->cohort = $cohort->value;
            $data->cohort_op = $cohort->op;
        }
        return $data;
    }

    /**
     * Get user filter fields for more complex field types
     *
     * @param string $fieldname
     * @param bool $advanced
     * @return object|null
     */
    protected function get_field($fieldname, $advanced) {
        switch ($fieldname) {
            case 'userprofilefield':
                return new user_filter_profilefield('userprofilefield', get_string('profilefields', 'admin'), $advanced);
            case 'coursecustomfield':
                return new course_filter_customfield($fieldname, get_string('coursefield', 'local_solalerts'), $advanced);
            case 'institution':
                return new user_filter_text('institution', get_string('institution'), $advanced, 'institution');
            case 'department':
                return new user_filter_text('department', get_string('department'), $advanced, 'department');
            case 'cohort':
                return new user_filter_cohort($advanced);
            default:
                return null;
        }
    }

    /**
     * Prepare the content field for embedded images
     *
     * @param stdClass $data
     * @return void
     */
    public function set_data($data) {
        $id = $data->id ?? null;
        $data->contentformat = FORMAT_HTML;
        $data = file_prepare_standard_editor(
            $data,
            'content',
            $this->get_editor_options(),
            context_system::instance(),
            'local_solalerts',
            'alert',
            $id
        );
        $data->content['text'] = $data->content_editor['text']['text'];
        parent::set_data($data);
    }
}
