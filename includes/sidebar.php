<?php
/**
 * Hotel & Resort Management System
 * Sidebar Include File
 * 
 * Reusable responsive sidebar with navigation menu
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}
?>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <i class="bi bi-building"></i>
            <span><?php echo APP_NAME; ?></span>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/dashboard.php">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="<?php echo APP_URL; ?>/modules/rooms/index.php">
                    <i class="bi bi-door-open"></i>
                    <span>Rooms</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="<?php echo APP_URL; ?>/modules/bookings/index.php">
                    <i class="bi bi-calendar-check"></i>
                    <span>Bookings</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="<?php echo APP_URL; ?>/modules/guests/index.php">
                    <i class="bi bi-people"></i>
                    <span>Guests</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="<?php echo APP_URL; ?>/modules/payments/index.php">
                    <i class="bi bi-credit-card"></i>
                    <span>Payments</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="<?php echo APP_URL; ?>/modules/services/index.php">
                    <i class="bi bi-cone-striped"></i>
                    <span>Services</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="<?php echo APP_URL; ?>/modules/staff/index.php">
                    <i class="bi bi-person-badge"></i>
                    <span>Staff</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="<?php echo APP_URL; ?>/modules/reports/index.php">
                    <i class="bi bi-graph-up"></i>
                    <span>Reports</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="<?php echo APP_URL; ?>/modules/settings/index.php">
                    <i class="bi bi-gear"></i>
                    <span>Settings</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="user-avatar">
                <i class="bi bi-person-circle"></i>
            </div>
            <div class="user-info">
                <div class="user-name">Admin User</div>
                <div class="user-role">Administrator</div>
            </div>
        </div>
    </div>
</aside>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>
