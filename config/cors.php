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

    
    'paths' => ['api/*', 'login', 'logout', 'register', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://ielts-ui.s3-website-us-east-1.amazonaws.com','http://localhost:8100',
                            'http://192.168.1.12:8100',
                            'http://13.220.190.184',
                            'capacitor://localhost',
                            'ionic://localhost',
                            'http://localhost',
                            'null',
                        ],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,

];
