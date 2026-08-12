<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fonnte (WhatsApp Gateway)
    |--------------------------------------------------------------------------
    |
    | Used to notify the researcher (not the respondent directly) to follow up
    | via WhatsApp. `researcher_number` is the destination for reminders.
    |
    */
    'fonnte' => [
        'token' => env('FONNTE_TOKEN'),
        'endpoint' => env('FONNTE_ENDPOINT', 'https://api.fonnte.com/send'),
        'researcher_number' => env('FONNTE_RESEARCHER_NUMBER'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Phishing Simulation Study
    |--------------------------------------------------------------------------
    |
    | Timing thresholds (hours) and reminder cap for the study workflow, plus
    | the external Tally questionnaire URL respondents are redirected to.
    |
    */
    'simulation' => [
        'tally_url' => env('SIMULATION_TALLY_URL'),
        'tally_signing_secret' => env('SIMULATION_TALLY_SIGNING_SECRET'),
        'ignored_after_hours' => (int) env('SIMULATION_IGNORED_AFTER_HOURS', 24),
        'questionnaire_after_hours' => (int) env('SIMULATION_QUESTIONNAIRE_AFTER_HOURS', 24),
        'max_reminders' => (int) env('SIMULATION_MAX_REMINDERS', 3),
    ],

];
