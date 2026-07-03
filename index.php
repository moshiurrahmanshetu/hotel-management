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

// Redirect to login page
header('Location: ' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/hotel-management/login.php');
exit;
