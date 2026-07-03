<?php
/**
 * Hotel & Resort Management System
 * Index Page - Landing Page
 * 
 * Redirects to login page
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__FILE__));
}

// Load bootstrap (includes config, installer check, etc.)
require_once APP_ROOT . '/includes/bootstrap.php';

// Redirect to login page
header('Location: ' . APP_URL . '/login.php');
exit;
