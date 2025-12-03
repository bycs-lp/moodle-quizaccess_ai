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

use local_ai_manager\ai_manager_utils;
use stdClass;

/**
 * Checks and fetches the AI availability state for quizaccess_ai.
 *
 * @package   quizaccess_ai
 * @copyright 2025 ISB Bayern
 * @author    Thomas Schönlein
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ai_access_handler {
    /**
     * Checks whether the AI features required for this quiz are available.
     * Only validates availability for local_ai_manager (core AI backends not handled yet).
     *
     * @param int $contextid The context id of the quiz.
     * @param array|null $requiredpurposes Purposes that must be enabled. If null, defaults are used.
     * @return bool|string True when AI is available, error message otherwise.
     */
    public function is_available(int $contextid, ?array $requiredpurposes = null): bool|string {
        // Currently only validates qtype_aitext together with local_ai_manager (core AI not covered yet).
        global $USER;
        $requiredpurposes = $requiredpurposes ?? $this->get_aitext_required_purposes();
        $config = ai_manager_utils::get_ai_config($USER, $contextid, null, $requiredpurposes);

        if ($config['availability']['available'] !== ai_manager_utils::AVAILABILITY_AVAILABLE) {
            $message = $config['availability']['errormessage'];
            if (empty($message)) {
                $message = get_string('error_aimanagerunavailable', 'quizaccess_ai');
            }
            return $message;
        }

        $unavailablemessages = [];
        $blockedbycontrol = [];
        $notconfiguredpurposes = [];
        foreach ($config['purposes'] as $purpose) {
            if (!in_array($purpose['purpose'], $requiredpurposes, true)) {
                continue;
            }
            if ($purpose['available'] === ai_manager_utils::AVAILABILITY_AVAILABLE) {
                continue;
            }

            $message = $purpose['errormessage'];
            $blockmessage = get_string('notallowedincourse', 'block_ai_control', $purpose['purpose']);
            $defaultnotconfigured = get_string('error_purposenotconfigured', 'local_ai_manager', $purpose['purpose']);

            if (
                $purpose['available'] === ai_manager_utils::AVAILABILITY_DISABLED
                && $message === $defaultnotconfigured
            ) {
                $notconfiguredpurposes[] = $purpose['purpose'];
                continue;
            }

            if (empty($message)) {
                $message = get_string('error_aipurposeunavailable', 'quizaccess_ai', $purpose['purpose']);
            }

            if ($message === $blockmessage) {
                $blockedbycontrol[] = $purpose['purpose'];
                continue;
            }
            $unavailablemessages[] = $message;
        }

        if (!empty($blockedbycontrol)) {
            $unavailablemessages[] = get_string(
                'notallowedincourse',
                'block_ai_control',
                implode(', ', $blockedbycontrol)
            );
        }
        if (!empty($notconfiguredpurposes)) {
            $unavailablemessages[] = get_string(
                'error_purposenotconfigured',
                'local_ai_manager',
                implode(', ', $notconfiguredpurposes)
            );
        }

        if (!empty($unavailablemessages)) {
            // Remove duplicate messages.
            $unavailablemessages = array_values(array_unique($unavailablemessages));
            if (count($unavailablemessages) === 1) {
                return $unavailablemessages[0];
            }
            return \html_writer::alist($unavailablemessages);
        }

        return true;
    }

    /**
     * Checks if qtype_aitext plugin is available.
     *
     * @return bool
     */
    public function is_aitext_available(): bool {
        $pm = \core_plugin_manager::instance();

        $installed = $pm->get_installed_plugins('qtype');
        if (!array_key_exists('aitext', $installed)) {
            return false;
        }
        $info = $pm->get_plugin_info('qtype_aitext');
        return $info && $info->is_enabled();
    }

    /**
     * Checks if local_ai_manager plugin is available and enabled.
     * Kept as a dedicated method to be easily mocked in tests; core AI would differ.
     *
     * @return bool
     */
    public function is_ai_manager_available(): bool {
        $pm = \core_plugin_manager::instance();
        $installed = $pm->get_installed_plugins('local');
        if(!array_key_exists('ai_manager',$installed)) {
            return false;
        }
        return (bool)$pm->get_plugin_info('local_ai_manager');
    }

    /**
     * Required AI purposes for this rule.
     * These are the purposes qtype_aitext needs when using local_ai_manager.
     *
     * @return array
     */
    public function get_aitext_required_purposes(): array {
        return ['feedback', 'translate'];
    }
}
