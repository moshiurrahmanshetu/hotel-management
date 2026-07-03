<?php
/**
 * Hotel & Resort Management System
 * Floors Module - Index Page
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(dirname(dirname(__FILE__))));
}

// Load configuration and authentication
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/includes/auth.php';

// Require authentication
requireAuth();

$page_title = 'Floors';
$page_description = 'Manage building floors';

// Breadcrumb items
$breadcrumb_items = [
    ['label' => 'Dashboard', 'url' => APP_URL . '/dashboard.php'],
    ['label' => 'Hotel Structure', 'url' => '#'],
    ['label' => 'Floors', 'active' => true]
];

$error = '';
$success = '';

$db = getDB();

// Get building filter
$buildingFilter = (int)($_GET['building_id'] ?? 0);

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $floorId = (int)($_POST['floor_id'] ?? 0);
        
        // Check if floor has rooms (when rooms module is implemented)
        // For now, we'll allow deletion
        
        try {
            // Soft delete
            $stmt = $db->prepare("UPDATE floors SET deleted_at = NOW() WHERE id = ?");
            $stmt->execute([$floorId]);
            
            logActivity('delete', 'floors', "Soft deleted floor ID: {$floorId}");
            $success = 'Floor deleted successfully.';
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                error_log("Delete floor error: " . $e->getMessage());
            }
            $error = 'An error occurred while deleting the floor.';
        }
    }
}

// Handle status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $floorId = (int)($_POST['floor_id'] ?? 0);
        $status = (int)($_POST['status'] ?? 0);
        
        try {
            $stmt = $db->prepare("UPDATE floors SET is_active = ? WHERE id = ?");
            $stmt->execute([$status, $floorId]);
            
            logActivity('update', 'floors', "Toggled floor status ID: {$floorId}");
            $success = 'Floor status updated successfully.';
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                error_log("Toggle status error: " . $e->getMessage());
            }
            $error = 'An error occurred while updating the status.';
        }
    }
}

// Get floors with pagination
$page = (int)($_GET['page'] ?? 1);
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Search filter
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$where = ['f.deleted_at IS NULL'];
$params = [];

if ($buildingFilter) {
    $where[] = "f.building_id = ?";
    $params[] = $buildingFilter;
}

if ($search) {
    $where[] = "(f.name LIKE ? OR f.floor_number LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($statusFilter !== '') {
    $where[] = "f.is_active = ?";
    $params[] = (int)$statusFilter;
}

$whereClause = implode(' AND ', $where);

// Get total count
$stmt = $db->prepare("SELECT COUNT(*) FROM floors f WHERE {$whereClause}");
$stmt->execute($params);
$totalFloors = $stmt->fetchColumn();
$totalPages = ceil($totalFloors / $perPage);

// Get floors
$stmt = $db->prepare("
    SELECT f.*, 
           b.name as building_name,
           b.code as building_code,
           p.name as property_name,
           p.code as property_code
    FROM floors f
    INNER JOIN buildings b ON f.building_id = b.id
    INNER JOIN properties p ON b.property_id = p.id
    WHERE {$whereClause}
    ORDER BY f.floor_number ASC, f.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute(array_merge($params, [$perPage, $offset]));
$floors = $stmt->fetchAll();

// Get all buildings for filter
$stmt = $db->query("
    SELECT b.id, b.name, b.code, p.name as property_name 
    FROM buildings b 
    INNER JOIN properties p ON b.property_id = p.id 
    WHERE b.deleted_at IS NULL 
    ORDER BY p.name, b.name ASC
");
$buildings = $stmt->fetchAll();
?>
<?php require_once APP_ROOT . '/includes/header.php'; ?>

<div class="main-wrapper">
    <?php require_once APP_ROOT . '/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php require_once APP_ROOT . '/includes/topbar.php'; ?>
        
        <main class="content-area">
            <div class="container-fluid">
                <div class="page-header">
                    <h1 class="page-title">Floors</h1>
                    <p class="page-subtitle">Manage building floors</p>
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
                        <div class="table-toolbar">
                            <div class="table-toolbar-left">
                                <?php if ($buildingFilter): ?>
                                    <a href="<?php echo APP_URL; ?>/modules/buildings/index.php" class="btn btn-outline-secondary me-2">
                                        <i class="bi bi-arrow-left me-2"></i>Back to Buildings
                                    </a>
                                <?php endif; ?>
                                <a href="<?php echo APP_URL; ?>/modules/floors/form.php<?php echo $buildingFilter ? '?building_id=' . $buildingFilter : ''; ?>" class="btn btn-primary">
                                    <i class="bi bi-plus-lg me-2"></i>Add Floor
                                </a>
                            </div>
                            <div class="table-toolbar-right">
                                <form action="" method="GET" class="d-flex gap-2">
                                    <select name="building_id" class="form-select table-filter">
                                        <option value="">All Buildings</option>
                                        <?php foreach ($buildings as $bld): ?>
                                            <option value="<?php echo $bld['id']; ?>" <?php echo $buildingFilter === $bld['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($bld['property_name'] . ' - ' . $bld['name']); ?> (<?php echo htmlspecialchars($bld['code']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="status" class="form-select table-filter">
                                        <option value="">All Status</option>
                                        <option value="1" <?php echo $statusFilter === '1' ? 'selected' : ''; ?>>Active</option>
                                        <option value="0" <?php echo $statusFilter === '0' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                    <input type="text" name="search" class="form-control table-search" placeholder="Search floors..." value="<?php echo htmlspecialchars($search); ?>">
                                    <button type="submit" class="btn btn-outline-secondary">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table data-table" id="floorsTable">
                                <thead>
                                    <tr>
                                        <th>Floor</th>
                                        <th>Floor Number</th>
                                        <th>Building</th>
                                        <th>Property</th>
                                        <th>Rooms</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($floors)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-5">
                                                <div class="empty-state">
                                                    <i class="bi bi-layers empty-state-icon"></i>
                                                    <h5 class="empty-state-title">No floors found</h5>
                                                    <p class="empty-state-description">Get started by adding your first floor.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($floors as $floor): ?>
                                            <tr data-aos="fade-up">
                                                <td>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($floor['name']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($floor['description'] ?? ''); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary"><?php echo $floor['floor_number']; ?></span>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($floor['building_name']); ?>
                                                    <small class="text-muted">(<?php echo htmlspecialchars($floor['building_code']); ?>)</small>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($floor['property_name']); ?>
                                                    <small class="text-muted">(<?php echo htmlspecialchars($floor['property_code']); ?>)</small>
                                                </td>
                                                <td>
                                                    <?php echo $floor['total_rooms'] ?: '-'; ?>
                                                </td>
                                                <td>
                                                    <?php if ($floor['is_active']): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="table-actions">
                                                        <a href="<?php echo APP_URL; ?>/modules/floors/form.php?id=<?php echo $floor['id']; ?>" class="table-action-btn" title="Edit">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <button type="button" class="table-action-btn" onclick="toggleStatus(<?php echo $floor['id']; ?>, <?php echo $floor['is_active'] ? 0 : 1; ?>)" title="<?php echo $floor['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                            <i class="bi bi-<?php echo $floor['is_active'] ? 'toggle-on' : 'toggle-off'; ?>"></i>
                                                        </button>
                                                        <button type="button" class="table-action-btn danger" onclick="confirmDelete(<?php echo $floor['id']; ?>, '<?php echo htmlspecialchars($floor['name']); ?>')" title="Delete">
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
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <nav class="mt-4">
                                <ul class="pagination">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $statusFilter; ?>&building_id=<?php echo $buildingFilter; ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <?php if ($i == $page): ?>
                                            <li class="page-item active">
                                                <span class="page-link"><?php echo $i; ?></span>
                                            </li>
                                        <?php else: ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $statusFilter; ?>&building_id=<?php echo $buildingFilter; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $statusFilter; ?>&building_id=<?php echo $buildingFilter; ?>">Next</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
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
            <h5 class="modal-title">Delete Floor</h5>
            <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete <strong id="deleteFloorName"></strong>? This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
            <button class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
        </div>
    </div>
</div>

<!-- Status Toggle Form -->
<form id="statusForm" method="POST" style="display: none;">
    <?php echo csrfField(); ?>
    <input type="hidden" name="action" value="toggle_status">
    <input type="hidden" name="floor_id" id="statusFloorId">
    <input type="hidden" name="status" id="statusValue">
</form>

<!-- Delete Form -->
<form id="deleteForm" method="POST" style="display: none;">
    <?php echo csrfField(); ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="floor_id" id="deleteFloorId">
</form>

<script>
function confirmDelete(floorId, floorName) {
    document.getElementById('deleteFloorName').textContent = floorName;
    document.getElementById('deleteFloorId').value = floorId;
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    document.getElementById('deleteForm').submit();
});

function toggleStatus(floorId, status) {
    document.getElementById('statusFloorId').value = floorId;
    document.getElementById('statusValue').value = status;
    document.getElementById('statusForm').submit();
}
</script>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
