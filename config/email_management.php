<?php

return [
    'timezone' => env('EMAIL_TIMEZONE', 'Asia/Bangkok'),
    'tracking_enabled' => (bool) env('EMAIL_TRACKING_ENABLED', true),
    'marketing_daily_limit' => (int) env('EMAIL_MARKETING_DAILY_LIMIT', 1),
    'marketing_weekly_limit' => (int) env('EMAIL_MARKETING_WEEKLY_LIMIT', 3),
    'quiet_hours_start' => env('EMAIL_QUIET_HOURS_START', '21:00'),
    'quiet_hours_end' => env('EMAIL_QUIET_HOURS_END', '08:00'),
    'daily_sending_limit' => (int) env('EMAIL_DAILY_SENDING_LIMIT', 1000),
    'emergency_stop' => (bool) env('EMAIL_EMERGENCY_STOP', false),
    'test_allowlist' => array_values(array_filter(array_map('trim', explode(',', env('EMAIL_TEST_ALLOWLIST', ''))))),
    'internal_recipients' => array_values(array_filter(array_map('trim', explode(',', env('EMAIL_INTERNAL_RECIPIENTS', ''))))),
    'sender_addresses' => [
        'general' => env('MAIL_FROM_ADDRESS_GENERAL', env('MAIL_FROM_ADDRESS', 'hello@example.com')),
        'gis' => env('MAIL_FROM_ADDRESS_GIS', env('MAIL_FROM_ADDRESS', 'hello@example.com')),
        'gms' => env('MAIL_FROM_ADDRESS_GMS', env('MAIL_FROM_ADDRESS', 'hello@example.com')),
    ],
    'brevo' => [
        'api_key' => env('BREVO_API_KEY'),
        'webhook_secret' => env('BREVO_WEBHOOK_SECRET'),
    ],
];
