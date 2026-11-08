<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

$configFile = getenv('SHOP_CONFIG') ?: APP_ROOT . '/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('Missing config.php. Copy config.example.php to config.php and fill it in.');
}
$GLOBALS['config'] = require $configFile;

date_default_timezone_set($GLOBALS['config']['timezone'] ?? 'America/Chicago');

require __DIR__ . '/helpers.php';
require __DIR__ . '/db.php';
require __DIR__ . '/catalog.php';
require __DIR__ . '/cart.php';
require __DIR__ . '/orders.php';
require __DIR__ . '/stripe.php';
require __DIR__ . '/mailer.php';
