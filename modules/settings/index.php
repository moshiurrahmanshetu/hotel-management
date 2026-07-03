<?php
/**
 * Hotel & Resort Management System
 * Settings Module - Index Page
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
requirePermission('settings.edit');

$page_title = 'System Settings';
$page_description = 'Manage system settings';

// Breadcrumb items
$breadcrumb_items = [
    ['label' => 'Dashboard', 'url' => APP_URL . '/dashboard.php'],
    ['label' => 'Settings', 'active' => true]
];

$error = '';
$success = '';

$db = getDB();

// Get current settings
function getSetting($key, $default = '') {
    global $db;
    $stmt = $db->prepare("SELECT value FROM system_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['value'] : $default;
}

// Get available timezones
$timezones = [
    'UTC' => 'UTC',
    'America/New_York' => 'America/New_York',
    'America/Chicago' => 'America/Chicago',
    'America/Denver' => 'America/Denver',
    'America/Los_Angeles' => 'America/Los_Angeles',
    'Europe/London' => 'Europe/London',
    'Europe/Paris' => 'Europe/Paris',
    'Europe/Berlin' => 'Europe/Berlin',
    'Asia/Tokyo' => 'Asia/Tokyo',
    'Asia/Shanghai' => 'Asia/Shanghai',
    'Asia/Dubai' => 'Asia/Dubai',
    'Asia/Kolkata' => 'Asia/Kolkata',
    'Australia/Sydney' => 'Australia/Sydney',
];

// Get available date formats
$dateFormats = [
    'Y-m-d' => 'YYYY-MM-DD',
    'd/m/Y' => 'DD/MM/YYYY',
    'm/d/Y' => 'MM/DD/YYYY',
    'F d, Y' => 'Month DD, YYYY',
];

// Get available time formats
$timeFormats = [
    'H:i:s' => '24-hour (HH:MM:SS)',
    'h:i:s A' => '12-hour (HH:MM:SS AM/PM)',
    'H:i' => '24-hour (HH:MM)',
    'h:i A' => '12-hour (HH:MM AM/PM)',
];

// Get available currencies
$stmt = $db->query("SELECT id, code, name, symbol FROM currencies WHERE is_active = 1 ORDER BY name ASC");
$currencies = $stmt->fetchAll();

// Get available languages
$stmt = $db->query("SELECT id, code, name FROM languages WHERE is_active = 1 ORDER BY name ASC");
$languages = $stmt->fetchAll();

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        try {
            $db->beginTransaction();
            
            // Hotel Information
            $hotelName = sanitizeString($_POST['hotel_name'] ?? '');
            $hotelTagline = sanitizeString($_POST['hotel_tagline'] ?? '');
            
            // Contact Information
            $contactEmail = sanitizeEmail($_POST['contact_email'] ?? '');
            $contactPhone = sanitizePhone($_POST['contact_phone'] ?? '');
            $contactFax = sanitizePhone($_POST['contact_fax'] ?? '');
            
            // Address
            $address = sanitizeString($_POST['address'] ?? '');
            $city = sanitizeString($_POST['city'] ?? '');
            $state = sanitizeString($_POST['state'] ?? '');
            $country = (int)($_POST['country'] ?? 0);
            $postalCode = sanitizeString($_POST['postal_code'] ?? '');
            
            // Timezone & Formats
            $timezone = $_POST['timezone'] ?? 'UTC';
            $dateFormat = $_POST['date_format'] ?? 'Y-m-d';
            $timeFormat = $_POST['time_format'] ?? 'H:i:s';
            
            // Currency & Language
            $currency = (int)($_POST['currency'] ?? 0);
            $language = (int)($_POST['language'] ?? 0);
            
            // Helper function to update setting
            function updateSetting($key, $value) {
                global $db;
                $stmt = $db->prepare("SELECT id FROM system_settings WHERE setting_key = ?");
                $stmt->execute([$key]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    $stmt = $db->prepare("UPDATE system_settings SET value = ?, updated_at = NOW() WHERE setting_key = ?");
                    $stmt->execute([$value, $key]);
                } else {
                    $stmt = $db->prepare("INSERT INTO system_settings (setting_key, value, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
                    $stmt->execute([$key, $value]);
                }
            }
            
            // Update all settings
            updateSetting('hotel_name', $hotelName);
            updateSetting('hotel_tagline', $hotelTagline);
            updateSetting('contact_email', $contactEmail);
            updateSetting('contact_phone', $contactPhone);
            updateSetting('contact_fax', $contactFax);
            updateSetting('address', $address);
            updateSetting('city', $city);
            updateSetting('state', $state);
            updateSetting('country_id', $country);
            updateSetting('postal_code', $postalCode);
            updateSetting('timezone', $timezone);
            updateSetting('date_format', $dateFormat);
            updateSetting('time_format', $timeFormat);
            updateSetting('currency_id', $currency);
            updateSetting('language_id', $language);
            
            // Handle logo upload
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $logo = $_FILES['logo'];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                
                if (!in_array($logo['type'], $allowedTypes)) {
                    throw new Exception('Invalid logo file type. Only JPEG, PNG, GIF, and WebP are allowed.');
                }
                
                if ($logo['size'] > 2 * 1024 * 1024) {
                    throw new Exception('Logo file size must be less than 2MB.');
                }
                
                $logoExt = pathinfo($logo['name'], PATHINFO_EXTENSION);
                $logoName = 'logo_' . time() . '.' . $logoExt;
                $logoPath = APP_ROOT . '/uploads/' . $logoName;
                
                if (!is_dir(APP_ROOT . '/uploads')) {
                    mkdir(APP_ROOT . '/uploads', 0755, true);
                }
                
                if (move_uploaded_file($logo['tmp_name'], $logoPath)) {
                    updateSetting('logo', APP_URL . '/uploads/' . $logoName);
                }
            }
            
            // Handle favicon upload
            if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
                $favicon = $_FILES['favicon'];
                $allowedTypes = ['image/x-icon', 'image/vnd.microsoft.icon', 'image/png'];
                
                if (!in_array($favicon['type'], $allowedTypes)) {
                    throw new Exception('Invalid favicon file type. Only ICO and PNG are allowed.');
                }
                
                if ($favicon['size'] > 500 * 1024) {
                    throw new Exception('Favicon file size must be less than 500KB.');
                }
                
                $faviconExt = pathinfo($favicon['name'], PATHINFO_EXTENSION);
                $faviconName = 'favicon_' . time() . '.' . $faviconExt;
                $faviconPath = APP_ROOT . '/uploads/' . $faviconName;
                
                if (!is_dir(APP_ROOT . '/uploads')) {
                    mkdir(APP_ROOT . '/uploads', 0755, true);
                }
                
                if (move_uploaded_file($favicon['tmp_name'], $faviconPath)) {
                    updateSetting('favicon', APP_URL . '/uploads/' . $faviconName);
                }
            }
            
            $db->commit();
            
            logActivity('update', 'settings', 'Updated system settings');
            $success = 'Settings updated successfully.';
            
            // Refresh settings
            $hotelName = getSetting('hotel_name', $hotelName);
            $hotelTagline = getSetting('hotel_tagline', $hotelTagline);
            $contactEmail = getSetting('contact_email', $contactEmail);
            $contactPhone = getSetting('contact_phone', $contactPhone);
            $contactFax = getSetting('contact_fax', $contactFax);
            $address = getSetting('address', $address);
            $city = getSetting('city', $city);
            $state = getSetting('state', $state);
            $country = (int)getSetting('country_id', $country);
            $postalCode = getSetting('postal_code', $postalCode);
            $timezone = getSetting('timezone', $timezone);
            $dateFormat = getSetting('date_format', $dateFormat);
            $timeFormat = getSetting('time_format', $timeFormat);
            $currency = (int)getSetting('currency_id', $currency);
            $language = (int)getSetting('language_id', $language);
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = $e->getMessage();
        } catch (PDOException $e) {
            $db->rollBack();
            if (DEBUG_MODE) {
                error_log("Settings update error: " . $e->getMessage());
            }
            $error = 'An error occurred while updating settings.';
        }
    }
}

// Get current settings values
$hotelName = getSetting('hotel_name', APP_NAME);
$hotelTagline = getSetting('hotel_tagline', '');
$contactEmail = getSetting('contact_email', '');
$contactPhone = getSetting('contact_phone', '');
$contactFax = getSetting('contact_fax', '');
$address = getSetting('address', '');
$city = getSetting('city', '');
$state = getSetting('state', '');
$country = (int)getSetting('country_id', 0);
$postalCode = getSetting('postal_code', '');
$timezone = getSetting('timezone', 'UTC');
$dateFormat = getSetting('date_format', 'Y-m-d');
$timeFormat = getSetting('time_format', 'H:i:s');
$currency = (int)getSetting('currency_id', 0);
$language = (int)getSetting('language_id', 0);

// Get countries for dropdown
$stmt = $db->query("SELECT id, name FROM countries ORDER BY name ASC");
$countries = $stmt->fetchAll();

// Get current logo and favicon
$currentLogo = getSetting('logo', '');
$currentFavicon = getSetting('favicon', '');
?>
<?php require_once APP_ROOT . '/includes/header.php'; ?>

<div class="main-wrapper">
    <?php require_once APP_ROOT . '/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php require_once APP_ROOT . '/includes/topbar.php'; ?>
        
        <main class="content-area">
            <div class="container-fluid">
                <div class="page-header">
                    <h1 class="page-title">System Settings</h1>
                    <p class="page-subtitle">Manage your hotel's system settings and preferences</p>
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
                
                <form action="" method="POST" enctype="multipart/form-data" id="settingsForm">
                    <?php echo csrfField(); ?>
                    
                    <div class="row">
                        <div class="col-lg-8">
                            <!-- Hotel Information -->
                            <div class="card mb-4" data-aos="fade-up">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="bi bi-building me-2"></i>Hotel Information
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="hotel_name" class="form-label required">Hotel Name</label>
                                        <input type="text" class="form-control" id="hotel_name" name="hotel_name" required value="<?php echo htmlspecialchars($hotelName); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="hotel_tagline" class="form-label">Tagline</label>
                                        <input type="text" class="form-control" id="hotel_tagline" name="hotel_tagline" value="<?php echo htmlspecialchars($hotelTagline); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Contact Information -->
                            <div class="card mb-4" data-aos="fade-up" data-aos-delay="100">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="bi bi-telephone me-2"></i>Contact Information
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="contact_email" class="form-label required">Email Address</label>
                                            <input type="email" class="form-control" id="contact_email" name="contact_email" required value="<?php echo htmlspecialchars($contactEmail); ?>">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="contact_phone" class="form-label required">Phone Number</label>
                                            <input type="text" class="form-control" id="contact_phone" name="contact_phone" required value="<?php echo htmlspecialchars($contactPhone); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="contact_fax" class="form-label">Fax Number</label>
                                        <input type="text" class="form-control" id="contact_fax" name="contact_fax" value="<?php echo htmlspecialchars($contactFax); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Address -->
                            <div class="card mb-4" data-aos="fade-up" data-aos-delay="200">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="bi bi-geo-alt me-2"></i>Address
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="address" class="form-label">Street Address</label>
                                        <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($address); ?>">
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="city" class="form-label">City</label>
                                            <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($city); ?>">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="state" class="form-label">State/Province</label>
                                            <input type="text" class="form-control" id="state" name="state" value="<?php echo htmlspecialchars($state); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="country" class="form-label">Country</label>
                                            <select class="form-select" id="country" name="country">
                                                <option value="">Select Country</option>
                                                <?php foreach ($countries as $c): ?>
                                                    <option value="<?php echo $c['id']; ?>" <?php echo $country === $c['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($c['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="postal_code" class="form-label">Postal Code</label>
                                            <input type="text" class="form-control" id="postal_code" name="postal_code" value="<?php echo htmlspecialchars($postalCode); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Regional Settings -->
                            <div class="card mb-4" data-aos="fade-up" data-aos-delay="300">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="bi bi-globe me-2"></i>Regional Settings
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="timezone" class="form-label required">Timezone</label>
                                            <select class="form-select" id="timezone" name="timezone" required>
                                                <?php foreach ($timezones as $key => $label): ?>
                                                    <option value="<?php echo $key; ?>" <?php echo $timezone === $key ? 'selected' : ''; ?>>
                                                        <?php echo $label; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="currency" class="form-label required">Currency</label>
                                            <select class="form-select" id="currency" name="currency" required>
                                                <option value="">Select Currency</option>
                                                <?php foreach ($currencies as $c): ?>
                                                    <option value="<?php echo $c['id']; ?>" <?php echo $currency === $c['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($c['name']); ?> (<?php echo htmlspecialchars($c['symbol']); ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="date_format" class="form-label required">Date Format</label>
                                            <select class="form-select" id="date_format" name="date_format" required>
                                                <?php foreach ($dateFormats as $key => $label): ?>
                                                    <option value="<?php echo $key; ?>" <?php echo $dateFormat === $key ? 'selected' : ''; ?>>
                                                        <?php echo $label; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="time_format" class="form-label required">Time Format</label>
                                            <select class="form-select" id="time_format" name="time_format" required>
                                                <?php foreach ($timeFormats as $key => $label): ?>
                                                    <option value="<?php echo $key; ?>" <?php echo $timeFormat === $key ? 'selected' : ''; ?>>
                                                        <?php echo $label; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="language" class="form-label required">Language</label>
                                        <select class="form-select" id="language" name="language" required>
                                            <option value="">Select Language</option>
                                            <?php foreach ($languages as $l): ?>
                                                <option value="<?php echo $l['id']; ?>" <?php echo $language === $l['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($l['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <!-- Branding -->
                            <div class="card mb-4" data-aos="fade-up" data-aos-delay="400">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="bi bi-image me-2"></i>Branding
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-4">
                                        <label for="logo" class="form-label">Logo</label>
                                        <input type="file" class="form-control" id="logo" name="logo" accept="image/jpeg,image/png,image/gif,image/webp">
                                        <small class="form-text text-muted">JPEG, PNG, GIF, WebP (Max 2MB)</small>
                                        
                                        <?php if ($currentLogo): ?>
                                            <div class="mt-3">
                                                <label class="form-label">Current Logo</label>
                                                <img src="<?php echo htmlspecialchars($currentLogo); ?>" alt="Current Logo" class="img-fluid rounded border" style="max-height: 100px;">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="favicon" class="form-label">Favicon</label>
                                        <input type="file" class="form-control" id="favicon" name="favicon" accept="image/x-icon,image/vnd.microsoft.icon,image/png">
                                        <small class="form-text text-muted">ICO, PNG (Max 500KB, 32x32 or 16x16)</small>
                                        
                                        <?php if ($currentFavicon): ?>
                                            <div class="mt-3">
                                                <label class="form-label">Current Favicon</label>
                                                <img src="<?php echo htmlspecialchars($currentFavicon); ?>" alt="Current Favicon" class="rounded border" style="width: 32px; height: 32px;">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Save Button -->
                            <div class="card" data-aos="fade-up" data-aos-delay="500">
                                <div class="card-body">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-save me-2"></i>Save Settings
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
