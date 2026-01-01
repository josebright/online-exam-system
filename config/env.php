<?php
$envFile = dirname(__DIR__) . '/.env';

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                $value = substr($value, 1, -1);
            }
            
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

function env($key) {
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return $_ENV[$key];
    }
    
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return $value;
    }
    
    $envFile = dirname(__DIR__) . '/.env';
    throw new Exception(
        "Required environment variable '{$key}' is not set.\n" .
        "Please set it in your .env file: {$envFile}\n" .
        "See .env.example for all required variables."
    );
}

function env_optional($key, $default = null) {
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return $_ENV[$key];
    }
    
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return $value;
    }
    
    return $default;
}

