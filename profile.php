<?php
/**
 * Hotel & Resort Management System
 * User Profile Page
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__FILE__));
}

// Load bootstrap (includes config, installer check, etc.)
require_once APP_ROOT . '/includes/bootstrap.php';
require_once APP_ROOT . '/includes/auth.php';

// Require authentication
requireAuth();

$page_title = 'My Profile';
$page_description = 'Manage your profile settings';

// Breadcrumb items
$breadcrumb_items = [
    ['label' => 'Dashboard', 'url' => APP_URL . '/dashboard.php'],
    ['label' => 'My Profile', 'active' => true]
];

$error = '';
$success = '';

// Get current user
$currentUser = authUser();
$userId = authId();

// Get user data from database
$db = getDB();
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    redirect(APP_URL . '/dashboard.php');
}

// Get available languages
$stmt = $db->query("SELECT id, name, code FROM languages WHERE is_active = 1 ORDER BY name ASC");
$languages = $stmt->fetchAll();

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'] ?? 'update';
        
        if ($action === 'update') {
            $firstName = sanitizeString($_POST['first_name'] ?? '');
            $lastName = sanitizeString($_POST['last_name'] ?? '');
            $email = sanitizeEmail($_POST['email'] ?? '');
            $username = sanitizeString($_POST['username'] ?? '');
            $phone = sanitizePhone($_POST['phone'] ?? '');
            $timezone = sanitizeString($_POST['timezone'] ?? 'UTC');
            $languageId = (int)($_POST['language_id'] ?? 0);
            
            // Validation
            if (!$firstName || !$lastName) {
                $error = 'Please enter first name and last name.';
            } elseif (!$email) {
                $error = 'Please enter a valid email address.';
            } else {
                // Check if email already exists (excluding current user)
                $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $userId]);
                if ($stmt->fetch()) {
                    $error = 'Email already exists.';
                } elseif ($username) {
                    // Check if username already exists (excluding current user)
                    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                    $stmt->execute([$username, $userId]);
                    if ($stmt->fetch()) {
                        $error = 'Username already exists.';
                    }
                } else {
                    // Store old values for activity log
                    $oldValues = [
                        'first_name' => $user['first_name'],
                        'last_name' => $user['last_name'],
                        'email' => $user['email'],
                        'username' => $user['username'],
                        'phone' => $user['phone'],
                        'timezone' => $user['timezone'],
                        'language_id' => $user['language_id']
                    ];
                    
                    // Update user
                    $stmt = $db->prepare("
                        UPDATE users 
                        SET first_name = ?, last_name = ?, email = ?, username = ?, 
                            phone = ?, timezone = ?, language_id = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $result = $stmt->execute([
                        $firstName, $lastName, $email, $username ?: null,
                        $phone ?: null, $timezone, $languageId ?: null, $userId
                    ]);
                    
                    if ($result) {
                        // Update session
                        $_SESSION['user_first_name'] = $firstName;
                        $_SESSION['user_last_name'] = $lastName;
                        $_SESSION['user_email'] = $email;
                        
                        // Log activity
                        $newValues = [
                            'first_name' => $firstName,
                            'last_name' => $lastName,
                            'email' => $email,
                            'username' => $username,
                            'phone' => $phone,
                            'timezone' => $timezone,
                            'language_id' => $languageId
                        ];
                        logActivity('profile_updated', 'auth', 'User updated profile', $oldValues, $newValues);
                        
                        $success = 'Profile updated successfully.';
                        
                        // Refresh user data
                        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                        $stmt->execute([$userId]);
                        $user = $stmt->fetch();
                    } else {
                        $error = 'Failed to update profile. Please try again.';
                    }
                }
            }
        } elseif ($action === 'upload_avatar') {
            // Handle avatar upload
            if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Please select a valid image file.';
            } else {
                $file = $_FILES['avatar'];
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                $maxSize = UPLOAD_MAX_SIZE;
                
                if (!in_array($file['type'], $allowedTypes)) {
                    $error = 'Invalid file type. Only JPG, PNG, and GIF images are allowed.';
                } elseif ($file['size'] > $maxSize) {
                    $error = 'File size exceeds maximum limit of ' . formatFileSize($maxSize) . '.';
                } else {
                    // Create upload directory if it doesn't exist
                    $uploadDir = UPLOAD_PATH . '/avatars';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    // Generate unique filename
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = 'avatar_' . $userId . '_' . time() . '.' . $extension;
                    $filepath = $uploadDir . '/' . $filename;
                    
                    // Delete old avatar
                    if ($user['avatar'] && file_exists(APP_ROOT . '/' . $user['avatar'])) {
                        unlink(APP_ROOT . '/' . $user['avatar']);
                    }
                    
                    // Upload new file
                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        // Update database
                        $avatarPath = 'uploads/avatars/' . $filename;
                        $stmt = $db->prepare("UPDATE users SET avatar = ?, updated_at = NOW() WHERE id = ?");
                        $result = $stmt->execute([$avatarPath, $userId]);
                        
                        if ($result) {
                            // Update session
                            $_SESSION['user_avatar'] = $avatarPath;
                            $_SESSION['user_avatar_updated'] = time();
                            
                            // Log activity
                            logActivity('avatar_changed', 'auth', 'User changed avatar', ['old_avatar' => $user['avatar']], ['new_avatar' => $avatarPath]);
                            
                            $success = 'Avatar updated successfully.';
                            
                            // Refresh user data
                            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                            $stmt->execute([$userId]);
                            $user = $stmt->fetch();
                        } else {
                            $error = 'Failed to update avatar in database.';
                        }
                    } else {
                        $error = 'Failed to upload file. Please try again.';
                    }
                }
            }
        } elseif ($action === 'remove_avatar') {
            // Remove avatar
            if ($user['avatar']) {
                // Delete file
                if (file_exists(APP_ROOT . '/' . $user['avatar'])) {
                    unlink(APP_ROOT . '/' . $user['avatar']);
                }
                
                // Update database
                $stmt = $db->prepare("UPDATE users SET avatar = NULL, updated_at = NOW() WHERE id = ?");
                $result = $stmt->execute([$userId]);
                
                if ($result) {
                    // Update session
                    $_SESSION['user_avatar'] = null;
                    $_SESSION['user_avatar_updated'] = time();
                    
                    // Log activity
                    logActivity('avatar_removed', 'auth', 'User removed avatar', ['old_avatar' => $user['avatar']], ['new_avatar' => null]);
                    
                    $success = 'Avatar removed successfully.';
                    
                    // Refresh user data
                    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch();
                } else {
                    $error = 'Failed to remove avatar.';
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
                    <h1 class="page-title">My Profile</h1>
                    <p class="page-subtitle">Manage your account settings and preferences</p>
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
                
                <div class="row">
                    <div class="col-lg-4">
                        <!-- Avatar Card -->
                        <div class="card">
                            <div class="card-body">
                                <div class="text-center">
                                    <div class="avatar-preview mb-3">
                                        <?php 
                                        $avatarUrl = getAvatarUrl($user['avatar'], $user['updated_at'], $user['id']);
                                        if ($avatarUrl): ?>
                                            <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="Avatar" class="avatar-image">
                                        <?php else: ?>
                                            <div class="avatar-placeholder">
                                                <i class="bi bi-person-circle"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <h5 class="mb-1"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                                    <p class="text-muted mb-3"><?php echo htmlspecialchars($user['email']); ?></p>
                                    
                                    <form method="POST" enctype="multipart/form-data" class="avatar-upload-form" id="avatarForm">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action" value="upload_avatar">
                                        <div class="mb-2">
                                            <label class="btn btn-outline-primary btn-sm">
                                                <i class="bi bi-upload"></i> Upload New Avatar
                                                <input type="file" name="avatar" id="avatarInput" accept="image/jpeg,image/jpg,image/png,image/gif" style="display: none;">
                                            </label>
                                        </div>
                                        <?php if ($user['avatar']): ?>
                                        <button type="submit" name="action" value="remove_avatar" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to remove your avatar?');">
                                            <i class="bi bi-trash"></i> Remove Avatar
                                        </button>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Account Info Card -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Account Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="info-item">
                                    <span class="info-label">User ID:</span>
                                    <span class="info-value"><?php echo $user['id']; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">UUID:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($user['uuid']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Status:</span>
                                    <span class="info-value">
                                        <?php if ($user['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inactive</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Member Since:</span>
                                    <span class="info-value"><?php echo formatDate($user['created_at'], 'F j, Y'); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Last Login:</span>
                                    <span class="info-value"><?php echo $user['last_login_at'] ? formatDateTime($user['last_login_at']) : 'Never'; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-8">
                        <!-- Profile Form -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Profile Settings</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="action" value="update">
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                                   value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                                   value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="username" name="username" 
                                               value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>">
                                        <div class="form-text">Optional. Leave blank to remove username.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="timezone" class="form-label">Timezone</label>
                                        <select class="form-select" id="timezone" name="timezone">
                                            <?php
                                            $timezones = [
                                                'UTC' => 'UTC',
                                                'America/New_York' => 'Eastern Time (US & Canada)',
                                                'America/Chicago' => 'Central Time (US & Canada)',
                                                'America/Denver' => 'Mountain Time (US & Canada)',
                                                'America/Los_Angeles' => 'Pacific Time (US & Canada)',
                                                'Europe/London' => 'London',
                                                'Europe/Paris' => 'Paris',
                                                'Europe/Berlin' => 'Berlin',
                                                'Asia/Tokyo' => 'Tokyo',
                                                'Asia/Shanghai' => 'Shanghai',
                                                'Asia/Dubai' => 'Dubai',
                                                'Australia/Sydney' => 'Sydney'
                                            ];
                                            foreach ($timezones as $tz => $label):
                                            ?>
                                            <option value="<?php echo $tz; ?>" <?php echo $user['timezone'] === $tz ? 'selected' : ''; ?>>
                                                <?php echo $label; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="language_id" class="form-label">Language</label>
                                        <select class="form-select" id="language_id" name="language_id">
                                            <option value="">Select Language</option>
                                            <?php foreach ($languages as $lang): ?>
                                            <option value="<?php echo $lang['id']; ?>" <?php echo $user['language_id'] == $lang['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($lang['name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between">
                                        <a href="<?php echo APP_URL; ?>/change-password.php" class="btn btn-outline-secondary">
                                            <i class="bi bi-shield-lock"></i> Change Password
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-save"></i> Save Changes
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
.avatar-preview {
    width: 150px;
    height: 150px;
    margin: 0 auto;
    border-radius: 50%;
    overflow: hidden;
    border: 4px solid #dee2e6;
    background-color: #f8f9fa;
}

.avatar-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 80px;
    color: #adb5bd;
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

<script>
// Avatar upload - auto-submit form after file selection
document.getElementById('avatarInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.querySelector('.avatar-preview');
            if (preview.querySelector('.avatar-image')) {
                preview.querySelector('.avatar-image').src = e.target.result;
            } else {
                preview.innerHTML = '<img src="' + e.target.result + '" alt="Avatar" class="avatar-image">';
            }
        };
        reader.readAsDataURL(file);
        
        // Auto-submit form after short delay
        setTimeout(function() {
            document.getElementById('avatarForm').submit();
        }, 500);
    }
});
</script>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
