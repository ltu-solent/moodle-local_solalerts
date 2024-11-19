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

namespace local_solalerts\hook;

use stdClass;

/**
 * Class after_course_content_header
 *
 * @package    local_solalerts
 * @copyright  2024 Southampton Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\attribute\tags('output', 'role', 'user', 'course')]
#[\core\attribute\label('Allows plugins to add alerts and notices after course_content_header')]
final class after_course_content_header {
    /**
     * Hook to modify solalerts
     *
     * @param \core\output\notification[] $alerts
     * @param array $notices
     */
    public function __construct(
        /** @var \core\output\notification[] Alerts */
        public array $alerts = [],
        /** @var array Notices */
        public array $notices = [],
    ) {
    }

    /**
     * Add alert
     *
     * @param \core\output\notification $alert
     * @return void
     */
    public function add_alert(\core\output\notification $alert): void {
        $this->alerts[] = $alert;
    }

    /**
     * Add notice
     *
     * @param string $notice Notice content
     * @return void
     */
    public function add_notice(string $notice): void {
        $this->notices[] = $notice;
    }

    /**
     * Return the alerts for output
     *
     * @return array
     */
    public function get_alerts(): array {
        return $this->alerts;
    }

    /**
     * Return the notices for output
     *
     * @return array
     */
    public function get_notices(): array {
        return $this->notices;
    }
}
