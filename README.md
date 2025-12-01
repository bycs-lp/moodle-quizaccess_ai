# quizaccess_ai

A quiz accessrule subplugin that prevents users from taking a quiz containing AI features if the users do not have access to AI functionalities.

## Purpose
Use this plugin when your quizzes include `aitext` questions that rely on an AI-Manager in the backend. It ensures only users with enabled AI access and the required purposes can start or continue such quizzes. If AI is globally disabled, blocked in the course, or the required purposes are not configured/allowed for the user, the quiz attempt is blocked with a meaningful message.