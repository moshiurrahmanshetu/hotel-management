<?php
/**
 * Hotel & Resort Management System
 * Room Rates Module - Create Page
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

$page_title = 'Add Room Rate';
$page_description = 'Add a new room rate';

// Breadcrumb items
$breadcrumb_items = [
    ['label' => 'Dashboard', 'url' => APP_URL . '/dashboard.php'],
    ['label' => 'Room Rates', 'url' => APP_URL . '/modules/room-rates/index.php'],
    ['label' => 'Add Room Rate', 'active' => true]
];

$error = '';
$success = '';

$db = getDB();

// Get rooms for dropdown
$stmt = $db->query("
    SELECT r.id, r.room_number, r.room_name, p.name as property_name 
    FROM rooms r 
    INNER JOIN properties p ON r.property_id = p.id 
    WHERE r.deleted_at IS NULL AND r.is_active = 1 
    ORDER BY p.name, r.room_number
");
$rooms = $stmt->fetchAll();

// Get rate plans for dropdown
$stmt = $db->query("SELECT id, name, code FROM rate_plans WHERE deleted_at IS NULL AND is_active = 1 ORDER BY name ASC");
$ratePlans = $stmt->fetchAll();

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $roomId = (int)($_POST['room_id'] ?? 0);
        $ratePlanId = (int)($_POST['rate_plan_id'] ?? 0);
        $basePrice = (float)($_POST['base_price'] ?? 0);
        $weekendPrice = (float)($_POST['weekend_price'] ?? 0);
        $extraAdultPrice = (float)($_POST['extra_adult_price'] ?? 0);
        $extraChildPrice = (float)($_POST['extra_child_price'] ?? 0);
        $taxIncluded = isset($_POST['tax_included']) ? 1 : 0;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        // Validation
        if (!$roomId) {
            $error = 'Please select a room.';
        } elseif (!$ratePlanId) {
            $error = 'Please select a rate plan.';
        } elseif ($basePrice < 0) {
            $error = 'Base price cannot be negative.';
        } elseif ($weekendPrice < 0) {
            $error = 'Weekend price cannot be negative.';
        } elseif ($extraAdultPrice < 0) {
            $error = 'Extra adult price cannot be negative.';
        } elseif ($extraChildPrice < 0) {
            $error = 'Extra child price cannot be negative.';
        } else {
            // Check if room already has this rate plan
            $stmt = $db->prepare("SELECT id FROM room_rates WHERE room_id = ? AND rate_plan_id = ? AND deleted_at IS NULL");
            $stmt->execute([$roomId, $ratePlanId]);
            if ($stmt->fetch()) {
                $error = 'This room already has this rate plan assigned.';
            }
        }
        
        if (!$error) {
            try {
                $uuid = generateUUID();
                $stmt = $db->prepare("
                    INSERT INTO room_rates (uuid, room_id, rate_plan_id, base_price, weekend_price, extra_adult_price, extra_child_price, tax_included, is_active, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([
                    $uuid,
                    $roomId,
                    $ratePlanId,
                    $basePrice,
                    $weekendPrice ?: null,
                    $extraAdultPrice ?: null,
                    $extraChildPrice ?: null,
                    $taxIncluded,
                    $isActive
                ]);
                
                logActivity('create', 'room_rates', "Created room rate for room ID: {$roomId}, rate plan ID: {$ratePlanId}");
                $success = 'Room rate created successfully.';
                
                // Clear form
                $_POST = [];
            } catch (PDOException $e) {
                if (DEBUG_MODE) {
                    error_log("Create room rate error: " . $e->getMessage());
                }
                $error = 'An error occurred while creating the room rate.';
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
                    <h1 class="page-title">Add Room Rate</h1>
                    <p class="page-subtitle">Add a new room rate and pricing</p>
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
                        <form action="" method="POST">
                            <?php echo csrfField(); ?>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="room_id" class="form-label required">Room</label>
                                    <select class="form-select" id="room_id" name="room_id" required>
                                        <option value="">Select Room</option>
                                        <?php foreach ($rooms as $room): ?>
                                            <option value="<?php echo $room['id']; ?>" <?php echo ($_POST['room_id'] ?? '') == $room['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($room['property_name']); ?> - <?php echo htmlspecialchars($room['room_number']); ?>
                                                <?php if ($room['room_name']): ?>
                                                    (<?php echo htmlspecialchars($room['room_name']); ?>)
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="rate_plan_id" class="form-label required">Rate Plan</label>
                                    <select class="form-select" id="rate_plan_id" name="rate_plan_id" required>
                                        <option value="">Select Rate Plan</option>
                                        <?php foreach ($ratePlans as $plan): ?>
                                            <option value="<?php echo $plan['id']; ?>" <?php echo ($_POST['rate_plan_id'] ?? '') == $plan['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($plan['name']); ?> (<?php echo htmlspecialchars($plan['code']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <h5 class="mb-3">Pricing</h5>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label for="base_price" class="form-label required">Base Price</label>
                                    <input type="number" class="form-control" id="base_price" name="base_price" required step="0.01" min="0" value="<?php echo htmlspecialchars($_POST['base_price'] ?? ''); ?>">
                                    <small class="form-text text-muted">Standard nightly rate</small>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label for="weekend_price" class="form-label">Weekend Price</label>
                                    <input type="number" class="form-control" id="weekend_price" name="weekend_price" step="0.01" min="0" value="<?php echo htmlspecialchars($_POST['weekend_price'] ?? ''); ?>">
                                    <small class="form-text text-muted">Friday & Saturday rate</small>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label for="extra_adult_price" class="form-label">Extra Adult Price</label>
                                    <input type="number" class="form-control" id="extra_adult_price" name="extra_adult_price" step="0.01" min="0" value="<?php echo htmlspecialchars($_POST['extra_adult_price'] ?? ''); ?>">
                                    <small class="form-text text-muted">Per additional adult</small>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label for="extra_child_price" class="form-label">Extra Child Price</label>
                                    <input type="number" class="form-control" id="extra_child_price" name="extra_child_price" step="0.01" min="0" value="<?php echo htmlspecialchars($_POST['extra_child_price'] ?? ''); ?>">
                                    <small class="form-text text-muted">Per additional child</small>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <h5 class="mb-3">Settings</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="tax_included" name="tax_included" <?php echo ($_POST['tax_included'] ?? '') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="tax_included">Tax Included in Price</label>
                                    </div>
                                    <small class="form-text text-muted">If checked, tax is included in the base price</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                        <label class="form-check-label" for="is_active">Active</label>
                                    </div>
                                    <small class="form-text text-muted">Only active rates can be used for bookings</small>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2 mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-2"></i>Save Room Rate
                                </button>
                                <a href="<?php echo APP_URL; ?>/modules/room-rates/index.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>Back to Room Rates
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
