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
 * Lib file for callbacks
 *
 * @package   local_solalerts
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_solalerts\api;
use local_solalerts\solalert;

/**
 * Hook for the theme to display qualifying alerts.
 *
 * @param array $alerts Pass through existing alerts
 * @return array new alerts
 */
function local_solalerts_solentzone_alerts($alerts) {
    global $COURSE, $DB, $PAGE;
    $sas = $DB->get_records('local_solalerts', ['contenttype' => solalert::CONTENTTYPE_ALERT, 'enabled' => true], 'sortorder ASC');
    foreach ($sas as $sa) {
        $display = api::can_display($sa, $PAGE->pagetype, $PAGE->context, $COURSE->id);
        if ($display) {
            $alerts[] = new \core\output\notification(format_text(clean_text($sa->content, FORMAT_PLAIN)), $sa->alerttype);
        }
    }
    return $alerts;
}

/**
 * Fetch all notices that can be displayed to this user in this context
 *
 * @param array $notices
 * @return array New notices
 */
function local_solalerts_solentzone_notices($notices) {
    global $COURSE, $DB, $PAGE;
    $sas = $DB->get_records('local_solalerts', ['contenttype' => solalert::CONTENTTYPE_NOTICE, 'enabled' => true], 'sortorder ASC');
    foreach ($sas as $sa) {
        $display = api::can_display($sa, $PAGE->pagetype, $PAGE->context, $COURSE->id);
        if ($display) {
            $sa->content = file_rewrite_pluginfile_urls(
                // The content of the text stored in the database.
                $sa->content,
                // The pluginfile URL which will serve the request.
                'pluginfile.php',
                // The combination of contextid / component / filearea / itemid
                // form the virtual bucket that file are stored in.
                context_system::instance()->id,
                'local_solalerts',
                'alert',
                $sa->id
            );
            $notices[] = format_text($sa->content);
        }
    }
    return $notices;
}

/**
 * Convert pluginfile urls into real urls
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return stored_file|bool
 */
function local_solalerts_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {

    if ($filearea != 'alert') {
        return false;
    }
    $itemid = array_shift($args);
    $filename = array_pop($args);
    if (empty($args)) {
        $filepath = '/';
    } else {
        $filepath = '/' . implode('/', $args) . '/';
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_solalerts', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false;
    }
    send_stored_file($file, 0, 0, $forcedownload, $options);
}
