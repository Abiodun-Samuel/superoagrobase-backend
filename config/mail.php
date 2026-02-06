<?php

return [

    'template' => [
        'contact' => env('CONTACT_MAIL_TEMPLATE_ID'),
        'contact_auto_reply' => env('CONTACT_MAIL_AUTO_REPLY_TEMPLATE_ID'),
        'welcome_verification' => env('WELCOME_VERIFICATION_TEMPLATE_ID'),
        'email_verification' => env('EMAIL_VERIFICATION_TEMPLATE_ID'),
        'password_reset' => env('PASSWORD_RESET_TEMPLATE_ID'),
        'password_reset_confirmation' => env('PASSWORD_RESET_CONFIRMATION_TEMPLATE_ID'),
        'vendor_request_admin' => env('VENDOR_REQUEST_ADMIN_TEMPLATE_ID'),
        'vendor_request_approved' => env('MAIL_TEMPLATE_VENDOR_REQUEST_APPROVED'),
        'vendor_request_rejected' => env('MAIL_TEMPLATE_VENDOR_REQUEST_REJECTED'),
    ],


    'default' => env('MAIL_MAILER', 'log'),


    'mailers' => [

        'zeptomail' => [
            'base_url' => env('ZEPTO_BASE_URL'),
            'api_key' => env('ZEPTO_API_KEY'),
        ],

        'smtp' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_SCHEME'),
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'resend' => [
            'transport' => 'resend',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
            'retry_after' => 60,
        ],

        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => [
                'ses',
                'postmark',
            ],
            'retry_after' => 60,
        ],

    ],


    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'contact@superoagrobase.com'),
        'name' => env('MAIL_FROM_NAME', 'Admin'),
    ],
];
