<?php
/**
 * Hotel & Resort Management System
 * Buildings Module - Form Page (Create/Edit)
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

$page_title = isset($_GET['id']) ? 'Edit Building' : 'Add Building';
$page_description = isset($_GET['id']) ? 'Edit building information' : 'Add a new building';

// Breadcrumb items
$breadcrumb_items = [
    ['label' => 'Dashboard', 'url' => APP_URL . '/dashboard.php'],
    ['label' => 'Hotel Structure', 'url' => '#'],
    ['label' => 'Buildings', 'url' => APP_URL . '/modules/buildings/index.php'],
    ['label' => isset($_GET['id']) ? 'Edit Building' : 'Add Building', 'active' => true]
];

$error = '';
$success = '';

$db = getDB();

// Get building ID for edit
$buildingId = (int)($_GET['id'] ?? 0);
$building = null;

// Get property filter from URL
$propertyFilter = (int)($_GET['property_id'] ?? 0);

if ($buildingId) {
    $stmt = $db->prepare("SELECT * FROM buildings WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$buildingId]);
    $building = $stmt->fetch();
    
    if (!$building) {
        redirect(APP_URL . '/modules/buildings/index.php');
    }
    
    $propertyFilter = $building['property_id'];
}

// Get properties
$stmt = $db->query("SELECT id, name, code FROM properties WHERE deleted_at IS NULL AND is_active = 1 ORDER BY name ASC");
$properties = $stmt->fetchAll();

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $propertyId = (int)($_POST['property_id'] ?? 0);
        $name = sanitizeString($_POST['name'] ?? '');
        $code = sanitizeString($_POST['code'] ?? '');
        $description = sanitizeString($_POST['description'] ?? '');
        $address = sanitizeString($_POST['address'] ?? '');
        $totalFloors = (int)($_POST['total_floors'] ?? 0);
        $totalRooms = (int)($_POST['total_rooms'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        // Validation
        if (!$propertyId) {
            $error = 'Please select a property.';
        } elseif (!$name) {
            $error = 'Please enter building name.';
        } elseif (!$code) {
            $error = 'Please enter building code.';
        } elseif (!preg_match('/^[A-Z0-9_]+$/', $code)) {
            $error = 'Code must contain only uppercase letters, numbers, and underscores.';
        } else {
            // Check if code already exists (excluding current building for edit)
            if ($buildingId) {
                $stmt = $db->prepare("SELECT id FROM buildings WHERE code = ? AND id != ? AND deleted_at IS NULL");
                $stmt->execute([$code, $buildingId]);
            } else {
                $stmt = $db->prepare("SELECT id FROM buildings WHERE code = ? AND deleted_at IS NULL");
                $stmt->execute([$code]);
            }
            
            if ($stmt->fetch()) {
                $error = 'Building code already exists.';
            }
        }
        
        if (!$error) {
            try {
                if ($buildingId) {
                    // Update
                    $stmt = $db->prepare("
                        UPDATE buildings 
                        SET property_id = ?, name = ?, code = ?, description = ?, address = ?, 
                            total_floors = ?, total_rooms = ?, is_active = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $propertyId, $name, $code, $description ?: null, $address ?: null,
                        $totalFloors, $totalRooms, $isActive, $buildingId
                    ]);
                    
                    logActivity('update', 'buildings', "Updated building: {$name}", $buildingId);
                    $success = 'Building updated successfully.';
                } else {
                    // Create
                    $uuid = generateUUID();
                    
                    $stmt = $db->prepare("
                        INSERT INTO buildings (uuid, property_id, name, code, description, address, 
                            total_floors, total_rooms, is_active, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                    ");
                    $stmt->execute([
                        $uuid, $propertyId, $name, $code, $description ?: null, $address ?: null,
                        $totalFloors, $totalRooms, $isActive
                    ]);
                    
                    logActivity('create', 'buildings', "Created building: {$name}");
                    $success = 'Building created successfully.';
                    
                    // Redirect to edit page
                    $buildingId = $db->lastInsertId();
                    header('refresh:2;url=' . APP_URL . '/modules/buildings/form.php?id=' . $buildingId);
                }
                
                // Refresh building data
                if ($buildingId) {
                    $stmt = $db->prepare("SELECT * FROM buildings WHERE id = ?");
                    $stmt->execute([$buildingId]);
                    $building = $stmt->fetch();
                }
                
            } catch (PDOException $e) {
                if (DEBUG_MODE) {
                    error_log("Building form error: " . $e->getMessage());
                }
                $error = 'An error occurred while saving the building.';
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
                    <h1 class="page-title"><?php echo $buildingId ? 'Edit Building' : 'Add Building'; ?></h1>
                    <p class="page-subtitle"><?php echo $buildingId ? 'Edit building information' : 'Add a new building to your property'; ?></p>
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
                        <form action="" method="POST" id="buildingForm">
                            <?php echo csrfField(); ?>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="property_id" class="form-label required">Property</label>
                                    <select class="form-select" id="property_id" name="property_id" required>
                                        <option value="">Select Property</option>
                                        <?php foreach ($properties as $property): ?>
                                            <option value="<?php echo $property['id']; ?>" <?php echo ($building['property_id'] ?? $propertyFilter) === $property['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($property['name']); ?> (<?php echo htmlspecialchars($property['code']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="code" class="form-label required">Building Code</label>
                                    <input type="text" class="form-control" id="code" name="code" required pattern="[A-Z0-9_]+" value="<?php echo htmlspecialchars($building['code'] ?? ''); ?>">
                                    <small class="form-text text-muted">Uppercase letters, numbers, and underscores only</small>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label required">Building Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($building['name'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($building['address'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($building['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="total_floors" class="form-label">Total Floors</label>
                                    <input type="number" class="form-control" id="total_floors" name="total_floors" min="0" value="<?php echo htmlspecialchars($building['total_floors'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="total_rooms" class="form-label">Total Rooms</label>
                                    <input type="number" class="form-control" id="total_rooms" name="total_rooms" min="0" value="<?php echo htmlspecialchars($building['total_rooms'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" <?php echo !isset($building) || $building['is_active'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-2"></i><?php echo $buildingId ? 'Update Building' : 'Create Building'; ?>
                                </button>
                                <a href="<?php echo APP_URL; ?>/modules/buildings/index.php<?php echo $propertyFilter ? '?property_id=' . $propertyFilter : ''; ?>" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>Back to Buildings
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
// Auto-generate code from name
document.getElementById('name').addEventListener('input', function() {
    const name = this.value;
    const code = name.toUpperCase().replace(/[^A-Z0-9]+/g, '_').replace(/^_+|_+$/g, '');
    document.getElementById('code').value = code;
});
</script>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
