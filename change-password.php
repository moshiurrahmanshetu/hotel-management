<?php
/**
 * Hotel & Resort Management System
 * Change Password Page
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

$page_title = 'Change Password';
$page_description = 'Update your account password';

// Breadcrumb items
$breadcrumb_items = [
    ['label' => 'Dashboard', 'url' => APP_URL . '/dashboard.php'],
    ['label' => 'My Profile', 'url' => APP_URL . '/profile.php'],
    ['label' => 'Change Password', 'active' => true]
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

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (!$currentPassword || !$newPassword || !$confirmPassword) {
            $error = 'Please fill in all password fields.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New password and confirm password do not match.';
        } elseif ($newPassword === $currentPassword) {
            $error = 'New password must be different from current password.';
        } else {
            // Verify current password
            if (!password_verify($currentPassword, $user['password'])) {
                $error = 'Current password is incorrect.';
            } else {
                // Validate password strength
                $passwordValidation = validatePasswordStrength($newPassword);
                if (!$passwordValidation['valid']) {
                    $error = implode(', ', $passwordValidation['errors']);
                } else {
                    // Hash new password
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    
                    // Update password
                    $stmt = $db->prepare("
                        UPDATE users 
                        SET password = ?, must_change_password = 0, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $result = $stmt->execute([$hashedPassword, $userId]);
                    
                    if ($result) {
                        // Log activity
                        logActivity('password_changed', 'auth', 'User changed password', null, ['user_id' => $userId]);
                        
                        $success = 'Password changed successfully. Please login with your new password.';
                        
                        // Logout user to force re-login with new password
                        logout();
                        redirect(APP_URL . '/login.php?password_changed=1');
                    } else {
                        $error = 'Failed to change password. Please try again.';
                    }
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
                    <h1 class="page-title">Change Password</h1>
                    <p class="page-subtitle">Update your account password for better security</p>
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
                
                <div class="row justify-content-center">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Change Your Password</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" id="passwordForm">
                                    <?php echo csrfField(); ?>
                                    
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password')">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password')">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                        <div class="form-text">
                                            Password must be at least 8 characters long and contain uppercase, lowercase, number, and special character.
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Password Strength Indicator -->
                                    <div class="mb-3">
                                        <label class="form-label">Password Strength</label>
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar" id="passwordStrength" role="progressbar" style="width: 0%"></div>
                                        </div>
                                        <div class="form-text mt-1" id="passwordStrengthText"></div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between">
                                        <a href="<?php echo APP_URL; ?>/profile.php" class="btn btn-outline-secondary">
                                            <i class="bi bi-arrow-left"></i> Back to Profile
                                        </a>
                                        <button type="submit" class="btn btn-primary" id="submitBtn">
                                            <i class="bi bi-shield-lock"></i> Change Password
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Password Requirements -->
                        <div class="card mt-3">
                            <div class="card-body">
                                <h6 class="card-title mb-3">Password Requirements:</h6>
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-2">
                                        <i class="bi bi-circle" id="req-length"></i>
                                        At least 8 characters
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-circle" id="req-uppercase"></i>
                                        At least one uppercase letter
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-circle" id="req-lowercase"></i>
                                        At least one lowercase letter
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-circle" id="req-number"></i>
                                        At least one number
                                    </li>
                                    <li class="mb-0">
                                        <i class="bi bi-circle" id="req-special"></i>
                                        At least one special character
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
#passwordStrength {
    transition: width 0.3s ease;
}

.progress-bar {
    background-color: #dc3545;
}

.progress-bar.bg-warning {
    background-color: #ffc107 !important;
}

.progress-bar.bg-success {
    background-color: #198754 !important;
}

.bi-circle {
    color: #6c757d;
}

.bi-check-circle-fill {
    color: #198754;
}
</style>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.nextElementSibling;
    const icon = button.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

// Password strength checker
const newPassword = document.getElementById('new_password');
const confirmPassword = document.getElementById('confirm_password');
const strengthBar = document.getElementById('passwordStrength');
const strengthText = document.getElementById('passwordStrengthText');
const submitBtn = document.getElementById('submitBtn');

const requirements = {
    length: document.getElementById('req-length'),
    uppercase: document.getElementById('req-uppercase'),
    lowercase: document.getElementById('req-lowercase'),
    number: document.getElementById('req-number'),
    special: document.getElementById('req-special')
};

newPassword.addEventListener('input', function() {
    const password = this.value;
    let strength = 0;
    let metRequirements = 0;
    
    // Check requirements
    if (password.length >= 8) {
        metRequirements++;
        requirements.length.classList.remove('bi-circle');
        requirements.length.classList.add('bi-check-circle-fill');
    } else {
        requirements.length.classList.remove('bi-check-circle-fill');
        requirements.length.classList.add('bi-circle');
    }
    
    if (/[A-Z]/.test(password)) {
        metRequirements++;
        requirements.uppercase.classList.remove('bi-circle');
        requirements.uppercase.classList.add('bi-check-circle-fill');
    } else {
        requirements.uppercase.classList.remove('bi-check-circle-fill');
        requirements.uppercase.classList.add('bi-circle');
    }
    
    if (/[a-z]/.test(password)) {
        metRequirements++;
        requirements.lowercase.classList.remove('bi-circle');
        requirements.lowercase.classList.add('bi-check-circle-fill');
    } else {
        requirements.lowercase.classList.remove('bi-check-circle-fill');
        requirements.lowercase.classList.add('bi-circle');
    }
    
    if (/[0-9]/.test(password)) {
        metRequirements++;
        requirements.number.classList.remove('bi-circle');
        requirements.number.classList.add('bi-check-circle-fill');
    } else {
        requirements.number.classList.remove('bi-check-circle-fill');
        requirements.number.classList.add('bi-circle');
    }
    
    if (/[^A-Za-z0-9]/.test(password)) {
        metRequirements++;
        requirements.special.classList.remove('bi-circle');
        requirements.special.classList.add('bi-check-circle-fill');
    } else {
        requirements.special.classList.remove('bi-check-circle-fill');
        requirements.special.classList.add('bi-circle');
    }
    
    // Calculate strength
    strength = (metRequirements / 5) * 100;
    strengthBar.style.width = strength + '%';
    
    if (strength <= 20) {
        strengthBar.className = 'progress-bar';
        strengthBar.style.backgroundColor = '#dc3545';
        strengthText.textContent = 'Very Weak';
    } else if (strength <= 40) {
        strengthBar.className = 'progress-bar';
        strengthBar.style.backgroundColor = '#dc3545';
        strengthText.textContent = 'Weak';
    } else if (strength <= 60) {
        strengthBar.className = 'progress-bar bg-warning';
        strengthText.textContent = 'Fair';
    } else if (strength <= 80) {
        strengthBar.className = 'progress-bar bg-success';
        strengthText.textContent = 'Good';
    } else {
        strengthBar.className = 'progress-bar bg-success';
        strengthText.textContent = 'Strong';
    }
    
    // Enable/disable submit button
    submitBtn.disabled = metRequirements < 5;
});

// Form validation
document.getElementById('passwordForm').addEventListener('submit', function(e) {
    if (newPassword.value !== confirmPassword.value) {
        e.preventDefault();
        alert('New password and confirm password do not match.');
    }
});
</script>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
