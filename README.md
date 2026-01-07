# quizaccess_ai - AI availability guard for quizzes

This quiz accessrule prevents users from starting or continuing quiz attempts if the AI functionality required by `qtype_aitext` is not available or permitted for them.

## Features

- Detects AI-dependent quizzes (those containing `aitext` questions) and blocks access if AI is unavailable.
- Respects AI availability and any course-level controls exposed by your AI manager backend.
- Provides meaningful error messages when AI is disabled, misconfigured, or specific purposes (e.g. feedback/translate) are not allowed.
- Plays nicely with Moodle’s quiz access rules: only activates when AI is needed; otherwise stays out of the way.

## Requirements

- Moodle 5.0 or later.
- [`qtype_aitext`](https://moodle.org/plugins/qtype_aitext) installed and enabled.
- An AI manager backend (e.g. [`local_ai_manager`](https://moodle.org/plugins/local_ai_manager)) installed and configured for `qtype_aitext`.
- (Optional) A course-level control plugin if you want course-level AI restrictions to be enforced.

## Installing via uploaded ZIP file

1. Log in to your Moodle site as an admin and go to *Site administration > Plugins > Install plugins*.
2. Upload the ZIP file with the plugin code. You should only be prompted to add extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually

The plugin can also be installed by putting the contents of this directory to

    {your/moodle/dirroot}/mod/quiz/accessrule/ai

Afterwards, log in to your Moodle site as an admin and go to *Site administration > Notifications* to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## License

2025, ISB Bayern

Lead developer: Thomas Schönlein

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
