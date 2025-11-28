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
use mod_quiz\quiz_settings;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/quiz/accessrule/ai/rule.php');

/**
 * Tests for the quizaccess_ai rule.
 *
 * @package     quizaccess_ai
 * @copyright   2025 ISB Bayern
 * @author      Thomas Schönlein
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \quizaccess_ai
 */
final class rule_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Ensures make() returns null when no questions exist.
     *
     * @covers ::make
     */
    public function test_make_returns_null_for_empty_quiz(): void {
        $quizobj = $this->mock_quiz_settings(false, []);

        $this->assertNull(\quizaccess_ai::make($quizobj, time(), false));
    }

    /**
     * Ensures make() returns null when no AI questions are present.
     *
     * @covers ::make
     */
    public function test_make_returns_null_without_ai_questions(): void {
        set_config('backend', 'local_ai_manager', 'qtype_aitext');
        $quizobj = $this->mock_quiz_settings(true, ['multichoice']);

        $this->assertNull(\quizaccess_ai::make($quizobj, time(), false));
    }

    /**
     * Ensures make() returns null when the AI backend is not selected.
     *
     * @covers ::make
     */
    public function test_make_returns_null_when_ai_manager_not_selected(): void {
        set_config('backend', 'other_backend', 'qtype_aitext');
        $quizobj = $this->mock_quiz_settings(true, ['aitext']);

        $this->assertNull(\quizaccess_ai::make($quizobj, time(), false));
    }

    /**
     * Ensures make() creates the rule when AI settings are valid.
     *
     * @covers ::make
     */
    public function test_make_creates_rule(): void {
        set_config('backend', 'local_ai_manager', 'qtype_aitext');
        $quizobj = $this->mock_quiz_settings(true, ['aitext', 'multichoice']);

        $this->assertInstanceOf(\quizaccess_ai::class, \quizaccess_ai::make($quizobj, time(), false));
    }

    /**
     * Verifies access is allowed when AI is available.
     *
     * @covers ::prevent_access
     * @covers ::prevent_new_attempt
     */
    public function test_prevent_access_passes_when_ai_available(): void {
        $rule = $this->create_rule($this->build_handler([
            'availability' => ['available' => ai_manager_utils::AVAILABILITY_AVAILABLE],
            'purposes' => [
                ['purpose' => 'feedback', 'available' => ai_manager_utils::AVAILABILITY_AVAILABLE],
                ['purpose' => 'translate', 'available' => ai_manager_utils::AVAILABILITY_AVAILABLE],
            ],
        ]));

        $this->assertFalse($rule->prevent_access());
        $this->assertFalse($rule->prevent_new_attempt(0, null));
    }

    /**
     * Verifies access is blocked when AI availability is hidden.
     *
     * @covers ::prevent_access
     * @covers ::prevent_new_attempt
     */
    public function test_prevent_access_blocks_when_ai_hidden(): void {
        $rule = $this->create_rule(
            $this->build_handler([
                'availability' => [
                    'available' => ai_manager_utils::AVAILABILITY_HIDDEN,
                    'errormessage' => get_string('error_tenantdisabled', 'local_ai_manager'),
                ],
                'purposes' => [],
            ])
        );

        $expected = get_string('error_tenantdisabled', 'local_ai_manager');
        $this->assertSame($expected, $rule->prevent_access());
        $this->assertSame($expected, $rule->prevent_new_attempt(0, null));
    }

    /**
     * Verifies access is blocked when AI is disabled.
     *
     * @covers ::prevent_access
     * @covers ::prevent_new_attempt
     */
    public function test_prevent_access_blocks_when_ai_disabled(): void {
        $rule = $this->create_rule(
            $this->build_handler([
                'availability' => [
                    'available' => ai_manager_utils::AVAILABILITY_DISABLED,
                    'errormessage' => 'ai_manager->errormessage',
                ],
                'purposes' => [],
            ])
        );

        $expected = 'ai_manager->errormessage';
        $this->assertSame($expected, $rule->prevent_access());
        $this->assertSame($expected, $rule->prevent_new_attempt(0, null));
    }

    /**
     * Verifies access is blocked when a required purpose is hidden.
     *
     * @covers ::prevent_access
     * @covers ::prevent_new_attempt
     */
    public function test_prevent_access_blocks_when_required_purpose_hidden(): void {
        $rule = $this->create_rule(
            $this->build_handler([
                'availability' => ['available' => ai_manager_utils::AVAILABILITY_AVAILABLE],
                'purposes' => [
                    ['purpose' => 'feedback', 'available' => ai_manager_utils::AVAILABILITY_HIDDEN, 'errormessage' => ''],
                    ['purpose' => 'translate', 'available' => ai_manager_utils::AVAILABILITY_AVAILABLE],
                ],
            ])
        );

        $expected = get_string('error_aipurposeunavailable', 'quizaccess_ai', 'feedback');
        $this->assertSame($expected, $rule->prevent_access());
        $this->assertSame($expected, $rule->prevent_new_attempt(0, null));
    }

    /**
     * Verifies access is blocked when a required purpose is disabled.
     *
     * @covers ::prevent_access
     * @covers ::prevent_new_attempt
     */
    public function test_prevent_access_blocks_when_required_purpose_disabled(): void {
        $rule = $this->create_rule(
            $this->build_handler([
                'availability' => ['available' => ai_manager_utils::AVAILABILITY_AVAILABLE],
                'purposes' => [
                    [
                        'purpose' => 'feedback',
                        'available' => ai_manager_utils::AVAILABILITY_DISABLED,
                        'errormessage' => 'ai_manager->errormessage',
                    ],
                    ['purpose' => 'translate', 'available' => ai_manager_utils::AVAILABILITY_AVAILABLE],
                ],
            ])
        );

        $expected = 'ai_manager->errormessage';
        $this->assertSame($expected, $rule->prevent_access());
        $this->assertSame($expected, $rule->prevent_new_attempt(0, null));
    }

    /**
     * Verifies multiple purpose messages are combined.
     *
     * @covers ::prevent_access
     * @covers ::prevent_new_attempt
     */
    public function test_prevent_access_combines_multiple_purpose_messages(): void {
        $rule = $this->create_rule(
            $this->build_handler([
                'availability' => ['available' => ai_manager_utils::AVAILABILITY_AVAILABLE],
                'purposes' => [
                    [
                        'purpose' => 'feedback',
                        'available' => ai_manager_utils::AVAILABILITY_HIDDEN,
                        'errormessage' => '',
                    ],
                    [
                        'purpose' => 'translate',
                        'available' => ai_manager_utils::AVAILABILITY_DISABLED,
                        'errormessage' => 'translate disabled',
                    ],
                ],
            ])
        );

        $expected = \html_writer::alist([
            get_string('error_aipurposeunavailable', 'quizaccess_ai', 'feedback'),
            'translate disabled',
        ]);

        $this->assertSame($expected, $rule->prevent_access());
        $this->assertSame($expected, $rule->prevent_new_attempt(0, null));
    }

    /**
     * Creates the rule and injects the provided handler.
     *
     * @param ai_access_handler $handler
     * @return \quizaccess_ai
     */
    protected function create_rule(ai_access_handler $handler): \quizaccess_ai {
        \core\di::set(ai_access_handler::class, $handler);
        $this->setAdminUser();

        $quizobj = $this->createMock(quiz_settings::class);
        $quizobj->method('get_quiz')->willReturn((object)[
            'id' => 1,
            'timeclose' => 0,
            'timelimit' => 0,
        ]);
        $quizobj->method('get_context')->willReturn(\context_system::instance());

        return new \quizaccess_ai($quizobj, time());
    }

    /**
     * Builds a handler stub that always returns the provided AI configuration.
     *
     * @param array $config
     * @return ai_access_handler
     */
    protected function build_handler(array $config): ai_access_handler {
        return new class ($config) extends ai_access_handler {
            /** @var array AI configuration stub */
            private array $config;

            /**
             * Constructor.
             *
             * @param array $config AI configuration stub
             */
            public function __construct(array $config) {
                $this->config = $config;
            }

            /**
             * Returns the provided configuration.
             *
             * @param \stdClass $user
             * @param int $contextid
             * @param array $requiredpurposes
             * @return array
             */
            protected function fetch_ai_config(\stdClass $user, int $contextid, array $requiredpurposes): array {
                return $this->config;
            }
        };
    }

    /**
     * Builds a quiz_settings mock for the rule factory tests.
     *
     * @param bool $hasquestions
     * @param array $qtypes
     * @return quiz_settings
     */
    protected function mock_quiz_settings(bool $hasquestions, array $qtypes): quiz_settings {
        $quiz = (object)[
            'id' => 1,
            'timeclose' => 0,
            'timelimit' => 0,
        ];

        $mock = $this->getMockBuilder(quiz_settings::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_quiz', 'get_context', 'has_questions', 'get_all_question_types_used'])
            ->getMock();

        $mock->method('has_questions')->willReturn($hasquestions);
        $mock->method('get_all_question_types_used')->willReturn($qtypes);
        $mock->method('get_quiz')->willReturn($quiz);
        $mock->method('get_context')->willReturn(\context_system::instance());

        return $mock;
    }
}
