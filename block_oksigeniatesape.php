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

/**
 * The block of Tesapé: on a course, the hero of the student, their team, where the class is and the way in; for the
 * teacher, their panel and the class screen. On the dashboard, the courses of the user where Tesapé is played.
 *
 * @package    block_oksigeniatesape
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_oksigeniatesape extends block_base {
    /**
     * Its title.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_oksigeniatesape');
    }

    /**
     * Where it can be added.
     *
     * @return array
     */
    public function applicable_formats() {
        return ['course-view' => true, 'my' => true, 'site' => false, 'mod' => false];
    }

    /**
     * Without settings of its own.
     *
     * @return bool
     */
    public function has_config() {
        return false;
    }

    /**
     * Its content.
     *
     * @return stdClass
     */
    public function get_content() {
        global $USER, $OUTPUT;
        if ($this->content !== null) {
            return $this->content;
        }
        $this->content = (object) ['text' => '', 'footer' => ''];
        if (!isloggedin() || isguestuser()) {
            return $this->content;
        }
        $course = $this->page->course;
        $data = $this->page->context->contextlevel === CONTEXT_COURSE && (int) $course->id !== SITEID ?
            \block_oksigeniatesape\content::for_course((int) $course->id, (int) $USER->id) :
            \block_oksigeniatesape\content::for_user((int) $USER->id);
        if ($data) {
            $this->content->text = $OUTPUT->render_from_template('block_oksigeniatesape/content', $data);
        }
        return $this->content;
    }
}
