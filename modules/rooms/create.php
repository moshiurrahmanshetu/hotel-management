<?php
/**
 * Hotel & Resort Management System
 * Rooms Module - Create Page
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

$page_title = 'Add Room';
$page_description = 'Add a new room';

// Breadcrumb items
$breadcrumb_items = [
    ['label' => 'Dashboard', 'url' => APP_URL . '/dashboard.php'],
    ['label' => 'Rooms', 'url' => APP_URL . '/modules/rooms/index.php'],
    ['label' => 'Add Room', 'active' => true]
];

$error = '';
$success = '';

$db = getDB();

// Get master data for dropdowns
$masterData = [];
$masterGroups = ['room_category', 'room_type', 'bed_type', 'view_type', 'amenity'];
foreach ($masterGroups as $groupSlug) {
    $stmt = $db->prepare("
        SELECT mi.id, mi.name, mi.code 
        FROM master_items mi 
        INNER JOIN master_groups mg ON mi.group_id = mg.id 
        WHERE mg.slug = ? AND mi.is_active = 1 AND mi.deleted_at IS NULL 
        ORDER BY mi.display_order ASC, mi.name ASC
    ");
    $stmt->execute([$groupSlug]);
    $masterData[$groupSlug] = $stmt->fetchAll();
}

// Get properties
$stmt = $db->query("SELECT id, name, code FROM properties WHERE deleted_at IS NULL AND is_active = 1 ORDER BY name ASC");
$properties = $stmt->fetchAll();

// Get custom fields for room module
$stmt = $db->prepare("
    SELECT * FROM custom_fields 
    WHERE module = 'room' AND is_active = 1 AND deleted_at IS NULL 
    ORDER BY display_order ASC
");
$stmt->execute();
$customFields = $stmt->fetchAll();

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $propertyId = (int)($_POST['property_id'] ?? 0);
        $buildingId = (int)($_POST['building_id'] ?? 0);
        $floorId = (int)($_POST['floor_id'] ?? 0);
        $roomNumber = sanitizeString($_POST['room_number'] ?? '');
        $roomName = sanitizeString($_POST['room_name'] ?? '');
        $roomCategoryId = (int)($_POST['room_category_id'] ?? 0);
        $roomTypeId = (int)($_POST['room_type_id'] ?? 0);
        $bedTypeId = (int)($_POST['bed_type_id'] ?? 0);
        $viewTypeId = (int)($_POST['view_type_id'] ?? 0);
        $maxAdults = (int)($_POST['max_adults'] ?? 2);
        $maxChildren = (int)($_POST['max_children'] ?? 0);
        $roomSize = (float)($_POST['room_size'] ?? 0);
        $sizeUnit = sanitizeString($_POST['size_unit'] ?? 'sqft');
        $basePrice = (float)($_POST['base_price'] ?? 0);
        $status = sanitizeString($_POST['status'] ?? 'available');
        $description = sanitizeString($_POST['description'] ?? '');
        $amenities = $_POST['amenities'] ?? [];
        $note = sanitizeString($_POST['note'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        // Validation
        if (!$propertyId) {
            $error = 'Please select a property.';
        } elseif (!$buildingId) {
            $error = 'Please select a building.';
        } elseif (!$floorId) {
            $error = 'Please select a floor.';
        } elseif (!$roomNumber) {
            $error = 'Please enter room number.';
        } elseif ($maxAdults < 0) {
            $error = 'Maximum adults cannot be negative.';
        } elseif ($maxChildren < 0) {
            $error = 'Maximum children cannot be negative.';
        } elseif ($basePrice < 0) {
            $error = 'Base price cannot be negative.';
        } elseif (!in_array($status, ['available', 'occupied', 'reserved', 'maintenance', 'cleaning', 'out_of_service'])) {
            $error = 'Invalid status selected.';
        } else {
            // Validate building belongs to property
            $stmt = $db->prepare("SELECT id FROM buildings WHERE id = ? AND property_id = ?");
            $stmt->execute([$buildingId, $propertyId]);
            if (!$stmt->fetch()) {
                $error = 'Selected building does not belong to the selected property.';
            }
            
            // Validate floor belongs to building
            if (!$error) {
                $stmt = $db->prepare("SELECT id FROM floors WHERE id = ? AND building_id = ?");
                $stmt->execute([$floorId, $buildingId]);
                if (!$stmt->fetch()) {
                    $error = 'Selected floor does not belong to the selected building.';
                }
            }
            
            // Check if room number already exists in property
            if (!$error) {
                $stmt = $db->prepare("SELECT id FROM rooms WHERE property_id = ? AND room_number = ? AND deleted_at IS NULL");
                $stmt->execute([$propertyId, $roomNumber]);
                if ($stmt->fetch()) {
                    $error = 'Room number already exists in this property.';
                }
            }
        }
        
        if (!$error) {
            try {
                $db->beginTransaction();
                
                // Create room
                $uuid = generateUUID();
                
                $stmt = $db->prepare("
                    INSERT INTO rooms (uuid, property_id, building_id, floor_id, room_number, room_name, 
                        room_category_id, room_type_id, bed_type_id, view_type_id, max_adults, max_children, 
                        room_size, size_unit, base_price, status, description, is_active, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([
                    $uuid, $propertyId, $buildingId, $floorId, $roomNumber, $roomName ?: null,
                    $roomCategoryId ?: null, $roomTypeId ?: null, $bedTypeId ?: null, $viewTypeId ?: null,
                    $maxAdults, $maxChildren, $roomSize ?: null, $sizeUnit, $basePrice, $status,
                    $description ?: null, $isActive
                ]);
                
                $roomId = $db->lastInsertId();
                
                // Add amenities
                if (!empty($amenities)) {
                    $amenityStmt = $db->prepare("INSERT INTO room_amenities (room_id, amenity_id, created_at) VALUES (?, ?, NOW())");
                    foreach ($amenities as $amenityId) {
                        $amenityStmt->execute([$roomId, (int)$amenityId]);
                    }
                }
                
                // Add note if provided
                if ($note) {
                    $noteStmt = $db->prepare("INSERT INTO room_notes (room_id, note, created_by, created_at) VALUES (?, ?, ?, NOW())");
                    $noteStmt->execute([$roomId, $note, authUser()['id']]);
                }
                
                // Save custom field values
                if (!empty($customFields)) {
                    $customFieldStmt = $db->prepare("INSERT INTO custom_field_values (uuid, field_id, entity_id, entity_type, value, created_at, updated_at) VALUES (?, ?, ?, 'room', ?, NOW(), NOW())");
                    foreach ($customFields as $field) {
                        $fieldName = 'custom_field_' . $field['id'];
                        if (isset($_POST[$fieldName])) {
                            $value = is_array($_POST[$fieldName]) ? json_encode($_POST[$fieldName]) : $_POST[$fieldName];
                            $customFieldUuid = generateUUID();
                            $customFieldStmt->execute([$customFieldUuid, $field['id'], $roomId, $value]);
                        }
                    }
                }
                
                $db->commit();
                
                logActivity('create', 'rooms', "Created room: {$roomNumber}", $roomId);
                $success = 'Room created successfully.';
                
                // Redirect to view page
                header('refresh:2;url=' . APP_URL . '/modules/rooms/view.php?id=' . $roomId);
                
            } catch (PDOException $e) {
                $db->rollBack();
                if (DEBUG_MODE) {
                    error_log("Create room error: " . $e->getMessage());
                }
                $error = 'An error occurred while creating the room.';
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
                    <h1 class="page-title">Add Room</h1>
                    <p class="page-subtitle">Add a new room to your hotel</p>
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
                        <form action="" method="POST" id="roomForm">
                            <?php echo csrfField(); ?>
                            
                            <h5 class="mb-3">Location</h5>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="property_id" class="form-label required">Property</label>
                                    <select class="form-select" id="property_id" name="property_id" required onchange="loadBuildings()">
                                        <option value="">Select Property</option>
                                        <?php foreach ($properties as $property): ?>
                                            <option value="<?php echo $property['id']; ?>" <?php echo ($propertyId ?? '') === $property['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($property['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="building_id" class="form-label required">Building</label>
                                    <select class="form-select" id="building_id" name="building_id" required onchange="loadFloors()" disabled>
                                        <option value="">Select Property First</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="floor_id" class="form-label required">Floor</label>
                                    <select class="form-select" id="floor_id" name="floor_id" required disabled>
                                        <option value="">Select Building First</option>
                                    </select>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <h5 class="mb-3">Room Details</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="room_number" class="form-label required">Room Number</label>
                                    <input type="text" class="form-control" id="room_number" name="room_number" required value="<?php echo htmlspecialchars($roomNumber ?? ''); ?>">
                                    <small class="form-text text-muted">Must be unique within the property</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="room_name" class="form-label">Room Name</label>
                                    <input type="text" class="form-control" id="room_name" name="room_name" value="<?php echo htmlspecialchars($roomName ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label for="room_category_id" class="form-label">Room Category</label>
                                    <select class="form-select" id="room_category_id" name="room_category_id">
                                        <option value="">Select Category</option>
                                        <?php foreach ($masterData['room_category'] ?? [] as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" <?php echo ($roomCategoryId ?? '') === $category['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label for="room_type_id" class="form-label">Room Type</label>
                                    <select class="form-select" id="room_type_id" name="room_type_id">
                                        <option value="">Select Type</option>
                                        <?php foreach ($masterData['room_type'] ?? [] as $type): ?>
                                            <option value="<?php echo $type['id']; ?>" <?php echo ($roomTypeId ?? '') === $type['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($type['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label for="bed_type_id" class="form-label">Bed Type</label>
                                    <select class="form-select" id="bed_type_id" name="bed_type_id">
                                        <option value="">Select Bed Type</option>
                                        <?php foreach ($masterData['bed_type'] ?? [] as $bed): ?>
                                            <option value="<?php echo $bed['id']; ?>" <?php echo ($bedTypeId ?? '') === $bed['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($bed['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label for="view_type_id" class="form-label">View Type</label>
                                    <select class="form-select" id="view_type_id" name="view_type_id">
                                        <option value="">Select View Type</option>
                                        <?php foreach ($masterData['view_type'] ?? [] as $view): ?>
                                            <option value="<?php echo $view['id']; ?>" <?php echo ($viewTypeId ?? '') === $view['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($view['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label for="max_adults" class="form-label required">Max Adults</label>
                                    <input type="number" class="form-control" id="max_adults" name="max_adults" required min="0" value="<?php echo htmlspecialchars($maxAdults ?? 2); ?>">
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label for="max_children" class="form-label">Max Children</label>
                                    <input type="number" class="form-control" id="max_children" name="max_children" min="0" value="<?php echo htmlspecialchars($maxChildren ?? 0); ?>">
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label for="room_size" class="form-label">Room Size</label>
                                    <input type="number" class="form-control" id="room_size" name="room_size" step="0.01" min="0" value="<?php echo htmlspecialchars($roomSize ?? ''); ?>">
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label for="size_unit" class="form-label">Size Unit</label>
                                    <select class="form-select" id="size_unit" name="size_unit">
                                        <option value="sqft" <?php echo ($sizeUnit ?? '') === 'sqft' ? 'selected' : ''; ?>>Sq Ft</option>
                                        <option value="sqm" <?php echo ($sizeUnit ?? '') === 'sqm' ? 'selected' : ''; ?>>Sq M</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="base_price" class="form-label required">Base Price</label>
                                    <input type="number" class="form-control" id="base_price" name="base_price" required step="0.01" min="0" value="<?php echo htmlspecialchars($basePrice ?? 0); ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label required">Status</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="available" <?php echo ($status ?? '') === 'available' ? 'selected' : ''; ?>>Available</option>
                                        <option value="occupied" <?php echo ($status ?? '') === 'occupied' ? 'selected' : ''; ?>>Occupied</option>
                                        <option value="reserved" <?php echo ($status ?? '') === 'reserved' ? 'selected' : ''; ?>>Reserved</option>
                                        <option value="maintenance" <?php echo ($status ?? '') === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                        <option value="cleaning" <?php echo ($status ?? '') === 'cleaning' ? 'selected' : ''; ?>>Cleaning</option>
                                        <option value="out_of_service" <?php echo ($status ?? '') === 'out_of_service' ? 'selected' : ''; ?>>Out of Service</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                            </div>
                            
                            <hr>
                            
                            <h5 class="mb-3">Amenities</h5>
                            <div class="mb-3">
                                <?php foreach ($masterData['amenity'] ?? [] as $amenity): ?>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="amenity_<?php echo $amenity['id']; ?>" name="amenities[]" value="<?php echo $amenity['id']; ?>">
                                        <label class="form-check-label" for="amenity_<?php echo $amenity['id']; ?>">
                                            <?php echo htmlspecialchars($amenity['name']); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php if (!empty($customFields)): ?>
                                <hr>
                                <h5 class="mb-3">Custom Fields</h5>
                                <?php foreach ($customFields as $field): ?>
                                    <div class="mb-3">
                                        <label for="custom_field_<?php echo $field['id']; ?>" class="form-label <?php echo $field['is_required'] ? 'required' : ''; ?>">
                                            <?php echo htmlspecialchars($field['field_label']); ?>
                                            <?php if ($field['is_required']): ?> *<?php endif; ?>
                                        </label>
                                        <?php renderCustomField($field); ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <hr>
                            
                            <h5 class="mb-3">Notes</h5>
                            <div class="mb-3">
                                <label for="note" class="form-label">Initial Note</label>
                                <textarea class="form-control" id="note" name="note" rows="2" placeholder="Add an initial note for this room..."><?php echo htmlspecialchars($note ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-2"></i>Create Room
                                </button>
                                <a href="<?php echo APP_URL; ?>/modules/rooms/index.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>Back to Rooms
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
let buildingsData = {};
let floorsData = {};

function loadBuildings() {
    const propertyId = document.getElementById('property_id').value;
    const buildingSelect = document.getElementById('building_id');
    const floorSelect = document.getElementById('floor_id');
    
    buildingSelect.innerHTML = '<option value="">Loading...</option>';
    buildingSelect.disabled = true;
    floorSelect.innerHTML = '<option value="">Select Building First</option>';
    floorSelect.disabled = true;
    
    if (!propertyId) {
        buildingSelect.innerHTML = '<option value="">Select Property First</option>';
        return;
    }
    
    fetch('<?php echo APP_URL; ?>/api/get-buildings.php?property_id=' + propertyId)
        .then(response => response.json())
        .then(data => {
            buildingsData = data;
            buildingSelect.innerHTML = '<option value="">Select Building</option>';
            data.forEach(building => {
                const option = document.createElement('option');
                option.value = building.id;
                option.textContent = building.name;
                buildingSelect.appendChild(option);
            });
            buildingSelect.disabled = false;
        })
        .catch(error => {
            console.error('Error loading buildings:', error);
            buildingSelect.innerHTML = '<option value="">Error loading buildings</option>';
        });
}

function loadFloors() {
    const buildingId = document.getElementById('building_id').value;
    const floorSelect = document.getElementById('floor_id');
    
    floorSelect.innerHTML = '<option value="">Loading...</option>';
    floorSelect.disabled = true;
    
    if (!buildingId) {
        floorSelect.innerHTML = '<option value="">Select Building First</option>';
        return;
    }
    
    fetch('<?php echo APP_URL; ?>/api/get-floors.php?building_id=' + buildingId)
        .then(response => response.json())
        .then(data => {
            floorsData = data;
            floorSelect.innerHTML = '<option value="">Select Floor</option>';
            data.forEach(floor => {
                const option = document.createElement('option');
                option.value = floor.id;
                option.textContent = floor.name + ' (Floor ' + floor.floor_number + ')';
                floorSelect.appendChild(option);
            });
            floorSelect.disabled = false;
        })
        .catch(error => {
            console.error('Error loading floors:', error);
            floorSelect.innerHTML = '<option value="">Error loading floors</option>';
        });
}
</script>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
