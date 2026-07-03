<?php
/**
 * Hotel & Resort Management System
 * Permissions Module - Index Page
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

$page_title = 'Permissions';
$page_description = 'View system permissions';

// Breadcrumb items
$breadcrumb_items = [
    ['label' => 'Dashboard', 'url' => APP_URL . '/dashboard.php'],
    ['label' => 'Permissions', 'active' => true]
];

// Get all permissions grouped by module
$db = getDB();
$stmt = $db->query("
    SELECT p.*, 
           COUNT(DISTINCT rp.role_id) as role_count
    FROM permissions p
    LEFT JOIN role_permissions rp ON p.id = rp.permission_id
    GROUP BY p.id
    ORDER BY p.module ASC, p.name ASC
");
$permissions = $stmt->fetchAll();

// Group permissions by module
$permissionsByModule = [];
foreach ($permissions as $permission) {
    $permissionsByModule[$permission['module']][] = $permission;
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
                    <h1 class="page-title">Permissions</h1>
                    <p class="page-subtitle">View all system permissions</p>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <div class="table-toolbar">
                            <div class="table-toolbar-left">
                                <a href="<?php echo APP_URL; ?>/modules/roles/index.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>Back to Roles
                                </a>
                            </div>
                        </div>
                        
                        <?php if (empty($permissionsByModule)): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                No permissions found.
                            </div>
                        <?php else: ?>
                            <?php foreach ($permissionsByModule as $module => $modulePermissions): ?>
                                <div class="permission-module mb-4">
                                    <h6 class="permission-module-title">
                                        <i class="bi bi-folder me-2"></i>
                                        <?php echo ucfirst($module); ?>
                                    </h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Permission Name</th>
                                                    <th>Slug</th>
                                                    <th>Description</th>
                                                    <th>Assigned to Roles</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($modulePermissions as $permission): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($permission['name']); ?></td>
                                                        <td><code><?php echo htmlspecialchars($permission['slug']); ?></code></td>
                                                        <td><?php echo htmlspecialchars($permission['description'] ?? '-'); ?></td>
                                                        <td>
                                                            <span class="badge bg-info"><?php echo $permission['role_count']; ?></span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
.permission-module {
    padding: 20px;
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
