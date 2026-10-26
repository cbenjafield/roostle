<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Catch Domain
    |--------------------------------------------------------------------------
    |
    | Mail sent to {token}@{catch_domain} is delivered to the inbox that owns
    | the token. The SMTP service resolves recipients against this domain.
    |
    */

    'catch_domain' => env('ROOSTLE_CATCH_DOMAIN', 'catch.roostle.co.uk'),

    /*
    |--------------------------------------------------------------------------
    | SMTP Server
    |--------------------------------------------------------------------------
    |
    | The connection details shown to users for the authenticated inbox.
    |
    */

    'smtp' => [
        'host' => env('ROOSTLE_SMTP_HOST', 'smtp.roostle.co.uk'),
        'port' => (int) env('ROOSTLE_SMTP_PORT', 587),
        'service_token' => env('ROOSTLE_SMTP_SERVICE_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Storage
    |--------------------------------------------------------------------------
    |
    | Raw messages and attachments are written to this filesystem disk.
    | Messages larger than max_message_kilobytes are rejected.
    |
    */

    'disk' => env('ROOSTLE_MESSAGE_DISK', env('FILESYSTEM_DISK', 'local')),

    'max_message_kilobytes' => (int) env('ROOSTLE_MAX_MESSAGE_KILOBYTES', 5120),

];
