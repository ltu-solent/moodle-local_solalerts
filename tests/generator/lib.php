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

use local_solalerts\solalert;

/**
 * Generator for SolAlerts
 *
 * @package   local_solalerts
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_solalerts_generator extends component_generator_base {

    /**
     * Alert count
     *
     * @var integer
     */
    public $alertcount = 0;
    /**
     * Reset process.
     *
     * Do not call directly.
     *
     * @return void
     */
    public function reset() {
        $this->alertcount = 0;
    }

    /**
     * Create a solalert from record fragment
     *
     * @param stdClass $record
     * @return local_solalerts\solalert
     */
    public function create_solalert($record = null) {
        global $USER;
        $this->alertcount++;
        $record = (object)(array)$record;
        // Fills in any missing data required.
        if (!isset($record->title)) {
            $record->title = 'Alert' . $this->alertcount;
        }
        if (!isset($record->content)) {
            $record->content = 'Alert ' . $this->alertcount . ' content.';
        }
        if (!isset($record->contentformat)) {
            $record->contentformat = FORMAT_HTML;
        }
        if (!isset($record->contenttype)) {
            $record->contenttype = solalert::CONTENTTYPE_ALERT;
        }
        if ($record->contenttype == solalert::CONTENTTYPE_ALERT) {
            if (!isset($record->alerttype)) {
                $record->alerttype = \core\notification::INFO;
            }
        } else {
            $record->alerttype = '';
        }
        if (!isset($record->pagetype)) {
            $record->pagetype = 'page-my-index';
        }
        if (!isset($record->displayfrom)) {
            $record->displayfrom = 0;
        }
        if (!isset($record->displayto)) {
            $record->displayto = 0;
        }
        if (!isset($record->enabled)) {
            $record->enabled = 1;
        }
        if (!isset($record->usermodified)) {
            $record->usermodified = $USER->id;
        }
        if (!isset($record->timecreated)) {
            $record->timecreated = time();
        }
        if (!isset($record->timemodified)) {
            $record->timemodified = time();
        }
        // Assume filters is already json encoded.
        $filters = $record->filters ?? '';
        $record->filters = $filters;
        $solalert = new solalert(0, $record);
        $solalert->create();
        return $solalert;
    }
}
