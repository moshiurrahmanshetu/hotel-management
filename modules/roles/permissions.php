<?php
/**
 * Hotel & Resort Management System
 * Roles Module - Manage Permissions Page
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
requirePermission('roles.permissions');

$page_title = 'Manage Permissions';
$page_description = 'Manage role permissions';

// Breadcrumb items
$breadcrumb_items = [
    ['label' => 'Dashboard', 'url' => APP_URL . '/dashboard.php'],
    ['label' => 'Roles', 'url' => APP_URL . '/modules/roles/index.php'],
    ['label' => 'Manage Permissions', 'active' => true]
];

$error = '';
$success = '';

// Get role ID
$roleId = (int)($_GET['id'] ?? 0);

if (!$roleId) {
    redirect(APP_URL . '/modules/roles/index.php');
}

// Get role data
$db = getDB();
$stmt = $db->prepare("SELECT * FROM roles WHERE id = ?");
$stmt->execute([$roleId]);
$role = $stmt->fetch();

if (!$role) {
    redirect(APP_URL . '/modules/roles/index.php');
}

// Get all permissions grouped by module
$stmt = $db->query("SELECT * FROM permissions ORDER BY module ASC, name ASC");
$allPermissions = $stmt->fetchAll();

// Group permissions by module
$permissionsByModule = [];
foreach ($allPermissions as $permission) {
    $permissionsByModule[$permission['module']][] = $permission;
}

// Get role's current permissions
$stmt = $db->prepare("SELECT permission_id FROM role_permissions WHERE role_id = ?");
$stmt->execute([$roleId]);
$rolePermissionIds = array_column($stmt->fetchAll(), 'permission_id');

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $selectedPermissions = $_POST['permissions'] ?? [];
        
        try {
            $db->beginTransaction();
            
            // Delete all existing permissions for this role
            $stmt = $db->prepare("DELETE FROM role_permissions WHERE role_id = ?");
            $stmt->execute([$roleId]);
            
            // Add new permissions
            foreach ($selectedPermissions as $permissionId) {
                $stmt = $db->prepare("INSERT INTO role_permissions (role_id, permission_id, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$roleId, (int)$permissionId]);
            }
            
            $db->commit();
            
            logActivity('update', 'roles', "Updated permissions for role: {$role['name']}");
            $success = 'Permissions updated successfully.';
            
            // Refresh role permissions
            $stmt = $db->prepare("SELECT permission_id FROM role_permissions WHERE role_id = ?");
            $stmt->execute([$roleId]);
            $rolePermissionIds = array_column($stmt->fetchAll(), 'permission_id');
            
        } catch (PDOException $e) {
            $db->rollBack();
            if (DEBUG_MODE) {
                error_log("Update permissions error: " . $e->getMessage());
            }
            $error = 'An error occurred while updating permissions.';
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
                    <h1 class="page-title">Manage Permissions</h1>
                    <p class="page-subtitle">Assign permissions to role: <strong><?php echo htmlspecialchars($role['name']); ?></strong></p>
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
                        <form action="" method="POST" id="permissionsForm">
                            <?php echo csrfField(); ?>
                            
                            <?php if (empty($permissionsByModule)): ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    No permissions found. Please create permissions first.
                                </div>
                            <?php else: ?>
                                <?php foreach ($permissionsByModule as $module => $permissions): ?>
                                    <div class="permission-module mb-4">
                                        <h6 class="permission-module-title">
                                            <i class="bi bi-folder me-2"></i>
                                            <?php echo ucfirst($module); ?>
                                        </h6>
                                        <div class="row">
                                            <?php foreach ($permissions as $permission): ?>
                                                <div class="col-md-4 col-lg-3 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="<?php echo $permission['id']; ?>" id="perm_<?php echo $permission['id']; ?>" <?php echo in_array($permission['id'], $rolePermissionIds) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="perm_<?php echo $permission['id']; ?>">
                                                            <?php echo htmlspecialchars($permission['name']); ?>
                                                        </label>
                                                    </div>
                                                    <small class="form-text text-muted d-block ms-4"><?php echo htmlspecialchars($permission['slug']); ?></small>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <div class="d-flex gap-2 mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save me-2"></i>Save Permissions
                                    </button>
                                    <a href="<?php echo APP_URL; ?>/modules/roles/index.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left me-2"></i>Back to Roles
                                    </a>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
.permission-module {
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.permission-module-title {
    color: #667eea;
    margin-bottom: 15px;
    font-weight: 600;
}
</style>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
