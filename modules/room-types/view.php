<?php
/**
 * Hotel & Resort Management System
 * Room Types Module - View Page
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
requirePermission('room_types.view');

$page_title = 'View Room Type';
$page_description = 'View room type details';

// Breadcrumb items
$breadcrumb_items = [
    ['label' => 'Dashboard', 'url' => APP_URL . '/dashboard.php'],
    ['label' => 'Room Types', 'url' => APP_URL . '/modules/room-types/index.php'],
    ['label' => 'View Room Type', 'active' => true]
];

$db = getDB();

// Get room type ID
$roomTypeId = (int)($_GET['id'] ?? 0);

if (!$roomTypeId) {
    redirect(APP_URL . '/modules/room-types/index.php');
}

// Get room type data
$stmt = $db->prepare("
    SELECT rt.*, 
           COUNT(DISTINCT r.id) as room_count
    FROM room_types rt
    LEFT JOIN rooms r ON rt.id = r.room_type_id AND r.deleted_at IS NULL
    WHERE rt.id = ? AND rt.deleted_at IS NULL
    GROUP BY rt.id
");
$stmt->execute([$roomTypeId]);
$roomType = $stmt->fetch();

if (!$roomType) {
    redirect(APP_URL . '/modules/room-types/index.php');
}

// Get room type's amenities
$stmt = $db->prepare("
    SELECT a.* 
    FROM amenities a
    INNER JOIN room_type_amenities rta ON a.id = rta.amenity_id
    WHERE rta.room_type_id = ?
    ORDER BY a.display_order ASC, a.name ASC
");
$stmt->execute([$roomTypeId]);
$amenities = $stmt->fetchAll();

// Get room type's images
$stmt = $db->prepare("SELECT * FROM room_type_images WHERE room_type_id = ? ORDER BY is_featured DESC, display_order ASC");
$stmt->execute([$roomTypeId]);
$images = $stmt->fetchAll();
?>
<?php require_once APP_ROOT . '/includes/header.php'; ?>

<div class="main-wrapper">
    <?php require_once APP_ROOT . '/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php require_once APP_ROOT . '/includes/topbar.php'; ?>
        
        <main class="content-area">
            <div class="container-fluid">
                <div class="page-header">
                    <h1 class="page-title">View Room Type</h1>
                    <p class="page-subtitle">View room type details and configuration</p>
                </div>
                
                <div class="row">
                    <div class="col-lg-8">
                        <!-- Room Type Details -->
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($roomType['name']); ?></h5>
                                <div>
                                    <?php if ($roomType['is_active']): ?>
                                    <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <strong>Code:</strong>
                                        <span class="ms-2"><code><?php echo htmlspecialchars($roomType['code']); ?></code></span>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>UUID:</strong>
                                        <span class="ms-2"><?php echo htmlspecialchars($roomType['uuid']); ?></span>
                                    </div>
                                </div>
                                
                                <?php if ($roomType['description']): ?>
                                <div class="mb-3">
                                    <strong>Description:</strong>
                                    <p class="mt-1"><?php echo nl2br(htmlspecialchars($roomType['description'])); ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <hr>
                                
                                <h6 class="mb-3">Pricing</h6>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <strong>Base Price:</strong>
                                        <span class="ms-2"><?php echo formatCurrency($roomType['base_price']); ?></span>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Weekend Price:</strong>
                                        <span class="ms-2"><?php echo $roomType['weekend_price'] ? formatCurrency($roomType['weekend_price']) : 'Same as base'; ?></span>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <h6 class="mb-3">Occupancy</h6>
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <strong>Max Adults:</strong>
                                        <span class="ms-2"><?php echo $roomType['max_adults']; ?></span>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Max Children:</strong>
                                        <span class="ms-2"><?php echo $roomType['max_children']; ?></span>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Max Occupancy:</strong>
                                        <span class="ms-2"><?php echo $roomType['max_occupancy']; ?></span>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <h6 class="mb-3">Room Details</h6>
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <strong>Room Size:</strong>
                                        <span class="ms-2"><?php echo $roomType['room_size'] ? $roomType['room_size'] . ' sq ft' : 'N/A'; ?></span>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Bed Type:</strong>
                                        <span class="ms-2"><?php echo $roomType['bed_type'] ?: 'N/A'; ?></span>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Number of Beds:</strong>
                                        <span class="ms-2"><?php echo $roomType['num_beds']; ?></span>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <h6 class="mb-3">Statistics</h6>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <strong>Total Rooms:</strong>
                                        <span class="ms-2"><?php echo $roomType['room_count']; ?></span>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Created:</strong>
                                        <span class="ms-2"><?php echo formatDateTime($roomType['created_at']); ?></span>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Last Updated:</strong>
                                        <span class="ms-2"><?php echo formatDateTime($roomType['updated_at']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Amenities -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Amenities</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($amenities)): ?>
                                <div class="row">
                                    <?php foreach ($amenities as $amenity): ?>
                                    <div class="col-md-4 mb-2">
                                        <div class="amenity-item">
                                            <?php if ($amenity['icon']): ?>
                                            <i class="bi <?php echo htmlspecialchars($amenity['icon']); ?> me-2"></i>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($amenity['name']); ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php else: ?>
                                <p class="text-muted mb-0">No amenities configured.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Images -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Images</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($images)): ?>
                                <div class="row">
                                    <?php foreach ($images as $image): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="image-card">
                                            <img src="<?php echo htmlspecialchars($image['image_path']); ?>" alt="Room Type Image">
                                            <?php if ($image['is_featured']): ?>
                                            <span class="badge bg-primary featured-badge">Featured</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php else: ?>
                                <p class="text-muted mb-0">No images uploaded.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <!-- Actions -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <?php if (hasPermission('room_types.edit')): ?>
                                    <a href="<?php echo APP_URL; ?>/modules/room-types/edit.php?id=<?php echo $roomType['id']; ?>" class="btn btn-primary">
                                        <i class="bi bi-pencil"></i> Edit Room Type
                                    </a>
                                    <?php endif; ?>
                                    <a href="<?php echo APP_URL; ?>/modules/room-types/index.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left"></i> Back to List
                                    </a>
                                    <?php if (hasPermission('room_types.delete')): ?>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this room type?');" class="mt-2">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="room_type_id" value="<?php echo $roomType['id']; ?>">
                                        <button type="submit" class="btn btn-danger w-100">
                                            <i class="bi bi-trash"></i> Delete Room Type
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Info -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Quick Info</h5>
                            </div>
                            <div class="card-body">
                                <div class="info-item">
                                    <span class="info-label">ID:</span>
                                    <span class="info-value"><?php echo $roomType['id']; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Code:</span>
                                    <span class="info-value"><code><?php echo htmlspecialchars($roomType['code']); ?></code></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Status:</span>
                                    <span class="info-value">
                                        <?php if ($roomType['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Amenities:</span>
                                    <span class="info-value"><?php echo count($amenities); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Images:</span>
                                    <span class="info-value"><?php echo count($images); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
.amenity-item {
    padding: 8px 12px;
    background-color: #f8f9fa;
    border-radius: 6px;
    display: flex;
    align-items: center;
}

.image-card {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid #dee2e6;
}

.image-card img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.featured-badge {
    position: absolute;
    top: 8px;
    right: 8px;
}

.info-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #dee2e6;
}

.info-item:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 500;
    color: #6c757d;
}

.info-value {
    color: #212529;
}
</style>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
