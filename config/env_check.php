<?php
require_once __DIR__ . '/env.php';

$requiredVars = [
    'APP_NAME',
    'APP_VERSION',
    'BASE_URL',
    'ADMIN_EMAIL',
    'DB_HOST',
    'DB_PORT',
    'DB_NAME',
    'DB_USER',
    'DB_PASS',
    'DB_CHARSET',
    'SESSION_LIFETIME',
    'MAX_LOGIN_ATTEMPTS',
    'LOGIN_TIMEOUT',
    'SESSION_COOKIE_SECURE',
    'MIN_EXAM_DURATION',
    'MAX_EXAM_DURATION',
    'AUTO_SAVE_INTERVAL',
    'TAB_SWITCH_WARNING_THRESHOLD',
    'ITEMS_PER_PAGE',
    'ERROR_REPORTING',
    'DISPLAY_ERRORS',
    'LOG_ERRORS',
];

$missing = [];
$empty = [];

foreach ($requiredVars as $var) {
    if (!isset($_ENV[$var]) && getenv($var) === false) {
        $missing[] = $var;
    } elseif (isset($_ENV[$var]) && $_ENV[$var] === '') {
        $empty[] = $var;
    }
}

if (empty($missing) && empty($empty)) {
    echo "✅ All required environment variables are set!\n";
    exit(0);
} else {
    echo "❌ Environment configuration error!\n\n";
    
    if (!empty($missing)) {
        echo "Missing variables:\n";
        foreach ($missing as $var) {
            echo "  - {$var}\n";
        }
        echo "\n";
    }
    
    if (!empty($empty)) {
        echo "Empty variables:\n";
        foreach ($empty as $var) {
            echo "  - {$var}\n";
        }
        echo "\n";
    }
    
    echo "Please set all required variables in your .env file.\n";
    echo "See .env.example for reference.\n";
    exit(1);
}

