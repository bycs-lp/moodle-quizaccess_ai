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

namespace quizaccess_ai;

defined('MOODLE_INTERNAL') || die();

use local_ai_manager\ai_manager_utils;
use stdClass;

/**
 * Helper class that encapsulates the AI availability check for quizaccess_ai.
 *
 * @package   quizaccess_ai
 * @copyright 2025, ISB Bayern
 * @author    Thomas Schönlein
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ai_access_handler {

    protected ?string $errormessage = null;

    /**
     * Checks whether the AI features required for this quiz are available.
     *
     * @param stdClass $user The user for whom the availability should be checked.
     * @param int $contextid The context id of the quiz.
     * @param array $requiredpurposes Purposes that must be enabled.
     * @return bool True when AI is available, false otherwise.
     */
    public function is_available(stdClass $user, int $contextid, array $requiredpurposes): bool {
        $this->errormessage = null;
        $config = $this->fetch_ai_config($user, $contextid, $requiredpurposes);
        
        if ($config['availability']['available'] !== ai_manager_utils::AVAILABILITY_AVAILABLE) {
            $this->errormessage = $config['availability']['errormessage'];
            if (empty($this->errormessage)) {
                $this->errormessage = get_string('error_aigeneralunavailable', 'quizaccess_ai');
            }
            return false;
        }

        $unavailablemessages = [];
        foreach ($config['purposes'] as $purpose) {
            if (!in_array($purpose['purpose'], $requiredpurposes, true)) {
                continue;
            }
            if ($purpose['available']=== ai_manager_utils::AVAILABILITY_AVAILABLE) {
                continue;
            }

        $message = $purpose['errormessage'] ?? '';
            if (empty($message)) {
                if ($purpose['available'] === ai_manager_utils::AVAILABILITY_DISABLED) {
                    $message = get_string('error_purposenotconfigured', 'local_ai_manager', $purpose['purpose']);
                } else {
                    $message = get_string('error_aipurposeunavailable', 'quizaccess_ai', $purpose['purpose']);
                }
            }
            $unavailablemessages[] = $message;
        }

        if (!empty($unavailablemessages)) {
            $this->errormessage = implode(PHP_EOL, $unavailablemessages);
            return false;
        }

        return true;
    }

    /**
     * Returns the error message if AI is not available.
     *
     * @return string|null
     */
    public function get_errormessage(): ?string {
        return $this->errormessage;
    }

    /**
     * Retrieves the configuration from the AI manager.
     *
     * @param stdClass $user
     * @param int $contextid
     * @param array $requiredpurposes
     * @return array
     */
    protected function fetch_ai_config(stdClass $user, int $contextid, array $requiredpurposes): array {
        return ai_manager_utils::get_ai_config($user, $contextid, null, $requiredpurposes);
    }
}
