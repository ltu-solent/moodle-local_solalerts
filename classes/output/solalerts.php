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

namespace local_solalerts\output;

use core\di;
use core\output\html_writer;
use core\output\renderer_base;
use local_solalerts\hook\after_course_content_header;
use renderable;
use stdClass;
use templatable;

/**
 * Class solalerts
 *
 * @package    local_solalerts
 * @copyright  2024 Southampton Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class solalerts implements renderable, templatable {

    /**
     * Context for solalert
     *
     * @param renderer_base $output
     * @return void
     */
    public function export_for_template(renderer_base $output) {
        $data = new stdClass();
        // Check for hooks here.
        $hook = new after_course_content_header();
        di::get(\core\hook\manager::class)->dispatch($hook);
        $alerts = $hook->get_alerts();
        foreach ($alerts as $alert) {
            if ($alert instanceof \core\output\notification) {
                $data->alerts[] = $alert->export_for_template($output);
            }
        }
        $notices = $hook->get_notices();
        foreach ($notices as $notice) {
            $data->notices[] = html_writer::div(format_text($notice), 'solentzone-notice border p-2 mb-2');
        }
        return $data;
    }
}
