<?php
/**
 * Hotel & Resort Management System
 * Properties Module - Index Page
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

$page_title = 'Properties';
$page_description = 'Manage hotel properties';

// Breadcrumb items
$breadcrumb_items = [
    ['label' => 'Dashboard', 'url' => APP_URL . '/dashboard.php'],
    ['label' => 'Hotel Structure', 'url' => '#'],
    ['label' => 'Properties', 'active' => true]
];

$error = '';
$success = '';

$db = getDB();

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $propertyId = (int)($_POST['property_id'] ?? 0);
        
        // Check if property has buildings
        $stmt = $db->prepare("SELECT COUNT(*) FROM buildings WHERE property_id = ? AND deleted_at IS NULL");
        $stmt->execute([$propertyId]);
        $buildingCount = $stmt->fetchColumn();
        
        if ($buildingCount > 0) {
            $error = 'Cannot delete property. It has ' . $buildingCount . ' building(s) assigned to it.';
        } else {
            try {
                // Soft delete
                $stmt = $db->prepare("UPDATE properties SET deleted_at = NOW() WHERE id = ?");
                $stmt->execute([$propertyId]);
                
                logActivity('delete', 'properties', "Soft deleted property ID: {$propertyId}");
                $success = 'Property deleted successfully.';
            } catch (PDOException $e) {
                if (DEBUG_MODE) {
                    error_log("Delete property error: " . $e->getMessage());
                }
                $error = 'An error occurred while deleting the property.';
            }
        }
    }
}

// Handle status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $propertyId = (int)($_POST['property_id'] ?? 0);
        $status = (int)($_POST['status'] ?? 0);
        
        try {
            $stmt = $db->prepare("UPDATE properties SET is_active = ? WHERE id = ?");
            $stmt->execute([$status, $propertyId]);
            
            logActivity('update', 'properties', "Toggled property status ID: {$propertyId}");
            $success = 'Property status updated successfully.';
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                error_log("Toggle status error: " . $e->getMessage());
            }
            $error = 'An error occurred while updating the status.';
        }
    }
}

// Get properties with pagination
$page = (int)($_GET['page'] ?? 1);
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Search filter
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$where = ['deleted_at IS NULL'];
$params = [];

if ($search) {
    $where[] = "(name LIKE ? OR code LIKE ? OR city LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($statusFilter !== '') {
    $where[] = "is_active = ?";
    $params[] = (int)$statusFilter;
}

$whereClause = implode(' AND ', $where);

// Get total count
$stmt = $db->prepare("SELECT COUNT(*) FROM properties WHERE {$whereClause}");
$stmt->execute($params);
$totalProperties = $stmt->fetchColumn();
$totalPages = ceil($totalProperties / $perPage);

// Get properties
$stmt = $db->prepare("
    SELECT p.*, 
           c.name as country_name,
           COUNT(DISTINCT b.id) as building_count
    FROM properties p
    LEFT JOIN countries c ON p.country_id = c.id
    LEFT JOIN buildings b ON p.id = b.property_id AND b.deleted_at IS NULL
    WHERE {$whereClause}
    GROUP BY p.id
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute(array_merge($params, [$perPage, $offset]));
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
                    <h1 class="page-title">Properties</h1>
                    <p class="page-subtitle">Manage hotel properties</p>
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
                                <a href="<?php echo APP_URL; ?>/modules/properties/form.php" class="btn btn-primary">
                                    <i class="bi bi-plus-lg me-2"></i>Add Property
                                </a>
                            </div>
                            <div class="table-toolbar-right">
                                <form action="" method="GET" class="d-flex gap-2">
                                    <select name="status" class="form-select table-filter">
                                        <option value="">All Status</option>
                                        <option value="1" <?php echo $statusFilter === '1' ? 'selected' : ''; ?>>Active</option>
                                        <option value="0" <?php echo $statusFilter === '0' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                    <input type="text" name="search" class="form-control table-search" placeholder="Search properties..." value="<?php echo htmlspecialchars($search); ?>">
                                    <button type="submit" class="btn btn-outline-secondary">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table data-table" id="propertiesTable">
                                <thead>
                                    <tr>
                                        <th>Property</th>
                                        <th>Code</th>
                                        <th>Location</th>
                                        <th>Buildings</th>
                                        <th>Rooms</th>
                                        <th>Stars</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($properties)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-5">
                                                <div class="empty-state">
                                                    <i class="bi bi-building empty-state-icon"></i>
                                                    <h5 class="empty-state-title">No properties found</h5>
                                                    <p class="empty-state-description">Get started by adding your first property.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($properties as $property): ?>
                                            <tr data-aos="fade-up">
                                                <td>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($property['name']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($property['description'] ?? ''); ?></small>
                                                </td>
                                                <td>
                                                    <code><?php echo htmlspecialchars($property['code']); ?></code>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($property['city'] ?? '-'); ?>
                                                    <?php if ($property['country_name']): ?>
                                                        <small class="text-muted">(<?php echo htmlspecialchars($property['country_name']); ?>)</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $property['building_count']; ?></span>
                                                </td>
                                                <td>
                                                    <?php echo $property['total_rooms'] ?: '-'; ?>
                                                </td>
                                                <td>
                                                    <?php if ($property['star_rating']): ?>
                                                        <?php for ($i = 1; $i <= $property['star_rating']; $i++): ?>
                                                            <i class="bi bi-star-fill text-warning"></i>
                                                        <?php endfor; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($property['is_active']): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="table-actions">
                                                        <a href="<?php echo APP_URL; ?>/modules/properties/form.php?id=<?php echo $property['id']; ?>" class="table-action-btn" title="Edit">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <a href="<?php echo APP_URL; ?>/modules/buildings/index.php?property_id=<?php echo $property['id']; ?>" class="table-action-btn" title="Buildings">
                                                            <i class="bi bi-building"></i>
                                                        </a>
                                                        <button type="button" class="table-action-btn" onclick="toggleStatus(<?php echo $property['id']; ?>, <?php echo $property['is_active'] ? 0 : 1; ?>)" title="<?php echo $property['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                            <i class="bi bi-<?php echo $property['is_active'] ? 'toggle-on' : 'toggle-off'; ?>"></i>
                                                        </button>
                                                        <button type="button" class="table-action-btn danger" onclick="confirmDelete(<?php echo $property['id']; ?>, '<?php echo htmlspecialchars($property['name']); ?>')" title="Delete">
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
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $statusFilter; ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <?php if ($i == $page): ?>
                                            <li class="page-item active">
                                                <span class="page-link"><?php echo $i; ?></span>
                                            </li>
                                        <?php else: ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $statusFilter; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $statusFilter; ?>">Next</a>
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
            <h5 class="modal-title">Delete Property</h5>
            <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete <strong id="deletePropertyName"></strong>? This action cannot be undone.</p>
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
    <input type="hidden" name="property_id" id="statusPropertyId">
    <input type="hidden" name="status" id="statusValue">
</form>

<!-- Delete Form -->
<form id="deleteForm" method="POST" style="display: none;">
    <?php echo csrfField(); ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="property_id" id="deletePropertyId">
</form>

<script>
function confirmDelete(propertyId, propertyName) {
    document.getElementById('deletePropertyName').textContent = propertyName;
    document.getElementById('deletePropertyId').value = propertyId;
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    document.getElementById('deleteForm').submit();
});

function toggleStatus(propertyId, status) {
    document.getElementById('statusPropertyId').value = propertyId;
    document.getElementById('statusValue').value = status;
    document.getElementById('statusForm').submit();
}
</script>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
