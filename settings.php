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
 * Sol alerts settings
 *
 * @package   local_solalerts
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$ADMIN->add(
    'appearance',
    new admin_externalpage(
        'solalerts',
        new lang_string('pluginname', 'local_solalerts'),
        new moodle_url('/local/solalerts/index.php'),
        'local/solalerts:managealerts'
    )
);

$settings = new admin_settingpage('local_solalerts', new lang_string('solalerts', 'local_solalerts'));
if ($hassiteconfig) {
    $options = 'page-my-index
page-frontpage
page-course-view
page-mod-assign-view';
    $settings->add(
        new admin_setting_configtextarea(
            'local_solalerts/pagetypes',
            new lang_string('pagetypes', 'local_solalerts'),
            new lang_string('pagetypes_desc', 'local_solalerts'),
            $options,
            PARAM_TEXT
        )
    );
    $ADMIN->add('localplugins', $settings);
}
