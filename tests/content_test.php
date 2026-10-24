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
use local_oksigeniatesape\local\regions;

/**
 * Tests for what the block of Tesapé shows.
 *
 * @package    block_oksigeniatesape
 * @category   test
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_oksigeniatesape\content
 */
#[\PHPUnit\Framework\Attributes\CoversClass(content::class)]
final class content_test extends \advanced_testcase {
    public function test_the_block_shows_the_hero_the_place_and_the_way_in(): void {
        global $OUTPUT;
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course(['fullname' => 'Ciencias de 5.º']);
        $c = (int) $course->id;
        $teacher = $gen->create_and_enrol($course, 'editingteacher');
        $student = $gen->create_and_enrol($course, 'student', ['firstname' => 'Lucía']);
        $newcomer = $gen->create_and_enrol($course, 'student');
        // Not set up: only the teacher sees something, the way to set it up.
        $this->assertNull(content::for_course($c, (int) $student->id));
        $off = content::for_course($c, (int) $teacher->id);
        $this->assertTrue($off['off']);
        $this->assertStringContainsString('/local/oksigeniatesape/teach.php', $off['teachurl']);
        regions::save($c, ['enabled' => 1]);
        missions::install($c, 'livingthings', 'valley', 'es');
        $mission = missions::get($c);
        $group = $gen->create_group(['courseid' => $c, 'name' => 'Equipo Garza']);
        $gen->create_group_member(['groupid' => $group->id, 'userid' => $student->id]);
        characters::create($student->id, 'scout', 'f');
        // The student: hero, team, place and the way in.
        $data = content::for_course($c, (int) $student->id);
        $this->assertSame('Lucía', $data['hero']['name']);
        $this->assertNotSame('', $data['hero']['role']);
        $this->assertSame(get_string('level', 'block_oksigeniatesape', 1), $data['hero']['level']);
        $this->assertSame('Equipo Garza', $data['team']);
        $first = missions::text($mission->data['chapters'][0]['name'], 'valley', (string) $mission->variant);
        $this->assertSame(get_string('now', 'block_oksigeniatesape', $first), $data['place']);
        $html = $OUTPUT->render_from_template('block_oksigeniatesape/content', $data);
        $this->assertStringContainsString('/local/oksigeniatesape/play.php?id=' . $c, $html);
        $this->assertStringContainsString(get_string('enter', 'block_oksigeniatesape'), $html);
        // Without a hero yet: create it.
        $this->assertTrue(content::for_course($c, (int) $newcomer->id)['create']);
        // The teacher: the panel and the class screen.
        $data = content::for_course($c, (int) $teacher->id);
        $this->assertTrue($data['teacher']);
        $html = $OUTPUT->render_from_template('block_oksigeniatesape/content', $data);
        $this->assertStringContainsString('/local/oksigeniatesape/board.php?id=' . $c, $html);
        // The dashboard: the courses where Tesapé is played.
        $mine = content::for_user((int) $student->id);
        $this->assertSame(['Ciencias de 5.º'], array_column($mine['courses'], 'name'));
        $this->assertSame('Lucía', $mine['hero']['name']);
        $this->assertNull(content::for_user((int) $gen->create_user()->id));
    }
}
