<?php
/**
 * Hotel & Resort Management System
 * Master Data Module - Group Form Page (Create/Edit)
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

$page_title = isset($_GET['id']) ? 'Edit Master Group' : 'Add Master Group';
$page_description = isset($_GET['id']) ? 'Edit master data group' : 'Add a new master data group';

// Breadcrumb items
$breadcrumb_items = [
    ['label' => 'Dashboard', 'url' => APP_URL . '/dashboard.php'],
    ['label' => 'Master Data', 'url' => '#'],
    ['label' => 'Groups', 'url' => APP_URL . '/modules/master-data/index.php'],
    ['label' => isset($_GET['id']) ? 'Edit Group' : 'Add Group', 'active' => true]
];

$error = '';
$success = '';

$db = getDB();

// Get group ID for edit
$groupId = (int)($_GET['id'] ?? 0);
$group = null;

if ($groupId) {
    $stmt = $db->prepare("SELECT * FROM master_groups WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$groupId]);
    $group = $stmt->fetch();
    
    if (!$group) {
        redirect(APP_URL . '/modules/master-data/index.php');
    }
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $name = sanitizeString($_POST['name'] ?? '');
        $slug = sanitizeString($_POST['slug'] ?? '');
        $description = sanitizeString($_POST['description'] ?? '');
        $iconClass = sanitizeString($_POST['icon_class'] ?? '');
        $color = sanitizeString($_POST['color'] ?? '');
        $displayOrder = (int)($_POST['display_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        // Validation
        if (!$name) {
            $error = 'Please enter group name.';
        } elseif (!$slug) {
            $error = 'Please enter group slug.';
        } elseif (!preg_match('/^[a-z0-9_]+$/', $slug)) {
            $error = 'Slug must contain only lowercase letters, numbers, and underscores.';
        } else {
            // Check if slug already exists (excluding current group for edit)
            if ($groupId) {
                $stmt = $db->prepare("SELECT id FROM master_groups WHERE slug = ? AND id != ? AND deleted_at IS NULL");
                $stmt->execute([$slug, $groupId]);
            } else {
                $stmt = $db->prepare("SELECT id FROM master_groups WHERE slug = ? AND deleted_at IS NULL");
                $stmt->execute([$slug]);
            }
            
            if ($stmt->fetch()) {
                $error = 'Group slug already exists.';
            }
            
            // Check if name already exists (excluding current group for edit)
            if (!$error) {
                if ($groupId) {
                    $stmt = $db->prepare("SELECT id FROM master_groups WHERE name = ? AND id != ? AND deleted_at IS NULL");
                    $stmt->execute([$name, $groupId]);
                } else {
                    $stmt = $db->prepare("SELECT id FROM master_groups WHERE name = ? AND deleted_at IS NULL");
                    $stmt->execute([$name]);
                }
                
                if ($stmt->fetch()) {
                    $error = 'Group name already exists.';
                }
            }
        }
        
        if (!$error) {
            try {
                if ($groupId) {
                    // Update
                    $stmt = $db->prepare("
                        UPDATE master_groups 
                        SET name = ?, slug = ?, description = ?, icon_class = ?, color = ?, 
                            display_order = ?, is_active = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $name, $slug, $description ?: null, $iconClass ?: null, $color ?: null,
                        $displayOrder, $isActive, $groupId
                    ]);
                    
                    logActivity('update', 'master_groups', "Updated master group: {$name}", $groupId);
                    $success = 'Master group updated successfully.';
                } else {
                    // Create
                    $uuid = generateUUID();
                    
                    $stmt = $db->prepare("
                        INSERT INTO master_groups (uuid, name, slug, description, icon_class, color, 
                            display_order, is_active, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                    ");
                    $stmt->execute([
                        $uuid, $name, $slug, $description ?: null, $iconClass ?: null, $color ?: null,
                        $displayOrder, $isActive
                    ]);
                    
                    logActivity('create', 'master_groups', "Created master group: {$name}");
                    $success = 'Master group created successfully.';
                    
                    // Redirect to edit page
                    $groupId = $db->lastInsertId();
                    header('refresh:2;url=' . APP_URL . '/modules/master-data/form.php?id=' . $groupId);
                }
                
                // Refresh group data
                if ($groupId) {
                    $stmt = $db->prepare("SELECT * FROM master_groups WHERE id = ?");
                    $stmt->execute([$groupId]);
                    $group = $stmt->fetch();
                }
                
            } catch (PDOException $e) {
                if (DEBUG_MODE) {
                    error_log("Group form error: " . $e->getMessage());
                }
                $error = 'An error occurred while saving the group.';
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
                    <h1 class="page-title"><?php echo $groupId ? 'Edit Master Group' : 'Add Master Group'; ?></h1>
                    <p class="page-subtitle"><?php echo $groupId ? 'Edit master data group' : 'Add a new master data category'; ?></p>
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
                        <form action="" method="POST" id="groupForm">
                            <?php echo csrfField(); ?>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label required">Group Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($group['name'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="slug" class="form-label required">Slug</label>
                                    <input type="text" class="form-control" id="slug" name="slug" required pattern="[a-z0-9_]+" value="<?php echo htmlspecialchars($group['slug'] ?? ''); ?>">
                                    <small class="form-text text-muted">Lowercase letters, numbers, and underscores only</small>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($group['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="icon_class" class="form-label">Icon Class</label>
                                    <input type="text" class="form-control" id="icon_class" name="icon_class" value="<?php echo htmlspecialchars($group['icon_class'] ?? ''); ?>" placeholder="bi bi-folder">
                                    <small class="form-text text-muted">Bootstrap Icons class (e.g., bi bi-folder)</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="color" class="form-label">Color</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="color" name="color" value="<?php echo htmlspecialchars($group['color'] ?? '#667eea'); ?>" style="width: 60px;">
                                        <input type="text" class="form-control" id="color_text" name="color_text" value="<?php echo htmlspecialchars($group['color'] ?? '#667eea'); ?>" placeholder="#667eea">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="display_order" class="form-label">Display Order</label>
                                    <input type="number" class="form-control" id="display_order" name="display_order" min="0" value="<?php echo htmlspecialchars($group['display_order'] ?? 0); ?>">
                                    <small class="form-text text-muted">Lower numbers appear first</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Status</label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" <?php echo !isset($group) || $group['is_active'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_active">Active</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-2"></i><?php echo $groupId ? 'Update Group' : 'Create Group'; ?>
                                </button>
                                <a href="<?php echo APP_URL; ?>/modules/master-data/index.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>Back to Groups
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

// Sync color inputs
document.getElementById('color').addEventListener('input', function() {
    document.getElementById('color_text').value = this.value;
});

document.getElementById('color_text').addEventListener('input', function() {
    document.getElementById('color').value = this.value;
});
</script>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
