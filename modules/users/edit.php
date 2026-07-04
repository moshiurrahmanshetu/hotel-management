<?php
/**
 * Hotel & Resort Management System
 * Users Module - Edit User Page
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
requirePermission('users.edit');

$page_title = 'Edit User';
$page_description = 'Edit system user';

// Breadcrumb items
$breadcrumb_items = [
    ['label' => 'Dashboard', 'url' => APP_URL . '/dashboard.php'],
    ['label' => 'Users', 'url' => APP_URL . '/modules/users/index.php'],
    ['label' => 'Edit User', 'active' => true]
];

$error = '';
$success = '';

// Get user ID
$userId = (int)($_GET['id'] ?? 0);

if (!$userId) {
    redirect(APP_URL . '/modules/users/index.php');
}

// Get user data
$db = getDB();
$stmt = $db->prepare("
    SELECT u.*, 
           GROUP_CONCAT(r.id) as role_ids
    FROM users u
    LEFT JOIN user_roles ur ON u.id = ur.user_id
    LEFT JOIN roles r ON ur.role_id = r.id
    WHERE u.id = ?
    GROUP BY u.id
");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    redirect(APP_URL . '/modules/users/index.php');
}

// Get available roles
$stmt = $db->query("SELECT id, name, slug FROM roles ORDER BY name ASC");
$roles = $stmt->fetchAll();

// Get user's current roles
$userRoleIds = $user['role_ids'] ? explode(',', $user['role_ids']) : [];

// Check if user is super admin
$stmt = $db->prepare("SELECT slug FROM user_roles ur INNER JOIN roles r ON ur.role_id = r.id WHERE ur.user_id = ?");
$stmt->execute([$userId]);
$userRoles = array_column($stmt->fetchAll(), 'slug');
$isSuperAdmin = in_array('super_admin', $userRoles);
$isCurrentUser = $userId === authId();

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
            $selectedRoles = $_POST['roles'] ?? [];
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            
            // Validation
            if (!$firstName || !$lastName) {
                $error = 'Please enter first name and last name.';
            } elseif (!$email) {
                $error = 'Please enter a valid email address.';
            } elseif (empty($selectedRoles)) {
                $error = 'Please select at least one role.';
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
                }
            }
            
            // Prevent removing super admin role from super admin
            if (!$error && $isSuperAdmin) {
                $stmt = $db->prepare("SELECT id FROM roles WHERE slug = 'super_admin'");
                $stmt->execute();
                $superAdminRoleId = $stmt->fetchColumn();
                
                if (!in_array($superAdminRoleId, $selectedRoles)) {
                    $error = 'Cannot remove Super Admin role from Super Admin user.';
                }
            }
            
            // Prevent deactivating super admin or own account
            if (!$error && $isActive === 0) {
                if ($isSuperAdmin) {
                    $error = 'Cannot deactivate Super Admin user.';
                } elseif ($isCurrentUser) {
                    $error = 'Cannot deactivate your own account.';
                }
            }
            
            if (!$error) {
                try {
                    $db->beginTransaction();
                    
                    // Update user
                    $stmt = $db->prepare("
                        UPDATE users 
                        SET first_name = ?, last_name = ?, email = ?, username = ?, phone = ?, is_active = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$firstName, $lastName, $email, $username ?: null, $phone ?: null, $isActive, $userId]);
                    
                    // Update roles
                    $stmt = $db->prepare("DELETE FROM user_roles WHERE user_id = ?");
                    $stmt->execute([$userId]);
                    
                    foreach ($selectedRoles as $roleId) {
                        $stmt = $db->prepare("INSERT INTO user_roles (user_id, role_id, created_at) VALUES (?, ?, NOW())");
                        $stmt->execute([$userId, (int)$roleId]);
                    }
                    
                    $db->commit();
                    
                    logActivity('update', 'users', "Updated user: {$email}", $userId);
                    $success = 'User updated successfully.';
                    
                    // Refresh user data
                    $stmt = $db->prepare("
                        SELECT u.*, 
                               GROUP_CONCAT(r.id) as role_ids
                        FROM users u
                        LEFT JOIN user_roles ur ON u.id = ur.user_id
                        LEFT JOIN roles r ON ur.role_id = r.id
                        WHERE u.id = ?
                        GROUP BY u.id
                    ");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch();
                    $userRoleIds = $user['role_ids'] ? explode(',', $user['role_ids']) : [];
                    
                } catch (PDOException $e) {
                    $db->rollBack();
                    if (DEBUG_MODE) {
                        error_log("Update user error: " . $e->getMessage());
                    }
                    $error = 'An error occurred while updating the user.';
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
                            // Update session if editing own profile
                            if ($isCurrentUser) {
                                $_SESSION['user_avatar'] = $avatarPath;
                                $_SESSION['user_avatar_updated'] = time();
                            }
                            
                            // Log activity
                            logActivity('avatar_changed', 'users', "Changed avatar for user ID: {$userId}", ['old_avatar' => $user['avatar']], ['new_avatar' => $avatarPath]);
                            
                            $success = 'Avatar updated successfully.';
                            
                            // Refresh user data
                            $stmt = $db->prepare("
                                SELECT u.*, 
                                       GROUP_CONCAT(r.id) as role_ids
                                FROM users u
                                LEFT JOIN user_roles ur ON u.id = ur.user_id
                                LEFT JOIN roles r ON ur.role_id = r.id
                                WHERE u.id = ?
                                GROUP BY u.id
                            ");
                            $stmt->execute([$userId]);
                            $user = $stmt->fetch();
                            $userRoleIds = $user['role_ids'] ? explode(',', $user['role_ids']) : [];
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
                    // Update session if editing own profile
                    if ($isCurrentUser) {
                        $_SESSION['user_avatar'] = null;
                        $_SESSION['user_avatar_updated'] = time();
                    }
                    
                    // Log activity
                    logActivity('avatar_removed', 'users', "Removed avatar for user ID: {$userId}", ['old_avatar' => $user['avatar']], ['new_avatar' => null]);
                    
                    $success = 'Avatar removed successfully.';
                    
                    // Refresh user data
                    $stmt = $db->prepare("
                        SELECT u.*, 
                               GROUP_CONCAT(r.id) as role_ids
                        FROM users u
                        LEFT JOIN user_roles ur ON u.id = ur.user_id
                        LEFT JOIN roles r ON ur.role_id = r.id
                        WHERE u.id = ?
                        GROUP BY u.id
                    ");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch();
                    $userRoleIds = $user['role_ids'] ? explode(',', $user['role_ids']) : [];
                } else {
                    $error = 'Failed to remove avatar.';
                }
            }
        } elseif ($action === 'change_password') {
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if (!$newPassword) {
                $error = 'Please enter a new password.';
            } elseif (strlen($newPassword) < 8) {
                $error = 'Password must be at least 8 characters long.';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'Passwords do not match.';
            } else {
                try {
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    
                    $stmt = $db->prepare("UPDATE users SET password = ?, must_change_password = 0, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$hashedPassword, $userId]);
                    
                    logActivity('update', 'users', "Changed password for user ID: {$userId}", $userId);
                    $success = 'Password changed successfully.';
                    
                } catch (PDOException $e) {
                    if (DEBUG_MODE) {
                        error_log("Change password error: " . $e->getMessage());
                    }
                    $error = 'An error occurred while changing the password.';
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
                    <h1 class="page-title">Edit User</h1>
                    <p class="page-subtitle">Edit user information and settings</p>
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
                
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">User Information</h5>
                            </div>
                            <div class="card-body">
                                <form action="" method="POST" id="userForm">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="action" value="update">
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="first_name" class="form-label required">First Name</label>
                                            <input type="text" class="form-control" id="first_name" name="first_name" required value="<?php echo htmlspecialchars($user['first_name']); ?>">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="last_name" class="form-label required">Last Name</label>
                                            <input type="text" class="form-control" id="last_name" name="last_name" required value="<?php echo htmlspecialchars($user['last_name']); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label required">Email Address</label>
                                            <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($user['email']); ?>">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="username" class="form-label">Username</label>
                                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>">
                                            <small class="form-text text-muted">Optional</small>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="phone" class="form-label">Phone Number</label>
                                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Status</label>
                                            <div class="form-check form-switch mt-2">
                                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" <?php echo $user['is_active'] ? 'checked' : ''; ?> <?php echo ($isSuperAdmin || $isCurrentUser) ? 'disabled' : ''; ?>>
                                                <label class="form-check-label" for="is_active">
                                                    <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                                    <?php if ($isSuperAdmin || $isCurrentUser): ?>
                                                        <small class="text-muted">(Cannot be changed)</small>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label required">Roles</label>
                                        <div class="row">
                                            <?php foreach ($roles as $role): ?>
                                                <div class="col-md-4 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="roles[]" value="<?php echo $role['id']; ?>" id="role_<?php echo $role['id']; ?>" <?php echo in_array($role['id'], $userRoleIds) ? 'checked' : ''; ?> <?php echo ($isSuperAdmin && $role['slug'] === 'super_admin') ? 'disabled' : ''; ?>>
                                                        <label class="form-check-label" for="role_<?php echo $role['id']; ?>">
                                                            <?php echo htmlspecialchars($role['name']); ?>
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php if ($isSuperAdmin): ?>
                                            <small class="form-text text-muted">Super Admin role cannot be removed from Super Admin users.</small>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-save me-2"></i>Update User
                                        </button>
                                        <a href="<?php echo APP_URL; ?>/modules/users/index.php" class="btn btn-outline-secondary">
                                            <i class="bi bi-arrow-left me-2"></i>Back to Users
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Profile Image</h5>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-3">
                                    <div class="avatar-preview-lg mb-3">
                                        <?php 
                                        $avatarUrl = getAvatarUrl($user['avatar'], $user['updated_at'], $user['id']);
                                        if ($avatarUrl): ?>
                                            <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="Avatar" class="avatar-image-lg">
                                        <?php else: ?>
                                            <i class="bi bi-person-circle avatar-placeholder-lg"></i>
                                        <?php endif; ?>
                                    </div>
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
                                        <button type="submit" name="action" value="remove_avatar" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to remove this avatar?');">
                                            <i class="bi bi-trash"></i> Remove Avatar
                                        </button>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Change Password</h5>
                            </div>
                            <div class="card-body">
                                <form action="" method="POST" id="passwordForm">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="action" value="change_password">
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="new_password" name="new_password" minlength="8">
                                            <button class="btn btn-outline-secondary" type="button" id="toggleNewPassword">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                        <small class="form-text text-muted">Minimum 8 characters</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm Password</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8">
                                            <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-warning w-100">
                                        <i class="bi bi-shield-lock me-2"></i>Change Password
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Toggle password visibility
document.getElementById('toggleNewPassword').addEventListener('click', function() {
    const passwordInput = document.getElementById('new_password');
    const icon = this.querySelector('i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
});

document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
    const passwordInput = document.getElementById('confirm_password');
    const icon = this.querySelector('i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
});

// Form validation
document.getElementById('userForm').addEventListener('submit', function(e) {
    const roles = document.querySelectorAll('input[name="roles[]"]:checked');
    if (roles.length === 0) {
        e.preventDefault();
        alert('Please select at least one role.');
    }
});

document.getElementById('passwordForm').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (newPassword !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match.');
    }
});

// Avatar upload - auto-submit form after file selection
document.getElementById('avatarInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.querySelector('.avatar-preview-lg');
            if (preview.querySelector('.avatar-image-lg')) {
                preview.querySelector('.avatar-image-lg').src = e.target.result;
            } else {
                preview.innerHTML = '<img src="' + e.target.result + '" alt="Avatar" class="avatar-image-lg">';
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
