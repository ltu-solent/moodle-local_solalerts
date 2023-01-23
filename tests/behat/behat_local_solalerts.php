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
 * Behat steps for SolAlerts
 *
 * @package   local_solalerts
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\PyStringNode as PyStringNode;
use Behat\Gherkin\Node\TableNode;
use local_solalerts\solalert;

/**
 * Steps for solalerts
 */
class behat_local_solalerts extends behat_base {
    /**
     * Create a new solalert in the database.
     *
     * For example
     * Given the following solalert exists:
     *   | title     | Name of solalert            |
     *   | content | For all users |
     *   | contenttype | alert          |
     *   | alerttype | info |
     *   | pagetype | page-my-index |
     *   | filters | json encoded string |
     *   | displayfrom | displayfrom |
     *   | displayto | displayto |
     *   | enabled | 1 |
     *   | sortorder | 0 |
     *
     * if present, roles are looked up in the database to get the ids.
     * @Given /^the following solalert alert exists:$/
     * @param TableNode $data Supplied data
     */
    public function the_following_solalert_alert_exists(TableNode $data) {
        global $DB;

        $solalert = (object)$data->getRowsHash();
        if (!isset($solalert->title) || $solalert->title == '') {
            throw new Exception('A solalert title must be given.');
        }

        if (!isset($solalert->content) || $solalert->content == '') {
            throw new Exception('A solalert must have some content.');
        }

        if (!isset($solalert->contentformat)) {
            $solalert->contentformat = FORMAT_HTML;
        }

        if (!isset($solalert->contenttype)) {
            $solalert->contenttype = solalert::CONTENTTYPE_ALERT;
        }

        if ($solalert->contenttype == solalert::CONTENTTYPE_ALERT) {
            if (!isset($solalert->alerttype)) {
                $solalert->alerttype = \core\notification::INFO;
            }
        } else {
            $solalert->alerttype = '';
        }

        if (!isset($solalert->pagetype)) {
            $solalert->pagetype = 'page-my-index';
        }
        $pagetypes = local_solalerts\api::pagetypes_menu();
        if (!isset($pagetypes[$solalert->pagetype])) {
            throw new Exception('An incorrect page type has been specified (' . $solalert->pagetype . ')');
        }
        $filters = new stdClass();
        if (isset($solalert->filters)) {
            $decoded = json_decode($solalert->filters);
        }
        if (isset($decoded->rolesincourse)) {
            $roles = $DB->get_records('role');
            $availableroles = local_solalerts\api::availableroles(CONTEXT_COURSE);
            $rolenamelist = explode(',', $decoded->rolesincourse);
            // Validate the shortname is correct and create a list of ids.
            $validroles = array_filter($roles, function($role) use ($rolenamelist, $availableroles) {
                if (!isset($availableroles[$role->id])) {
                    return false;
                }
                return in_array($role->shortname, $rolenamelist);
            });
            $roleids = join(',', array_keys($validroles));
            $filters->rolesincourse = $roleids;
        } else {
            $filters->rolesincourse = '';
        }

        if (isset($decoded->rolesinsystem)) {
            $availableroles = local_solalerts\api::availableroles(CONTEXT_COURSE);
            $rolenamelist = explode(',', $decoded->rolesinsystem);
            // Validate the shortname is correct and create a list of ids.
            $validroles = array_filter($roles, function($role) use ($rolenamelist, $availableroles) {
                if (!isset($availableroles[$role->id])) {
                    return false;
                }
                return in_array($role->shortname, $rolenamelist);
            });
            $roleids = join(',', array_keys($validroles));
            $filters->rolesinsystem = $roleids;
        } else {
            $filters->rolesinsystem = '';
        }
        // Try to encode it. If this fails, return empty string.
        $encoded = json_encode($filters);
        if ($encoded) {
            $solalert->filters = $encoded;
        } else {
            $solalert->filters = '';
        }

        if (!isset($solalert->displayfrom)) {
            $solalert->displayfrom = 0;
        }
        if (!isset($solalert->displayto)) {
            $solalert->displayto = 0;
        }

        if (!isset($solalert->enabled)) {
            $solalert->enabled = false;
        }

        if (!isset($solalert->sortorder)) {
            $solalert->sortorder = 0;
        }

        $solalert->usermodified = get_admin()->id;
        $solalert->timemodified = time();
        $solalert->timecreated = time();

        $alert = new solalert(0, $solalert);
        $alert->create();
    }
}
