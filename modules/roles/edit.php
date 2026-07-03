<?php
/**
 * Hotel & Resort Management System
 * Roles Module - Edit Role Page
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(dirname(dirname(__FILE__))));
}

// Load configuration and authentication
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/includes/auth.php';

// Require authentication and permission
requireAuth();
requirePermission('roles.edit');

$page_title = 'Edit Role';
$page_description = 'Edit system role';

// Breadcrumb items
$breadcrumb_items = [
    ['label' => 'Dashboard', 'url' => APP_URL . '/dashboard.php'],
    ['label' => 'Roles', 'url' => APP_URL . '/modules/roles/index.php'],
    ['label' => 'Edit Role', 'active' => true]
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

// Check if role is super admin
$isSuperAdmin = $role['slug'] === 'super_admin';

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $name = sanitizeString($_POST['name'] ?? '');
        $slug = sanitizeString($_POST['slug'] ?? '');
        $description = sanitizeString($_POST['description'] ?? '');
        $isDefault = isset($_POST['is_default']) ? 1 : 0;
        
        // Validation
        if (!$name) {
            $error = 'Please enter a role name.';
        } elseif (!$slug) {
            $error = 'Please enter a role slug.';
        } elseif (!preg_match('/^[a-z0-9_]+$/', $slug)) {
            $error = 'Slug must contain only lowercase letters, numbers, and underscores.';
        } else {
            // Check if slug already exists (excluding current role)
            $stmt = $db->prepare("SELECT id FROM roles WHERE slug = ? AND id != ?");
            $stmt->execute([$slug, $roleId]);
            if ($stmt->fetch()) {
                $error = 'Role slug already exists.';
            }
        }
        
        // Prevent changing super admin slug
        if (!$error && $isSuperAdmin && $slug !== 'super_admin') {
            $error = 'Cannot change Super Admin role slug.';
        }
        
        if (!$error) {
            try {
                $stmt = $db->prepare("
                    UPDATE roles 
                    SET name = ?, slug = ?, description = ?, is_default = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$name, $slug, $description ?: null, $isDefault, $roleId]);
                
                logActivity('update', 'roles', "Updated role: {$name}");
                $success = 'Role updated successfully.';
                
                // Refresh role data
                $stmt = $db->prepare("SELECT * FROM roles WHERE id = ?");
                $stmt->execute([$roleId]);
                $role = $stmt->fetch();
                
            } catch (PDOException $e) {
                if (DEBUG_MODE) {
                    error_log("Update role error: " . $e->getMessage());
                }
                $error = 'An error occurred while updating the role.';
            }
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
                    <h1 class="page-title">Edit Role</h1>
                    <p class="page-subtitle">Edit role information</p>
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
                        <form action="" method="POST" id="roleForm">
                            <?php echo csrfField(); ?>
                            
                            <div class="mb-3">
                                <label for="name" class="form-label required">Role Name</label>
                                <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($role['name']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="slug" class="form-label required">Role Slug</label>
                                <input type="text" class="form-control" id="slug" name="slug" required pattern="[a-z0-9_]+" value="<?php echo htmlspecialchars($role['slug']); ?>" <?php echo $isSuperAdmin ? 'readonly' : ''; ?>>
                                <small class="form-text text-muted">Only lowercase letters, numbers, and underscores</small>
                                <?php if ($isSuperAdmin): ?>
                                    <small class="form-text text-danger">Super Admin slug cannot be changed</small>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($role['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_default" name="is_default" <?php echo $role['is_default'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_default">Default Role</label>
                                </div>
                                <small class="form-text text-muted">Default roles cannot be deleted</small>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-2"></i>Update Role
                                </button>
                                <a href="<?php echo APP_URL; ?>/modules/roles/index.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>Back to Roles
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
