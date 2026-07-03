<?php
/**
 * Hotel & Resort Management System
 * Master Data Module - Groups Index Page
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

$page_title = 'Master Data Groups';
$page_description = 'Manage master data groups';

// Breadcrumb items
$breadcrumb_items = [
    ['label' => 'Dashboard', 'url' => APP_URL . '/dashboard.php'],
    ['label' => 'Master Data', 'url' => '#'],
    ['label' => 'Groups', 'active' => true]
];

$error = '';
$success = '';

$db = getDB();

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $groupId = (int)($_POST['group_id'] ?? 0);
        
        // Check if group has items
        $stmt = $db->prepare("SELECT COUNT(*) FROM master_items WHERE group_id = ? AND deleted_at IS NULL");
        $stmt->execute([$groupId]);
        $itemCount = $stmt->fetchColumn();
        
        if ($itemCount > 0) {
            $error = 'Cannot delete group. It has ' . $itemCount . ' item(s) assigned to it.';
        } else {
            try {
                // Soft delete
                $stmt = $db->prepare("UPDATE master_groups SET deleted_at = NOW() WHERE id = ?");
                $stmt->execute([$groupId]);
                
                logActivity('delete', 'master_groups', "Soft deleted master group ID: {$groupId}");
                $success = 'Master group deleted successfully.';
            } catch (PDOException $e) {
                if (DEBUG_MODE) {
                    error_log("Delete group error: " . $e->getMessage());
                }
                $error = 'An error occurred while deleting the group.';
            }
        }
    }
}

// Handle status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $groupId = (int)($_POST['group_id'] ?? 0);
        $status = (int)($_POST['status'] ?? 0);
        
        try {
            $stmt = $db->prepare("UPDATE master_groups SET is_active = ? WHERE id = ?");
            $stmt->execute([$status, $groupId]);
            
            logActivity('update', 'master_groups', "Toggled group status ID: {$groupId}");
            $success = 'Group status updated successfully.';
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                error_log("Toggle status error: " . $e->getMessage());
            }
            $error = 'An error occurred while updating the status.';
        }
    }
}

// Get groups with pagination
$page = (int)($_GET['page'] ?? 1);
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Search filter
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$where = ['deleted_at IS NULL'];
$params = [];

if ($search) {
    $where[] = "(name LIKE ? OR slug LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($statusFilter !== '') {
    $where[] = "is_active = ?";
    $params[] = (int)$statusFilter;
}

$whereClause = implode(' AND ', $where);

// Get total count
$stmt = $db->prepare("SELECT COUNT(*) FROM master_groups WHERE {$whereClause}");
$stmt->execute($params);
$totalGroups = $stmt->fetchColumn();
$totalPages = ceil($totalGroups / $perPage);

// Get groups
$stmt = $db->prepare("
    SELECT g.*, 
           COUNT(DISTINCT i.id) as item_count
    FROM master_groups g
    LEFT JOIN master_items i ON g.id = i.group_id AND i.deleted_at IS NULL
    WHERE {$whereClause}
    GROUP BY g.id
    ORDER BY g.display_order ASC, g.name ASC
    LIMIT ? OFFSET ?
");
$stmt->execute(array_merge($params, [$perPage, $offset]));
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
                    <h1 class="page-title">Master Data Groups</h1>
                    <p class="page-subtitle">Manage master data categories (Room Types, Amenities, etc.)</p>
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
                                <a href="<?php echo APP_URL; ?>/modules/master-data/form.php" class="btn btn-primary">
                                    <i class="bi bi-plus-lg me-2"></i>Add Group
                                </a>
                            </div>
                            <div class="table-toolbar-right">
                                <form action="" method="GET" class="d-flex gap-2">
                                    <select name="status" class="form-select table-filter">
                                        <option value="">All Status</option>
                                        <option value="1" <?php echo $statusFilter === '1' ? 'selected' : ''; ?>>Active</option>
                                        <option value="0" <?php echo $statusFilter === '0' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                    <input type="text" name="search" class="form-control table-search" placeholder="Search groups..." value="<?php echo htmlspecialchars($search); ?>">
                                    <button type="submit" class="btn btn-outline-secondary">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table data-table" id="groupsTable">
                                <thead>
                                    <tr>
                                        <th>Group Name</th>
                                        <th>Slug</th>
                                        <th>Items</th>
                                        <th>Icon</th>
                                        <th>Color</th>
                                        <th>Order</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($groups)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-5">
                                                <div class="empty-state">
                                                    <i class="bi bi-folder empty-state-icon"></i>
                                                    <h5 class="empty-state-title">No groups found</h5>
                                                    <p class="empty-state-description">Get started by adding your first master data group.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($groups as $group): ?>
                                            <tr data-aos="fade-up">
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if ($group['icon_class']): ?>
                                                            <i class="<?php echo htmlspecialchars($group['icon_class']); ?> me-2" style="color: <?php echo htmlspecialchars($group['color'] ?? '#667eea'); ?>;"></i>
                                                        <?php endif; ?>
                                                        <div>
                                                            <div class="fw-bold"><?php echo htmlspecialchars($group['name']); ?></div>
                                                            <small class="text-muted"><?php echo htmlspecialchars($group['description'] ?? ''); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <code><?php echo htmlspecialchars($group['slug']); ?></code>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $group['item_count']; ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($group['icon_class']): ?>
                                                        <i class="<?php echo htmlspecialchars($group['icon_class']); ?>"></i>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($group['color']): ?>
                                                        <span class="color-badge" style="background-color: <?php echo htmlspecialchars($group['color']); ?>;"></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo $group['display_order']; ?>
                                                </td>
                                                <td>
                                                    <?php if ($group['is_active']): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="table-actions">
                                                        <a href="<?php echo APP_URL; ?>/modules/master-data/items.php?group_id=<?php echo $group['id']; ?>" class="table-action-btn" title="Manage Items">
                                                            <i class="bi bi-list-ul"></i>
                                                        </a>
                                                        <a href="<?php echo APP_URL; ?>/modules/master-data/form.php?id=<?php echo $group['id']; ?>" class="table-action-btn" title="Edit">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <button type="button" class="table-action-btn" onclick="toggleStatus(<?php echo $group['id']; ?>, <?php echo $group['is_active'] ? 0 : 1; ?>)" title="<?php echo $group['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                            <i class="bi bi-<?php echo $group['is_active'] ? 'toggle-on' : 'toggle-off'; ?>"></i>
                                                        </button>
                                                        <button type="button" class="table-action-btn danger" onclick="confirmDelete(<?php echo $group['id']; ?>, '<?php echo htmlspecialchars($group['name']); ?>')" title="Delete">
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
            <h5 class="modal-title">Delete Master Group</h5>
            <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete <strong id="deleteGroupName"></strong>? This action cannot be undone.</p>
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
    <input type="hidden" name="group_id" id="statusGroupId">
    <input type="hidden" name="status" id="statusValue">
</form>

<!-- Delete Form -->
<form id="deleteForm" method="POST" style="display: none;">
    <?php echo csrfField(); ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="group_id" id="deleteGroupId">
</form>

<script>
function confirmDelete(groupId, groupName) {
    document.getElementById('deleteGroupName').textContent = groupName;
    document.getElementById('deleteGroupId').value = groupId;
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    document.getElementById('deleteForm').submit();
});

function toggleStatus(groupId, status) {
    document.getElementById('statusGroupId').value = groupId;
    document.getElementById('statusValue').value = status;
    document.getElementById('statusForm').submit();
}
</script>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
