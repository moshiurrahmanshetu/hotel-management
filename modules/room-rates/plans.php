<?php
/**
 * Hotel & Resort Management System
 * Room Rates Module - Rate Plans Page
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

$page_title = 'Rate Plans';
$page_description = 'Manage rate plans';

// Breadcrumb items
$breadcrumb_items = [
    ['label' => 'Dashboard', 'url' => APP_URL . '/dashboard.php'],
    ['label' => 'Room Rates', 'url' => APP_URL . '/modules/room-rates/index.php'],
    ['label' => 'Rate Plans', 'active' => true]
];

$error = '';
$success = '';

$db = getDB();

// Get filter values
$search = sanitizeString($_GET['search'] ?? '');
$statusFilter = sanitizeString($_GET['status'] ?? '');
$page = (int)($_GET['page'] ?? 1);
$perPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $perPage;

// Build WHERE clause
$where = ['deleted_at IS NULL'];
$params = [];

if ($search) {
    $where[] = "(name LIKE ? OR code LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($statusFilter) {
    if ($statusFilter === 'active') {
        $where[] = "is_active = 1";
    } elseif ($statusFilter === 'inactive') {
        $where[] = "is_active = 0";
    }
}

$whereClause = implode(' AND ', $where);

// Get total count
$countSql = "SELECT COUNT(*) FROM rate_plans WHERE {$whereClause}";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$totalRecords = $stmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

// Get rate plans
$sql = "SELECT * FROM rate_plans WHERE {$whereClause} ORDER BY name ASC LIMIT {$perPage} OFFSET {$offset}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$ratePlans = $stmt->fetchAll();

// Handle create/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $planId = (int)($_POST['plan_id'] ?? 0);
        $name = sanitizeString($_POST['name'] ?? '');
        $code = sanitizeString($_POST['code'] ?? '');
        $description = sanitizeString($_POST['description'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        // Validation
        if (!$name) {
            $error = 'Please enter a name.';
        } elseif (!$code) {
            $error = 'Please enter a code.';
        } else {
            // Check if code already exists
            if ($planId) {
                $stmt = $db->prepare("SELECT id FROM rate_plans WHERE code = ? AND id != ? AND deleted_at IS NULL");
                $stmt->execute([$code, $planId]);
            } else {
                $stmt = $db->prepare("SELECT id FROM rate_plans WHERE code = ? AND deleted_at IS NULL");
                $stmt->execute([$code]);
            }
            if ($stmt->fetch()) {
                $error = 'This code already exists.';
            }
        }
        
        if (!$error) {
            try {
                if ($planId) {
                    // Update
                    $stmt = $db->prepare("UPDATE rate_plans SET name = ?, code = ?, description = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$name, $code, $description ?: null, $isActive, $planId]);
                    logActivity('update', 'rate_plans', "Updated rate plan ID: {$planId}");
                    $success = 'Rate plan updated successfully.';
                } else {
                    // Create
                    $uuid = generateUUID();
                    $stmt = $db->prepare("INSERT INTO rate_plans (uuid, name, code, description, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
                    $stmt->execute([$uuid, $name, $code, $description ?: null, $isActive]);
                    logActivity('create', 'rate_plans', "Created rate plan: {$name}");
                    $success = 'Rate plan created successfully.';
                }
                
                // Refresh data
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $ratePlans = $stmt->fetchAll();
                
                // Clear form
                $_POST = [];
            } catch (PDOException $e) {
                if (DEBUG_MODE) {
                    error_log("Rate plan error: " . $e->getMessage());
                }
                $error = 'An error occurred while saving the rate plan.';
            }
        }
    }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $planId = (int)($_POST['plan_id'] ?? 0);
        
        try {
            $stmt = $db->prepare("UPDATE rate_plans SET deleted_at = NOW() WHERE id = ?");
            $stmt->execute([$planId]);
            
            logActivity('delete', 'rate_plans', "Deleted rate plan ID: {$planId}");
            $success = 'Rate plan deleted successfully.';
            
            // Refresh data
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $ratePlans = $stmt->fetchAll();
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                error_log("Delete rate plan error: " . $e->getMessage());
            }
            $error = 'An error occurred while deleting the rate plan.';
        }
    }
}

// Handle toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $planId = (int)($_POST['plan_id'] ?? 0);
        $status = (int)($_POST['status'] ?? 0);
        
        try {
            $stmt = $db->prepare("UPDATE rate_plans SET is_active = ? WHERE id = ?");
            $stmt->execute([$status, $planId]);
            
            logActivity('update', 'rate_plans', "Toggled rate plan ID: {$planId}");
            
            // Refresh data
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $ratePlans = $stmt->fetchAll();
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                error_log("Toggle rate plan error: " . $e->getMessage());
            }
            $error = 'An error occurred while updating the rate plan.';
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
                    <h1 class="page-title">Rate Plans</h1>
                    <p class="page-subtitle">Manage rate plans and pricing strategies</p>
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
                
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card mb-4">
                            <div class="card-body">
                                <form action="" method="GET" class="mb-4">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <input type="text" class="form-control" name="search" placeholder="Search rate plans..." value="<?php echo htmlspecialchars($search); ?>">
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
                                            <a href="<?php echo APP_URL; ?>/modules/room-rates/plans.php" class="btn btn-outline-secondary">
                                                <i class="bi bi-x-circle me-2"></i>Clear
                                            </a>
                                        </div>
                                    </div>
                                </form>
                                
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Code</th>
                                                <th>Description</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($ratePlans)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center py-4">
                                                        <div class="empty-state">
                                                            <i class="bi bi-tags empty-state-icon"></i>
                                                            <h5 class="empty-state-title">No rate plans found</h5>
                                                            <p class="empty-state-description">Add your first rate plan to get started.</p>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($ratePlans as $plan): ?>
                                                    <tr data-aos="fade-up">
                                                        <td>
                                                            <div class="fw-bold"><?php echo htmlspecialchars($plan['name']); ?></div>
                                                        </td>
                                                        <td>
                                                            <code><?php echo htmlspecialchars($plan['code']); ?></code>
                                                        </td>
                                                        <td>
                                                            <small class="text-muted"><?php echo htmlspecialchars($plan['description'] ?? '-'); ?></small>
                                                        </td>
                                                        <td>
                                                            <?php if ($plan['is_active']): ?>
                                                                <span class="badge bg-success">Active</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-danger">Inactive</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <div class="table-actions">
                                                                <button type="button" class="table-action-btn" onclick="editPlan(<?php echo $plan['id']; ?>, '<?php echo htmlspecialchars($plan['name']); ?>', '<?php echo htmlspecialchars($plan['code']); ?>', '<?php echo htmlspecialchars($plan['description'] ?? ''); ?>', <?php echo $plan['is_active']; ?>)" title="Edit">
                                                                    <i class="bi bi-pencil"></i>
                                                                </button>
                                                                <button type="button" class="table-action-btn" onclick="toggleStatus(<?php echo $plan['id']; ?>, <?php echo $plan['is_active'] ? 0 : 1; ?>)" title="<?php echo $plan['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                                    <i class="bi bi-<?php echo $plan['is_active'] ? 'toggle-on' : 'toggle-off'; ?>"></i>
                                                                </button>
                                                                <button type="button" class="table-action-btn danger" onclick="confirmDelete(<?php echo $plan['id']; ?>, '<?php echo htmlspecialchars($plan['name']); ?>')" title="Delete">
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
                                                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?>">Previous</a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                                <?php if ($i == $page): ?>
                                                    <li class="page-item active">
                                                        <span class="page-link"><?php echo $i; ?></span>
                                                    </li>
                                                <?php elseif ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                                    <li class="page-item">
                                                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?>"><?php echo $i; ?></a>
                                                    </li>
                                                <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                                    <li class="page-item disabled">
                                                        <span class="page-link">...</span>
                                                    </li>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                            
                                            <?php if ($page < $totalPages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?>">Next</a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0" id="formTitle">Add Rate Plan</h5>
                            </div>
                            <div class="card-body">
                                <form action="" method="POST" id="planForm">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="plan_id" id="plan_id" value="">
                                    
                                    <div class="mb-3">
                                        <label for="name" class="form-label required">Name</label>
                                        <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="code" class="form-label required">Code</label>
                                        <input type="text" class="form-control" id="code" name="code" required value="<?php echo htmlspecialchars($_POST['code'] ?? ''); ?>">
                                        <small class="form-text text-muted">Unique identifier for this rate plan</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                            <label class="form-check-label" for="is_active">Active</label>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary" id="submitBtn">
                                            <i class="bi bi-save me-2"></i>Save Rate Plan
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" id="cancelBtn" onclick="resetForm()" style="display: none;">
                                            <i class="bi bi-x me-2"></i>Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-3">
                    <a href="<?php echo APP_URL; ?>/modules/room-rates/index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Back to Room Rates
                    </a>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal-backdrop" id="deleteModal">
    <div class="modal">
        <div class="modal-header">
            <h5 class="modal-title">Delete Rate Plan</h5>
            <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete this rate plan?</p>
            <p class="text-muted"><strong id="deletePlanName"></strong></p>
            <p class="text-warning small">This will also delete all room rates associated with this plan.</p>
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
    <input type="hidden" name="plan_id" id="togglePlanId">
    <input type="hidden" name="status" id="toggleStatus">
</form>

<!-- Delete Form -->
<form id="deleteForm" method="POST" style="display: none;">
    <?php echo csrfField(); ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="plan_id" id="deletePlanId">
</form>

<script>
let deletePlanId = null;

function editPlan(id, name, code, description, isActive) {
    document.getElementById('plan_id').value = id;
    document.getElementById('name').value = name;
    document.getElementById('code').value = code;
    document.getElementById('description').value = description;
    document.getElementById('is_active').checked = isActive;
    document.getElementById('formTitle').textContent = 'Edit Rate Plan';
    document.getElementById('submitBtn').innerHTML = '<i class="bi bi-save me-2"></i>Update Rate Plan';
    document.getElementById('cancelBtn').style.display = 'inline-block';
}

function resetForm() {
    document.getElementById('plan_id').value = '';
    document.getElementById('name').value = '';
    document.getElementById('code').value = '';
    document.getElementById('description').value = '';
    document.getElementById('is_active').checked = true;
    document.getElementById('formTitle').textContent = 'Add Rate Plan';
    document.getElementById('submitBtn').innerHTML = '<i class="bi bi-save me-2"></i>Save Rate Plan';
    document.getElementById('cancelBtn').style.display = 'none';
}

function confirmDelete(planId, name) {
    deletePlanId = planId;
    document.getElementById('deletePlanName').textContent = name;
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
    deletePlanId = null;
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (deletePlanId) {
        document.getElementById('deletePlanId').value = deletePlanId;
        document.getElementById('deleteForm').submit();
    }
});

function toggleStatus(planId, status) {
    document.getElementById('togglePlanId').value = planId;
    document.getElementById('toggleStatus').value = status;
    document.getElementById('toggleForm').submit();
}
</script>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
