<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/*', 'login', 'logout'],

    'allowed_methods' => ['*'],

    // Allow API calls from everywhere for now
    'allowed_origins' => [
        'http://ielts-ui.s3-website-us-east-1.amazonaws.com',
        'http://13.220.190.184',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    // Required if your frontend sends cookies
    'supports_credentials' => false,

    'exposed_headers' => [],

    'max_age' => 0,

];
