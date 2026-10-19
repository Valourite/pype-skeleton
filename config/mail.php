<?php

return [
    // smtp | sendmail | mail (through PHPMailer), log (write emails to the log; the default),
    // array (keep them in memory, for tests), null (drop them), or your own Transport class.
    // Local testing with Mailpit: MAIL_DRIVER=smtp, MAIL_HOST=127.0.0.1, MAIL_PORT=1025, MAIL_ENCRYPTION=none
    'driver' => env('MAIL_DRIVER', 'log'),

    'smtp' => [
        'host' => env('MAIL_HOST', '127.0.0.1'),
        'port' => (int) env('MAIL_PORT', 587),
        'username' => env('MAIL_USERNAME', ''),
        'password' => env('MAIL_PASSWORD', ''),
        'encryption' => env('MAIL_ENCRYPTION', 'tls'),   // tls (STARTTLS, port 587) | ssl (port 465) | none
        'timeout' => 10,
    ],

    // Sender used when a message doesn't set one.
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', env('APP_NAME', 'pype')),
    ],
];
