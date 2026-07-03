<?php
/**
 * Hotel & Resort Management System
 * Master Data Module - Items Index Page
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

$page_title = 'Master Data Items';
$page_description = 'Manage master data items';

// Breadcrumb items
$breadcrumb_items = [
    ['label' => 'Dashboard', 'url' => APP_URL . '/dashboard.php'],
    ['label' => 'Master Data', 'url' => '#'],
    ['label' => 'Items', 'active' => true]
];

$error = '';
$success = '';

$db = getDB();

// Get group filter
$groupFilter = (int)($_GET['group_id'] ?? 0);

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $itemId = (int)($_POST['item_id'] ?? 0);
        
        try {
            // Soft delete
            $stmt = $db->prepare("UPDATE master_items SET deleted_at = NOW() WHERE id = ?");
            $stmt->execute([$itemId]);
            
            logActivity('delete', 'master_items', "Soft deleted master item ID: {$itemId}");
            $success = 'Master item deleted successfully.';
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                error_log("Delete item error: " . $e->getMessage());
            }
            $error = 'An error occurred while deleting the item.';
        }
    }
}

// Handle status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $itemId = (int)($_POST['item_id'] ?? 0);
        $status = (int)($_POST['status'] ?? 0);
        
        try {
            $stmt = $db->prepare("UPDATE master_items SET is_active = ? WHERE id = ?");
            $stmt->execute([$status, $itemId]);
            
            logActivity('update', 'master_items', "Toggled item status ID: {$itemId}");
            $success = 'Item status updated successfully.';
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                error_log("Toggle status error: " . $e->getMessage());
            }
            $error = 'An error occurred while updating the status.';
        }
    }
}

// Get items with pagination
$page = (int)($_GET['page'] ?? 1);
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Search filter
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$where = ['i.deleted_at IS NULL'];
$params = [];

if ($groupFilter) {
    $where[] = "i.group_id = ?";
    $params[] = $groupFilter;
}

if ($search) {
    $where[] = "(i.name LIKE ? OR i.code LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($statusFilter !== '') {
    $where[] = "i.is_active = ?";
    $params[] = (int)$statusFilter;
}

$whereClause = implode(' AND ', $where);

// Get total count
$stmt = $db->prepare("SELECT COUNT(*) FROM master_items i WHERE {$whereClause}");
$stmt->execute($params);
$totalItems = $stmt->fetchColumn();
$totalPages = ceil($totalItems / $perPage);

// Get items
$stmt = $db->prepare("
    SELECT i.*, 
           g.name as group_name,
           g.slug as group_slug,
           g.icon_class as group_icon,
           g.color as group_color
    FROM master_items i
    INNER JOIN master_groups g ON i.group_id = g.id
    WHERE {$whereClause}
    ORDER BY g.display_order ASC, i.display_order ASC, i.name ASC
    LIMIT ? OFFSET ?
");
$stmt->execute(array_merge($params, [$perPage, $offset]));
$items = $stmt->fetchAll();

// Get all groups for filter
$stmt = $db->query("SELECT id, name, slug FROM master_groups WHERE deleted_at IS NULL ORDER BY display_order ASC, name ASC");
$groups = $stmt->fetchAll();
?>
<?php require_once APP_ROOT . '/includes/header.php'; ?>

<div class="main-wrapper">
    <?php require_once APP_ROOT . '/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php require_once APP_ROOT . '/includes/topbar.php'; ?>
        
        <main class="content-area">
            <div class="container-fluid">
                <div class="page-header">
                    <h1 class="page-title">Master Data Items</h1>
                    <p class="page-subtitle">Manage master data items (room types, amenities, etc.)</p>
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
                                <?php if ($groupFilter): ?>
                                    <a href="<?php echo APP_URL; ?>/modules/master-data/index.php" class="btn btn-outline-secondary me-2">
                                        <i class="bi bi-arrow-left me-2"></i>Back to Groups
                                    </a>
                                <?php endif; ?>
                                <a href="<?php echo APP_URL; ?>/modules/master-data/item-form.php<?php echo $groupFilter ? '?group_id=' . $groupFilter : ''; ?>" class="btn btn-primary">
                                    <i class="bi bi-plus-lg me-2"></i>Add Item
                                </a>
                            </div>
                            <div class="table-toolbar-right">
                                <form action="" method="GET" class="d-flex gap-2">
                                    <select name="group_id" class="form-select table-filter">
                                        <option value="">All Groups</option>
                                        <?php foreach ($groups as $grp): ?>
                                            <option value="<?php echo $grp['id']; ?>" <?php echo $groupFilter === $grp['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($grp['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="status" class="form-select table-filter">
                                        <option value="">All Status</option>
                                        <option value="1" <?php echo $statusFilter === '1' ? 'selected' : ''; ?>>Active</option>
                                        <option value="0" <?php echo $statusFilter === '0' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                    <input type="text" name="search" class="form-control table-search" placeholder="Search items..." value="<?php echo htmlspecialchars($search); ?>">
                                    <button type="submit" class="btn btn-outline-secondary">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table data-table" id="itemsTable">
                                <thead>
                                    <tr>
                                        <th>Item Name</th>
                                        <th>Code</th>
                                        <th>Group</th>
                                        <th>Icon</th>
                                        <th>Color</th>
                                        <th>Order</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($items)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-5">
                                                <div class="empty-state">
                                                    <i class="bi bi-list-ul empty-state-icon"></i>
                                                    <h5 class="empty-state-title">No items found</h5>
                                                    <p class="empty-state-description">Get started by adding your first master data item.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($items as $item): ?>
                                            <tr data-aos="fade-up">
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if ($item['icon_class']): ?>
                                                            <i class="<?php echo htmlspecialchars($item['icon_class']); ?> me-2" style="color: <?php echo htmlspecialchars($item['color'] ?? $item['group_color'] ?? '#667eea'); ?>;"></i>
                                                        <?php elseif ($item['group_icon']): ?>
                                                            <i class="<?php echo htmlspecialchars($item['group_icon']); ?> me-2" style="color: <?php echo htmlspecialchars($item['group_color'] ?? '#667eea'); ?>;"></i>
                                                        <?php endif; ?>
                                                        <div>
                                                            <div class="fw-bold"><?php echo htmlspecialchars($item['name']); ?></div>
                                                            <small class="text-muted"><?php echo htmlspecialchars($item['description'] ?? ''); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if ($item['code']): ?>
                                                        <code><?php echo htmlspecialchars($item['code']); ?></code>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($item['group_name']); ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($item['icon_class']): ?>
                                                        <i class="<?php echo htmlspecialchars($item['icon_class']); ?>"></i>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($item['color']): ?>
                                                        <span class="color-badge" style="background-color: <?php echo htmlspecialchars($item['color']); ?>;"></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo $item['display_order']; ?>
                                                </td>
                                                <td>
                                                    <?php if ($item['is_active']): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="table-actions">
                                                        <a href="<?php echo APP_URL; ?>/modules/master-data/item-form.php?id=<?php echo $item['id']; ?>" class="table-action-btn" title="Edit">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <button type="button" class="table-action-btn" onclick="toggleStatus(<?php echo $item['id']; ?>, <?php echo $item['is_active'] ? 0 : 1; ?>)" title="<?php echo $item['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                            <i class="bi bi-<?php echo $item['is_active'] ? 'toggle-on' : 'toggle-off'; ?>"></i>
                                                        </button>
                                                        <button type="button" class="table-action-btn danger" onclick="confirmDelete(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>')" title="Delete">
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
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $statusFilter; ?>&group_id=<?php echo $groupFilter; ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <?php if ($i == $page): ?>
                                            <li class="page-item active">
                                                <span class="page-link"><?php echo $i; ?></span>
                                            </li>
                                        <?php else: ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $statusFilter; ?>&group_id=<?php echo $groupFilter; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $statusFilter; ?>&group_id=<?php echo $groupFilter; ?>">Next</a>
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

<style>
.color-badge {
    display: inline-block;
    width: 24px;
    height: 24px;
    border-radius: 4px;
    border: 1px solid #dee2e6;
}
</style>

<!-- Delete Confirmation Modal -->
<div class="modal-backdrop" id="deleteModal">
    <div class="modal">
        <div class="modal-header">
            <h5 class="modal-title">Delete Master Item</h5>
            <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete <strong id="deleteItemName"></strong>? This action cannot be undone.</p>
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
    <input type="hidden" name="item_id" id="statusItemId">
    <input type="hidden" name="status" id="statusValue">
</form>

<!-- Delete Form -->
<form id="deleteForm" method="POST" style="display: none;">
    <?php echo csrfField(); ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="item_id" id="deleteItemId">
</form>

<script>
function confirmDelete(itemId, itemName) {
    document.getElementById('deleteItemName').textContent = itemName;
    document.getElementById('deleteItemId').value = itemId;
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    document.getElementById('deleteForm').submit();
});

function toggleStatus(itemId, status) {
    document.getElementById('statusItemId').value = itemId;
    document.getElementById('statusValue').value = status;
    document.getElementById('statusForm').submit();
}
</script>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
