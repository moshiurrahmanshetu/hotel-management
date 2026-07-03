<?php
/**
 * Hotel & Resort Management System
 * Roles Module - Create Role Page
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
requirePermission('roles.create');

$page_title = 'Create Role';
$page_description = 'Add a new system role';

// Breadcrumb items
$breadcrumb_items = [
    ['label' => 'Dashboard', 'url' => APP_URL . '/dashboard.php'],
    ['label' => 'Roles', 'url' => APP_URL . '/modules/roles/index.php'],
    ['label' => 'Create Role', 'active' => true]
];

$error = '';
$success = '';

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
            // Check if slug already exists
            $db = getDB();
            $stmt = $db->prepare("SELECT id FROM roles WHERE slug = ?");
            $stmt->execute([$slug]);
            if ($stmt->fetch()) {
                $error = 'Role slug already exists.';
            }
        }
        
        if (!$error) {
            try {
                $stmt = $db->prepare("
                    INSERT INTO roles (name, slug, description, is_default, created_at, updated_at)
                    VALUES (?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([$name, $slug, $description ?: null, $isDefault]);
                
                logActivity('create', 'roles', "Created role: {$name}");
                $success = 'Role created successfully.';
                
                // Redirect to roles list
                header('refresh:2;url=' . APP_URL . '/modules/roles/index.php');
                
            } catch (PDOException $e) {
                if (DEBUG_MODE) {
                    error_log("Create role error: " . $e->getMessage());
                }
                $error = 'An error occurred while creating the role.';
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
                    <h1 class="page-title">Create Role</h1>
                    <p class="page-subtitle">Add a new system role</p>
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
                                <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="slug" class="form-label required">Role Slug</label>
                                <input type="text" class="form-control" id="slug" name="slug" required pattern="[a-z0-9_]+" value="<?php echo htmlspecialchars($_POST['slug'] ?? ''); ?>">
                                <small class="form-text text-muted">Only lowercase letters, numbers, and underscores (e.g., manager, staff)</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_default" name="is_default" <?php echo isset($_POST['is_default']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_default">Default Role</label>
                                </div>
                                <small class="form-text text-muted">Default roles cannot be deleted</small>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-2"></i>Create Role
                                </button>
                                <a href="<?php echo APP_URL; ?>/modules/roles/index.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-lg me-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Auto-generate slug from name
document.getElementById('name').addEventListener('input', function() {
    const name = this.value;
    const slug = name.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
    document.getElementById('slug').value = slug;
});
</script>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
