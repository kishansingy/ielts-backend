<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Daily Vocabulary Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the daily vocabulary notification system
    |
    */

    'notification' => [
        'enabled' => env('VOCABULARY_NOTIFICATIONS_ENABLED', true),
        'time' => env('VOCABULARY_NOTIFICATION_TIME', '09:00'),
        'timezone' => env('VOCABULARY_NOTIFICATION_TIMEZONE', 'UTC'),
        'retry_attempts' => env('VOCABULARY_NOTIFICATION_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('VOCABULARY_NOTIFICATION_RETRY_DELAY', 300), // 5 minutes
    ],

    'selection' => [
        'strategy' => env('VOCABULARY_SELECTION_STRATEGY', 'priority'), // priority, random, sequential
        'fallback_to_random' => env('VOCABULARY_FALLBACK_TO_RANDOM', true),
        'exclude_recent_days' => env('VOCABULARY_EXCLUDE_RECENT_DAYS', 30), // Don't repeat words sent in last 30 days
    ],

    'targeting' => [
        'level_based' => env('VOCABULARY_LEVEL_BASED_TARGETING', false),
        'default_level' => env('VOCABULARY_DEFAULT_LEVEL', 'intermediate'),
        'levels' => [
            'beginner' => [
                'priority_boost' => 0,
                'max_word_length' => 8,
            ],
            'intermediate' => [
                'priority_boost' => 5,
                'max_word_length' => 12,
            ],
            'advanced' => [
                'priority_boost' => 10,
                'max_word_length' => null,
            ],
        ],
    ],

    'oxford' => [
        'base_url' => 'https://www.oxfordlearnersdictionaries.com/definition/english/',
        'auto_generate_url' => env('VOCABULARY_AUTO_GENERATE_OXFORD_URL', true),
    ],

    'analytics' => [
        'track_interactions' => env('VOCABULARY_TRACK_INTERACTIONS', true),
        'track_click_through' => env('VOCABULARY_TRACK_CLICK_THROUGH', true),
        'retention_days' => env('VOCABULARY_ANALYTICS_RETENTION_DAYS', 365),
    ],
];