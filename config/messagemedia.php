<?php

return [
    /*
     * MessageMedia API credentials
     */
    'api_key' => env('MESSAGEMEDIA_API_KEY'),
    'api_secret' => env('MESSAGEMEDIA_API_SECRET'),

    /*
     * Use HMAC signature authentication (optional)
     */
    'use_hmac' => env('MESSAGEMEDIA_USE_HMAC', false),

    /*
     * MessageMedia API base URL
     */
    'base_url' => env('MESSAGEMEDIA_BASE_URL', 'https://api.messagemedia.com/v1'),

    /*
     * HTTP timeout in seconds
     */
    'timeout' => env('MESSAGEMEDIA_TIMEOUT', 30),

    /*
     * Verify SSL certificates
     */
    'verify_ssl' => env('MESSAGEMEDIA_VERIFY_SSL', true),

    /*
     * Enable debug logging
     */
    'debug' => env('MESSAGEMEDIA_DEBUG', false),
];
