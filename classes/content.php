<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace block_oksigeniatesape;

use local_oksigeniatesape\local\characters;
use local_oksigeniatesape\local\missions;
use local_oksigeniatesape\local\pacts;
use local_oksigeniatesape\local\regions;
use local_oksigeniatesape\local\teams;

/**
 * What the block shows, as the data of its template.
 *
 * @package    block_oksigeniatesape
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content {
    /** @var int Most courses listed on the dashboard. */
    public const COURSES = 8;

    /**
     * On a course: for a student, their hero, team, where the class is and the way in; for a teacher, the way to their
     * panel and the class screen, or to set the game up. Null when there is nothing to show.
     *
     * @param int $courseid
     * @param int $userid
     * @return array|null
     */
    public static function for_course(int $courseid, int $userid): ?array {
        $context = \context_course::instance($courseid);
        $teacher = has_capability('local/oksigeniatesape:award', $context, $userid);
        $on = regions::enabled($courseid);
        if (!$on) {
            return $teacher ? ['off' => true, 'teachurl' => self::url('teach', $courseid)->out(false)] : null;
        }
        $data = ['place' => self::place($courseid), 'playurl' => self::url('play', $courseid)->out(false)];
        if ($teacher) {
            return $data + ['teacher' => true, 'teachurl' => self::url('teach', $courseid)->out(false),
                'boardurl' => self::url('board', $courseid)->out(false)];
        }
        if (!has_capability('local/oksigeniatesape:play', $context, $userid)) {
            return null;
        }
        $char = characters::get($userid);
        if (!$char) {
            return $data + ['create' => true];
        }
        $team = teams::of($courseid, $userid);
        return $data + ['hero' => self::hero($char, $courseid), 'team' => $team ? format_string($team->name) : ''];
    }

    /**
     * On the dashboard: the hero of the user and their courses where Tesapé is played. Null without any.
     *
     * @param int $userid
     * @return array|null
     */
    public static function for_user(int $userid): ?array {
        $courses = [];
        foreach (enrol_get_users_courses($userid, true, 'id, fullname') as $course) {
            if (count($courses) >= self::COURSES) {
                break;
            }
            if (!regions::enabled((int) $course->id)) {
                continue;
            }
            $context = \context_course::instance((int) $course->id);
            $teacher = has_capability('local/oksigeniatesape:award', $context, $userid);
            $courses[] = ['name' => format_string($course->fullname, true, ['context' => $context]),
                'place' => self::place((int) $course->id),
                'url' => self::url($teacher ? 'teach' : 'play', (int) $course->id)->out(false)];
        }
        if (!$courses) {
            return null;
        }
        $char = characters::get($userid);
        return ['dashboard' => true, 'courses' => $courses, 'hero' => $char ? self::hero($char, 0) : null];
    }

    /**
     * The hero of a student: their name in the game, role and level.
     *
     * @param \stdClass $char
     * @param int $courseid For the names of the roles of the course (0: the default ones).
     * @return array
     */
    private static function hero(\stdClass $char, int $courseid): array {
        $region = $courseid ? regions::get($courseid) : null;
        $family = (string) ($region->family ?? 'storyheroes');
        $form = $char->nameform === 'f' ? 'f' : 'm';
        $key = 'role_' . $family . '_' . $char->role . '_' . $form;
        $manager = get_string_manager();
        $role = $manager->string_exists($key, 'local_oksigeniatesape') ? get_string($key, 'local_oksigeniatesape') : '';
        $user = \core_user::get_user((int) $char->userid);
        return ['name' => $char->nickname ? format_string($char->nickname) : ($user ? format_string($user->firstname) : ''),
            'role' => $role, 'level' => get_string('level', 'block_oksigeniatesape', (int) $char->level)];
    }

    /**
     * Where the class of a course is: the first place with its lantern still out, or that it lit them all.
     *
     * @param int $courseid
     * @return string
     */
    private static function place(int $courseid): string {
        $mission = missions::get($courseid);
        $chapter = $mission ? pacts::chapter($mission) : null;
        if (!$chapter) {
            return '';
        }
        if ($chapter->litat) {
            return get_string('alllit', 'block_oksigeniatesape');
        }
        foreach ($mission->data['chapters'] ?? [] as $info) {
            if ((string) $info['code'] === (string) $chapter->code) {
                return get_string('now', 'block_oksigeniatesape', missions::text(
                    $info['name'] ?? $chapter->code,
                    (string) $mission->skin,
                    (string) $mission->variant
                ));
            }
        }
        return '';
    }

    /**
     * A page of Tesapé for a course.
     *
     * @param string $page play, teach or board.
     * @param int $courseid
     * @return \moodle_url
     */
    private static function url(string $page, int $courseid): \moodle_url {
        return new \moodle_url('/local/oksigeniatesape/' . $page . '.php', ['id' => $courseid]);
    }
}
