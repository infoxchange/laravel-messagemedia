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
     * HTTP Proxy URL (optional)
     * Format: http://host:port or http://user:pass@host:port
     * Falls back to HTTP_PROXY environment variable if MESSAGEMEDIA_PROXY is not set
     */
    'proxy' => env('MESSAGEMEDIA_PROXY', env('HTTP_PROXY')),

    /*
     * Sub-account ID (optional)
     * When set, all requests include the Account: header to act on behalf of this sub-account.
     * Example: 'Infoxchange_25380_0003'
     */
    'sub_account' => env('MESSAGEMEDIA_SUB_ACCOUNT'),

    /*
     * Default sender address (optional)
     * E.164 phone number used as source_number for all outbound messages when not set per-message.
     * Example: '+61491570001'
     */
    'sender_address' => env('MESSAGEMEDIA_SENDER_ADDRESS'),

    /*
     * Enable debug logging
     */
    'debug' => env('MESSAGEMEDIA_DEBUG', false),
];
