<?php
/**
 * Hotel & Resort Management System
 * Rooms Module - View Page
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

$page_title = 'View Room';
$page_description = 'View room details';

// Breadcrumb items
$breadcrumb_items = [
    ['label' => 'Dashboard', 'url' => APP_URL . '/dashboard.php'],
    ['label' => 'Rooms', 'url' => APP_URL . '/modules/rooms/index.php'],
    ['label' => 'View Room', 'active' => true]
];

$db = getDB();

// Get room ID
$roomId = (int)($_GET['id'] ?? 0);

if (!$roomId) {
    redirect(APP_URL . '/modules/rooms/index.php');
}

// Get room details
$stmt = $db->prepare("
    SELECT r.*, 
           p.name as property_name, p.code as property_code,
           b.name as building_name, b.code as building_code,
           f.name as floor_name, f.floor_number,
           rc.name as room_category_name, rc.icon_class as category_icon, rc.color as category_color,
           rt.name as room_type_name, rt.icon_class as type_icon, rt.color as type_color,
           bt.name as bed_type_name,
           vt.name as view_type_name
    FROM rooms r
    INNER JOIN properties p ON r.property_id = p.id
    INNER JOIN buildings b ON r.building_id = b.id
    INNER JOIN floors f ON r.floor_id = f.id
    LEFT JOIN master_items rc ON r.room_category_id = rc.id
    LEFT JOIN master_items rt ON r.room_type_id = rt.id
    LEFT JOIN master_items bt ON r.bed_type_id = bt.id
    LEFT JOIN master_items vt ON r.view_type_id = vt.id
    WHERE r.id = ? AND r.deleted_at IS NULL
");
$stmt->execute([$roomId]);
$room = $stmt->fetch();

if (!$room) {
    redirect(APP_URL . '/modules/rooms/index.php');
}

// Get room amenities
$stmt = $db->prepare("
    SELECT mi.id, mi.name, mi.icon_class, mi.color 
    FROM room_amenities ra 
    INNER JOIN master_items mi ON ra.amenity_id = mi.id 
    WHERE ra.room_id = ?
");
$stmt->execute([$roomId]);
$amenities = $stmt->fetchAll();

// Get room notes
$stmt = $db->prepare("
    SELECT rn.*, u.first_name, u.last_name 
    FROM room_notes rn 
    INNER JOIN users u ON rn.created_by = u.id 
    WHERE rn.room_id = ? 
    ORDER BY rn.created_at DESC
");
$stmt->execute([$roomId]);
$notes = $stmt->fetchAll();

// Get custom field values
$customFieldValues = [];
$stmt = $db->prepare("
    SELECT cfv.value, cf.field_label, cf.field_type, cf.options 
    FROM custom_field_values cfv 
    INNER JOIN custom_fields cf ON cfv.field_id = cf.id 
    WHERE cfv.entity_id = ? AND cfv.entity_type = 'room' AND cf.is_active = 1
");
$stmt->execute([$roomId]);
while ($row = $stmt->fetch()) {
    $customFieldValues[] = $row;
}

// Status badge colors
$statusColors = [
    'available' => 'success',
    'occupied' => 'danger',
    'reserved' => 'warning',
    'maintenance' => 'secondary',
    'cleaning' => 'info',
    'out_of_service' => 'dark'
];

$statusLabels = [
    'available' => 'Available',
    'occupied' => 'Occupied',
    'reserved' => 'Reserved',
    'maintenance' => 'Maintenance',
    'cleaning' => 'Cleaning',
    'out_of_service' => 'Out of Service'
];
?>
<?php require_once APP_ROOT . '/includes/header.php'; ?>

<div class="main-wrapper">
    <?php require_once APP_ROOT . '/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php require_once APP_ROOT . '/includes/topbar.php'; ?>
        
        <main class="content-area">
            <div class="container-fluid">
                <div class="page-header">
                    <h1 class="page-title">View Room</h1>
                    <p class="page-subtitle">Room details for <?php echo htmlspecialchars($room['room_number']); ?></p>
                </div>
                
                <div class="row">
                    <div class="col-lg-8">
                        <!-- Room Details Card -->
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Room Information</h5>
                                <span class="badge bg-<?php echo $statusColors[$room['status']] ?? 'secondary'; ?> fs-6">
                                    <?php echo $statusLabels[$room['status']] ?? ucfirst($room['status']); ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="text-muted small">Room Number</label>
                                        <div class="fw-bold fs-5"><?php echo htmlspecialchars($room['room_number']); ?></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="text-muted small">Room Name</label>
                                        <div><?php echo htmlspecialchars($room['room_name'] ?? '-'); ?></div>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <h6 class="mb-3">Location</h6>
                                <div class="row">
                                    <div class="col-md-4 mb-2">
                                        <label class="text-muted small">Property</label>
                                        <div><?php echo htmlspecialchars($room['property_name']); ?></div>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="text-muted small">Building</label>
                                        <div><?php echo htmlspecialchars($room['building_name']); ?></div>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="text-muted small">Floor</label>
                                        <div><?php echo htmlspecialchars($room['floor_name']); ?> (Floor <?php echo $room['floor_number']; ?>)</div>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <h6 class="mb-3">Room Type & Category</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <label class="text-muted small">Room Category</label>
                                        <div>
                                            <?php if ($room['room_category_name']): ?>
                                                <span class="badge" style="background-color: <?php echo $room['category_color'] ?? '#667eea'; ?>; color: white;">
                                                    <?php if ($room['category_icon']): ?>
                                                        <i class="<?php echo htmlspecialchars($room['category_icon']); ?> me-1"></i>
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($room['room_category_name']); ?>
                                                </span>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="text-muted small">Room Type</label>
                                        <div>
                                            <?php if ($room['room_type_name']): ?>
                                                <span class="badge" style="background-color: <?php echo $room['type_color'] ?? '#667eea'; ?>; color: white;">
                                                    <?php if ($room['type_icon']): ?>
                                                        <i class="<?php echo htmlspecialchars($room['type_icon']); ?> me-1"></i>
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($room['room_type_name']); ?>
                                                </span>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mt-2">
                                    <div class="col-md-6 mb-2">
                                        <label class="text-muted small">Bed Type</label>
                                        <div><?php echo htmlspecialchars($room['bed_type_name'] ?? '-'); ?></div>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="text-muted small">View Type</label>
                                        <div><?php echo htmlspecialchars($room['view_type_name'] ?? '-'); ?></div>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <h6 class="mb-3">Capacity & Size</h6>
                                <div class="row">
                                    <div class="col-md-3 mb-2">
                                        <label class="text-muted small">Max Adults</label>
                                        <div class="fw-bold"><?php echo $room['max_adults']; ?></div>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label class="text-muted small">Max Children</label>
                                        <div class="fw-bold"><?php echo $room['max_children']; ?></div>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label class="text-muted small">Room Size</label>
                                        <div>
                                            <?php if ($room['room_size']): ?>
                                                <?php echo number_format($room['room_size'], 2); ?> <?php echo strtoupper($room['size_unit']); ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label class="text-muted small">Base Price</label>
                                        <div class="fw-bold fs-5 text-primary"><?php echo number_format($room['base_price'], 2); ?></div>
                                    </div>
                                </div>
                                
                                <?php if ($room['description']): ?>
                                    <hr>
                                    <h6 class="mb-3">Description</h6>
                                    <p><?php echo nl2br(htmlspecialchars($room['description'])); ?></p>
                                <?php endif; ?>
                                
                                <hr>
                                
                                <div class="d-flex gap-2">
                                    <a href="<?php echo APP_URL; ?>/modules/rooms/edit.php?id=<?php echo $roomId; ?>" class="btn btn-primary">
                                        <i class="bi bi-pencil me-2"></i>Edit Room
                                    </a>
                                    <a href="<?php echo APP_URL; ?>/modules/rooms/gallery.php?room_id=<?php echo $roomId; ?>" class="btn btn-outline-primary">
                                        <i class="bi bi-images me-2"></i>Gallery
                                    </a>
                                    <a href="<?php echo APP_URL; ?>/modules/rooms/index.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left me-2"></i>Back to Rooms
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Amenities Card -->
                        <?php if (!empty($amenities)): ?>
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Amenities</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php foreach ($amenities as $amenity): ?>
                                            <div class="col-md-4 col-sm-6 mb-3">
                                                <div class="d-flex align-items-center">
                                                    <?php if ($amenity['icon_class']): ?>
                                                        <i class="<?php echo htmlspecialchars($amenity['icon_class']); ?> me-2" style="color: <?php echo htmlspecialchars($amenity['color'] ?? '#667eea'); ?>;"></i>
                                                    <?php endif; ?>
                                                    <span><?php echo htmlspecialchars($amenity['name']); ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Custom Fields Card -->
                        <?php if (!empty($customFieldValues)): ?>
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Custom Fields</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php foreach ($customFieldValues as $cf): ?>
                                            <div class="col-md-6 mb-3">
                                                <label class="text-muted small"><?php echo htmlspecialchars($cf['field_label']); ?></label>
                                                <div>
                                                    <?php 
                                                    $value = $cf['value'];
                                                    if (in_array($cf['field_type'], ['select', 'radio']) && $value) {
                                                        $options = json_decode($cf['options'], true);
                                                        echo htmlspecialchars($options[$value] ?? $value);
                                                    } elseif ($cf['field_type'] === 'checkbox' && $value) {
                                                        echo $value ? 'Yes' : 'No';
                                                    } elseif ($cf['field_type'] === 'multi_select' && $value) {
                                                        $options = json_decode($cf['options'], true);
                                                        $values = json_decode($value, true);
                                                        $labels = [];
                                                        foreach ($values as $v) {
                                                            $labels[] = $options[$v] ?? $v;
                                                        }
                                                        echo htmlspecialchars(implode(', ', $labels));
                                                    } else {
                                                        echo htmlspecialchars($value);
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-lg-4">
                        <!-- Room Status Card -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Room Status</h5>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-3">
                                    <div class="display-1 mb-2">
                                        <i class="bi bi-<?php 
                                        echo match($room['status']) {
                                            'available' => 'check-circle-fill text-success',
                                            'occupied' => 'person-fill text-danger',
                                            'reserved' => 'clock-fill text-warning',
                                            'maintenance' => 'tools text-secondary',
                                            'cleaning' => 'broom text-info',
                                            'out_of_service' => 'x-circle-fill text-dark'
                                        }; 
                                        ?>"></i>
                                    </div>
                                    <h4 class="fw-bold"><?php echo $statusLabels[$room['status']] ?? ucfirst($room['status']); ?></h4>
                                </div>
                                
                                <hr>
                                
                                <div class="mb-3">
                                    <label class="text-muted small">Active Status</label>
                                    <div>
                                        <?php if ($room['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inactive</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="text-muted small">Created</label>
                                    <div><?php echo date('M d, Y H:i', strtotime($room['created_at'])); ?></div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="text-muted small">Last Updated</label>
                                    <div><?php echo date('M d, Y H:i', strtotime($room['updated_at'])); ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Notes Card -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Notes</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($notes)): ?>
                                    <?php foreach ($notes as $note): ?>
                                        <div class="alert alert-secondary mb-2">
                                            <small class="text-muted">
                                                <?php echo date('M d, Y H:i', strtotime($note['created_at'])); ?>
                                                - <?php echo htmlspecialchars($note['first_name'] . ' ' . $note['last_name']); ?>
                                            </small>
                                            <p class="mb-0 mt-1"><?php echo htmlspecialchars($note['note']); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted text-center mb-0">No notes added yet.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
