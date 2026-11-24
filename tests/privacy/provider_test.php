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
 * Privacy provider tests.
 *
 * @package    block_rate
 * @copyright  2026 Nicolas Jacquemet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_rate\privacy;

use core_privacy\local\request\approved_contextlist;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the privacy provider.
 *
 * @package    block_rate
 * @covers \block_rate\privacy\provider
 */
final class provider_test extends \advanced_testcase {
    /**
     * Test context discovery and deletion for course and module ratings.
     */
    public function test_delete_user_data_in_all_rating_contexts(): void {
        global $DB;

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $forum = $generator->create_module('forum', ['course' => $course->id]);
        $user = $generator->create_user();
        $otheruser = $generator->create_user();

        $this->create_rating($course->id, 0, $user->id);
        $this->create_rating($course->id, $forum->cmid, $user->id);
        $this->create_rating($course->id, $forum->cmid, $otheruser->id);

        $coursecontext = \context_course::instance($course->id);
        $modulecontext = \context_module::instance($forum->cmid);
        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertEqualsCanonicalizing([$coursecontext->id, $modulecontext->id], $contextlist->get_contextids());

        $approvedcontextlist = new approved_contextlist(
            $user,
            'block_rate',
            [$coursecontext->id, $modulecontext->id]
        );
        provider::delete_data_for_user($approvedcontextlist);

        $this->assertFalse($DB->record_exists('block_rate', ['userid' => $user->id]));
        $this->assertTrue($DB->record_exists('block_rate', ['userid' => $otheruser->id]));
    }

    /**
     * Create a rating record.
     *
     * @param int $courseid The course ID.
     * @param int $cmid The course module ID, or zero for a course rating.
     * @param int $userid The user ID.
     */
    private function create_rating(int $courseid, int $cmid, int $userid): void {
        global $DB;

        $DB->insert_record('block_rate', (object) [
            'course' => $courseid,
            'cmid' => $cmid,
            'userid' => $userid,
            'rating' => 5,
            'created' => time(),
        ]);
    }
}
