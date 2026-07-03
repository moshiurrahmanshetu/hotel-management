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

// Application Settings
define('APP_NAME', 'Hotel & Resort Management System');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/hotel-management');
define('APP_ENV', 'development'); // development, production

// Session Settings
define('SESSION_NAME', 'HOTEL_SESSION');
define('SESSION_LIFETIME', 3600); // 1 hour in seconds

// Timezone
define('TIMEZONE', 'UTC');
date_default_timezone_set(TIMEZONE);

// File Upload Settings
define('UPLOAD_MAX_SIZE', 5242880); // 5MB in bytes
define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);
define('UPLOAD_PATH', APP_ROOT . '/assets/uploads');

// Pagination
define('ITEMS_PER_PAGE', 20);

// Date Format
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'F j, Y');
define('DISPLAY_DATETIME_FORMAT', 'F j, Y g:i A');

// Currency Settings
define('CURRENCY_CODE', 'USD');
define('CURRENCY_SYMBOL', '$');
define('CURRENCY_POSITION', 'left'); // left, right

// Email Settings
define('MAIL_FROM_NAME', APP_NAME);
define('MAIL_FROM_EMAIL', 'noreply@hotel-management.com');
define('MAIL_ENABLED', false);

// Maintenance Mode
define('MAINTENANCE_MODE', false);

// Debug Mode (set to false in production)
define('DEBUG_MODE', true);

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
define('SESSION_SECURE', false); // Set to true if using HTTPS
define('SESSION_HTTPONLY', true);
define('SESSION_SAMESITE', 'Lax');

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
define('LOG_ENABLED', true);
define('LOG_PATH', APP_ROOT . '/logs');
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR

// Backup Settings
define('BACKUP_ENABLED', false);
define('BACKUP_PATH', APP_ROOT . '/backups');
define('BACKUP_RETENTION_DAYS', 30);
