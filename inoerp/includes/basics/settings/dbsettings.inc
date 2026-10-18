<?php

/**
 * Database Configuration
 * Reads from environment variables with secure defaults
 * Copy .env.example to .env and configure your values
 */

// Load .env file if it exists (for development)
$envPath = dirname(__DIR__, 4) . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Remove quotes if present
            $value = trim($value, '"\'');
            if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

// Primary Database
defined('DB_SERVER') ? null : define('DB_SERVER', $_ENV['DB_SERVER'] ?? $_SERVER['DB_SERVER'] ?? 'localhost');
defined('DB_PORT') ? null : define('DB_PORT', $_ENV['DB_PORT'] ?? $_SERVER['DB_PORT'] ?? '3306');
defined('DB_NAME') ? null : define('DB_NAME', $_ENV['DB_NAME'] ?? $_SERVER['DB_NAME'] ?? '');
defined('DB_USER') ? null : define('DB_USER', $_ENV['DB_USER'] ?? $_SERVER['DB_USER'] ?? '');
defined('DB_PASS') ? null : define('DB_PASS', $_ENV['DB_PASS'] ?? $_SERVER['DB_PASS'] ?? '');
defined('DB_CHARSET') ? null : define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? $_SERVER['DB_CHARSET'] ?? 'utf8mb4');

// Secondary Database (optional)
defined('DB_SERVER2') ? null : define('DB_SERVER2', $_ENV['DB_SERVER2'] ?? $_SERVER['DB_SERVER2'] ?? '');
defined('DB_NAME2') ? null : define('DB_NAME2', $_ENV['DB_NAME2'] ?? $_SERVER['DB_NAME2'] ?? '');
defined('DB_USER2') ? null : define('DB_USER2', $_ENV['DB_USER2'] ?? $_SERVER['DB_USER2'] ?? '');
defined('DB_PASS2') ? null : define('DB_PASS2', $_ENV['DB_PASS2'] ?? $_SERVER['DB_PASS2'] ?? '');

// Validate required database settings in production
if (defined('APP_ENV') && APP_ENV === 'production') {
    $required = ['DB_SERVER', 'DB_NAME', 'DB_USER', 'DB_PASS'];
    foreach ($required as $const) {
        if (!defined($const) || empty(constant($const))) {
            error_log("CRITICAL: Required database constant $const is not set");
            // In production, you might want to die() here instead of just logging
        }
    }
}

// Mail Settings
defined('MAIL_HOST') ? null : define('MAIL_HOST', $_ENV['MAIL_HOST'] ?? $_SERVER['MAIL_HOST'] ?? '');
defined('MAIL_PORT') ? null : define('MAIL_PORT', $_ENV['MAIL_PORT'] ?? $_SERVER['MAIL_PORT'] ?? '587');
defined('MAIL_USERNAME') ? null : define('MAIL_USERNAME', $_ENV['MAIL_USERNAME'] ?? $_SERVER['MAIL_USERNAME'] ?? '');
defined('MAIL_PASSWORD') ? null : define('MAIL_PASSWORD', $_ENV['MAIL_PASSWORD'] ?? $_SERVER['MAIL_PASSWORD'] ?? '');
defined('MAIL_ENCRYPTION') ? null : define('MAIL_ENCRYPTION', $_ENV['MAIL_ENCRYPTION'] ?? $_SERVER['MAIL_ENCRYPTION'] ?? 'tls');
defined('MAIL_FROM_ADDRESS') ? null : define('MAIL_FROM_ADDRESS', $_ENV['MAIL_FROM_ADDRESS'] ?? $_SERVER['MAIL_FROM_ADDRESS'] ?? '');
defined('MAIL_FROM_NAME') ? null : define('MAIL_FROM_NAME', $_ENV['MAIL_FROM_NAME'] ?? $_SERVER['MAIL_FROM_NAME'] ?? 'inoERP');

// Application Settings
defined('APP_ENV') ? null : define('APP_ENV', $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'production');
defined('APP_DEBUG') ? null : define('APP_DEBUG', filter_var($_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN));
defined('APP_URL') ? null : define('APP_URL', $_ENV['APP_URL'] ?? $_SERVER['APP_URL'] ?? '');

// Security Settings
defined('APP_KEY') ? null : define('APP_KEY', $_ENV['APP_KEY'] ?? $_SERVER['APP_KEY'] ?? '');
defined('JWT_SECRET') ? null : define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? $_SERVER['JWT_SECRET'] ?? '');
defined('SESSION_LIFETIME') ? null : define('SESSION_LIFETIME', (int)($_ENV['SESSION_LIFETIME'] ?? $_SERVER['SESSION_LIFETIME'] ?? 120));

// Generate APP_KEY if not set (development only)
if (APP_ENV !== 'production' && empty(APP_KEY)) {
    define('APP_KEY', bin2hex(random_bytes(32)));
}