# quizaccess_ai

A quiz accessrule subplugin that prevents users from taking a quiz containing AI features if the users do not have access to AI functionalities.

## Requirements
- Moodle with question type `qtype_aitext` installed and enabled
- Plugin `local_ai_manager` installed and enabled as backend for `qtype_aitext`

## Behaviour
- The rule only activates if the quiz contains `aitext` questions and the `qtype_aitext` backend is set to `local_ai_manager`.
- Access/attempts are blocked if AI tools are unavailable globally or required purposes (`feedback`, `translate`) are hidden/disabled for the user/context.

