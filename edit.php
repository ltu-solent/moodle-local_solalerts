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
 * Edit an alert
 *
 * @package   local_solalerts
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_solalerts\forms\solalert_form;
use local_solalerts\solalert;

require_once('../../config.php');
require_login(null, false);
$context = context_system::instance();
require_capability('local/solalerts:managealerts', $context);

$id = optional_param('id', 0, PARAM_INT);
$action = optional_param('action', 'new', PARAM_ALPHA);
$confirmdelete = optional_param('confirmdelete', null, PARAM_BOOL);

$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');

if (!in_array($action, ['edit', 'delete', 'new'])) {
    $action = 'new';
}
$pageparams = [
    'action' => $action,
    'id' => $id,
];

$solalert = null;
$form = null;

if ($action == 'edit' || $action == 'delete') {
    if ($id == 0) {
        throw new moodle_exception('invalidid', 'local_solalerts');
    }
} else {
    $action = 'new';
}
$editoroptions = ['maxfiles' => -1, 'noclean' => true, 'context' => context_system::instance()];
$solalert = new solalert($id);

$customdata = [
    'persistent' => $solalert,
    'userid' => $USER->id,
];

if ($confirmdelete && confirm_sesskey()) {
    $title = $solalert->get('title');
    $solalert->delete();
    redirect(
        new moodle_url('/local/solalerts/index.php'),
        get_string('deleted', 'local_solalerts', $title),
        null,
        \core\output\notification::NOTIFY_INFO
    );
}

$PAGE->set_url(new moodle_url('/local/solalerts/edit.php', $pageparams));
$form = new solalert_form($PAGE->url->out(false), $customdata);
if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/solalerts/index.php'));
}

if ($formdata = $form->get_data()) {
    if (empty($formdata->id)) {
        $solalert = new solalert(0, $formdata);
        $solalert->create();
        $data = new stdClass();
        $data->id = $solalert->get('id');
        $data->contentformat = FORMAT_HTML;
        $data->content_editor = [
            'text' => $solalert->get('content'),
            'format' => FORMAT_HTML,
            'itemid' => $solalert->get('id'),
        ];
        $data = file_postupdate_standard_editor(
            $data,
            'content',
            $editoroptions,
            $context,
            'local_solalerts',
            'alert',
            $solalert->get('id')
        );
        $solalert->set('content', $data->content);
        $solalert->update();
        redirect(
            new moodle_url('/local/solalerts/index.php'),
            get_string('newsaved', 'local_solalerts'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } else {
        $solalert = new solalert($formdata->id);

        if ($action == 'edit') {
            $solalert->from_record($formdata);
            $data = new stdClass();
            $data->id = $solalert->get('id');
            $data->content = $solalert->get('content');
            $data->content_editor = [
                'text' => $solalert->get('content'),
                'format' => FORMAT_HTML,
                'itemid' => $solalert->get('id'),
            ];
            $data->contentformat = FORMAT_HTML;
            $data = file_postupdate_standard_editor(
                $data,
                // The field name in the database.
                'content',
                $editoroptions,
                // The combination of contextid, component, filearea, and itemid.
                context_system::instance(),
                'local_solalerts',
                'alert',
                $solalert->get('id')
            );
            // Set image file urls to @@PLUGINFILE@@ etc.
            $solalert->set('content', $data->content);
            $solalert->update();
            redirect(
                new moodle_url('/local/solalerts/index.php'),
                get_string('updated', 'local_solalerts', $formdata->title),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        }
    }
}

$PAGE->set_title(get_string('editsolalert', 'local_solalerts'));
$PAGE->set_heading(get_string('editsolalert', 'local_solalerts'));

echo $OUTPUT->header();

if ($action == 'delete') {
    $heading = new lang_string('confirmdelete', 'local_solalerts', $solalert->get('title'));
    echo html_writer::tag('h3', $heading);
    $deleteurl = new moodle_url('/local/solalerts/edit.php', [
        'action' => 'delete',
        'confirmdelete' => true,
        'id' => $id,
        'sesskey' => sesskey(),
    ]);
    $deletebutton = new single_button($deleteurl, get_string('delete'));
    echo $OUTPUT->confirm(
        $heading,
        $deletebutton,
        new moodle_url('/local/solalerts/index.php')
    );
} else {
    $heading = new lang_string('newsolalert', 'local_solalerts');
    if ($id > 0) {
        $heading = new lang_string('editsolalert', 'local_solalerts');
    }
    echo html_writer::tag('h3', $heading);

    $form->display();
}

echo $OUTPUT->footer();
