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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_TOKEN'),
        'from' => env('TWILIO_FROM'), // Phone number for SMS
        'whatsapp_from' => env('TWILIO_WHATSAPP_FROM'), // WhatsApp number
        // WhatsApp Content Template SIDs, indexed by message_type (1..4, etc.)
        'whatsapp_templates' => [
            // Ejemplo: tipo 1 (sesión gratuita / intro)
            1 => 'HXaf44c34f721aaa3e9ff411e3c8a5f941',
            // 2, 3, 4: añade aquí las SIDs cuando Twilio las vaya aprobando
            2 => null,
            3 => null,
            4 => null,
        ],
    ],

    'whatsapp' => [
        'professional_phone' => env('WHATSAPP_PROFESSIONAL_PHONE', '+34600000000'),
    ],

];
