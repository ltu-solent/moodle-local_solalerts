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

namespace local_solalerts\local;

use context_system;
use local_solalerts\api;
use local_solalerts\solalert;

/**
 * Class after_course_content_header_callback
 *
 * @package    local_solalerts
 * @copyright  2024 Southampton Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class after_course_content_header_callback {
    /**
     * Callback function
     *
     * @param \local_solalerts\hook\after_course_content_header $hook
     * @return void
     */
    public static function callback(\local_solalerts\hook\after_course_content_header $hook): void {
        global $COURSE, $DB, $PAGE;
        // Solalerts.
        $sas = $DB->get_records(
            'local_solalerts',
            [
                'contenttype' => solalert::CONTENTTYPE_ALERT,
                'enabled' => true,
            ],
            'sortorder ASC'
        );
        foreach ($sas as $sa) {
            $display = api::can_display($sa, $PAGE->pagetype, $PAGE->context, $COURSE->id);
            if ($display) {
                $alert = new \core\output\notification(format_text(clean_text($sa->content, FORMAT_PLAIN)), $sa->alerttype);
                $hook->add_alert($alert);
            }
        }
        // Notices.
        $sas = $DB->get_records(
            'local_solalerts',
            [
                'contenttype' => solalert::CONTENTTYPE_NOTICE,
                'enabled' => true,
            ],
            'sortorder ASC'
        );
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
                $notice = format_text($sa->content);
                $hook->add_notice($notice);
            }
        }
    }
}
