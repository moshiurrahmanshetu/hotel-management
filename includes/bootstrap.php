<?php
/**
 * Hotel & Resort Management System
 * Bootstrap File
 * 
 * This file loads BEFORE config.php to ensure all directories exist
 * It runs the installer check to auto-create missing directories
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Load env loader first (needed by installer checker and config)
require_once APP_ROOT . '/includes/env.php';

// Load installer checker (runs before config to create directories)
require_once APP_ROOT . '/includes/installer_checker.php';

// Run installation check (can be disabled by setting SKIP_INSTALL_CHECK=true in .env)
// Note: We check for .env first to see if SKIP_INSTALL_CHECK is set
$envFile = APP_ROOT . '/.env';
$skipInstallCheck = false;

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), 'SKIP_INSTALL_CHECK') === 0) {
            if (strpos($line, '=true') !== false) {
                $skipInstallCheck = true;
            }
            break;
        }
    }
}

if (!$skipInstallCheck) {
    runInstallerCheck();
}

// Now load configuration
require_once APP_ROOT . '/config/config.php';
