<?php
/**
 * Hotel & Resort Management System
 * Room Types Module - Edit Page
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
requirePermission('room_types.edit');

$page_title = 'Edit Room Type';
$page_description = 'Edit room type configuration';

// Breadcrumb items
$breadcrumb_items = [
    ['label' => 'Dashboard', 'url' => APP_URL . '/dashboard.php'],
    ['label' => 'Room Types', 'url' => APP_URL . '/modules/room-types/index.php'],
    ['label' => 'Edit Room Type', 'active' => true]
];

$error = '';
$success = '';

$db = getDB();

// Get room type ID
$roomTypeId = (int)($_GET['id'] ?? 0);

if (!$roomTypeId) {
    redirect(APP_URL . '/modules/room-types/index.php');
}

// Get room type data
$stmt = $db->prepare("SELECT * FROM room_types WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$roomTypeId]);
$roomType = $stmt->fetch();

if (!$roomType) {
    redirect(APP_URL . '/modules/room-types/index.php');
}

// Get available amenities
$stmt = $db->query("SELECT * FROM amenities WHERE is_active = 1 ORDER BY display_order ASC, name ASC");
$amenities = $stmt->fetchAll();

// Get room type's current amenities
$stmt = $db->prepare("SELECT amenity_id FROM room_type_amenities WHERE room_type_id = ?");
$stmt->execute([$roomTypeId]);
$currentAmenities = array_column($stmt->fetchAll(), 'amenity_id');

// Get room type's images
$stmt = $db->prepare("SELECT * FROM room_type_images WHERE room_type_id = ? ORDER BY display_order ASC");
$stmt->execute([$roomTypeId]);
$images = $stmt->fetchAll();

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'] ?? 'update';
        
        if ($action === 'update') {
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
                // Check if code already exists (excluding current room type)
                $stmt = $db->prepare("SELECT id FROM room_types WHERE code = ? AND id != ?");
                $stmt->execute([$code, $roomTypeId]);
                if ($stmt->fetch()) {
                    $error = 'Room type code already exists.';
                } else {
                    try {
                        $db->beginTransaction();
                        
                        // Store old values for activity log
                        $oldValues = [
                            'name' => $roomType['name'],
                            'code' => $roomType['code'],
                            'base_price' => $roomType['base_price'],
                            'is_active' => $roomType['is_active']
                        ];
                        
                        // Update room type
                        $stmt = $db->prepare("
                            UPDATE room_types 
                            SET name = ?, code = ?, description = ?, base_price = ?, weekend_price = ?, 
                                max_adults = ?, max_children = ?, max_occupancy = ?, room_size = ?, 
                                bed_type = ?, num_beds = ?, is_active = ?, updated_at = NOW()
                            WHERE id = ?
                        ");
                        $stmt->execute([
                            $name, $code, $description, $basePrice, 
                            $weekendPrice ?: null, $maxAdults, $maxChildren, 
                            $maxOccupancy, $roomSize ?: null, $bedType ?: null, 
                            $numBeds, $isActive, $roomTypeId
                        ]);
                        
                        // Update amenities - delete all and re-insert
                        $stmt = $db->prepare("DELETE FROM room_type_amenities WHERE room_type_id = ?");
                        $stmt->execute([$roomTypeId]);
                        
                        if (!empty($selectedAmenities)) {
                            foreach ($selectedAmenities as $amenityId) {
                                $stmt = $db->prepare("
                                    INSERT INTO room_type_amenities (room_type_id, amenity_id)
                                    VALUES (?, ?)
                                ");
                                $stmt->execute([$roomTypeId, (int)$amenityId]);
                            }
                        }
                        
                        // Handle new image uploads
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
                                            $isFeatured = (count($images) === 0 && $i === 0) ? 1 : 0;
                                            
                                            $stmt = $db->prepare("
                                                INSERT INTO room_type_images 
                                                (room_type_id, image_path, is_featured, display_order)
                                                VALUES (?, ?, ?, ?)
                                            ");
                                            $stmt->execute([$roomTypeId, $imagePath, $isFeatured, count($images) + $i]);
                                        }
                                    }
                                }
                            }
                        }
                        
                        $db->commit();
                        
                        // Refresh room type data
                        $stmt = $db->prepare("SELECT * FROM room_types WHERE id = ?");
                        $stmt->execute([$roomTypeId]);
                        $roomType = $stmt->fetch();
                        
                        // Refresh amenities
                        $stmt = $db->prepare("SELECT amenity_id FROM room_type_amenities WHERE room_type_id = ?");
                        $stmt->execute([$roomTypeId]);
                        $currentAmenities = array_column($stmt->fetchAll(), 'amenity_id');
                        
                        // Refresh images
                        $stmt = $db->prepare("SELECT * FROM room_type_images WHERE room_type_id = ? ORDER BY display_order ASC");
                        $stmt->execute([$roomTypeId]);
                        $images = $stmt->fetchAll();
                        
                        $newValues = [
                            'name' => $name,
                            'code' => $code,
                            'base_price' => $basePrice,
                            'is_active' => $isActive
                        ];
                        
                        logActivity('update', 'room_types', "Updated room type: {$name}", $oldValues, $newValues);
                        
                        $success = 'Room type updated successfully.';
                    } catch (PDOException $e) {
                        $db->rollBack();
                        if (DEBUG_MODE) {
                            error_log("Update room type error: " . $e->getMessage());
                        }
                        $error = 'An error occurred while updating the room type.';
                    }
                }
            }
        } elseif ($action === 'delete_image') {
            $imageId = (int)($_POST['image_id'] ?? 0);
            
            try {
                // Get image path
                $stmt = $db->prepare("SELECT image_path FROM room_type_images WHERE id = ? AND room_type_id = ?");
                $stmt->execute([$imageId, $roomTypeId]);
                $image = $stmt->fetch();
                
                if ($image) {
                    // Delete file
                    if (file_exists(APP_ROOT . '/' . $image['image_path'])) {
                        unlink(APP_ROOT . '/' . $image['image_path']);
                    }
                    
                    // Delete from database
                    $stmt = $db->prepare("DELETE FROM room_type_images WHERE id = ?");
                    $stmt->execute([$imageId]);
                    
                    logActivity('delete_image', 'room_types', "Deleted image from room type: {$roomType['name']}", null, ['image_id' => $imageId]);
                    
                    $success = 'Image deleted successfully.';
                    
                    // Refresh images
                    $stmt = $db->prepare("SELECT * FROM room_type_images WHERE room_type_id = ? ORDER BY display_order ASC");
                    $stmt->execute([$roomTypeId]);
                    $images = $stmt->fetchAll();
                }
            } catch (PDOException $e) {
                if (DEBUG_MODE) {
                    error_log("Delete image error: " . $e->getMessage());
                }
                $error = 'An error occurred while deleting the image.';
            }
        } elseif ($action === 'set_featured') {
            $imageId = (int)($_POST['image_id'] ?? 0);
            
            try {
                // Remove featured from all images
                $stmt = $db->prepare("UPDATE room_type_images SET is_featured = 0 WHERE room_type_id = ?");
                $stmt->execute([$roomTypeId]);
                
                // Set featured on selected image
                $stmt = $db->prepare("UPDATE room_type_images SET is_featured = 1 WHERE id = ?");
                $stmt->execute([$imageId]);
                
                logActivity('set_featured', 'room_types', "Set featured image for room type: {$roomType['name']}", null, ['image_id' => $imageId]);
                
                $success = 'Featured image updated successfully.';
                
                // Refresh images
                $stmt = $db->prepare("SELECT * FROM room_type_images WHERE room_type_id = ? ORDER BY display_order ASC");
                $stmt->execute([$roomTypeId]);
                $images = $stmt->fetchAll();
            } catch (PDOException $e) {
                if (DEBUG_MODE) {
                    error_log("Set featured error: " . $e->getMessage());
                }
                $error = 'An error occurred while updating the featured image.';
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
                    <h1 class="page-title">Edit Room Type</h1>
                    <p class="page-subtitle">Edit room type configuration</p>
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
                            <input type="hidden" name="action" value="update">
                            
                            <!-- Basic Information -->
                            <h5 class="mb-3">Basic Information</h5>
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Room Type Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($roomType['name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="code" name="code" 
                                           value="<?php echo htmlspecialchars($roomType['code']); ?>" required>
                                    <div class="form-text">Use uppercase letters, numbers, hyphens, and underscores only.</div>
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($roomType['description'] ?? ''); ?></textarea>
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
                                               value="<?php echo $roomType['base_price']; ?>" step="0.01" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="weekend_price" class="form-label">Weekend Price</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><?php echo CURRENCY_SYMBOL; ?></span>
                                        <input type="number" class="form-control" id="weekend_price" name="weekend_price" 
                                               value="<?php echo $roomType['weekend_price'] ?? ''; ?>" step="0.01" min="0">
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
                                           value="<?php echo $roomType['max_adults']; ?>" min="1" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="max_children" class="form-label">Max Children</label>
                                    <input type="number" class="form-control" id="max_children" name="max_children" 
                                           value="<?php echo $roomType['max_children']; ?>" min="0">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="max_occupancy" class="form-label">Max Occupancy <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="max_occupancy" name="max_occupancy" 
                                           value="<?php echo $roomType['max_occupancy']; ?>" min="1" required>
                                </div>
                            </div>
                            
                            <!-- Room Details -->
                            <h5 class="mb-3">Room Details</h5>
                            <div class="row mb-4">
                                <div class="col-md-4 mb-3">
                                    <label for="room_size" class="form-label">Room Size (sq ft)</label>
                                    <input type="number" class="form-control" id="room_size" name="room_size" 
                                           value="<?php echo $roomType['room_size'] ?? ''; ?>" step="0.01" min="0">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="bed_type" class="form-label">Bed Type</label>
                                    <select class="form-select" id="bed_type" name="bed_type">
                                        <option value="">Select Bed Type</option>
                                        <option value="King" <?php echo ($roomType['bed_type'] ?? '') === 'King' ? 'selected' : ''; ?>>King</option>
                                        <option value="Queen" <?php echo ($roomType['bed_type'] ?? '') === 'Queen' ? 'selected' : ''; ?>>Queen</option>
                                        <option value="Double" <?php echo ($roomType['bed_type'] ?? '') === 'Double' ? 'selected' : ''; ?>>Double</option>
                                        <option value="Twin" <?php echo ($roomType['bed_type'] ?? '') === 'Twin' ? 'selected' : ''; ?>>Twin</option>
                                        <option value="Single" <?php echo ($roomType['bed_type'] ?? '') === 'Single' ? 'selected' : ''; ?>>Single</option>
                                        <option value="Sofa Bed" <?php echo ($roomType['bed_type'] ?? '') === 'Sofa Bed' ? 'selected' : ''; ?>>Sofa Bed</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="num_beds" class="form-label">Number of Beds</label>
                                    <input type="number" class="form-control" id="num_beds" name="num_beds" 
                                           value="<?php echo $roomType['num_beds']; ?>" min="1">
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
                                                       <?php echo in_array($amenity['id'], $currentAmenities) ? 'checked' : ''; ?>>
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
                            
                            <!-- Existing Images -->
                            <?php if (!empty($images)): ?>
                            <h5 class="mb-3">Existing Images</h5>
                            <div class="row mb-4">
                                <?php foreach ($images as $image): ?>
                                <div class="col-md-3 mb-3">
                                    <div class="image-preview-card">
                                        <img src="<?php echo htmlspecialchars($image['image_path']); ?>" alt="Room Type Image">
                                        <?php if ($image['is_featured']): ?>
                                        <span class="badge bg-primary featured-badge">Featured</span>
                                        <?php endif; ?>
                                        <div class="image-actions">
                                            <?php if (!$image['is_featured']): ?>
                                            <form method="POST" style="display: inline;">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="action" value="set_featured">
                                                <input type="hidden" name="image_id" value="<?php echo $image['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-primary" title="Set as Featured">
                                                    <i class="bi bi-star"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this image?');">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="action" value="delete_image">
                                                <input type="hidden" name="image_id" value="<?php echo $image['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            
                            <!-- New Images -->
                            <h5 class="mb-3">Upload New Images</h5>
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label for="images" class="form-label">Upload Images</label>
                                        <input type="file" class="form-control" id="images" name="images[]" 
                                               accept="image/jpeg,image/jpg,image/png,image/gif" multiple>
                                        <div class="form-text">You can upload multiple images. Max size: <?php echo formatFileSize(UPLOAD_MAX_SIZE); ?>.</div>
                                    </div>
                                    <div id="imagePreview" class="row"></div>
                                </div>
                            </div>
                            
                            <!-- Status -->
                            <h5 class="mb-3">Status</h5>
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" <?php echo $roomType['is_active'] ? 'checked' : ''; ?>>
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
                                    <i class="bi bi-save"></i> Update Room Type
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
                <div class="image-preview">
                    <img src="${e.target.result}" alt="Preview">
                </div>
            `;
            preview.appendChild(col);
        };
        
        reader.readAsDataURL(file);
    }
});
</script>

<style>
.image-preview-card {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid #dee2e6;
}

.image-preview-card img {
    width: 100%;
    height: 150px;
    object-fit: cover;
}

.featured-badge {
    position: absolute;
    top: 8px;
    right: 8px;
}

.image-actions {
    position: absolute;
    bottom: 8px;
    right: 8px;
    display: flex;
    gap: 4px;
}

.image-preview {
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid #dee2e6;
}

.image-preview img {
    width: 100%;
    height: 150px;
    object-fit: cover;
}
</style>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
