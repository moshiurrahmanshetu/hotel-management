<?php
/**
 * Hotel & Resort Management System
 * Roles Module - Index Page
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(dirname(dirname(__FILE__))));
}

// Load bootstrap (includes config, installer check, etc.)
require_once APP_ROOT . '/includes/bootstrap.php';
require_once APP_ROOT . '/includes/auth.php';

// Require authentication and permission
requireAuth();
requirePermission('roles.view');

$page_title = 'Roles';
$page_description = 'Manage system roles and permissions';

// Breadcrumb items
$breadcrumb_items = [
    ['label' => 'Dashboard', 'url' => APP_URL . '/dashboard.php'],
    ['label' => 'Roles', 'active' => true]
];

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $roleId = (int)($_POST['role_id'] ?? 0);
        
        // Prevent deleting super admin role
        $db = getDB();
        $stmt = $db->prepare("SELECT slug FROM roles WHERE id = ?");
        $stmt->execute([$roleId]);
        $role = $stmt->fetch();
        
        if (!$role) {
            $error = 'Role not found.';
        } elseif ($role['slug'] === 'super_admin') {
            $error = 'Cannot delete Super Admin role.';
        } else {
            try {
                $db->beginTransaction();
                
                // Delete role permissions
                $stmt = $db->prepare("DELETE FROM role_permissions WHERE role_id = ?");
                $stmt->execute([$roleId]);
                
                // Delete user roles
                $stmt = $db->prepare("DELETE FROM user_roles WHERE role_id = ?");
                $stmt->execute([$roleId]);
                
                // Delete role
                $stmt = $db->prepare("DELETE FROM roles WHERE id = ?");
                $stmt->execute([$roleId]);
                
                $db->commit();
                
                logActivity('delete', 'roles', "Deleted role ID: {$roleId}");
                $success = 'Role deleted successfully.';
            } catch (PDOException $e) {
                $db->rollBack();
                if (DEBUG_MODE) {
                    error_log("Delete role error: " . $e->getMessage());
                }
                $error = 'An error occurred while deleting the role.';
            }
        }
    }
}

// Get roles with user count
$db = getDB();
$stmt = $db->query("
    SELECT r.*, 
           COUNT(DISTINCT ur.user_id) as user_count,
           COUNT(DISTINCT rp.permission_id) as permission_count
    FROM roles r
    LEFT JOIN user_roles ur ON r.id = ur.role_id
    LEFT JOIN role_permissions rp ON r.id = rp.role_id
    GROUP BY r.id
    ORDER BY r.name ASC
");
$roles = $stmt->fetchAll();
?>
<?php require_once APP_ROOT . '/includes/header.php'; ?>

<div class="main-wrapper">
    <?php require_once APP_ROOT . '/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php require_once APP_ROOT . '/includes/topbar.php'; ?>
        
        <main class="content-area">
            <div class="container-fluid">
                <div class="page-header">
                    <h1 class="page-title">Roles</h1>
                    <p class="page-subtitle">Manage system roles and their permissions</p>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="bi bi-check-circle me-2"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <div class="table-toolbar">
                            <div class="table-toolbar-left">
                                <?php if (hasPermission('roles.create')): ?>
                                    <a href="<?php echo APP_URL; ?>/modules/roles/create.php" class="btn btn-primary">
                                        <i class="bi bi-plus-lg me-2"></i>Add Role
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table data-table" id="rolesTable">
                                <thead>
                                    <tr>
                                        <th>Role Name</th>
                                        <th>Slug</th>
                                        <th>Users</th>
                                        <th>Permissions</th>
                                        <th>Default</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($roles)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5">
                                                <div class="empty-state">
                                                    <i class="bi bi-shield-lock empty-state-icon"></i>
                                                    <h5 class="empty-state-title">No roles found</h5>
                                                    <p class="empty-state-description">Get started by adding your first role.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($roles as $role): ?>
                                            <tr data-aos="fade-up">
                                                <td>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($role['name']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($role['description'] ?? ''); ?></small>
                                                </td>
                                                <td>
                                                    <code><?php echo htmlspecialchars($role['slug']); ?></code>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $role['user_count']; ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary"><?php echo $role['permission_count']; ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($role['is_default']): ?>
                                                        <span class="badge bg-success">Yes</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">No</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="table-actions">
                                                        <?php if (hasPermission('roles.edit')): ?>
                                                            <a href="<?php echo APP_URL; ?>/modules/roles/edit.php?id=<?php echo $role['id']; ?>" class="table-action-btn" title="Edit">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        
                                                        <?php if (hasPermission('roles.permissions')): ?>
                                                            <a href="<?php echo APP_URL; ?>/modules/roles/permissions.php?id=<?php echo $role['id']; ?>" class="table-action-btn" title="Manage Permissions">
                                                                <i class="bi bi-key"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        
                                                        <?php if (hasPermission('roles.delete') && $role['slug'] !== 'super_admin'): ?>
                                                            <button type="button" class="table-action-btn danger" onclick="confirmDelete(<?php echo $role['id']; ?>, '<?php echo htmlspecialchars($role['name']); ?>')" title="Delete">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
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
            <h5 class="modal-title">Delete Role</h5>
            <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete <strong id="deleteRoleName"></strong>? This action cannot be undone.</p>
            <p class="text-muted small">Users assigned to this role will lose these permissions.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
            <button class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
        </div>
    </div>
</div>

<!-- Delete Form -->
<form id="deleteForm" method="POST" style="display: none;">
    <?php echo csrfField(); ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="role_id" id="deleteRoleId">
</form>

<script>
function confirmDelete(roleId, roleName) {
    document.getElementById('deleteRoleName').textContent = roleName;
    document.getElementById('deleteRoleId').value = roleId;
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    document.getElementById('deleteForm').submit();
});
</script>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
