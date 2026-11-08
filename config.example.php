<?php
// Copy this file to config.php and fill it in. config.php holds secrets: never commit or share it.
return [
    'site_url' => 'https://shop.benzilinskas.com',
    'instagram_url' => 'https://www.instagram.com/seasonal.job/',
    'timezone' => 'America/Chicago',

    // Hostinger hPanel → Databases → MySQL Databases. The host is usually localhost.
    'db' => [
        'dsn' => 'mysql:host=localhost;dbname=u000000000_shop;charset=utf8mb4',
        'user' => 'u000000000_shop',
        'pass' => 'database-password',
    ],

    // Stripe Dashboard → Developers → API keys. Start with the sk_test_ key, switch to sk_live_ at launch.
    // The webhook secret (whsec_...) is shown after adding the endpoint https://shop.benzilinskas.com/webhook.php
    'stripe' => [
        'secret_key' => 'sk_test_...',
        'webhook_secret' => 'whsec_...',
        // Turn on once Stripe Tax is set up for the states where you must collect sales tax.
        'automatic_tax' => false,
    ],

    'shipping' => [
        'countries' => ['US'],
        'flat_cents' => 600,
        'free_over_cents' => 6500,
    ],

    // Use a mailbox on your domain (Hostinger hPanel → Emails) so messages aren't marked as spam.
    'mail' => [
        'from' => 'shop@benzilinskas.com',
        'from_name' => 'seasonal.job',
        'reply_to' => 'shop@benzilinskas.com',
        'admin' => 'you@example.com',   // where new-order emails go
        'log_only' => false,            // true writes emails to storage/mail.log instead of sending
    ],

    // Password for https://shop.benzilinskas.com/admin/ — at least 12 characters.
    'admin_password' => '',
];
