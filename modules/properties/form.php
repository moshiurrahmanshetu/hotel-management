<?php
/**
 * Hotel & Resort Management System
 * Properties Module - Form Page (Create/Edit)
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

$page_title = isset($_GET['id']) ? 'Edit Property' : 'Add Property';
$page_description = isset($_GET['id']) ? 'Edit property information' : 'Add a new property';

// Breadcrumb items
$breadcrumb_items = [
    ['label' => 'Dashboard', 'url' => APP_URL . '/dashboard.php'],
    ['label' => 'Hotel Structure', 'url' => '#'],
    ['label' => 'Properties', 'url' => APP_URL . '/modules/properties/index.php'],
    ['label' => isset($_GET['id']) ? 'Edit Property' : 'Add Property', 'active' => true]
];

$error = '';
$success = '';

$db = getDB();

// Get property ID for edit
$propertyId = (int)($_GET['id'] ?? 0);
$property = null;

if ($propertyId) {
    $stmt = $db->prepare("SELECT * FROM properties WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$propertyId]);
    $property = $stmt->fetch();
    
    if (!$property) {
        redirect(APP_URL . '/modules/properties/index.php');
    }
}

// Get countries
$stmt = $db->query("SELECT id, name FROM countries ORDER BY name ASC");
$countries = $stmt->fetchAll();

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $name = sanitizeString($_POST['name'] ?? '');
        $code = sanitizeString($_POST['code'] ?? '');
        $description = sanitizeString($_POST['description'] ?? '');
        $address = sanitizeString($_POST['address'] ?? '');
        $city = sanitizeString($_POST['city'] ?? '');
        $state = sanitizeString($_POST['state'] ?? '');
        $countryId = (int)($_POST['country_id'] ?? 0);
        $postalCode = sanitizeString($_POST['postal_code'] ?? '');
        $phone = sanitizePhone($_POST['phone'] ?? '');
        $email = sanitizeEmail($_POST['email'] ?? '');
        $website = sanitizeUrl($_POST['website'] ?? '');
        $starRating = (int)($_POST['star_rating'] ?? 0);
        $totalRooms = (int)($_POST['total_rooms'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        // Validation
        if (!$name) {
            $error = 'Please enter property name.';
        } elseif (!$code) {
            $error = 'Please enter property code.';
        } elseif (!preg_match('/^[A-Z0-9_]+$/', $code)) {
            $error = 'Code must contain only uppercase letters, numbers, and underscores.';
        } elseif ($starRating < 0 || $starRating > 5) {
            $error = 'Star rating must be between 0 and 5.';
        } else {
            // Check if code already exists (excluding current property for edit)
            if ($propertyId) {
                $stmt = $db->prepare("SELECT id FROM properties WHERE code = ? AND id != ? AND deleted_at IS NULL");
                $stmt->execute([$code, $propertyId]);
            } else {
                $stmt = $db->prepare("SELECT id FROM properties WHERE code = ? AND deleted_at IS NULL");
                $stmt->execute([$code]);
            }
            
            if ($stmt->fetch()) {
                $error = 'Property code already exists.';
            }
        }
        
        if (!$error) {
            try {
                if ($propertyId) {
                    // Update
                    $stmt = $db->prepare("
                        UPDATE properties 
                        SET name = ?, code = ?, description = ?, address = ?, city = ?, state = ?, 
                            country_id = ?, postal_code = ?, phone = ?, email = ?, website = ?, 
                            star_rating = ?, total_rooms = ?, is_active = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $name, $code, $description ?: null, $address ?: null, $city ?: null, $state ?: null,
                        $countryId ?: null, $postalCode ?: null, $phone ?: null, $email ?: null, $website ?: null,
                        $starRating, $totalRooms, $isActive, $propertyId
                    ]);
                    
                    logActivity('update', 'properties', "Updated property: {$name}", $propertyId);
                    $success = 'Property updated successfully.';
                } else {
                    // Create
                    $uuid = generateUUID();
                    
                    $stmt = $db->prepare("
                        INSERT INTO properties (uuid, name, code, description, address, city, state, 
                            country_id, postal_code, phone, email, website, star_rating, total_rooms, 
                            is_active, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                    ");
                    $stmt->execute([
                        $uuid, $name, $code, $description ?: null, $address ?: null, $city ?: null, $state ?: null,
                        $countryId ?: null, $postalCode ?: null, $phone ?: null, $email ?: null, $website ?: null,
                        $starRating, $totalRooms, $isActive
                    ]);
                    
                    logActivity('create', 'properties', "Created property: {$name}");
                    $success = 'Property created successfully.';
                    
                    // Redirect to edit page
                    $propertyId = $db->lastInsertId();
                    header('refresh:2;url=' . APP_URL . '/modules/properties/form.php?id=' . $propertyId);
                }
                
                // Refresh property data
                if ($propertyId) {
                    $stmt = $db->prepare("SELECT * FROM properties WHERE id = ?");
                    $stmt->execute([$propertyId]);
                    $property = $stmt->fetch();
                }
                
            } catch (PDOException $e) {
                if (DEBUG_MODE) {
                    error_log("Property form error: " . $e->getMessage());
                }
                $error = 'An error occurred while saving the property.';
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
                    <h1 class="page-title"><?php echo $propertyId ? 'Edit Property' : 'Add Property'; ?></h1>
                    <p class="page-subtitle"><?php echo $propertyId ? 'Edit property information' : 'Add a new property to your hotel'; ?></p>
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
                        <form action="" method="POST" id="propertyForm">
                            <?php echo csrfField(); ?>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label required">Property Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($property['name'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="code" class="form-label required">Property Code</label>
                                    <input type="text" class="form-control" id="code" name="code" required pattern="[A-Z0-9_]+" value="<?php echo htmlspecialchars($property['code'] ?? ''); ?>">
                                    <small class="form-text text-muted">Uppercase letters, numbers, and underscores only</small>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($property['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($property['address'] ?? ''); ?>">
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="city" class="form-label">City</label>
                                    <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($property['city'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="state" class="form-label">State/Province</label>
                                    <input type="text" class="form-control" id="state" name="state" value="<?php echo htmlspecialchars($property['state'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="country_id" class="form-label">Country</label>
                                    <select class="form-select" id="country_id" name="country_id">
                                        <option value="">Select Country</option>
                                        <?php foreach ($countries as $country): ?>
                                            <option value="<?php echo $country['id']; ?>" <?php echo ($property['country_id'] ?? '') === $country['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($country['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="postal_code" class="form-label">Postal Code</label>
                                    <input type="text" class="form-control" id="postal_code" name="postal_code" value="<?php echo htmlspecialchars($property['postal_code'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($property['phone'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($property['email'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="website" class="form-label">Website</label>
                                    <input type="url" class="form-control" id="website" name="website" value="<?php echo htmlspecialchars($property['website'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="star_rating" class="form-label">Star Rating</label>
                                    <select class="form-select" id="star_rating" name="star_rating">
                                        <?php for ($i = 0; $i <= 5; $i++): ?>
                                            <option value="<?php echo $i; ?>" <?php echo ($property['star_rating'] ?? 0) === $i ? 'selected' : ''; ?>>
                                                <?php echo $i; ?> Star<?php echo $i !== 1 ? 's' : ''; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="total_rooms" class="form-label">Total Rooms</label>
                                    <input type="number" class="form-control" id="total_rooms" name="total_rooms" min="0" value="<?php echo htmlspecialchars($property['total_rooms'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Status</label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" <?php echo !isset($property) || $property['is_active'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_active">Active</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-2"></i><?php echo $propertyId ? 'Update Property' : 'Create Property'; ?>
                                </button>
                                <a href="<?php echo APP_URL; ?>/modules/properties/index.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>Back to Properties
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
