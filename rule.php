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

use mod_quiz\local\access_rule_base;
use mod_quiz\quiz_settings;
use quizaccess_ai\ai_access_handler;

/**
 * A rule preventing access to quiz if AI is not available.
 *
 * @package   quizaccess_ai
 * @copyright 2025 ISB Bayern
 * @author    Thomas Schönlein
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizaccess_ai extends access_rule_base {
    #[\Override]
    public static function make(quiz_settings $quizobj, $timenow, $canignoretimelimits) {
        if (!$quizobj->has_questions()) {
            return null;
        }
        $qtypes = $quizobj->get_all_question_types_used(true);

        // Check if Quiz contains AI questions.
        if (!in_array('aitext', $qtypes)) {
            return null;
        }

        $handler = \core\di::get(ai_access_handler::class);
        if (!$handler->is_aitext_available()) {
            return null;
        }

        // Check if the AI backend is configured correctly.
        if (get_config('qtype_aitext', 'backend') !== 'local_ai_manager') {
            return null;
        }

        if (!$handler->is_ai_manager_available()) {
            return null;
        }
        return new self($quizobj, $timenow);
    }

    #[\Override]
    public function prevent_new_attempt($numprevattempts, $lastattempt) {
        return $this->check_ai_availability();
    }

    /**
     * Runs when a user accesses an existing attempt (view, attempt, or summary page).
     * Review pages are not covered.
     */
    #[\Override]
    public function prevent_access() {
        return $this->check_ai_availability();
    }

    /**
     * Checks the availability of AI functionality required for the quiz.
     *
     * @return string|false Returns an error message if AI functionality is not available, or false if it is available.
     */
    protected function check_ai_availability() {
        global $USER;
        $handler = \core\di::get(ai_access_handler::class);
        $contextid = $this->quizobj->get_context()->id;

        $availability = $handler->is_available($contextid);
        return $availability === true ? false : $availability;
    }
}
