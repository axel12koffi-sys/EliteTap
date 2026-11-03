<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('APP_DIR', __DIR__);

$configFile = getenv('ELITETAP_CONFIG') ?: APP_DIR . '/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('Configuration manquante : copier app/config.sample.php vers app/config.php.');
}
$GLOBALS['config'] = require $configFile;

date_default_timezone_set('America/Toronto');
mb_internal_encoding('UTF-8');

if (!empty($GLOBALS['config']['debug'])) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

require APP_DIR . '/lib/db.php';
require APP_DIR . '/lib/helpers.php';
require APP_DIR . '/lib/auth.php';
require APP_DIR . '/lib/mailer.php';
require APP_DIR . '/lib/stats.php';
require APP_DIR . '/lib/i18n.php';
require APP_DIR . '/lib/stripe.php';
require APP_DIR . '/lib/plans.php';
require APP_DIR . '/lib/settings.php';
require APP_DIR . '/lib/orders.php';
require APP_DIR . '/lib/wallet.php';
require APP_DIR . '/lib/inbox.php';

if (PHP_SAPI !== 'cli') {
    $secure = (($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off')
        || str_starts_with(config('app_url'), 'https://');
    session_name('etsid');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: SAMEORIGIN');
    if (!is_indexable()) {
        header('X-Robots-Tag: noindex, nofollow');
    }
}
