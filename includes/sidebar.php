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

// Load authentication
require_once APP_ROOT . '/includes/auth.php';

// Get current user
$currentUser = authUser();
$userName = $currentUser ? ($currentUser['first_name'] . ' ' . $currentUser['last_name']) : 'Guest';
$userRole = $currentUser && !empty($currentUser['roles']) ? ucfirst(str_replace('_', ' ', $currentUser['roles'][0])) : 'Guest';
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
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/dashboard.php" data-page="dashboard">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <?php if (hasPermission('users.view') || hasPermission('roles.view')): ?>
            <li class="nav-item has-submenu">
                <a class="nav-link submenu-toggle" href="javascript:void(0)">
                    <i class="bi bi-gear"></i>
                    <span>Administration</span>
                    <i class="bi bi-chevron-down submenu-arrow"></i>
                </a>
                <ul class="nav submenu">
                    <?php if (hasPermission('users.view')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/modules/users/index.php" data-page="users">
                            <i class="bi bi-people"></i>
                            <span>Users</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('roles.view')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/modules/roles/index.php" data-page="roles">
                            <i class="bi bi-shield-lock"></i>
                            <span>Roles</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('roles.permissions')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/modules/permissions/index.php" data-page="permissions">
                            <i class="bi bi-key"></i>
                            <span>Permissions</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>
            
            <li class="nav-item has-submenu">
                <a class="nav-link submenu-toggle" href="javascript:void(0)">
                    <i class="bi bi-building"></i>
                    <span>Hotel Structure</span>
                    <i class="bi bi-chevron-down submenu-arrow"></i>
                </a>
                <ul class="nav submenu">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/modules/properties/index.php" data-page="properties">
                            <i class="bi bi-building"></i>
                            <span>Properties</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/modules/buildings/index.php" data-page="buildings">
                            <i class="bi bi-building"></i>
                            <span>Buildings</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/modules/floors/index.php" data-page="floors">
                            <i class="bi bi-layers"></i>
                            <span>Floors</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <li class="nav-item has-submenu">
                <a class="nav-link submenu-toggle" href="javascript:void(0)">
                    <i class="bi bi-database"></i>
                    <span>Master Data</span>
                    <i class="bi bi-chevron-down submenu-arrow"></i>
                </a>
                <ul class="nav submenu">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/modules/master-data/index.php" data-page="master-groups">
                            <i class="bi bi-folder"></i>
                            <span>Groups</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/modules/master-data/items.php" data-page="master-items">
                            <i class="bi bi-list-ul"></i>
                            <span>Items</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="<?php echo APP_URL; ?>/modules/custom-fields/index.php" data-page="custom-fields">
                    <i class="bi bi-input-cursor-text"></i>
                    <span>Custom Fields</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="<?php echo APP_URL; ?>/modules/rooms/index.php" data-page="rooms">
                    <i class="bi bi-door-open"></i>
                    <span>Rooms</span>
                </a>
            </li>
            
            <li class="nav-item has-submenu">
                <a class="nav-link submenu-toggle" href="javascript:void(0)">
                    <i class="bi bi-calendar-check"></i>
                    <span>Bookings</span>
                    <span class="badge bg-primary rounded-pill ms-auto">3</span>
                    <i class="bi bi-chevron-down submenu-arrow"></i>
                </a>
                <ul class="nav submenu">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/modules/bookings/index.php" data-page="bookings">
                            <i class="bi bi-grid"></i>
                            <span>All Bookings</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/modules/bookings/calendar.php" data-page="booking-calendar">
                            <i class="bi bi-calendar3"></i>
                            <span>Calendar</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/modules/bookings/checkin.php" data-page="checkin">
                            <i class="bi bi-box-arrow-in-right"></i>
                            <span>Check-in</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/modules/bookings/checkout.php" data-page="checkout">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Check-out</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <li class="nav-item has-submenu">
                <a class="nav-link submenu-toggle" href="javascript:void(0)">
                    <i class="bi bi-people"></i>
                    <span>Guests</span>
                    <i class="bi bi-chevron-down submenu-arrow"></i>
                </a>
                <ul class="nav submenu">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/modules/guests/index.php" data-page="guests">
                            <i class="bi bi-grid"></i>
                            <span>All Guests</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/modules/guests/add.php" data-page="add-guest">
                            <i class="bi bi-person-plus"></i>
                            <span>Add Guest</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <li class="nav-item has-submenu">
                <a class="nav-link submenu-toggle" href="javascript:void(0)">
                    <i class="bi bi-credit-card"></i>
                    <span>Payments</span>
                    <i class="bi bi-chevron-down submenu-arrow"></i>
                </a>
                <ul class="nav submenu">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/modules/payments/index.php" data-page="payments">
                            <i class="bi bi-grid"></i>
                            <span>All Payments</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/modules/payments/invoices.php" data-page="invoices">
                            <i class="bi bi-receipt"></i>
                            <span>Invoices</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <li class="nav-item has-submenu">
                <a class="nav-link submenu-toggle" href="javascript:void(0)">
                    <i class="bi bi-cone-striped"></i>
                    <span>Services</span>
                    <i class="bi bi-chevron-down submenu-arrow"></i>
                </a>
                <ul class="nav submenu">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/modules/services/index.php" data-page="services">
                            <i class="bi bi-grid"></i>
                            <span>All Services</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/modules/services/categories.php" data-page="service-categories">
                            <i class="bi bi-tags"></i>
                            <span>Categories</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <li class="nav-item has-submenu">
                <a class="nav-link submenu-toggle" href="javascript:void(0)">
                    <i class="bi bi-person-badge"></i>
                    <span>Staff</span>
                    <i class="bi bi-chevron-down submenu-arrow"></i>
                </a>
                <ul class="nav submenu">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/modules/staff/index.php" data-page="staff">
                            <i class="bi bi-grid"></i>
                            <span>All Staff</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/modules/staff/departments.php" data-page="departments">
                            <i class="bi bi-building"></i>
                            <span>Departments</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/modules/staff/shifts.php" data-page="shifts">
                            <i class="bi bi-clock"></i>
                            <span>Shifts</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <li class="nav-item has-submenu">
                <a class="nav-link submenu-toggle" href="javascript:void(0)">
                    <i class="bi bi-graph-up"></i>
                    <span>Reports</span>
                    <i class="bi bi-chevron-down submenu-arrow"></i>
                </a>
                <ul class="nav submenu">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/modules/reports/index.php" data-page="reports">
                            <i class="bi bi-grid"></i>
                            <span>Overview</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/modules/reports/occupancy.php" data-page="occupancy-report">
                            <i class="bi bi-bar-chart"></i>
                            <span>Occupancy</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/modules/reports/revenue.php" data-page="revenue-report">
                            <i class="bi bi-currency-dollar"></i>
                            <span>Revenue</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <?php if (hasPermission('settings.edit')): ?>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo APP_URL; ?>/modules/settings/index.php" data-page="settings">
                    <i class="bi bi-gear"></i>
                    <span>Settings</span>
                </a>
            </li>
            <?php endif; ?>
            </li>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="user-avatar">
                <?php if ($currentUser && $currentUser['avatar']): ?>
                    <img src="<?php echo htmlspecialchars($currentUser['avatar']); ?>" alt="User Avatar">
                <?php else: ?>
                    <i class="bi bi-person-circle"></i>
                <?php endif; ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($userName); ?></div>
                <div class="user-role"><?php echo htmlspecialchars($userRole); ?></div>
            </div>
            <button class="user-toggle">
                <i class="bi bi-chevron-up"></i>
            </button>
        </div>
    </div>
</aside>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>
