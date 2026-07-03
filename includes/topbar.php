<?php
/**
 * Hotel & Resort Management System
 * Topbar Include File
 * 
 * Reusable responsive topbar with search, notifications, and user profile
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
$userEmail = $currentUser ? $currentUser['email'] : '';
?>

<!-- Topbar -->
<header class="topbar" id="topbar">
    <div class="topbar-left">
        <button class="topbar-toggle" id="topbarToggle">
            <i class="bi bi-list"></i>
        </button>
        
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="breadcrumb-nav">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?php echo APP_URL; ?>/dashboard.php">
                        <i class="bi bi-house"></i>
                    </a>
                </li>
                <?php if (isset($breadcrumb_items) && is_array($breadcrumb_items)): ?>
                    <?php foreach ($breadcrumb_items as $item): ?>
                        <?php if (isset($item['active']) && $item['active']): ?>
                            <li class="breadcrumb-item active" aria-current="page">
                                <?php echo htmlspecialchars($item['label']); ?>
                            </li>
                        <?php else: ?>
                            <li class="breadcrumb-item">
                                <a href="<?php echo isset($item['url']) ? $item['url'] : '#'; ?>">
                                    <?php echo htmlspecialchars($item['label']); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ol>
        </nav>
    </div>
    
    <div class="topbar-right">
        <!-- Search Box -->
        <div class="search-box">
            <div class="input-group">
                <span class="input-group-text">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" class="form-control" placeholder="Search..." id="globalSearch">
                <button class="btn btn-outline-secondary search-toggle" type="button" id="searchToggle">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>
        
        <!-- Fullscreen Toggle -->
        <div class="topbar-item">
            <button class="topbar-btn" id="fullscreenToggle" title="Fullscreen">
                <i class="bi bi-fullscreen"></i>
            </button>
        </div>
        
        <!-- Dark Mode Toggle -->
        <div class="topbar-item">
            <button class="topbar-btn" id="darkModeToggle" title="Toggle Dark Mode">
                <i class="bi bi-moon"></i>
            </button>
        </div>
        
        <!-- Date & Time -->
        <div class="topbar-item d-none d-lg-block">
            <div class="datetime-display">
                <div class="date" id="currentDate"></div>
                <div class="time" id="currentTime"></div>
            </div>
        </div>
        
        <!-- Notifications -->
        <div class="topbar-item dropdown">
            <button class="topbar-btn dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-bell"></i>
                <span class="badge bg-danger notification-badge">3</span>
            </button>
            <div class="dropdown-menu dropdown-menu-end notification-dropdown">
                <div class="dropdown-header">
                    <h6>Notifications</h6>
                    <a href="#" class="mark-read">Mark all as read</a>
                </div>
                <div class="dropdown-body">
                    <a href="#" class="notification-item unread">
                        <div class="notification-icon">
                            <i class="bi bi-calendar-check text-primary"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">New Booking</div>
                            <div class="notification-text">Room 101 has been booked</div>
                            <div class="notification-time">5 minutes ago</div>
                        </div>
                    </a>
                    <a href="#" class="notification-item unread">
                        <div class="notification-icon">
                            <i class="bi bi-credit-card text-success"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">Payment Received</div>
                            <div class="notification-text">$500 payment received</div>
                            <div class="notification-time">1 hour ago</div>
                        </div>
                    </a>
                    <a href="#" class="notification-item">
                        <div class="notification-icon">
                            <i class="bi bi-exclamation-triangle text-warning"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">Room Maintenance</div>
                            <div class="notification-text">Room 205 needs maintenance</div>
                            <div class="notification-time">3 hours ago</div>
                        </div>
                    </a>
                </div>
                <div class="dropdown-footer">
                    <a href="#" class="view-all">View all notifications</a>
                </div>
            </div>
        </div>
        
        <!-- User Profile -->
        <div class="topbar-item dropdown">
            <button class="topbar-btn dropdown-toggle user-dropdown-btn" type="button" data-bs-toggle="dropdown">
                <div class="user-avatar">
                    <?php if ($currentUser && $currentUser['avatar']): ?>
                        <img src="<?php echo htmlspecialchars($currentUser['avatar']); ?>" alt="User Avatar">
                    <?php else: ?>
                        <i class="bi bi-person-circle"></i>
                    <?php endif; ?>
                </div>
                <span class="user-name d-none d-md-inline"><?php echo htmlspecialchars($userName); ?></span>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end user-dropdown">
                <div class="dropdown-header">
                    <div class="user-info">
                        <div class="user-avatar-lg">
                            <?php if ($currentUser && $currentUser['avatar']): ?>
                                <img src="<?php echo htmlspecialchars($currentUser['avatar']); ?>" alt="User Avatar">
                            <?php else: ?>
                                <i class="bi bi-person-circle"></i>
                            <?php endif; ?>
                        </div>
                        <div class="user-details">
                            <div class="user-name"><?php echo htmlspecialchars($userName); ?></div>
                            <div class="user-email"><?php echo htmlspecialchars($userEmail); ?></div>
                        </div>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item">
                    <i class="bi bi-person"></i>
                    <span>My Profile</span>
                </a>
                <a href="#" class="dropdown-item">
                    <i class="bi bi-gear"></i>
                    <span>Settings</span>
                </a>
                <a href="#" class="dropdown-item">
                    <i class="bi bi-shield-lock"></i>
                    <span>Change Password</span>
                </a>
                <div class="dropdown-divider"></div>
                <a href="<?php echo APP_URL; ?>/logout.php" class="dropdown-item text-danger">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
</header>
