<?php
/**
 * Hotel & Resort Management System
 * Security Helper Functions
 * 
 * CSRF protection, XSS escaping, input sanitization
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Load configuration
require_once APP_ROOT . '/config/config.php';

/**
 * Generate CSRF token
 * 
 * @return string CSRF token
 */
function generateCsrfToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['_csrf_token'];
}

/**
 * Get CSRF token
 * 
 * @return string|null CSRF token or null if not set
 */
function getCsrfToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return $_SESSION['_csrf_token'] ?? null;
}

/**
 * Validate CSRF token
 * 
 * @param string $token Token to validate
 * @return bool
 */
function validateCsrfToken($token) {
    $sessionToken = getCsrfToken();
    
    if ($sessionToken === null) {
        return false;
    }
    
    return hash_equals($sessionToken, $token);
}

/**
 * Check CSRF token from request
 * 
 * @return bool
 */
function checkCsrfToken() {
    $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    
    if ($token === null) {
        return false;
    }
    
    return validateCsrfToken($token);
}

/**
 * Generate CSRF hidden input field
 * 
 * @return string HTML input field
 */
function csrfField() {
    $token = generateCsrfToken();
    return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Generate CSRF meta tag for AJAX requests
 * 
 * @return string HTML meta tag
 */
function csrfMeta() {
    $token = generateCsrfToken();
    return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Escape HTML to prevent XSS
 * 
 * @param string $string String to escape
 * @param int $flags Flags for htmlspecialchars
 * @return string Escaped string
 */
function e($string, $flags = ENT_QUOTES, $encoding = 'UTF-8') {
    return htmlspecialchars($string, $flags, $encoding);
}

/**
 * Escape JavaScript string
 * 
 * @param string $string String to escape
 * @return string Escaped string
 */
function escapeJs($string) {
    return json_encode($string, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

/**
 * Escape SQL identifier (table name, column name)
 * 
 * @param string $identifier Identifier to escape
 * @return string Escaped identifier
 */
function escapeIdentifier($identifier) {
    return '`' . str_replace('`', '``', $identifier) . '`';
}

/**
 * Sanitize email address
 * 
 * @param string $email Email to sanitize
 * @return string|false Sanitized email or false if invalid
 */
function sanitizeEmail($email) {
    $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Sanitize URL
 * 
 * @param string $url URL to sanitize
 * @return string|false Sanitized URL or false if invalid
 */
function sanitizeUrl($url) {
    $url = filter_var(trim($url), FILTER_SANITIZE_URL);
    return filter_var($url, FILTER_VALIDATE_URL);
}

/**
 * Sanitize integer
 * 
 * @param mixed $value Value to sanitize
 * @param int $min Minimum value
 * @param int $max Maximum value
 * @return int|false Sanitized integer or false if invalid
 */
function sanitizeInt($value, $min = null, $max = null) {
    $value = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    $int = filter_var($value, FILTER_VALIDATE_INT);
    
    if ($int === false) {
        return false;
    }
    
    if ($min !== null && $int < $min) {
        return false;
    }
    
    if ($max !== null && $int > $max) {
        return false;
    }
    
    return $int;
}

/**
 * Sanitize float
 * 
 * @param mixed $value Value to sanitize
 * @param float $min Minimum value
 * @param float $max Maximum value
 * @return float|false Sanitized float or false if invalid
 */
function sanitizeFloat($value, $min = null, $max = null) {
    $value = filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $float = filter_var($value, FILTER_VALIDATE_FLOAT);
    
    if ($float === false) {
        return false;
    }
    
    if ($min !== null && $float < $min) {
        return false;
    }
    
    if ($max !== null && $float > $max) {
        return false;
    }
    
    return $float;
}

/**
 * Sanitize boolean
 * 
 * @param mixed $value Value to sanitize
 * @return bool
 */
function sanitizeBool($value) {
    return filter_var($value, FILTER_VALIDATE_BOOLEAN);
}

/**
 * Sanitize string (remove tags, trim)
 * 
 * @param string $string String to sanitize
 * @param int $length Maximum length
 * @return string Sanitized string
 */
function sanitizeString($string, $length = null) {
    $string = strip_tags(trim($string));
    
    if ($length !== null) {
        $string = mb_substr($string, 0, $length, 'UTF-8');
    }
    
    return $string;
}

/**
 * Sanitize phone number
 * 
 * @param string $phone Phone number to sanitize
 * @return string Sanitized phone number
 */
function sanitizePhone($phone) {
    return preg_replace('/[^0-9+\s-]/', '', trim($phone));
}

/**
 * Sanitize filename
 * 
 * @param string $filename Filename to sanitize
 * @return string Sanitized filename
 */
function sanitizeFilename($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    $filename = preg_replace('/_{2,}/', '_', $filename);
    return trim($filename, '._-');
}

/**
 * Sanitize array recursively
 * 
 * @param array $array Array to sanitize
 * @param callable $sanitizer Sanitizer function
 * @return array Sanitized array
 */
function sanitizeArray(array $array, callable $sanitizer = null) {
    $sanitizer = $sanitizer ?? function($value) {
        return is_string($value) ? sanitizeString($value) : $value;
    };
    
    return array_map(function($value) use ($sanitizer) {
        if (is_array($value)) {
            return sanitizeArray($value, $sanitizer);
        }
        return $sanitizer($value);
    }, $array);
}

/**
 * Validate password strength
 * 
 * @param string $password Password to validate
 * @param int $minLength Minimum length
 * @return array Result with valid flag and errors
 */
function validatePasswordStrength($password, $minLength = 8) {
    $errors = [];
    
    if (strlen($password) < $minLength) {
        $errors[] = "Password must be at least {$minLength} characters";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Hash password using PHP password API
 * 
 * @param string $password Plain text password
 * @param int $algo Password algorithm constant
 * @param array $options Password hash options
 * @return string Hashed password
 */
function hashPassword($password, $algo = PASSWORD_DEFAULT, $options = []) {
    $defaultOptions = ['cost' => 12];
    $options = array_merge($defaultOptions, $options);
    return password_hash($password, $algo, $options);
}

/**
 * Verify password against hash
 * 
 * @param string $password Plain text password
 * @param string $hash Hashed password
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Check if password needs rehashing
 * 
 * @param string $hash Hashed password
 * @param int $algo Password algorithm constant
 * @param array $options Password hash options
 * @return bool
 */
function passwordNeedsRehash($hash, $algo = PASSWORD_DEFAULT, $options = []) {
    return password_needs_rehash($hash, $algo, $options);
}

/**
 * Generate random token
 * 
 * @param int $length Token length
 * @return string Random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Generate secure random bytes
 * 
 * @param int $length Number of bytes
 * @return string Random bytes
 */
function randomBytes($length) {
    return random_bytes($length);
}

/**
 * Generate secure random integer
 * 
 * @param int $min Minimum value
 * @param int $max Maximum value
 * @return int Random integer
 */
function randomInt($min, $max) {
    return random_int($min, $max);
}

/**
 * Validate IP address
 * 
 * @param string $ip IP address to validate
 * @return bool
 */
function validateIp($ip) {
    return filter_var($ip, FILTER_VALIDATE_IP) !== false;
}

/**
 * Validate IPv4 address
 * 
 * @param string $ip IP address to validate
 * @return bool
 */
function validateIpv4($ip) {
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
}

/**
 * Validate IPv6 address
 * 
 * @param string $ip IP address to validate
 * @return bool
 */
function validateIpv6($ip) {
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
}

/**
 * Check if IP is in private range
 * 
 * @param string $ip IP address to check
 * @return bool
 */
function isPrivateIp($ip) {
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) === false;
}

/**
 * Rate limit check
 * 
 * @param string $identifier Identifier (IP, user ID, etc.)
 * @param int $maxAttempts Maximum attempts
 * @param int $windowSeconds Time window in seconds
 * @return array Result with allowed flag and remaining attempts
 */
function rateLimit($identifier, $maxAttempts = 5, $windowSeconds = 60) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $key = '_rate_limit_' . md5($identifier);
    $now = time();
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'attempts' => 0,
            'window_start' => $now
        ];
    }
    
    $data = $_SESSION[$key];
    
    // Reset if window expired
    if ($now - $data['window_start'] > $windowSeconds) {
        $data['attempts'] = 0;
        $data['window_start'] = $now;
    }
    
    $data['attempts']++;
    $_SESSION[$key] = $data;
    
    $allowed = $data['attempts'] <= $maxAttempts;
    $remaining = max(0, $maxAttempts - $data['attempts']);
    $resetTime = $data['window_start'] + $windowSeconds;
    
    return [
        'allowed' => $allowed,
        'remaining' => $remaining,
        'reset_time' => $resetTime,
        'attempts' => $data['attempts']
    ];
}

/**
 * Clear rate limit
 * 
 * @param string $identifier Identifier
 * @return void
 */
function clearRateLimit($identifier) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $key = '_rate_limit_' . md5($identifier);
    unset($_SESSION[$key]);
}

/**
 * Validate request method
 * 
 * @param string $method Expected method
 * @return bool
 */
function validateRequestMethod($method) {
    return strtoupper($_SERVER['REQUEST_METHOD']) === strtoupper($method);
}

/**
 * Validate AJAX request
 * 
 * @return bool
 */
function validateAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Validate same origin
 * 
 * @return bool
 */
function validateSameOrigin() {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
    $allowedOrigins = [APP_URL];
    
    if (empty($origin)) {
        return true; // Allow if no origin header
    }
    
    foreach ($allowedOrigins as $allowed) {
        if (strpos($origin, $allowed) === 0) {
            return true;
        }
    }
    
    return false;
}

/**
 * Sanitize user input from $_GET or $_POST
 * 
 * @param string $key Input key
 * @param mixed $default Default value
 * @param string $type Expected type (string, int, float, bool, email, url)
 * @return mixed Sanitized value or default
 */
function input($key, $default = null, $type = 'string') {
    $value = $_REQUEST[$key] ?? $default;
    
    if ($value === null) {
        return $default;
    }
    
    switch ($type) {
        case 'int':
            return sanitizeInt($value) ?? $default;
        case 'float':
            return sanitizeFloat($value) ?? $default;
        case 'bool':
            return sanitizeBool($value);
        case 'email':
            return sanitizeEmail($value) ?? $default;
        case 'url':
            return sanitizeUrl($value) ?? $default;
        case 'phone':
            return sanitizePhone($value);
        case 'filename':
            return sanitizeFilename($value);
        default:
            return sanitizeString($value);
    }
}

/**
 * Clean old input from session
 * 
 * @return void
 */
function clearOldInput() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    unset($_SESSION['_old_input']);
}

/**
 * Flash old input to session
 * 
 * @param array $data Data to flash
 * @return void
 */
function flashOldInput(array $data = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if ($data === null) {
        $_SESSION['_old_input'] = $_POST;
    } else {
        $_SESSION['_old_input'] = $data;
    }
}

/**
 * Get old input value
 * 
 * @param string $key Input key
 * @param mixed $default Default value
 * @return mixed Old input value
 */
function old($key, $default = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return $_SESSION['_old_input'][$key] ?? $default;
}

/**
 * Security headers
 * 
 * @return void
 */
function securityHeaders() {
    // Prevent clickjacking
    header('X-Frame-Options: SAMEORIGIN');
    
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Enable XSS protection
    header('X-XSS-Protection: 1; mode=block');
    
    // Referrer policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy (basic)
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://unpkg.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self';");
    
    // HSTS (only in production with HTTPS)
    if (isProduction() && isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

/**
 * Check if user agent is suspicious
 * 
 * @param string $userAgent User agent string
 * @return bool
 */
function isSuspiciousUserAgent($userAgent) {
    $suspiciousPatterns = [
        '/bot/i',
        '/crawl/i',
        '/spider/i',
        '/curl/i',
        '/wget/i',
        '/python/i',
        '/java/i',
        '/perl/i'
    ];
    
    foreach ($suspiciousPatterns as $pattern) {
        if (preg_match($pattern, $userAgent)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Log security event
 * 
 * @param string $event Event type
 * @param string $description Event description
 * @param array $context Additional context
 * @return bool
 */
function logSecurityEvent($event, $description, $context = []) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event' => $event,
        'description' => $description,
        'ip' => getClientIP(),
        'user_agent' => getUserAgent(),
        'context' => $context
    ];
    
    $logMessage = json_encode($logEntry);
    
    if (LOG_ENABLED) {
        error_log("[SECURITY] " . $logMessage);
    }
    
    return true;
}
