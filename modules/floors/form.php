<?php
/**
 * Hotel & Resort Management System
 * Floors Module - Form Page (Create/Edit)
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

$page_title = isset($_GET['id']) ? 'Edit Floor' : 'Add Floor';
$page_description = isset($_GET['id']) ? 'Edit floor information' : 'Add a new floor';

// Breadcrumb items
$breadcrumb_items = [
    ['label' => 'Dashboard', 'url' => APP_URL . '/dashboard.php'],
    ['label' => 'Hotel Structure', 'url' => '#'],
    ['label' => 'Floors', 'url' => APP_URL . '/modules/floors/index.php'],
    ['label' => isset($_GET['id']) ? 'Edit Floor' : 'Add Floor', 'active' => true]
];

$error = '';
$success = '';

$db = getDB();

// Get floor ID for edit
$floorId = (int)($_GET['id'] ?? 0);
$floor = null;

// Get building filter from URL
$buildingFilter = (int)($_GET['building_id'] ?? 0);

if ($floorId) {
    $stmt = $db->prepare("SELECT * FROM floors WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$floorId]);
    $floor = $stmt->fetch();
    
    if (!$floor) {
        redirect(APP_URL . '/modules/floors/index.php');
    }
    
    $buildingFilter = $floor['building_id'];
}

// Get buildings
$stmt = $db->query("
    SELECT b.id, b.name, b.code, p.name as property_name 
    FROM buildings b 
    INNER JOIN properties p ON b.property_id = p.id 
    WHERE b.deleted_at IS NULL AND b.is_active = 1 
    ORDER BY p.name, b.name ASC
");
$buildings = $stmt->fetchAll();

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $buildingId = (int)($_POST['building_id'] ?? 0);
        $floorNumber = (int)($_POST['floor_number'] ?? 0);
        $name = sanitizeString($_POST['name'] ?? '');
        $description = sanitizeString($_POST['description'] ?? '');
        $totalRooms = (int)($_POST['total_rooms'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        // Validation
        if (!$buildingId) {
            $error = 'Please select a building.';
        } elseif (!$floorNumber || $floorNumber < 1) {
            $error = 'Please enter a valid floor number.';
        } elseif (!$name) {
            $error = 'Please enter floor name.';
        } else {
            // Check if floor number already exists in the same building (excluding current floor for edit)
            if ($floorId) {
                $stmt = $db->prepare("SELECT id FROM floors WHERE building_id = ? AND floor_number = ? AND id != ? AND deleted_at IS NULL");
                $stmt->execute([$buildingId, $floorNumber, $floorId]);
            } else {
                $stmt = $db->prepare("SELECT id FROM floors WHERE building_id = ? AND floor_number = ? AND deleted_at IS NULL");
                $stmt->execute([$buildingId, $floorNumber]);
            }
            
            if ($stmt->fetch()) {
                $error = 'Floor number already exists in this building.';
            }
        }
        
        if (!$error) {
            try {
                if ($floorId) {
                    // Update
                    $stmt = $db->prepare("
                        UPDATE floors 
                        SET building_id = ?, floor_number = ?, name = ?, description = ?, 
                            total_rooms = ?, is_active = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $buildingId, $floorNumber, $name, $description ?: null,
                        $totalRooms, $isActive, $floorId
                    ]);
                    
                    logActivity('update', 'floors', "Updated floor: {$name}", $floorId);
                    $success = 'Floor updated successfully.';
                } else {
                    // Create
                    $uuid = generateUUID();
                    
                    $stmt = $db->prepare("
                        INSERT INTO floors (uuid, building_id, floor_number, name, description, 
                            total_rooms, is_active, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                    ");
                    $stmt->execute([
                        $uuid, $buildingId, $floorNumber, $name, $description ?: null,
                        $totalRooms, $isActive
                    ]);
                    
                    logActivity('create', 'floors', "Created floor: {$name}");
                    $success = 'Floor created successfully.';
                    
                    // Redirect to edit page
                    $floorId = $db->lastInsertId();
                    header('refresh:2;url=' . APP_URL . '/modules/floors/form.php?id=' . $floorId);
                }
                
                // Refresh floor data
                if ($floorId) {
                    $stmt = $db->prepare("SELECT * FROM floors WHERE id = ?");
                    $stmt->execute([$floorId]);
                    $floor = $stmt->fetch();
                }
                
            } catch (PDOException $e) {
                if (DEBUG_MODE) {
                    error_log("Floor form error: " . $e->getMessage());
                }
                $error = 'An error occurred while saving the floor.';
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
                    <h1 class="page-title"><?php echo $floorId ? 'Edit Floor' : 'Add Floor'; ?></h1>
                    <p class="page-subtitle"><?php echo $floorId ? 'Edit floor information' : 'Add a new floor to your building'; ?></p>
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
                        <form action="" method="POST" id="floorForm">
                            <?php echo csrfField(); ?>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="building_id" class="form-label required">Building</label>
                                    <select class="form-select" id="building_id" name="building_id" required>
                                        <option value="">Select Building</option>
                                        <?php foreach ($buildings as $building): ?>
                                            <option value="<?php echo $building['id']; ?>" <?php echo ($floor['building_id'] ?? $buildingFilter) === $building['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($building['property_name'] . ' - ' . $building['name']); ?> (<?php echo htmlspecialchars($building['code']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="floor_number" class="form-label required">Floor Number</label>
                                    <input type="number" class="form-control" id="floor_number" name="floor_number" required min="1" value="<?php echo htmlspecialchars($floor['floor_number'] ?? ''); ?>">
                                    <small class="form-text text-muted">Must be unique within the building</small>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label required">Floor Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($floor['name'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="total_rooms" class="form-label">Total Rooms</label>
                                    <input type="number" class="form-control" id="total_rooms" name="total_rooms" min="0" value="<?php echo htmlspecialchars($floor['total_rooms'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($floor['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" <?php echo !isset($floor) || $floor['is_active'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-2"></i><?php echo $floorId ? 'Update Floor' : 'Create Floor'; ?>
                                </button>
                                <a href="<?php echo APP_URL; ?>/modules/floors/index.php<?php echo $buildingFilter ? '?building_id=' . $buildingFilter : ''; ?>" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>Back to Floors
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
// Auto-generate name from floor number
document.getElementById('floor_number').addEventListener('input', function() {
    const floorNumber = this.value;
    if (floorNumber && !document.getElementById('name').value) {
        const suffixes = ['th', 'st', 'nd', 'rd'];
        const v = floorNumber % 100;
        const suffix = (v >= 11 && v <= 13) ? 'th' : suffixes[v % 10] || 'th';
        document.getElementById('name').value = floorNumber + suffix + ' Floor';
    }
});
</script>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
