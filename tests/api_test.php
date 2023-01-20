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
 * Solalert API test
 *
 * @package   local_solalerts
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2023 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_solalerts;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;

/** Test the api class */
class api_test extends advanced_testcase {
    /**
     * Test whether an alert can be displayed in the provided context.
     * @covers \local_solalerts\api::candisplay
     * @dataProvider can_display_provider
     * @param string $pagetype
     * @param array $filters Filters that are to be applied
     * @param int $displayfrom Unixtime
     * @param int $displayto Unixtime
     * @param bool $enabled
     *
     * @return void
     */
    public function test_can_display($pagetype, $filters, $displayfrom, $displayto, $enabled) {
        $this->resetAfterTest();
        $solgen = $this->getDataGenerator()->get_plugin_generator('local_solalerts');
        // Set up users, courses, activities.
        // User to match conditions.
        // User to mismatch conditions.
        // Page to match conditions.
        // Page to mismatch conditions.
        // Set up user profile fields.
        $profilefield = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'text',
            'name' => 'Test profile field',
            'shortname' => 'testprofilefield'
        ]);
        // Set up course custom fields.
        $customcat = $this->getDataGenerator()->create_custom_field_category(['name' => 'customcat']);
        $coursefield = $this->getDataGenerator()->create_custom_field([
            'name' => 'Course custom field',
            'shortname' => 'coursecustomfield',
            'datatype' => 'text',
            'categoryid' => $customcat->get('id'),
            'component' => 'core_course', // This is by default, but just in case it changes.
            'area' => 'course'
        ]);
        $matchcohort = $this->getDataGenerator()->create_cohort();
        $mismatchcohort = $this->getDataGenerator()->create_cohort();
        $matchuserdata = [];
        $mismatchuserdata = [];
        if (isset($filters['department'])) {
            $matchuserdata['department'] = $filters['department']['value'];
        }
        if (isset($filters['institution'])) {
            $matchuserdata['institution'] = $filters['institution']['value'];
        }
        $solalert = $solgen->create_solalert([
            'pagetype' => $pagetype,
            'filters' => json_encode($filters),
            'displayfrom' => $displayfrom,
            'displayto' => $displayto,
            'enabled' => $enabled
        ]);
        $matchuser = $this->getDataGenerator()->create_user($matchuserdata);
        $mismatchuser = $this->getDataGenerator()->create_user($mismatchuserdata);
        $matchcourse = $this->getDataGenerator()->create_course();
        $mismatchcourse = $this->getDataGenerator()->create_course();

    }

    /**
     * Provider for can_display function
     *
     * @return array
     */
    public function can_display_provider() {
        return [
            'department=student' => [
                'pagetype' => 'page-my-index',
                'filters' => [
                    'department' => [
                        'op' => 0,
                        'value' => 'student'
                    ]
                ],
                'displayfrom' => 0,
                'displayto' => 0,
                'enabled' => 1
            ]
        ];
    }
}
