<?php
/**
 * Hotel & Resort Management System
 * Dashboard Page
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__FILE__));
}

// Load bootstrap (includes config, installer check, etc.)
require_once APP_ROOT . '/includes/bootstrap.php';
require_once APP_ROOT . '/includes/auth.php';

// Require authentication
requireAuth();

$page_title = 'Dashboard';
$page_description = 'Welcome to ' . APP_NAME;

// Breadcrumb items
$breadcrumb_items = [
    ['label' => 'Dashboard', 'active' => true]
];

// Get current user
$currentUser = authUser();
?>
<?php require_once APP_ROOT . '/includes/header.php'; ?>

<div class="main-wrapper">
    <?php require_once APP_ROOT . '/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php require_once APP_ROOT . '/includes/topbar.php'; ?>
        
        <main class="content-area">
            <div class="container-fluid">
                <div class="page-header">
                    <h1 class="page-title">Dashboard</h1>
                    <p class="page-subtitle">Welcome back! Here's what's happening today.</p>
                </div>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="100">
                        <div class="stat-card stat-card-primary">
                            <div class="stat-icon">
                                <i class="bi bi-door-open"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-label">Total Rooms</div>
                                <div class="stat-value">120</div>
                                <div class="stat-change positive">
                                    <i class="bi bi-arrow-up"></i>
                                    <span>12% from last month</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="200">
                        <div class="stat-card stat-card-success">
                            <div class="stat-icon">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-label">Active Bookings</div>
                                <div class="stat-value">45</div>
                                <div class="stat-change positive">
                                    <i class="bi bi-arrow-up"></i>
                                    <span>8% from last month</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="300">
                        <div class="stat-card stat-card-warning">
                            <div class="stat-icon">
                                <i class="bi bi-people"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-label">Total Guests</div>
                                <div class="stat-value">89</div>
                                <div class="stat-change positive">
                                    <i class="bi bi-arrow-up"></i>
                                    <span>15% from last month</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="400">
                        <div class="stat-card stat-card-info">
                            <div class="stat-icon">
                                <i class="bi bi-currency-dollar"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-label">Revenue</div>
                                <div class="stat-value">$12,450</div>
                                <div class="stat-change positive">
                                    <i class="bi bi-arrow-up"></i>
                                    <span>23% from last month</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="row mb-4">
                    <div class="col-lg-8 mb-4" data-aos="fade-up" data-aos-delay="500">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Recent Bookings</h5>
                                <a href="#" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Booking ID</th>
                                                <th>Guest Name</th>
                                                <th>Room</th>
                                                <th>Check-in</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>#BK001</td>
                                                <td>John Doe</td>
                                                <td>Room 101</td>
                                                <td>2024-01-15</td>
                                                <td><span class="badge bg-success">Confirmed</span></td>
                                            </tr>
                                            <tr>
                                                <td>#BK002</td>
                                                <td>Jane Smith</td>
                                                <td>Room 205</td>
                                                <td>2024-01-16</td>
                                                <td><span class="badge bg-warning">Pending</span></td>
                                            </tr>
                                            <tr>
                                                <td>#BK003</td>
                                                <td>Mike Johnson</td>
                                                <td>Room 302</td>
                                                <td>2024-01-17</td>
                                                <td><span class="badge bg-success">Confirmed</span></td>
                                            </tr>
                                            <tr>
                                                <td>#BK004</td>
                                                <td>Sarah Williams</td>
                                                <td>Room 401</td>
                                                <td>2024-01-18</td>
                                                <td><span class="badge bg-info">Checked In</span></td>
                                            </tr>
                                            <tr>
                                                <td>#BK005</td>
                                                <td>Tom Brown</td>
                                                <td>Room 503</td>
                                                <td>2024-01-19</td>
                                                <td><span class="badge bg-secondary">Cancelled</span></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4 mb-4" data-aos="fade-up" data-aos-delay="600">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Room Status</h5>
                            </div>
                            <div class="card-body">
                                <div class="room-status">
                                    <div class="status-item">
                                        <div class="status-label">
                                            <span class="status-dot available"></span>
                                            Available
                                        </div>
                                        <div class="status-count">45</div>
                                    </div>
                                    <div class="status-item">
                                        <div class="status-label">
                                            <span class="status-dot occupied"></span>
                                            Occupied
                                        </div>
                                        <div class="status-count">52</div>
                                    </div>
                                    <div class="status-item">
                                        <div class="status-label">
                                            <span class="status-dot maintenance"></span>
                                            Maintenance
                                        </div>
                                        <div class="status-count">15</div>
                                    </div>
                                    <div class="status-item">
                                        <div class="status-label">
                                            <span class="status-dot reserved"></span>
                                            Reserved
                                        </div>
                                        <div class="status-count">8</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="row" data-aos="fade-up" data-aos-delay="700">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="quick-actions">
                                    <a href="#" class="quick-action-btn">
                                        <i class="bi bi-plus-circle"></i>
                                        <span>New Booking</span>
                                    </a>
                                    <a href="#" class="quick-action-btn">
                                        <i class="bi bi-person-plus"></i>
                                        <span>Add Guest</span>
                                    </a>
                                    <a href="#" class="quick-action-btn">
                                        <i class="bi bi-door-open"></i>
                                        <span>Check-in</span>
                                    </a>
                                    <a href="#" class="quick-action-btn">
                                        <i class="bi bi-door-closed"></i>
                                        <span>Check-out</span>
                                    </a>
                                    <a href="#" class="quick-action-btn">
                                        <i class="bi bi-receipt"></i>
                                        <span>New Payment</span>
                                    </a>
                                    <a href="#" class="quick-action-btn">
                                        <i class="bi bi-file-earmark-plus"></i>
                                        <span>Generate Report</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <?php require_once APP_ROOT . '/includes/footer.php'; ?>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
