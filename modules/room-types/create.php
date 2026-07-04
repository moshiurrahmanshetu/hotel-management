<?php
/**
 * Hotel & Resort Management System
 * Room Types Module - Create Page
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
requirePermission('room_types.create');

$page_title = 'Add Room Type';
$page_description = 'Create a new room type';

// Breadcrumb items
$breadcrumb_items = [
    ['label' => 'Dashboard', 'url' => APP_URL . '/dashboard.php'],
    ['label' => 'Room Types', 'url' => APP_URL . '/modules/room-types/index.php'],
    ['label' => 'Add Room Type', 'active' => true]
];

$error = '';
$success = '';

$db = getDB();

// Get available amenities
$stmt = $db->query("SELECT * FROM amenities WHERE is_active = 1 ORDER BY display_order ASC, name ASC");
$amenities = $stmt->fetchAll();

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $name = sanitizeString($_POST['name'] ?? '');
        $code = sanitizeString($_POST['code'] ?? '');
        $description = sanitizeString($_POST['description'] ?? '');
        $basePrice = (float)($_POST['base_price'] ?? 0);
        $weekendPrice = (float)($_POST['weekend_price'] ?? 0);
        $maxAdults = (int)($_POST['max_adults'] ?? 2);
        $maxChildren = (int)($_POST['max_children'] ?? 0);
        $maxOccupancy = (int)($_POST['max_occupancy'] ?? 2);
        $roomSize = (float)($_POST['room_size'] ?? 0);
        $bedType = sanitizeString($_POST['bed_type'] ?? '');
        $numBeds = (int)($_POST['num_beds'] ?? 1);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $selectedAmenities = $_POST['amenities'] ?? [];
        
        // Validation
        if (!$name) {
            $error = 'Please enter room type name.';
        } elseif (!$code) {
            $error = 'Please enter room type code.';
        } elseif (!preg_match('/^[A-Z0-9_-]+$/', $code)) {
            $error = 'Code must contain only uppercase letters, numbers, hyphens, and underscores.';
        } elseif ($basePrice < 0) {
            $error = 'Base price must be a positive number.';
        } elseif ($maxAdults < 1) {
            $error = 'Maximum adults must be at least 1.';
        } elseif ($maxOccupancy < 1) {
            $error = 'Maximum occupancy must be at least 1.';
        } elseif ($maxOccupancy < ($maxAdults + $maxChildren)) {
            $error = 'Maximum occupancy must be at least the sum of adults and children.';
        } else {
            // Check if code already exists
            $stmt = $db->prepare("SELECT id FROM room_types WHERE code = ?");
            $stmt->execute([$code]);
            if ($stmt->fetch()) {
                $error = 'Room type code already exists.';
            } else {
                try {
                    $db->beginTransaction();
                    
                    // Generate UUID
                    $uuid = generateUUID();
                    
                    // Insert room type
                    $stmt = $db->prepare("
                        INSERT INTO room_types 
                        (uuid, name, code, description, base_price, weekend_price, 
                         max_adults, max_children, max_occupancy, room_size, bed_type, 
                         num_beds, is_active)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $uuid, $name, $code, $description, $basePrice, 
                        $weekendPrice ?: null, $maxAdults, $maxChildren, 
                        $maxOccupancy, $roomSize ?: null, $bedType ?: null, 
                        $numBeds, $isActive
                    ]);
                    
                    $roomTypeId = $db->lastInsertId();
                    
                    // Insert amenities
                    if (!empty($selectedAmenities)) {
                        foreach ($selectedAmenities as $amenityId) {
                            $stmt = $db->prepare("
                                INSERT INTO room_type_amenities (room_type_id, amenity_id)
                                VALUES (?, ?)
                            ");
                            $stmt->execute([$roomTypeId, (int)$amenityId]);
                        }
                    }
                    
                    // Handle image uploads
                    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                        $uploadDir = UPLOAD_PATH . '/room-types';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        
                        $files = $_FILES['images'];
                        $fileCount = count($files['name']);
                        
                        for ($i = 0; $i < $fileCount; $i++) {
                            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                                if (in_array($files['type'][$i], $allowedTypes) && $files['size'][$i] <= UPLOAD_MAX_SIZE) {
                                    $extension = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                                    $filename = 'room_type_' . $roomTypeId . '_' . time() . '_' . $i . '.' . $extension;
                                    $filepath = $uploadDir . '/' . $filename;
                                    
                                    if (move_uploaded_file($files['tmp_name'][$i], $filepath)) {
                                        $imagePath = 'uploads/room-types/' . $filename;
                                        $isFeatured = ($i === 0) ? 1 : 0; // First image is featured by default
                                        
                                        $stmt = $db->prepare("
                                            INSERT INTO room_type_images 
                                            (room_type_id, image_path, is_featured, display_order)
                                            VALUES (?, ?, ?, ?)
                                        ");
                                        $stmt->execute([$roomTypeId, $imagePath, $isFeatured, $i]);
                                    }
                                }
                            }
                        }
                    }
                    
                    $db->commit();
                    
                    logActivity('create', 'room_types', "Created room type: {$name}", null, ['room_type_id' => $roomTypeId, 'name' => $name, 'code' => $code]);
                    
                    $success = 'Room type created successfully.';
                    
                    // Redirect to index after short delay
                    header("refresh:2;url=" . APP_URL . "/modules/room-types/index.php");
                } catch (PDOException $e) {
                    $db->rollBack();
                    if (DEBUG_MODE) {
                        error_log("Create room type error: " . $e->getMessage());
                    }
                    $error = 'An error occurred while creating the room type.';
                }
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
                    <h1 class="page-title">Add Room Type</h1>
                    <p class="page-subtitle">Create a new room type configuration</p>
                </div>
                
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <?php echo csrfField(); ?>
                            
                            <!-- Basic Information -->
                            <h5 class="mb-3">Basic Information</h5>
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Room Type Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="code" name="code" 
                                           value="<?php echo htmlspecialchars($_POST['code'] ?? ''); ?>" 
                                           placeholder="e.g., DELUXE_SUITE" required>
                                    <div class="form-text">Use uppercase letters, numbers, hyphens, and underscores only.</div>
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                            <!-- Pricing -->
                            <h5 class="mb-3">Pricing</h5>
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label for="base_price" class="form-label">Base Price <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><?php echo CURRENCY_SYMBOL; ?></span>
                                        <input type="number" class="form-control" id="base_price" name="base_price" 
                                               value="<?php echo $_POST['base_price'] ?? ''; ?>" step="0.01" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="weekend_price" class="form-label">Weekend Price</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><?php echo CURRENCY_SYMBOL; ?></span>
                                        <input type="number" class="form-control" id="weekend_price" name="weekend_price" 
                                               value="<?php echo $_POST['weekend_price'] ?? ''; ?>" step="0.01" min="0">
                                    </div>
                                    <div class="form-text">Optional. Leave blank if same as base price.</div>
                                </div>
                            </div>
                            
                            <!-- Occupancy -->
                            <h5 class="mb-3">Occupancy</h5>
                            <div class="row mb-4">
                                <div class="col-md-4 mb-3">
                                    <label for="max_adults" class="form-label">Max Adults <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="max_adults" name="max_adults" 
                                           value="<?php echo $_POST['max_adults'] ?? 2; ?>" min="1" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="max_children" class="form-label">Max Children</label>
                                    <input type="number" class="form-control" id="max_children" name="max_children" 
                                           value="<?php echo $_POST['max_children'] ?? 0; ?>" min="0">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="max_occupancy" class="form-label">Max Occupancy <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="max_occupancy" name="max_occupancy" 
                                           value="<?php echo $_POST['max_occupancy'] ?? 2; ?>" min="1" required>
                                </div>
                            </div>
                            
                            <!-- Room Details -->
                            <h5 class="mb-3">Room Details</h5>
                            <div class="row mb-4">
                                <div class="col-md-4 mb-3">
                                    <label for="room_size" class="form-label">Room Size (sq ft)</label>
                                    <input type="number" class="form-control" id="room_size" name="room_size" 
                                           value="<?php echo $_POST['room_size'] ?? ''; ?>" step="0.01" min="0">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="bed_type" class="form-label">Bed Type</label>
                                    <select class="form-select" id="bed_type" name="bed_type">
                                        <option value="">Select Bed Type</option>
                                        <option value="King" <?php echo ($_POST['bed_type'] ?? '') === 'King' ? 'selected' : ''; ?>>King</option>
                                        <option value="Queen" <?php echo ($_POST['bed_type'] ?? '') === 'Queen' ? 'selected' : ''; ?>>Queen</option>
                                        <option value="Double" <?php echo ($_POST['bed_type'] ?? '') === 'Double' ? 'selected' : ''; ?>>Double</option>
                                        <option value="Twin" <?php echo ($_POST['bed_type'] ?? '') === 'Twin' ? 'selected' : ''; ?>>Twin</option>
                                        <option value="Single" <?php echo ($_POST['bed_type'] ?? '') === 'Single' ? 'selected' : ''; ?>>Single</option>
                                        <option value="Sofa Bed" <?php echo ($_POST['bed_type'] ?? '') === 'Sofa Bed' ? 'selected' : ''; ?>>Sofa Bed</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="num_beds" class="form-label">Number of Beds</label>
                                    <input type="number" class="form-control" id="num_beds" name="num_beds" 
                                           value="<?php echo $_POST['num_beds'] ?? 1; ?>" min="1">
                                </div>
                            </div>
                            
                            <!-- Amenities -->
                            <h5 class="mb-3">Amenities</h5>
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="row">
                                        <?php foreach ($amenities as $amenity): ?>
                                        <div class="col-md-4 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       id="amenity_<?php echo $amenity['id']; ?>" 
                                                       name="amenities[]" 
                                                       value="<?php echo $amenity['id']; ?>"
                                                       <?php echo in_array($amenity['id'], $_POST['amenities'] ?? []) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="amenity_<?php echo $amenity['id']; ?>">
                                                    <?php if ($amenity['icon']): ?>
                                                    <i class="bi <?php echo htmlspecialchars($amenity['icon']); ?>"></i>
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($amenity['name']); ?>
                                                </label>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Images -->
                            <h5 class="mb-3">Images</h5>
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label for="images" class="form-label">Upload Images</label>
                                        <input type="file" class="form-control" id="images" name="images[]" 
                                               accept="image/jpeg,image/jpg,image/png,image/gif" multiple>
                                        <div class="form-text">You can upload multiple images. First image will be set as featured. Max size: <?php echo formatFileSize(UPLOAD_MAX_SIZE); ?>.</div>
                                    </div>
                                    <div id="imagePreview" class="row"></div>
                                </div>
                            </div>
                            
                            <!-- Status -->
                            <h5 class="mb-3">Status</h5>
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                        <label class="form-check-label" for="is_active">Active</label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Actions -->
                            <div class="d-flex justify-content-between">
                                <a href="<?php echo APP_URL; ?>/modules/room-types/index.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Create Room Type
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Image preview
document.getElementById('images').addEventListener('change', function(e) {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = '';
    
    const files = e.target.files;
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const col = document.createElement('div');
            col.className = 'col-md-3 mb-3';
            col.innerHTML = `
                <div class="image-preview active">
                    <img src="${e.target.result}" alt="Preview">
                    <span class="badge bg-primary">${i === 0 ? 'Featured' : ''}</span>
                </div>
            `;
            preview.appendChild(col);
        };
        
        reader.readAsDataURL(file);
    }
});
</script>

<style>
.image-preview {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid #dee2e6;
}

.image-preview img {
    width: 100%;
    height: 150px;
    object-fit: cover;
}

.image-preview .badge {
    position: absolute;
    top: 8px;
    right: 8px;
}
</style>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
