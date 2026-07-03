<?php
/**
 * Hotel & Resort Management System
 * Logout Page
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__FILE__));
}

// Load bootstrap (includes config, installer check, etc.)
require_once APP_ROOT . '/includes/bootstrap.php';
require_once APP_ROOT . '/includes/auth.php';

// Logout user
logout();

// Redirect to login page
redirect(APP_URL . '/login.php');
