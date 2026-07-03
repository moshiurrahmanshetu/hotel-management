<?php
/**
 * Hotel & Resort Management System
 * Room Rates Module - Listing Page
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(dirname(dirname(__FILE__))));
}

// Load bootstrap (includes config, installer check, etc.)
require_once APP_ROOT . '/includes/bootstrap.php';
require_once APP_ROOT . '/includes/auth.php';

// Require authentication
requireAuth();

$page_title = 'Room Rates';
$page_description = 'Manage room rates and pricing';

// Breadcrumb items
$breadcrumb_items = [
    ['label' => 'Dashboard', 'url' => APP_URL . '/dashboard.php'],
    ['label' => 'Room Rates', 'active' => true]
];

$error = '';
$success = '';

$db = getDB();

// Get filter values
$search = sanitizeString($_GET['search'] ?? '');
$ratePlanFilter = (int)($_GET['rate_plan'] ?? 0);
$statusFilter = sanitizeString($_GET['status'] ?? '');
$page = (int)($_GET['page'] ?? 1);
$perPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $perPage;

// Build WHERE clause
$where = ['rr.deleted_at IS NULL'];
$params = [];

if ($search) {
    $where[] = "(r.room_number LIKE ? OR r.room_name LIKE ? OR rp.name LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($ratePlanFilter) {
    $where[] = "rr.rate_plan_id = ?";
    $params[] = $ratePlanFilter;
}

if ($statusFilter) {
    if ($statusFilter === 'active') {
        $where[] = "rr.is_active = 1";
    } elseif ($statusFilter === 'inactive') {
        $where[] = "rr.is_active = 0";
    }
}

$whereClause = implode(' AND ', $where);

// Get total count
$countSql = "SELECT COUNT(*) FROM room_rates rr INNER JOIN rooms r ON rr.room_id = r.id INNER JOIN rate_plans rp ON rr.rate_plan_id = rp.id WHERE {$whereClause}";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$totalRecords = $stmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

// Get room rates
$sql = "
    SELECT rr.*, 
           r.room_number, r.room_name,
           rp.name as rate_plan_name, rp.code as rate_plan_code,
           p.name as property_name
    FROM room_rates rr
    INNER JOIN rooms r ON rr.room_id = r.id
    INNER JOIN rate_plans rp ON rr.rate_plan_id = rp.id
    INNER JOIN properties p ON r.property_id = p.id
    WHERE {$whereClause}
    ORDER BY p.name, r.room_number, rp.name
    LIMIT {$perPage} OFFSET {$offset}
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$roomRates = $stmt->fetchAll();

// Get rate plans for filter
$stmt = $db->query("SELECT id, name, code FROM rate_plans WHERE deleted_at IS NULL ORDER BY name ASC");
$ratePlans = $stmt->fetchAll();

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $rateId = (int)($_POST['rate_id'] ?? 0);
        
        try {
            $stmt = $db->prepare("UPDATE room_rates SET deleted_at = NOW() WHERE id = ?");
            $stmt->execute([$rateId]);
            
            logActivity('delete', 'room_rates', "Deleted room rate ID: {$rateId}");
            $success = 'Room rate deleted successfully.';
            
            // Refresh data
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $roomRates = $stmt->fetchAll();
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                error_log("Delete room rate error: " . $e->getMessage());
            }
            $error = 'An error occurred while deleting the room rate.';
        }
    }
}

// Handle toggle active status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $rateId = (int)($_POST['rate_id'] ?? 0);
        $status = (int)($_POST['status'] ?? 0);
        
        try {
            $stmt = $db->prepare("UPDATE room_rates SET is_active = ? WHERE id = ?");
            $stmt->execute([$status, $rateId]);
            
            logActivity('update', 'room_rates', "Toggled room rate ID: {$rateId}");
            
            // Refresh data
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $roomRates = $stmt->fetchAll();
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                error_log("Toggle room rate error: " . $e->getMessage());
            }
            $error = 'An error occurred while updating the room rate.';
        }
    }
}
?>
<?php require_once APP_ROOT . '/includes/header.php'; ?>

<div class="main-wrapper">
    <?php require_once APP_ROOT . '/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php require_once APP_ROOT . '/includes/topbar.php'; ?>
        
        <main class="content-area">
            <div class="container-fluid">
                <div class="page-header">
                    <h1 class="page-title">Room Rates</h1>
                    <p class="page-subtitle">Manage room rates and pricing plans</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="bi bi-check-circle me-2"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <form action="" method="GET" class="mb-4">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <input type="text" class="form-control" name="search" placeholder="Search room or rate plan..." value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" name="rate_plan">
                                        <option value="">All Rate Plans</option>
                                        <?php foreach ($ratePlans as $plan): ?>
                                            <option value="<?php echo $plan['id']; ?>" <?php echo $ratePlanFilter === $plan['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($plan['name']); ?> (<?php echo htmlspecialchars($plan['code']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" name="status">
                                        <option value="">All Status</option>
                                        <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search me-2"></i>Search
                                    </button>
                                    <a href="<?php echo APP_URL; ?>/modules/room-rates/index.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-x-circle me-2"></i>Clear
                                    </a>
                                </div>
                            </div>
                        </form>
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Room</th>
                                        <th>Property</th>
                                        <th>Rate Plan</th>
                                        <th>Base Price</th>
                                        <th>Weekend Price</th>
                                        <th>Tax Included</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($roomRates)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <div class="empty-state">
                                                    <i class="bi bi-currency-dollar empty-state-icon"></i>
                                                    <h5 class="empty-state-title">No room rates found</h5>
                                                    <p class="empty-state-description">Add your first room rate to get started.</p>
                                                    <a href="<?php echo APP_URL; ?>/modules/room-rates/create.php" class="btn btn-primary">
                                                        <i class="bi bi-plus me-2"></i>Add Room Rate
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($roomRates as $rate): ?>
                                            <tr data-aos="fade-up">
                                                <td>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($rate['room_number']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($rate['room_name'] ?? ''); ?></small>
                                                </td>
                                                <td>
                                                    <small><?php echo htmlspecialchars($rate['property_name']); ?></small>
                                                </td>
                                                <td>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($rate['rate_plan_name']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($rate['rate_plan_code']); ?></small>
                                                </td>
                                                <td>
                                                    <div class="fw-bold"><?php echo number_format($rate['base_price'], 2); ?></div>
                                                </td>
                                                <td>
                                                    <?php if ($rate['weekend_price']): ?>
                                                        <?php echo number_format($rate['weekend_price'], 2); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($rate['tax_included']): ?>
                                                        <span class="badge bg-success">Yes</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">No</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($rate['is_active']): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="table-actions">
                                                        <button type="button" class="table-action-btn" onclick="toggleStatus(<?php echo $rate['id']; ?>, <?php echo $rate['is_active'] ? 0 : 1; ?>)" title="<?php echo $rate['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                            <i class="bi bi-<?php echo $rate['is_active'] ? 'toggle-on' : 'toggle-off'; ?>"></i>
                                                        </button>
                                                        <a href="<?php echo APP_URL; ?>/modules/room-rates/edit.php?id=<?php echo $rate['id']; ?>" class="table-action-btn" title="Edit">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <button type="button" class="table-action-btn danger" onclick="confirmDelete(<?php echo $rate['id']; ?>, '<?php echo htmlspecialchars($rate['room_number']); ?> - <?php echo htmlspecialchars($rate['rate_plan_name']); ?>')" title="Delete">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $ratePlanFilter ? '&rate_plan=' . $ratePlanFilter : ''; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <?php if ($i == $page): ?>
                                            <li class="page-item active">
                                                <span class="page-link"><?php echo $i; ?></span>
                                            </li>
                                        <?php elseif ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $ratePlanFilter ? '&rate_plan=' . $ratePlanFilter : ''; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $ratePlanFilter ? '&rate_plan=' . $ratePlanFilter : ''; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?>">Next</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <a href="<?php echo APP_URL; ?>/modules/room-rates/create.php" class="btn btn-primary">
                                <i class="bi bi-plus me-2"></i>Add Room Rate
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal-backdrop" id="deleteModal">
    <div class="modal">
        <div class="modal-header">
            <h5 class="modal-title">Delete Room Rate</h5>
            <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete this room rate?</p>
            <p class="text-muted"><strong><?php echo htmlspecialchars($roomRateInfo ?? ''); ?></strong></p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
            <button class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
        </div>
    </div>
</div>

<!-- Toggle Status Form -->
<form id="toggleForm" method="POST" style="display: none;">
    <?php echo csrfField(); ?>
    <input type="hidden" name="action" value="toggle">
    <input type="hidden" name="rate_id" id="toggleRateId">
    <input type="hidden" name="status" id="toggleStatus">
</form>

<!-- Delete Form -->
<form id="deleteForm" method="POST" style="display: none;">
    <?php echo csrfField(); ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="rate_id" id="deleteRateId">
</form>

<script>
let deleteRateId = null;
let roomRateInfo = '';

function confirmDelete(rateId, info) {
    deleteRateId = rateId;
    roomRateInfo = info;
    document.getElementById('deleteModal').querySelector('.text-muted').textContent = info;
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
    deleteRateId = null;
    roomRateInfo = '';
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (deleteRateId) {
        document.getElementById('deleteRateId').value = deleteRateId;
        document.getElementById('deleteForm').submit();
    }
});

function toggleStatus(rateId, status) {
    document.getElementById('toggleRateId').value = rateId;
    document.getElementById('toggleStatus').value = status;
    document.getElementById('toggleForm').submit();
}
</script>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
