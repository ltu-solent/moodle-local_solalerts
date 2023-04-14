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
use context_course;
use context_system;
use context_user;

defined('MOODLE_INTERNAL') || die();

global $CFG;

/** Test the api class
 * @group sol
 */
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
        global $DB;
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
        $matchcoursedata = [];
        $mismatchcoursedata = [];
        $randomstring = random_string(10);
        if (isset($filters['department'])) {
            $matchuserdata['department'] = $filters['department']['value'];
        }
        if (isset($filters['institution'])) {
            $matchuserdata['institution'] = $filters['institution']['value'];
        }
        if (isset($filters['rolesincourse'])) {
            $rolenames = explode(",", $filters['rolesincourse']);
            // Converts names to id.
            [$insql, $inparams] = $DB->get_in_or_equal($rolenames);
            $roles = $DB->get_records_select('role', "shortname {$insql}", $inparams);
            $filters['rolesincourse'] = join(",", array_keys($roles));
            $setrole = $rolenames[0]; // Get the first role to enrol the matching users.
        }
        if (isset($filters['coursecustomfield'])) {
            $filters['coursecustomfield']['fld'] = $coursefield->get('id');

            $matchcoursedata['customfields'][] = [
                'shortname' => 'coursecustomfield',
                'value' => $filters['coursecustomfield']['value']
            ];
        }
        if (isset($filters['userprofilefield'])) {
            $filters['userprofilefield']['fld'] = $profilefield->id;
            $matchuserdata['profile_field_testprofilefield'] = $filters['userprofilefield']['value'];
        }
        $mismatchuserdata = [
            'department' => $randomstring,
            'institution' => $randomstring,
            'profile_field_testprofilefield' => $randomstring
        ];

        $solalert = $solgen->create_solalert([
            'pagetype' => $pagetype,
            'filters' => json_encode($filters),
            'displayfrom' => $displayfrom,
            'displayto' => $displayto,
            'enabled' => $enabled
        ]);
        $solrecord = $solalert->to_record();
        $matchuser = $this->getDataGenerator()->create_user($matchuserdata);
        $mismatchuser = $this->getDataGenerator()->create_user($mismatchuserdata);
        $matchcourse = $this->getDataGenerator()->create_course($matchcoursedata);
        $mismatchcourse = $this->getDataGenerator()->create_course($mismatchcoursedata);
        if (isset($setrole)) {
            $this->getDataGenerator()->enrol_user($matchuser->id, $matchcourse->id, $setrole);
        }

        // Matching context.
        $this->setUser($matchuser);
        $context = $this->setcontext($pagetype, $matchuser, $matchcourse);
        $this->assertTrue(api::can_display($solrecord, $pagetype, $context, $matchcourse->id));

        // Mismatch context.
        $this->setUser($mismatchuser);
        $context = $this->setcontext($pagetype, $mismatchuser, $mismatchcourse);
        $this->assertFalse(api::can_display($solrecord, $pagetype, $context, $mismatchcourse->id));

        // Try various other time and status options for the user that would otherwise match.
        $this->setUser($matchuser);
        $context = $this->setcontext($pagetype, $matchuser, $matchcourse);

        $solrecord->enabled = 0;
        $this->assertFalse(api::can_display($solrecord, $pagetype, $context, $matchcourse->id));

        $solrecord->enabled = 1;
        $solrecord->displayfrom = strtotime("Tomorrow");
        $this->assertFalse(api::can_display($solrecord, $pagetype, $context, $matchcourse->id));

        $solrecord->displayfrom = strtotime("1 year ago");
        $solrecord->displayto = strtotime("Yesterday");
        $this->assertFalse(api::can_display($solrecord, $pagetype, $context, $matchcourse->id));

        $solrecord->displayto = 0;
        $this->assertTrue(api::can_display($solrecord, $pagetype, $context, $matchcourse->id));

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
                        'op' => api::TEXT_FILTER_CONTAINS,
                        'value' => 'student'
                    ]
                ],
                'displayfrom' => 0,
                'displayto' => 0,
                'enabled' => 1
            ],
            'institution=academic' => [
                'pagetype' => 'page-my-index',
                'filters' => [
                    'institution' => [
                        'op' => api::TEXT_FILTER_CONTAINS,
                        'value' => 'academic'
                    ]
                ],
                'displayfrom' => 0,
                'displayto' => 0,
                'enabled' => 1
            ],
            'courseview-student' => [
                'pagetype' => 'page-course-view',
                'filters' => [
                    'rolesincourse' => 'student'
                ],
                'displayfrom' => 0,
                'displayto' => 0,
                'enabled' => 1
            ],
            'pagetype=module' => [
                'pagetype' => 'page-course-view',
                'filters' => [
                    'coursecustomfield' => [
                        'op' => api::TEXT_FILTER_CONTAINS,
                        'value' => 'module',
                    ]
                ],
                'displayfrom' => 0,
                'displayto' => 0,
                'enabled' => 1
            ]
        ];
    }

    /**
     * Sets the appropriate context for the alert context
     *
     * @param string $pagetype
     * @param stdClass $user
     * @param stdClass $course
     * @param stdClass $cm
     * @return \context
     */
    private function setcontext($pagetype, $user, $course = null, $cm = null) {
        $context = context_system::instance();
        if ($pagetype == 'page-course-view') {
            $context = context_course::instance($course->id);
        }

        return $context;
    }
}
