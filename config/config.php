<?php
/**
 * Hotel & Resort Management System
 * Configuration File
 * 
 * This file contains all system configuration values
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Load environment loader
require_once APP_ROOT . '/includes/env.php';

// Load logger
require_once APP_ROOT . '/includes/logger.php';

// Load constants
require_once APP_ROOT . '/includes/constants.php';

// Load error handler
require_once APP_ROOT . '/includes/error_handler.php';

// Load helpers (generic helper functions)
require_once APP_ROOT . '/includes/helpers.php';

// Load security (security-related functions)
require_once APP_ROOT . '/includes/security.php';

// Application Settings
define('APP_NAME', env('APP_NAME', 'Hotel & Resort Management System'));
define('APP_VERSION', '1.0.0');
define('APP_URL', env('APP_URL', 'http://localhost/hotel-management'));
define('APP_ENV', env('APP_ENV', 'development')); // development, production
define('APP_DEBUG', env('APP_DEBUG', 'true') === 'true');

// Database Settings
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_NAME', env('DB_DATABASE', 'hotel_management'));
define('DB_USER', env('DB_USERNAME', 'root'));
define('DB_PASS', env('DB_PASSWORD', ''));

// Session Settings
define('SESSION_NAME', env('SESSION_NAME', 'HOTEL_SESSION'));
define('SESSION_LIFETIME', (int)env('SESSION_LIFETIME', '3600')); // 1 hour in seconds

// Timezone
define('TIMEZONE', env('APP_TIMEZONE', 'UTC'));
date_default_timezone_set(TIMEZONE);

// File Upload Settings
define('UPLOAD_MAX_SIZE', 5242880); // 5MB in bytes
define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);
define('UPLOAD_PATH', APP_ROOT . '/' . env('UPLOAD_PATH', 'uploads'));

// Pagination
define('ITEMS_PER_PAGE', (int)env('ITEMS_PER_PAGE', '20'));

// Date Format
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'F j, Y');
define('DISPLAY_DATETIME_FORMAT', 'F j, Y g:i A');

// Currency Settings
define('CURRENCY_CODE', env('CURRENCY_CODE', 'USD'));
define('CURRENCY_SYMBOL', env('CURRENCY_SYMBOL', '$'));
define('CURRENCY_POSITION', env('CURRENCY_POSITION', 'left')); // left, right

// Email Settings
define('MAIL_FROM_NAME', env('MAIL_FROM_NAME', APP_NAME));
define('MAIL_FROM_EMAIL', env('MAIL_FROM_EMAIL', 'noreply@hotel-management.com'));
define('MAIL_ENABLED', env('MAIL_ENABLED', 'false') === 'true');

// Maintenance Mode
define('MAINTENANCE_MODE', false);

// Debug Mode (set to false in production)
define('DEBUG_MODE', APP_DEBUG);

// Error Reporting
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Security Settings
define('PASSWORD_MIN_LENGTH', 8);
define('SESSION_SECURE', env('SESSION_SECURE', 'false') === 'true'); // Set to true if using HTTPS
define('SESSION_HTTPONLY', env('SESSION_HTTPONLY', 'true') === 'true');
define('SESSION_SAMESITE', env('SESSION_SAMESITE', 'Lax'));

// Theme Settings
define('THEME_NAME', 'default');
define('THEME_COLOR', '#0d6efd');

// API Settings
define('API_ENABLED', false);
define('API_RATE_LIMIT', 100); // requests per minute

// Cache Settings
define('CACHE_ENABLED', false);
define('CACHE_LIFETIME', 3600); // 1 hour

// Logging
define('LOG_ENABLED', env('LOG_ENABLED', 'true') === 'true');
define('LOG_PATH', APP_ROOT . '/logs');
define('LOG_LEVEL', env('LOG_LEVEL', 'INFO')); // DEBUG, INFO, WARNING, ERROR

// Backup Settings
define('BACKUP_ENABLED', false);
define('BACKUP_PATH', APP_ROOT . '/backups');
define('BACKUP_RETENTION_DAYS', 30);

// Note: Installation check is now handled by bootstrap.php which loads before this file
