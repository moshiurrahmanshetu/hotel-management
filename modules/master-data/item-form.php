<?php
/**
 * Hotel & Resort Management System
 * Master Data Module - Item Form Page (Create/Edit)
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

$page_title = isset($_GET['id']) ? 'Edit Master Item' : 'Add Master Item';
$page_description = isset($_GET['id']) ? 'Edit master data item' : 'Add a new master data item';

// Breadcrumb items
$breadcrumb_items = [
    ['label' => 'Dashboard', 'url' => APP_URL . '/dashboard.php'],
    ['label' => 'Master Data', 'url' => '#'],
    ['label' => 'Items', 'url' => APP_URL . '/modules/master-data/items.php'],
    ['label' => isset($_GET['id']) ? 'Edit Item' : 'Add Item', 'active' => true]
];

$error = '';
$success = '';

$db = getDB();

// Get item ID for edit
$itemId = (int)($_GET['id'] ?? 0);
$item = null;

// Get group filter from URL
$groupFilter = (int)($_GET['group_id'] ?? 0);

if ($itemId) {
    $stmt = $db->prepare("SELECT * FROM master_items WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$itemId]);
    $item = $stmt->fetch();
    
    if (!$item) {
        redirect(APP_URL . '/modules/master-data/items.php');
    }
    
    $groupFilter = $item['group_id'];
}

// Get groups
$stmt = $db->query("SELECT id, name, slug FROM master_groups WHERE deleted_at IS NULL AND is_active = 1 ORDER BY display_order ASC, name ASC");
$groups = $stmt->fetchAll();

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $groupId = (int)($_POST['group_id'] ?? 0);
        $name = sanitizeString($_POST['name'] ?? '');
        $code = sanitizeString($_POST['code'] ?? '');
        $description = sanitizeString($_POST['description'] ?? '');
        $iconClass = sanitizeString($_POST['icon_class'] ?? '');
        $color = sanitizeString($_POST['color'] ?? '');
        $options = $_POST['options'] ?? '';
        $displayOrder = (int)($_POST['display_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        // Validation
        if (!$groupId) {
            $error = 'Please select a group.';
        } elseif (!$name) {
            $error = 'Please enter item name.';
        } else {
            // Check if name already exists in the same group (excluding current item for edit)
            if ($itemId) {
                $stmt = $db->prepare("SELECT id FROM master_items WHERE group_id = ? AND name = ? AND id != ? AND deleted_at IS NULL");
                $stmt->execute([$groupId, $name, $itemId]);
            } else {
                $stmt = $db->prepare("SELECT id FROM master_items WHERE group_id = ? AND name = ? AND deleted_at IS NULL");
                $stmt->execute([$groupId, $name]);
            }
            
            if ($stmt->fetch()) {
                $error = 'Item name already exists in this group.';
            }
            
            // Check if code already exists in the same group (excluding current item for edit)
            if (!$error && $code) {
                if ($itemId) {
                    $stmt = $db->prepare("SELECT id FROM master_items WHERE group_id = ? AND code = ? AND id != ? AND deleted_at IS NULL");
                    $stmt->execute([$groupId, $code, $itemId]);
                } else {
                    $stmt = $db->prepare("SELECT id FROM master_items WHERE group_id = ? AND code = ? AND deleted_at IS NULL");
                    $stmt->execute([$groupId, $code]);
                }
                
                if ($stmt->fetch()) {
                    $error = 'Item code already exists in this group.';
                }
            }
        }
        
        if (!$error) {
            try {
                // Validate JSON for options
                $optionsJson = null;
                if ($options) {
                    json_decode($options);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $error = 'Invalid JSON format for options.';
                    } else {
                        $optionsJson = $options;
                    }
                }
                
                if (!$error) {
                    if ($itemId) {
                        // Update
                        $stmt = $db->prepare("
                            UPDATE master_items 
                            SET group_id = ?, name = ?, code = ?, description = ?, icon_class = ?, color = ?, 
                                options = ?, display_order = ?, is_active = ?, updated_at = NOW()
                            WHERE id = ?
                        ");
                        $stmt->execute([
                            $groupId, $name, $code ?: null, $description ?: null, $iconClass ?: null, $color ?: null,
                            $optionsJson, $displayOrder, $isActive, $itemId
                        ]);
                        
                        logActivity('update', 'master_items', "Updated master item: {$name}", $itemId);
                        $success = 'Master item updated successfully.';
                    } else {
                        // Create
                        $uuid = generateUUID();
                        
                        $stmt = $db->prepare("
                            INSERT INTO master_items (uuid, group_id, name, code, description, icon_class, color, 
                                options, display_order, is_active, created_at, updated_at)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                        ");
                        $stmt->execute([
                            $uuid, $groupId, $name, $code ?: null, $description ?: null, $iconClass ?: null, $color ?: null,
                            $optionsJson, $displayOrder, $isActive
                        ]);
                        
                        logActivity('create', 'master_items', "Created master item: {$name}");
                        $success = 'Master item created successfully.';
                        
                        // Redirect to edit page
                        $itemId = $db->lastInsertId();
                        header('refresh:2;url=' . APP_URL . '/modules/master-data/item-form.php?id=' . $itemId);
                    }
                    
                    // Refresh item data
                    if ($itemId) {
                        $stmt = $db->prepare("SELECT * FROM master_items WHERE id = ?");
                        $stmt->execute([$itemId]);
                        $item = $stmt->fetch();
                    }
                }
                
            } catch (PDOException $e) {
                if (DEBUG_MODE) {
                    error_log("Item form error: " . $e->getMessage());
                }
                $error = 'An error occurred while saving the item.';
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
                    <h1 class="page-title"><?php echo $itemId ? 'Edit Master Item' : 'Add Master Item'; ?></h1>
                    <p class="page-subtitle"><?php echo $itemId ? 'Edit master data item' : 'Add a new master data item'; ?></p>
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
                        <form action="" method="POST" id="itemForm">
                            <?php echo csrfField(); ?>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="group_id" class="form-label required">Group</label>
                                    <select class="form-select" id="group_id" name="group_id" required>
                                        <option value="">Select Group</option>
                                        <?php foreach ($groups as $group): ?>
                                            <option value="<?php echo $group['id']; ?>" <?php echo ($item['group_id'] ?? $groupFilter) === $group['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($group['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="code" class="form-label">Code</label>
                                    <input type="text" class="form-control" id="code" name="code" value="<?php echo htmlspecialchars($item['code'] ?? ''); ?>">
                                    <small class="form-text text-muted">Optional, must be unique within group</small>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label required">Item Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($item['name'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="display_order" class="form-label">Display Order</label>
                                    <input type="number" class="form-control" id="display_order" name="display_order" min="0" value="<?php echo htmlspecialchars($item['display_order'] ?? 0); ?>">
                                    <small class="form-text text-muted">Lower numbers appear first</small>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($item['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="icon_class" class="form-label">Icon Class</label>
                                    <input type="text" class="form-control" id="icon_class" name="icon_class" value="<?php echo htmlspecialchars($item['icon_class'] ?? ''); ?>" placeholder="bi bi-star">
                                    <small class="form-text text-muted">Bootstrap Icons class (e.g., bi bi-star)</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="color" class="form-label">Color</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="color" name="color" value="<?php echo htmlspecialchars($item['color'] ?? '#667eea'); ?>" style="width: 60px;">
                                        <input type="text" class="form-control" id="color_text" name="color_text" value="<?php echo htmlspecialchars($item['color'] ?? '#667eea'); ?>" placeholder="#667eea">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="options" class="form-label">Options (JSON)</label>
                                <textarea class="form-control" id="options" name="options" rows="4" placeholder='{"key": "value"}'><?php echo htmlspecialchars($item['options'] ?? ''); ?></textarea>
                                <small class="form-text text-muted">Additional options stored as JSON (optional)</small>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" <?php echo !isset($item) || $item['is_active'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-2"></i><?php echo $itemId ? 'Update Item' : 'Create Item'; ?>
                                </button>
                                <a href="<?php echo APP_URL; ?>/modules/master-data/items.php<?php echo $groupFilter ? '?group_id=' . $groupFilter : ''; ?>" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>Back to Items
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
// Sync color inputs
document.getElementById('color').addEventListener('input', function() {
    document.getElementById('color_text').value = this.value;
});

document.getElementById('color_text').addEventListener('input', function() {
    document.getElementById('color').value = this.value;
});
</script>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
