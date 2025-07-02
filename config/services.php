<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'facebook' => [
        'app_id' => env('META_APP_ID'),
        'app_secret' => env('META_APP_SECRET'),
        'page_id' => env('FACEBOOK_PAGE_ID'),
        'token' => env('META_ACCESS_TOKEN'),
        'default_graph_version' => env('FACEBOOK_DEFAULT_GRAPH_VERSION', 'v23.0'),
    ],

    'instagram' => [
        'app_id' => env('INSTAGRAM_APP_ID'),
        'app_secret' => env('INSTAGRAM_APP_SECRET'),
        'access_token' => env('META_ACCESS_TOKEN'), // Stesso token di Facebook
        'business_account_id' => env('INSTAGRAM_BUSINESS_ACCOUNT_ID'),
        'default_graph_version' => env('INSTAGRAM_DEFAULT_GRAPH_VERSION', 'v23.0'),
    ],

];
