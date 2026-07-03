<?php
/**
 * Hotel & Resort Management System
 * Buildings Module - Index Page
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

$page_title = 'Buildings';
$page_description = 'Manage hotel buildings';

// Breadcrumb items
$breadcrumb_items = [
    ['label' => 'Dashboard', 'url' => APP_URL . '/dashboard.php'],
    ['label' => 'Hotel Structure', 'url' => '#'],
    ['label' => 'Buildings', 'active' => true]
];

$error = '';
$success = '';

$db = getDB();

// Get property filter
$propertyFilter = (int)($_GET['property_id'] ?? 0);

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $buildingId = (int)($_POST['building_id'] ?? 0);
        
        // Check if building has floors
        $stmt = $db->prepare("SELECT COUNT(*) FROM floors WHERE building_id = ? AND deleted_at IS NULL");
        $stmt->execute([$buildingId]);
        $floorCount = $stmt->fetchColumn();
        
        if ($floorCount > 0) {
            $error = 'Cannot delete building. It has ' . $floorCount . ' floor(s) assigned to it.';
        } else {
            try {
                // Soft delete
                $stmt = $db->prepare("UPDATE buildings SET deleted_at = NOW() WHERE id = ?");
                $stmt->execute([$buildingId]);
                
                logActivity('delete', 'buildings', "Soft deleted building ID: {$buildingId}");
                $success = 'Building deleted successfully.';
            } catch (PDOException $e) {
                if (DEBUG_MODE) {
                    error_log("Delete building error: " . $e->getMessage());
                }
                $error = 'An error occurred while deleting the building.';
            }
        }
    }
}

// Handle status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $buildingId = (int)($_POST['building_id'] ?? 0);
        $status = (int)($_POST['status'] ?? 0);
        
        try {
            $stmt = $db->prepare("UPDATE buildings SET is_active = ? WHERE id = ?");
            $stmt->execute([$status, $buildingId]);
            
            logActivity('update', 'buildings', "Toggled building status ID: {$buildingId}");
            $success = 'Building status updated successfully.';
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                error_log("Toggle status error: " . $e->getMessage());
            }
            $error = 'An error occurred while updating the status.';
        }
    }
}

// Get buildings with pagination
$page = (int)($_GET['page'] ?? 1);
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Search filter
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$where = ['b.deleted_at IS NULL'];
$params = [];

if ($propertyFilter) {
    $where[] = "b.property_id = ?";
    $params[] = $propertyFilter;
}

if ($search) {
    $where[] = "(b.name LIKE ? OR b.code LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($statusFilter !== '') {
    $where[] = "b.is_active = ?";
    $params[] = (int)$statusFilter;
}

$whereClause = implode(' AND ', $where);

// Get total count
$stmt = $db->prepare("SELECT COUNT(*) FROM buildings b WHERE {$whereClause}");
$stmt->execute($params);
$totalBuildings = $stmt->fetchColumn();
$totalPages = ceil($totalBuildings / $perPage);

// Get buildings
$stmt = $db->prepare("
    SELECT b.*, 
           p.name as property_name,
           p.code as property_code,
           COUNT(DISTINCT f.id) as floor_count
    FROM buildings b
    INNER JOIN properties p ON b.property_id = p.id
    LEFT JOIN floors f ON b.id = f.building_id AND f.deleted_at IS NULL
    WHERE {$whereClause}
    GROUP BY b.id
    ORDER BY b.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute(array_merge($params, [$perPage, $offset]));
$buildings = $stmt->fetchAll();

// Get all properties for filter
$stmt = $db->query("SELECT id, name, code FROM properties WHERE deleted_at IS NULL ORDER BY name ASC");
$properties = $stmt->fetchAll();
?>
<?php require_once APP_ROOT . '/includes/header.php'; ?>

<div class="main-wrapper">
    <?php require_once APP_ROOT . '/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php require_once APP_ROOT . '/includes/topbar.php'; ?>
        
        <main class="content-area">
            <div class="container-fluid">
                <div class="page-header">
                    <h1 class="page-title">Buildings</h1>
                    <p class="page-subtitle">Manage hotel buildings</p>
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
                                <?php if ($propertyFilter): ?>
                                    <a href="<?php echo APP_URL; ?>/modules/properties/index.php" class="btn btn-outline-secondary me-2">
                                        <i class="bi bi-arrow-left me-2"></i>Back to Properties
                                    </a>
                                <?php endif; ?>
                                <a href="<?php echo APP_URL; ?>/modules/buildings/form.php<?php echo $propertyFilter ? '?property_id=' . $propertyFilter : ''; ?>" class="btn btn-primary">
                                    <i class="bi bi-plus-lg me-2"></i>Add Building
                                </a>
                            </div>
                            <div class="table-toolbar-right">
                                <form action="" method="GET" class="d-flex gap-2">
                                    <select name="property_id" class="form-select table-filter">
                                        <option value="">All Properties</option>
                                        <?php foreach ($properties as $prop): ?>
                                            <option value="<?php echo $prop['id']; ?>" <?php echo $propertyFilter === $prop['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($prop['name']); ?> (<?php echo htmlspecialchars($prop['code']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="status" class="form-select table-filter">
                                        <option value="">All Status</option>
                                        <option value="1" <?php echo $statusFilter === '1' ? 'selected' : ''; ?>>Active</option>
                                        <option value="0" <?php echo $statusFilter === '0' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                    <input type="text" name="search" class="form-control table-search" placeholder="Search buildings..." value="<?php echo htmlspecialchars($search); ?>">
                                    <button type="submit" class="btn btn-outline-secondary">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table data-table" id="buildingsTable">
                                <thead>
                                    <tr>
                                        <th>Building</th>
                                        <th>Code</th>
                                        <th>Property</th>
                                        <th>Floors</th>
                                        <th>Rooms</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($buildings)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-5">
                                                <div class="empty-state">
                                                    <i class="bi bi-building empty-state-icon"></i>
                                                    <h5 class="empty-state-title">No buildings found</h5>
                                                    <p class="empty-state-description">Get started by adding your first building.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($buildings as $building): ?>
                                            <tr data-aos="fade-up">
                                                <td>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($building['name']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($building['description'] ?? ''); ?></small>
                                                </td>
                                                <td>
                                                    <code><?php echo htmlspecialchars($building['code']); ?></code>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($building['property_name']); ?>
                                                    <small class="text-muted">(<?php echo htmlspecialchars($building['property_code']); ?>)</small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $building['floor_count']; ?></span>
                                                </td>
                                                <td>
                                                    <?php echo $building['total_rooms'] ?: '-'; ?>
                                                </td>
                                                <td>
                                                    <?php if ($building['is_active']): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="table-actions">
                                                        <a href="<?php echo APP_URL; ?>/modules/buildings/form.php?id=<?php echo $building['id']; ?>" class="table-action-btn" title="Edit">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <a href="<?php echo APP_URL; ?>/modules/floors/index.php?building_id=<?php echo $building['id']; ?>" class="table-action-btn" title="Floors">
                                                            <i class="bi bi-layers"></i>
                                                        </a>
                                                        <button type="button" class="table-action-btn" onclick="toggleStatus(<?php echo $building['id']; ?>, <?php echo $building['is_active'] ? 0 : 1; ?>)" title="<?php echo $building['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                            <i class="bi bi-<?php echo $building['is_active'] ? 'toggle-on' : 'toggle-off'; ?>"></i>
                                                        </button>
                                                        <button type="button" class="table-action-btn danger" onclick="confirmDelete(<?php echo $building['id']; ?>, '<?php echo htmlspecialchars($building['name']); ?>')" title="Delete">
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
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $statusFilter; ?>&property_id=<?php echo $propertyFilter; ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <?php if ($i == $page): ?>
                                            <li class="page-item active">
                                                <span class="page-link"><?php echo $i; ?></span>
                                            </li>
                                        <?php else: ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $statusFilter; ?>&property_id=<?php echo $propertyFilter; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $statusFilter; ?>&property_id=<?php echo $propertyFilter; ?>">Next</a>
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
            <h5 class="modal-title">Delete Building</h5>
            <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete <strong id="deleteBuildingName"></strong>? This action cannot be undone.</p>
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
    <input type="hidden" name="building_id" id="statusBuildingId">
    <input type="hidden" name="status" id="statusValue">
</form>

<!-- Delete Form -->
<form id="deleteForm" method="POST" style="display: none;">
    <?php echo csrfField(); ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="building_id" id="deleteBuildingId">
</form>

<script>
function confirmDelete(buildingId, buildingName) {
    document.getElementById('deleteBuildingName').textContent = buildingName;
    document.getElementById('deleteBuildingId').value = buildingId;
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    document.getElementById('deleteForm').submit();
});

function toggleStatus(buildingId, status) {
    document.getElementById('statusBuildingId').value = buildingId;
    document.getElementById('statusValue').value = status;
    document.getElementById('statusForm').submit();
}
</script>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
