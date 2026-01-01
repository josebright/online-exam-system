<?php
require_once __DIR__ . '/env.php';

$errorReporting = env('ERROR_REPORTING');
if ($errorReporting === 'E_ALL') {
    error_reporting(E_ALL);
} else {
    error_reporting((int)$errorReporting);
}

ini_set('display_errors', (int)env('DISPLAY_ERRORS'));
ini_set('log_errors', (int)env('LOG_ERRORS'));
ini_set('error_log', __DIR__ . '/../logs/error.log');

date_default_timezone_set('UTC');

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', (int)env('SESSION_COOKIE_SECURE'));
ini_set('session.cookie_samesite', 'Strict');

define('APP_NAME', env('APP_NAME'));
define('APP_VERSION', env('APP_VERSION'));
define('BASE_URL', env('BASE_URL'));
define('ADMIN_EMAIL', env('ADMIN_EMAIL'));

define('SESSION_LIFETIME', (int)env('SESSION_LIFETIME'));
define('MAX_LOGIN_ATTEMPTS', (int)env('MAX_LOGIN_ATTEMPTS'));
define('LOGIN_TIMEOUT', (int)env('LOGIN_TIMEOUT'));

define('MIN_EXAM_DURATION', (int)env('MIN_EXAM_DURATION'));
define('MAX_EXAM_DURATION', (int)env('MAX_EXAM_DURATION'));
define('AUTO_SAVE_INTERVAL', (int)env('AUTO_SAVE_INTERVAL'));
define('TAB_SWITCH_WARNING_THRESHOLD', (int)env('TAB_SWITCH_WARNING_THRESHOLD'));

define('ITEMS_PER_PAGE', (int)env('ITEMS_PER_PAGE'));

define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('LOG_PATH', ROOT_PATH . '/logs');

if (!file_exists(LOG_PATH)) {
    mkdir(LOG_PATH, 0755, true);
}

if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}

require_once __DIR__ . '/database.php';
?>


