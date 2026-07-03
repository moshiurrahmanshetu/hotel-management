<?php

/**
 * Hotel & Resort Management System
 * Header Include File
 * 
 * Reusable header with meta tags, CSS, and JavaScript includes
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Load bootstrap if not already loaded (ensures config is available)
if (!defined('APP_NAME')) {
    require_once APP_ROOT . '/includes/bootstrap.php';
}

$page_title = isset($page_title) ? $page_title : APP_NAME;
$page_description = isset($page_description) ? $page_description : APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta name="author" content="<?php echo APP_NAME; ?>">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- AOS Animation CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo APP_URL; ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?php echo APP_URL; ?>/assets/css/components.css" rel="stylesheet">
    
    <?php if (isset($extra_css)): ?>
        <?php echo $extra_css; ?>
    <?php endif; ?>
</head>
<body>
    <?php
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    ?>
