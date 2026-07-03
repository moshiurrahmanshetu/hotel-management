<?php
/**
 * Hotel & Resort Management System
 * Reset Password Page
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__FILE__));
}

// Load configuration and authentication
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/includes/auth.php';

// Redirect if already authenticated
if (isAuthenticated()) {
    redirect(APP_URL . '/dashboard.php');
}

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

// Validate token first
$tokenValid = false;
if ($token) {
    $validation = validatePasswordResetToken($token);
    $tokenValid = $validation['success'];
    
    if (!$tokenValid) {
        $error = $validation['message'];
    }
} else {
    $error = 'Invalid or missing reset token.';
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    // Validate CSRF token
    if (!checkCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (!$password) {
            $error = 'Please enter a new password.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } else {
            $result = resetPassword($token, $password);
            
            if ($result['success']) {
                $success = $result['message'];
                // Redirect to login after successful reset
                header('refresh:3;url=' . APP_URL . '/login.php');
            } else {
                $error = $result['message'];
            }
        }
    }
}

$page_title = 'Reset Password';
$page_description = 'Reset your password for ' . APP_NAME;
?>
<?php require_once APP_ROOT . '/includes/header.php'; ?>

<div class="login-page">
    <div class="login-container" data-aos="fade-up">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <i class="bi bi-lock-fill"></i>
                </div>
                <h1>Reset Password</h1>
                <p class="login-subtitle">Enter your new password below</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    <?php echo $success; ?>
                </div>
                <p class="text-center mt-3">
                    <a href="<?php echo APP_URL; ?>/login.php" class="btn btn-primary">Go to Login</a>
                </p>
            <?php endif; ?>
            
            <?php if ($tokenValid && !$success): ?>
            <form class="login-form" action="" method="POST">
                <?php echo csrfField(); ?>
                
                <div class="form-group mb-3">
                    <label for="password" class="form-label">New Password</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-lock"></i>
                        </span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter new password" required minlength="8">
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <small class="form-text text-muted">Password must be at least 8 characters long.</small>
                </div>
                
                <div class="form-group mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-lock-fill"></i>
                        </span>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required minlength="8">
                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group mb-3">
                    <button type="submit" class="btn btn-primary btn-block w-100">
                        <span class="btn-text">Reset Password</span>
                        <span class="btn-loader d-none">
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        </span>
                    </button>
                </div>
            </form>
            
            <div class="login-footer">
                <p class="mb-0">
                    <a href="<?php echo APP_URL; ?>/login.php">
                        <i class="bi bi-arrow-left me-1"></i> Back to Login
                    </a>
                </p>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="login-info">
            <div class="info-content">
                <h2>Create New Password</h2>
                <p>Choose a strong password to secure your account. Make sure it's something you haven't used before.</p>
                <ul class="feature-list">
                    <li><i class="bi bi-shield-check"></i> At least 8 characters</li>
                    <li><i class="bi bi-text-uppercase"></i> Mix of uppercase & lowercase</li>
                    <li><i class="bi bi-type-numeric"></i> Include numbers</li>
                    <li><i class="bi bi-type"></i> Use special characters</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
    .login-page {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 20px;
    }
    
    .login-container {
        display: flex;
        max-width: 900px;
        width: 100%;
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        overflow: hidden;
    }
    
    .login-card {
        flex: 1;
        padding: 50px;
    }
    
    .login-header {
        text-align: center;
        margin-bottom: 30px;
    }
    
    .login-logo {
        font-size: 60px;
        color: #667eea;
        margin-bottom: 20px;
    }
    
    .login-header h1 {
        font-size: 24px;
        font-weight: 700;
        color: #333;
        margin-bottom: 10px;
    }
    
    .login-subtitle {
        color: #666;
        font-size: 14px;
    }
    
    .login-form .input-group-text {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-right: none;
    }
    
    .login-form .form-control {
        border-left: none;
        padding-left: 0;
    }
    
    .login-form .form-control:focus {
        border-color: #667eea;
        box-shadow: none;
    }
    
    .login-form .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        padding: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .login-form .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
    }
    
    .login-footer {
        text-align: center;
        margin-top: 20px;
    }
    
    .login-footer a {
        color: #667eea;
        text-decoration: none;
    }
    
    .login-footer a:hover {
        text-decoration: underline;
    }
    
    .login-info {
        flex: 1;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 50px;
        display: flex;
        align-items: center;
        color: white;
    }
    
    .info-content h2 {
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 20px;
    }
    
    .info-content p {
        font-size: 16px;
        line-height: 1.6;
        margin-bottom: 30px;
        opacity: 0.9;
    }
    
    .feature-list {
        list-style: none;
        padding: 0;
    }
    
    .feature-list li {
        padding: 10px 0;
        font-size: 15px;
    }
    
    .feature-list i {
        margin-right: 10px;
        font-size: 18px;
    }
    
    @media (max-width: 768px) {
        .login-container {
            flex-direction: column;
        }
        
        .login-info {
            display: none;
        }
        
        .login-card {
            padding: 30px;
        }
    }
</style>

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
    
    // Form submission loading state
    document.querySelector('.login-form').addEventListener('submit', function(e) {
        const btn = this.querySelector('button[type="submit"]');
        const btnText = btn.querySelector('.btn-text');
        const btnLoader = btn.querySelector('.btn-loader');
        
        btnText.classList.add('d-none');
        btnLoader.classList.remove('d-none');
        btn.disabled = true;
    });
</script>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
