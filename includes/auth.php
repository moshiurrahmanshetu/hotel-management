<?php
/**
 * Hotel & Resort Management System
 * Authentication Helper Functions
 * 
 * Role-Based Access Control (RBAC) and Authentication helpers
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Note: helpers.php and security.php are loaded by config.php which is loaded by bootstrap.php
// This file should be loaded after bootstrap.php

/**
 * Start secure session
 */
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path' => '/',
            'domain' => '',
            'secure' => SESSION_SECURE,
            'httponly' => SESSION_HTTPONLY,
            'samesite' => SESSION_SAMESITE
        ]);
        session_start();
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['_created'])) {
            $_SESSION['_created'] = time();
        } else if (time() - $_SESSION['_created'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['_created'] = time();
        }
    }
}

/**
 * Check if user is authenticated
 * 
 * @return bool
 */
function isAuthenticated() {
    startSecureSession();
    return isset($_SESSION['user_id']) && isset($_SESSION['user_email']);
}

/**
 * Get current authenticated user
 * 
 * @return array|null
 */
function authUser() {
    if (!isAuthenticated()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'uuid' => $_SESSION['user_uuid'] ?? null,
        'first_name' => $_SESSION['user_first_name'] ?? '',
        'last_name' => $_SESSION['user_last_name'] ?? '',
        'email' => $_SESSION['user_email'],
        'avatar' => $_SESSION['user_avatar'] ?? null,
        'roles' => $_SESSION['user_roles'] ?? []
    ];
}

/**
 * Get current user ID
 * 
 * @return int|null
 */
function authId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Check if user has specific role
 * 
 * @param string|array $roles Role slug or array of role slugs
 * @return bool
 */
function hasRole($roles) {
    $user = authUser();
    if (!$user) {
        return false;
    }
    
    $userRoles = $user['roles'] ?? [];
    $roles = is_array($roles) ? $roles : [$roles];
    
    foreach ($roles as $role) {
        if (in_array($role, $userRoles)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Check if user has specific permission
 * 
 * @param string|array $permissions Permission slug or array of permission slugs
 * @return bool
 */
function hasPermission($permissions) {
    $user = authUser();
    if (!$user) {
        return false;
    }
    
    // Super admin has all permissions
    if (in_array('super_admin', $user['roles'])) {
        return true;
    }
    
    $userPermissions = $_SESSION['user_permissions'] ?? [];
    $permissions = is_array($permissions) ? $permissions : [$permissions];
    
    foreach ($permissions as $permission) {
        if (in_array($permission, $userPermissions)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Require authentication - redirect to login if not authenticated
 * 
 * @return void
 */
function requireAuth() {
    if (!isAuthenticated()) {
        $_SESSION['_redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect(APP_URL . '/login.php');
    }
}

/**
 * Require specific role - redirect to 403 if user doesn't have role
 * 
 * @param string|array $roles Role slug or array of role slugs
 * @return void
 */
function requireRole($roles) {
    requireAuth();
    
    if (!hasRole($roles)) {
        redirect(APP_URL . '/403.php');
    }
}

/**
 * Require specific permission - redirect to 403 if user doesn't have permission
 * 
 * @param string|array $permissions Permission slug or array of permission slugs
 * @return void
 */
function requirePermission($permissions) {
    requireAuth();
    
    if (!hasPermission($permissions)) {
        redirect(APP_URL . '/403.php');
    }
}

/**
 * Attempt user login
 * 
 * @param string $email User email
 * @param string $password User password
 * @param bool $remember Remember me
 * @return array Result with success, message, and user data
 */
function attemptLogin($email, $password, $remember = false) {
    $db = getDB();
    
    try {
        // Check if account is locked
        $stmt = $db->prepare("SELECT locked_until FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && $user['locked_until'] && strtotime($user['locked_until']) > time()) {
            return [
                'success' => false,
                'message' => 'Account is temporarily locked. Please try again later.'
            ];
        }
        
        // Get user with roles
        $stmt = $db->prepare("
            SELECT u.*, 
                   GROUP_CONCAT(r.slug) as roles
            FROM users u
            LEFT JOIN user_roles ur ON u.id = ur.user_id
            LEFT JOIN roles r ON ur.role_id = r.id
            WHERE u.email = ? AND u.is_active = 1
            GROUP BY u.id
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            // Log failed attempt
            logLoginAttempt(null, $email, false);
            return [
                'success' => false,
                'message' => 'Invalid credentials'
            ];
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            // Increment failed attempts
            $failedAttempts = $user['failed_login_attempts'] + 1;
            $lockedUntil = null;
            
            // Lock account after 5 failed attempts for 30 minutes
            if ($failedAttempts >= 5) {
                $lockedUntil = date('Y-m-d H:i:s', time() + 1800);
            }
            
            $updateStmt = $db->prepare("
                UPDATE users 
                SET failed_login_attempts = ?, locked_until = ? 
                WHERE id = ?
            ");
            $updateStmt->execute([$failedAttempts, $lockedUntil, $user['id']]);
            
            // Log failed attempt
            logLoginAttempt($user['id'], $email, false);
            
            return [
                'success' => false,
                'message' => $failedAttempts >= 5 
                    ? 'Account locked due to too many failed attempts. Please try again in 30 minutes.'
                    : 'Invalid credentials'
            ];
        }
        
        // Check if password needs rehash
        if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $updateStmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $updateStmt->execute([$newHash, $user['id']]);
        }
        
        // Reset failed attempts
        $updateStmt = $db->prepare("
            UPDATE users 
            SET failed_login_attempts = 0, locked_until = NULL, 
                last_login_at = NOW(), last_login_ip = ? 
            WHERE id = ?
        ");
        $updateStmt->execute([getClientIP(), $user['id']]);
        
        // Get user permissions
        $stmt = $db->prepare("
            SELECT p.slug
            FROM permissions p
            INNER JOIN role_permissions rp ON p.id = rp.permission_id
            INNER JOIN user_roles ur ON rp.role_id = ur.role_id
            WHERE ur.user_id = ?
        ");
        $stmt->execute([$user['id']]);
        $permissions = array_column($stmt->fetchAll(), 'slug');
        
        // Start session and set user data
        startSecureSession();
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_uuid'] = $user['uuid'];
        $_SESSION['user_first_name'] = $user['first_name'];
        $_SESSION['user_last_name'] = $user['last_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_avatar'] = $user['avatar'];
        $_SESSION['user_roles'] = $user['roles'] ? explode(',', $user['roles']) : [];
        $_SESSION['user_permissions'] = $permissions;
        $_SESSION['_created'] = time();
        
        // Handle remember me
        if ($remember) {
            $token = generateToken();
            $expiresAt = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)); // 30 days
            
            $updateStmt = $db->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
            $updateStmt->execute([$token, $user['id']]);
            
            setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
        }
        
        // Log successful login
        logLoginAttempt($user['id'], $email, true);
        logActivity('login', 'auth', 'User logged in successfully');
        
        // Log session
        logUserSession($user['id']);
        
        return [
            'success' => true,
            'message' => 'Login successful',
            'user' => authUser()
        ];
        
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            error_log("Login error: " . $e->getMessage());
        }
        return [
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ];
    }
}

/**
 * Logout user
 * 
 * @return void
 */
function logout() {
    startSecureSession();
    
    $userId = authId();
    
    // Log activity
    if ($userId) {
        logActivity('logout', 'auth', 'User logged out');
    }
    
    // Clear remember token
    if (isset($_COOKIE['remember_token'])) {
        $db = getDB();
        $stmt = $db->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
        $stmt->execute([$userId]);
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    }
    
    // Destroy session
    $_SESSION = [];
    
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/', '', false, true);
    }
    
    session_destroy();
}

/**
 * Check remember me token
 * 
 * @return bool
 */
function checkRememberMe() {
    if (!isset($_COOKIE['remember_token'])) {
        return false;
    }
    
    $token = $_COOKIE['remember_token'];
    $db = getDB();
    
    try {
        $stmt = $db->prepare("
            SELECT u.*, 
                   GROUP_CONCAT(r.slug) as roles
            FROM users u
            LEFT JOIN user_roles ur ON u.id = ur.user_id
            LEFT JOIN roles r ON ur.role_id = r.id
            WHERE u.remember_token = ? AND u.is_active = 1
            GROUP BY u.id
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return false;
        }
        
        // Get user permissions
        $stmt = $db->prepare("
            SELECT p.slug
            FROM permissions p
            INNER JOIN role_permissions rp ON p.id = rp.permission_id
            INNER JOIN user_roles ur ON rp.role_id = ur.role_id
            WHERE ur.user_id = ?
        ");
        $stmt->execute([$user['id']]);
        $permissions = array_column($stmt->fetchAll(), 'slug');
        
        // Start session and set user data
        startSecureSession();
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_uuid'] = $user['uuid'];
        $_SESSION['user_first_name'] = $user['first_name'];
        $_SESSION['user_last_name'] = $user['last_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_avatar'] = $user['avatar'];
        $_SESSION['user_roles'] = $user['roles'] ? explode(',', $user['roles']) : [];
        $_SESSION['user_permissions'] = $permissions;
        $_SESSION['_created'] = time();
        
        // Log session
        logUserSession($user['id']);
        
        return true;
        
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            error_log("Remember me error: " . $e->getMessage());
        }
        return false;
    }
}

/**
 * Log login attempt
 * 
 * @param int|null $userId User ID
 * @param string $email Email address
 * @param bool $success Success status
 * @return bool
 */
function logLoginAttempt($userId, $email, $success) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO login_attempts (user_id, email, ip_address, user_agent, success)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $userId,
            $email,
            getClientIP(),
            getUserAgent(),
            $success ? 1 : 0
        ]);
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            error_log("Log login attempt error: " . $e->getMessage());
        }
        return false;
    }
}

/**
 * Log user session
 * 
 * @param int $userId User ID
 * @return bool
 */
function logUserSession($userId) {
    try {
        $db = getDB();
        $sessionId = session_id();
        
        // Check if session already exists
        $stmt = $db->prepare("SELECT id FROM user_sessions WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing session
            $stmt = $db->prepare("
                UPDATE user_sessions 
                SET user_id = ?, ip_address = ?, user_agent = ?, last_activity = NOW()
                WHERE session_id = ?
            ");
            return $stmt->execute([$userId, getClientIP(), getUserAgent(), $sessionId]);
        } else {
            // Insert new session
            $stmt = $db->prepare("
                INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent)
                VALUES (?, ?, ?, ?)
            ");
            return $stmt->execute([$userId, $sessionId, getClientIP(), getUserAgent()]);
        }
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            error_log("Log session error: " . $e->getMessage());
        }
        return false;
    }
}

/**
 * Create password reset token
 * 
 * @param string $email Email address
 * @return array Result with success and message
 */
function createPasswordResetToken($email) {
    $db = getDB();
    
    try {
        // Get user
        $stmt = $db->prepare("SELECT id, email FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'If an account exists with this email, a password reset link has been sent.'
            ];
        }
        
        // Generate token
        $token = generateToken() . bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour
        
        // Delete any existing tokens for this user
        $stmt = $db->prepare("DELETE FROM password_resets WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        
        // Insert new token
        $stmt = $db->prepare("
            INSERT INTO password_resets (user_id, email, token, ip_address, user_agent, expires_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user['id'],
            $email,
            $token,
            getClientIP(),
            getUserAgent(),
            $expiresAt
        ]);
        
        // In production, send email with reset link
        // For now, return the token for testing
        return [
            'success' => true,
            'message' => 'Password reset link has been sent to your email.',
            'token' => $token // Remove this in production
        ];
        
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            error_log("Create password reset token error: " . $e->getMessage());
        }
        return [
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ];
    }
}

/**
 * Validate password reset token
 * 
 * @param string $token Reset token
 * @return array Result with success and user data
 */
function validatePasswordResetToken($token) {
    $db = getDB();
    
    try {
        $stmt = $db->prepare("
            SELECT pr.*, u.id as user_id, u.email
            FROM password_resets pr
            INNER JOIN users u ON pr.user_id = u.id
            WHERE pr.token = ? AND pr.used_at IS NULL AND pr.expires_at > NOW()
            ORDER BY pr.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $reset = $stmt->fetch();
        
        if (!$reset) {
            return [
                'success' => false,
                'message' => 'Invalid or expired reset token.'
            ];
        }
        
        return [
            'success' => true,
            'user_id' => $reset['user_id'],
            'email' => $reset['email']
        ];
        
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            error_log("Validate reset token error: " . $e->getMessage());
        }
        return [
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ];
    }
}

/**
 * Reset password
 * 
 * @param string $token Reset token
 * @param string $password New password
 * @return array Result with success and message
 */
function resetPassword($token, $password) {
    $db = getDB();
    
    try {
        // Validate token
        $validation = validatePasswordResetToken($token);
        if (!$validation['success']) {
            return $validation;
        }
        
        $userId = $validation['user_id'];
        
        // Validate password strength
        $passwordValidation = validatePasswordStrength($password);
        if (!$passwordValidation['valid']) {
            return [
                'success' => false,
                'message' => implode(', ', $passwordValidation['errors'])
            ];
        }
        
        // Hash new password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Update password
        $stmt = $db->prepare("
            UPDATE users 
            SET password = ?, must_change_password = 0, failed_login_attempts = 0, locked_until = NULL
            WHERE id = ?
        ");
        $stmt->execute([$hashedPassword, $userId]);
        
        // Mark token as used
        $stmt = $db->prepare("UPDATE password_resets SET used_at = NOW() WHERE token = ?");
        $stmt->execute([$token]);
        
        // Log activity
        logActivity('password_reset', 'auth', 'User reset password', $userId);
        
        return [
            'success' => true,
            'message' => 'Password has been reset successfully. You can now login with your new password.'
        ];
        
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            error_log("Reset password error: " . $e->getMessage());
        }
        return [
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ];
    }
}

/**
 * Get redirect URL after login
 * 
 * @return string
 */
function getRedirectUrl() {
    $redirect = $_SESSION['_redirect_after_login'] ?? APP_URL . '/dashboard.php';
    unset($_SESSION['_redirect_after_login']);
    return $redirect;
}

/**
 * Check if route is accessible (for middleware)
 * 
 * @param string $route Route to check
 * @return bool
 */
function canAccessRoute($route) {
    // Define route permissions
    $routePermissions = [
        'dashboard' => [],
        'users' => ['users.view'],
        'roles' => ['roles.view'],
        'settings' => ['settings.view'],
        // Add more routes as needed
    ];
    
    if (!isset($routePermissions[$route])) {
        return true; // Allow if no specific permission required
    }
    
    $requiredPermissions = $routePermissions[$route];
    return hasPermission($requiredPermissions);
}

// Initialize remember me check on every page load
if (session_status() === PHP_SESSION_NONE) {
    startSecureSession();
}

if (!isAuthenticated()) {
    checkRememberMe();
}
