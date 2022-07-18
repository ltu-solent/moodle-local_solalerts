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
 * List available alerts
 *
 * @package   local_solalerts
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

require_capability('local/solalerts:managealerts', context_system::instance());
$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_heading(get_string('pluginname', 'local_solalerts'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('pluginname', 'local_solalerts'));
$PAGE->set_url($CFG->wwwroot.'/local/solalerts/index.php');

echo $OUTPUT->header();

$new = new action_link(new moodle_url('/local/solalerts/edit.php', ['action' => 'new']),
    get_string('newsolalert', 'local_solalerts'), null,
    ['class' => 'btn btn-primary'],
    new pix_icon('i/calendareventtime', get_string('newsolalert', 'local_solalerts')));
echo $OUTPUT->render($new);

$table = new \local_solalerts\tables\solalerts_table('solalerts');

$table->out(100, false);

echo $OUTPUT->footer();
