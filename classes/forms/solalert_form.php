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
 * @package   local_solalert
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_solalerts\forms;

use context_system;
use core\form\persistent as persistentform;
use lang_string;
use local_solalerts\api;
use local_solalerts\solalert;
use stdClass;

class solalert_form extends persistentform {

    protected static $persistentclass = solalert::class; //solalert::class;

    public function definition() {
        global $CFG;
        $context = context_system::instance();
        $mform = $this->_form;
        $required = new lang_string('required');
        $mform->addElement('text', 'title', new lang_string('title', 'local_solalerts'));
        $mform->addRule('title', $required, 'required', null, 'client');
        $mform->addElement('editor', 'content', new lang_string('content', 'local_solalerts'), null, ['autosave' => false]);
        $mform->setType('content', PARAM_RAW);
        $mform->addRule('content', $required, 'required', null, 'client');
        $mform->addHelpButton('content', 'content', 'local_solalerts');

        $choices = [
            solalert::CONTENTTYPE_ALERT => new lang_string('alert', 'local_solalerts'),
            solalert::CONTENTTYPE_BANNER => new lang_string('banner', 'local_solalerts'),
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

        $choices = ['' => new lang_string('choosepagetype', 'local_solalerts')]
            + \local_solalerts\api::pagetypes_menu();
        $mform->addElement('select', 'pagetype', new lang_string('pagetype', 'local_solalerts'), $choices);
        $mform->addHelpButton('pagetype', 'pagetype', 'local_solalerts');
        $mform->addRule('pagetype', $required, 'required', null, 'client');

        $mform->addElement('text', 'userprofilefield', new lang_string('userprofilefield', 'local_solalerts'));
        $mform->addHelpButton('userprofilefield', 'userprofilefield', 'local_solalerts');

        $mform->addElement('text', 'coursefield', new lang_string('coursefield', 'local_solalerts'));
        $mform->addHelpButton('coursefield', 'coursefield', 'local_solalerts');

        $choices = api::availableroles(CONTEXT_COURSE);
        $select = $mform->addElement('select', 'rolesincourse', new lang_string('courseroles', 'local_solalerts'), $choices);
        $select->setMultiple(true);

        $choices = api::availableroles(CONTEXT_SYSTEM);
        $select = $mform->addElement('select', 'rolesinsystems', new lang_string('systemroles', 'local_solalerts'), $choices);
        $select->setMultiple(true);

        $mform->addElement('date_time_selector', 'displayfrom', new lang_string('displayfrom', 'local_solalerts'), ['optional' => true]);
        $mform->addElement('date_time_selector', 'displayto', new lang_string('displayto', 'local_solalerts'), ['optional' => true]);

        $mform->addElement('advcheckbox', 'enabled', new lang_string('enabled', 'local_solalerts'));

        $mform->addElement('hidden', 'usermodified');
        $mform->addElement('hidden', 'timemodified');

        $this->add_action_buttons();
    }

    /**
     * Convert some fields.
     *
     * @param stdClass $data
     * @return object
     */
    protected static function convert_fields(stdClass $data) {
        $data = parent::convert_fields($data);
        // Multiselects are stored as commas-separated strings so we need to convert
        // the form array to the csv format.
        $data->rolesincourse = implode(',', $data->rolesincourse);
        $data->rolesinsystems = implode(',', $data->rolesinsystems);
        return $data;
    }

    /**
     * Get the default data.
     *
     * @return stdClass
     */
    protected function get_default_data() {
        $data = parent::get_default_data();
        $data->rolesincourse = $this->get_persistent()->get('rolesincourse');
        $data->rolesinsystems = $this->get_persistent()->get('rolesinsystems');
        return $data;
    }

}