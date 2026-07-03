<?php
/**
 * Hotel & Resort Management System
 * Users Module - Create User Page
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(dirname(dirname(__FILE__))));
}

// Load configuration and authentication
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/includes/auth.php';

// Require authentication and permission
requireAuth();
requirePermission('users.create');

$page_title = 'Create User';
$page_description = 'Add a new system user';

// Breadcrumb items
$breadcrumb_items = [
    ['label' => 'Dashboard', 'url' => APP_URL . '/dashboard.php'],
    ['label' => 'Users', 'url' => APP_URL . '/modules/users/index.php'],
    ['label' => 'Create User', 'active' => true]
];

$error = '';
$success = '';

// Get available roles
$db = getDB();
$stmt = $db->query("SELECT id, name, slug FROM roles ORDER BY name ASC");
$roles = $stmt->fetchAll();

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $firstName = sanitizeString($_POST['first_name'] ?? '');
        $lastName = sanitizeString($_POST['last_name'] ?? '');
        $email = sanitizeEmail($_POST['email'] ?? '');
        $username = sanitizeString($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $phone = sanitizePhone($_POST['phone'] ?? '');
        $selectedRoles = $_POST['roles'] ?? [];
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        // Validation
        if (!$firstName || !$lastName) {
            $error = 'Please enter first name and last name.';
        } elseif (!$email) {
            $error = 'Please enter a valid email address.';
        } elseif (!$password) {
            $error = 'Please enter a password.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } elseif (empty($selectedRoles)) {
            $error = 'Please select at least one role.';
        } else {
            // Check if email already exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email already exists.';
            } elseif ($username) {
                // Check if username already exists
                $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $error = 'Username already exists.';
                }
            }
        }
        
        if (!$error) {
            try {
                $db->beginTransaction();
                
                // Generate UUID
                $uuid = generateUUID();
                
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user
                $stmt = $db->prepare("
                    INSERT INTO users (uuid, first_name, last_name, email, username, password, phone, is_active, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([$uuid, $firstName, $lastName, $email, $username ?: null, $hashedPassword, $phone ?: null]);
                
                $userId = $db->lastInsertId();
                
                // Assign roles
                foreach ($selectedRoles as $roleId) {
                    $stmt = $db->prepare("INSERT INTO user_roles (user_id, role_id, created_at) VALUES (?, ?, NOW())");
                    $stmt->execute([$userId, (int)$roleId]);
                }
                
                $db->commit();
                
                logActivity('create', 'users', "Created user: {$email}", $userId);
                $success = 'User created successfully.';
                
                // Redirect to users list
                header('refresh:2;url=' . APP_URL . '/modules/users/index.php');
                
            } catch (PDOException $e) {
                $db->rollBack();
                if (DEBUG_MODE) {
                    error_log("Create user error: " . $e->getMessage());
                }
                $error = 'An error occurred while creating the user.';
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
                    <h1 class="page-title">Create User</h1>
                    <p class="page-subtitle">Add a new system user</p>
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
                        <form action="" method="POST" id="userForm">
                            <?php echo csrfField(); ?>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label required">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label required">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label required">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                                    <small class="form-text text-muted">Optional</small>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label required">Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password" required minlength="8">
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <small class="form-text text-muted">Minimum 8 characters</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label required">Roles</label>
                                <div class="row">
                                    <?php foreach ($roles as $role): ?>
                                        <div class="col-md-4 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="roles[]" value="<?php echo $role['id']; ?>" id="role_<?php echo $role['id']; ?>" <?php echo in_array($role['id'], $_POST['roles'] ?? []) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="role_<?php echo $role['id']; ?>">
                                                    <?php echo htmlspecialchars($role['name']); ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" <?php echo isset($_POST['is_active']) ? 'checked' : 'checked'; ?>>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-2"></i>Create User
                                </button>
                                <a href="<?php echo APP_URL; ?>/modules/users/index.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-lg me-2"></i>Cancel
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
// Toggle password visibility
document.getElementById('togglePassword').addEventListener('click', function() {
    const passwordInput = document.getElementById('password');
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
</script>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
