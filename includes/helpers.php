<?php
/**
 * Hotel & Resort Management System
 * Helper Functions File
 * 
 * Reusable helper functions for the application
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Note: This file should be loaded by bootstrap.php which loads config.php
// Do NOT load bootstrap here to avoid circular dependency

/**
 * Get database connection instance
 * 
 * @return PDO
 */
function db() {
    static $db = null;
    if ($db === null) {
        require_once APP_ROOT . '/config/database.php';
        $db = getDB();
    }
    return $db;
}

/**
 * Redirect to a URL
 * 
 * @param string $url URL to redirect to
 * @param int $statusCode HTTP status code
 * @return void
 */
function redirect($url, $statusCode = 302) {
    header('Location: ' . $url, true, $statusCode);
    exit;
}

/**
 * Sanitize input data
 * 
 * @param mixed $data Data to sanitize
 * @return mixed Sanitized data
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Escape string for SQL (use prepared statements instead)
 * 
 * @param string $string String to escape
 * @return string Escaped string
 * @deprecated Use prepared statements instead
 */
function escape($string) {
    return db()->quote($string);
}

/**
 * Check if request is POST
 * 
 * @return bool
 */
function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Check if request is GET
 * 
 * @return bool
 */
function isGet() {
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

/**
 * Check if request is AJAX
 * 
 * @return bool
 */
function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Send JSON response
 * 
 * @param array $data Data to send
 * @param int $statusCode HTTP status code
 * @return void
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Generate UUID v4
 * 
 * @return string UUID
 */
function generateUUID() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Set version to 4
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Set variant to RFC 4122
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Get current logged-in user
 * 
 * @return array|null User data or null if not logged in
 */
function currentUser() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return $_SESSION['user'] ?? null;
}

/**
 * Get current user ID
 * 
 * @return int|null User ID or null if not logged in
 */
function currentUserId() {
    $user = currentUser();
    return $user['id'] ?? null;
}

/**
 * Check if user is logged in
 * 
 * @return bool
 */
function isLoggedIn() {
    return currentUser() !== null;
}

/**
 * Get client IP address
 * 
 * @return string IP address
 */
function getClientIP() {
    $ip = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

/**
 * Get user agent
 * 
 * @return string User agent string
 */
function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
}

/**
 * Get current URL
 * 
 * @return string Current URL
 */
function currentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Get base URL
 * 
 * @return string Base URL
 */
function baseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
}

/**
 * Format date
 * 
 * @param string $date Date string or timestamp
 * @param string $format Date format
 * @return string Formatted date
 */
function formatDate($date, $format = null) {
    $format = $format ?? DISPLAY_DATE_FORMAT;
    if (is_numeric($date)) {
        return date($format, $date);
    }
    return date($format, strtotime($date));
}

/**
 * Format datetime
 * 
 * @param string $datetime Datetime string or timestamp
 * @param string $format Datetime format
 * @return string Formatted datetime
 */
function formatDateTime($datetime, $format = null) {
    $format = $format ?? DISPLAY_DATETIME_FORMAT;
    if (is_numeric($datetime)) {
        return date($format, $datetime);
    }
    return date($format, strtotime($datetime));
}

/**
 * Format currency
 * 
 * @param float $amount Amount to format
 * @param string $currency Currency code
 * @return string Formatted currency
 */
function formatCurrency($amount, $currency = CURRENCY_CODE) {
    $symbol = CURRENCY_SYMBOL;
    $position = CURRENCY_POSITION;
    
    $formatted = number_format($amount, 2, '.', ',');
    
    if ($position === 'left') {
        return $symbol . $formatted;
    } else {
        return $formatted . $symbol;
    }
}

/**
 * Truncate string
 * 
 * @param string $string String to truncate
 * @param int $length Maximum length
 * @param string $suffix Suffix to add
 * @return string Truncated string
 */
function truncate($string, $length = 100, $suffix = '...') {
    if (strlen($string) <= $length) {
        return $string;
    }
    return substr($string, 0, $length) . $suffix;
}

/**
 * Generate random string
 * 
 * @param int $length Length of string
 * @return string Random string
 */
function randomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Get setting value
 * 
 * @param string $key Setting key
 * @param mixed $default Default value if not found
 * @return mixed Setting value
 */
function getSetting($key, $default = null) {
    try {
        $stmt = db()->prepare("SELECT setting_value, setting_type FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        
        if (!$result) {
            return $default;
        }
        
        $value = $result['setting_value'];
        $type = $result['setting_type'];
        
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $value;
            case 'json':
            case 'array':
                return json_decode($value, true);
            default:
                return $value;
        }
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            error_log("Get setting error: " . $e->getMessage());
        }
        return $default;
    }
}

/**
 * Set setting value
 * 
 * @param string $key Setting key
 * @param mixed $value Setting value
 * @param string $type Setting type
 * @param string $group Setting group. Defaults to 'general'
 * @param string $description Setting description
 * @return bool
 */
function setSetting($key, $value, $type = 'string', $group = 'general', $description = null) {
    try {
        $db = db();
        
        // Convert value based on type
        if (is_bool($value)) {
            $value = $value ? '1' : '0';
            $type = 'boolean';
        } elseif (is_int($value)) {
            $value = (string) $value;
            $type = 'integer';
        } elseif (is_array($value)) {
            $value = json_encode($value);
            $type = 'json';
        }
        
        // Check if setting exists
        $stmt = $db->prepare("SELECT id FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            // Update existing setting
            $stmt = $db->prepare("UPDATE system_settings SET setting_value = ?, setting_type = ?, `group` = ?, description = ? WHERE setting_key = ?");
            $stmt->execute([$value, $type, $group, $description, $key]);
        } else {
            // Insert new setting
            $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type, `group`, description) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$key, $value, $type, $group, $description]);
        }
        
        return true;
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            error_log("Set setting error: " . $e->getMessage());
        }
        return false;
    }
}

/**
 * Log activity
 * 
 * @param string $action Action performed
 * @param string $module Module name
 * @param string $description Description
 * @param array|null $oldValues Old values
 * @param array|null $newValues New values
 * @return bool
 */
function logActivity($action, $module, $description = null, $oldValues = null, $newValues = null) {
    try {
        $userId = currentUserId();
        $ipAddress = getClientIP();
        $userAgent = getUserAgent();
        
        $stmt = db()->prepare("
            INSERT INTO activity_logs 
            (user_id, action, module, description, ip_address, user_agent, old_values, new_values) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            $action,
            $module,
            $description,
            $ipAddress,
            $userAgent,
            $oldValues ? json_encode($oldValues) : null,
            $newValues ? json_encode($newValues) : null
        ]);
        
        return true;
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            error_log("Log activity error: " . $e->getMessage());
        }
        return false;
    }
}

/**
 * Get pagination info
 * 
 * @param int $total Total records
 * @param int $page Current page
 * @param int $perPage Items per page
 * @return array Pagination info
 */
function getPagination($total, $page = 1, $perPage = null) {
    $perPage = $perPage ?? ITEMS_PER_PAGE;
    $totalPages = ceil($total / $perPage);
    $offset = ($page - 1) * $perPage;
    
    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $page,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'has_next' => $page < $totalPages,
        'has_prev' => $page > 1
    ];
}

/**
 * Build pagination HTML
 * 
 * @param array $pagination Pagination info
 * @param string $url Base URL
 * @return string HTML
 */
function buildPagination($pagination, $url) {
    $html = '<nav aria-label="Page navigation"><ul class="pagination">';
    
    // Previous button
    if ($pagination['has_prev']) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $url . '?page=' . ($pagination['current_page'] - 1) . '">Previous</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
    }
    
    // Page numbers
    for ($i = 1; $i <= $pagination['total_pages']; $i++) {
        if ($i == $pagination['current_page']) {
            $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="' . $url . '?page=' . $i . '">' . $i . '</a></li>';
        }
    }
    
    // Next button
    if ($pagination['has_next']) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $url . '?page=' . ($pagination['current_page'] + 1) . '">Next</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Next</span></li>';
    }
    
    $html .= '</ul></nav>';
    
    return $html;
}

/**
 * Upload file
 * 
 * @param array $file $_FILES array item
 * @param string $destination Destination directory
 * @param array $allowedTypes Allowed file types
 * @param int $maxSize Maximum file size in bytes
 * @return array Result with success status and file path or error
 */
function uploadFile($file, $destination, $allowedTypes = null, $maxSize = null) {
    $allowedTypes = $allowedTypes ?? UPLOAD_ALLOWED_TYPES;
    $maxSize = $maxSize ?? UPLOAD_MAX_SIZE;
    
    // Check if file was uploaded
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'error' => 'No file uploaded'];
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        return ['success' => false, 'error' => $errors[$file['error']] ?? 'Unknown upload error'];
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File size exceeds maximum allowed'];
    }
    
    // Check file type
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExt, $allowedTypes)) {
        return ['success' => false, 'error' => 'File type not allowed'];
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . time() . '.' . $fileExt;
    $filepath = rtrim($destination, '/') . '/' . $filename;
    
    // Create directory if it doesn't exist
    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filepath' => $filepath, 'filename' => $filename];
    } else {
        return ['success' => false, 'error' => 'Failed to move uploaded file'];
    }
}

/**
 * Delete file
 * 
 * @param string $filepath File path
 * @return bool
 */
function deleteFile($filepath) {
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

/**
 * Send email (placeholder)
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $message Email message
 * @param array $headers Additional headers
 * @return bool
 */
function sendEmail($to, $subject, $message, $headers = []) {
    // Placeholder for email functionality
    // Implement with PHPMailer or similar in production
    if (!MAIL_ENABLED) {
        return false;
    }
    
    $defaultHeaders = [
        'From' => MAIL_FROM_EMAIL,
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/html; charset=UTF-8'
    ];
    
    $headers = array_merge($defaultHeaders, $headers);
    
    $headerString = '';
    foreach ($headers as $key => $value) {
        $headerString .= $key . ': ' . $value . "\r\n";
    }
    
    return mail($to, $subject, $message, $headerString);
}

/**
 * Debug variable
 * 
 * @param mixed $var Variable to debug
 * @param bool $die Die after output
 * @return void
 */
function debug($var, $die = true) {
    echo '<pre style="background: #f5f5f5; padding: 15px; border: 1px solid #ddd; margin: 10px;">';
    print_r($var);
    echo '</pre>';
    if ($die) {
        die();
    }
}

/**
 * Get all request inputs
 * 
 * @return array All request inputs
 */
function all() {
    return array_merge($_GET, $_POST);
}

/**
 * Get request input with sanitization
 * 
 * @param string $key Input key
 * @param mixed $default Default value
 * @param string $type Expected type (string, int, float, bool, email, url, phone, filename)
 * @return mixed Input value
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
 * Get old input value (for form repopulation)
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
 * Flash message to session
 * 
 * @param string $key Message key
 * @param mixed $value Message value
 * @return void
 */
function flash($key, $value = null) {
    if ($value === null) {
        $message = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $message;
    }
    $_SESSION['_flash'][$key] = $value;
}

/**
 * Check if has flash message
 * 
 * @param string $key Message key
 * @return bool
 */
function hasFlash($key) {
    return isset($_SESSION['_flash'][$key]);
}

/**
 * Back helper (redirect back)
 * 
 * @return void
 */
function back() {
    $referer = $_SERVER['HTTP_REFERER'] ?? baseUrl();
    redirect($referer);
}

/**
 * Asset URL helper
 * 
 * @param string $path Asset path
 * @return string Asset URL
 */
function asset($path) {
    return APP_URL . '/assets/' . ltrim($path, '/');
}

/**
 * URL helper
 * 
 * @param string $path URL path
 * @return string Full URL
 */
function url($path = '') {
    return APP_URL . '/' . ltrim($path, '/');
}

/**
 * Route helper (placeholder for routing)
 * 
 * @param string $name Route name
 * @param array $params Route parameters
 * @return string Route URL
 */
function route($name, $params = []) {
    // Placeholder for routing functionality
    return url($name);
}

/**
 * Check if environment is production
 * 
 * @return bool
 */
function isProduction() {
    return APP_ENV === 'production';
}

/**
 * Check if environment is development
 * 
 * @return bool
 */
function isDevelopment() {
    return APP_ENV === 'development';
}

/**
 * Get app version
 * 
 * @return string
 */
function version() {
    return APP_VERSION;
}

/**
 * Get app name
 * 
 * @return string
 */
function appName() {
    return APP_NAME;
}

/**
 * Render custom field input
 * 
 * @param array $field Custom field data
 * @param mixed $value Current value (optional)
 * @return string HTML input
 */
function renderCustomField($field, $value = '') {
    $fieldName = 'custom_field_' . $field['id'];
    $fieldId = 'custom_field_' . $field['id'];
    $required = $field['is_required'] ? 'required' : '';
    $placeholder = $field['placeholder'] ? 'placeholder="' . htmlspecialchars($field['placeholder']) . '"' : '';
    $defaultValue = $value !== '' ? $value : ($field['default_value'] ?? '');
    
    $html = '';
    
    switch ($field['field_type']) {
        case 'text':
            $html = '<input type="text" class="form-control" id="' . $fieldId . '" name="' . $fieldName . '" ' . $required . ' ' . $placeholder . ' value="' . htmlspecialchars($defaultValue) . '">';
            break;
            
        case 'number':
            $html = '<input type="number" class="form-control" id="' . $fieldId . '" name="' . $fieldName . '" ' . $required . ' ' . $placeholder . ' value="' . htmlspecialchars($defaultValue) . '">';
            break;
            
        case 'email':
            $html = '<input type="email" class="form-control" id="' . $fieldId . '" name="' . $fieldName . '" ' . $required . ' ' . $placeholder . ' value="' . htmlspecialchars($defaultValue) . '">';
            break;
            
        case 'phone':
            $html = '<input type="tel" class="form-control" id="' . $fieldId . '" name="' . $fieldName . '" ' . $required . ' ' . $placeholder . ' value="' . htmlspecialchars($defaultValue) . '">';
            break;
            
        case 'textarea':
            $html = '<textarea class="form-control" id="' . $fieldId . '" name="' . $fieldName . '" ' . $required . ' ' . $placeholder . ' rows="3">' . htmlspecialchars($defaultValue) . '</textarea>';
            break;
            
        case 'select':
            $options = json_decode($field['options'], true) ?: [];
            $html = '<select class="form-select" id="' . $fieldId . '" name="' . $fieldName . '" ' . $required . '>';
            $html .= '<option value="">Select Option</option>';
            foreach ($options as $key => $label) {
                $selected = $defaultValue == $key ? 'selected' : '';
                $html .= '<option value="' . htmlspecialchars($key) . '" ' . $selected . '>' . htmlspecialchars($label) . '</option>';
            }
            $html .= '</select>';
            break;
            
        case 'multi_select':
            $options = json_decode($field['options'], true) ?: [];
            $selectedValues = is_array($defaultValue) ? $defaultValue : (json_decode($defaultValue, true) ?: []);
            $html = '<select class="form-select" id="' . $fieldId . '" name="' . $fieldName . '[]" multiple>';
            foreach ($options as $key => $label) {
                $selected = in_array($key, $selectedValues) ? 'selected' : '';
                $html .= '<option value="' . htmlspecialchars($key) . '" ' . $selected . '>' . htmlspecialchars($label) . '</option>';
            }
            $html .= '</select>';
            break;
            
        case 'checkbox':
            $checked = $defaultValue ? 'checked' : '';
            $html = '<div class="form-check">';
            $html .= '<input class="form-check-input" type="checkbox" id="' . $fieldId . '" name="' . $fieldName . '" value="1" ' . $checked . '>';
            $html .= '<label class="form-check-label" for="' . $fieldId . '">Yes</label>';
            $html .= '</div>';
            break;
            
        case 'radio':
            $options = json_decode($field['options'], true) ?: [];
            $html = '<div>';
            foreach ($options as $key => $label) {
                $checked = $defaultValue == $key ? 'checked' : '';
                $html .= '<div class="form-check form-check-inline">';
                $html .= '<input class="form-check-input" type="radio" id="' . $fieldId . '_' . $key . '" name="' . $fieldName . '" value="' . htmlspecialchars($key) . '" ' . $checked . '>';
                $html .= '<label class="form-check-label" for="' . $fieldId . '_' . $key . '">' . htmlspecialchars($label) . '</label>';
                $html .= '</div>';
            }
            $html .= '</div>';
            break;
            
        case 'date':
            $html = '<input type="date" class="form-control" id="' . $fieldId . '" name="' . $fieldName . '" ' . $required . ' value="' . htmlspecialchars($defaultValue) . '">';
            break;
            
        case 'time':
            $html = '<input type="time" class="form-control" id="' . $fieldId . '" name="' . $fieldName . '" ' . $required . ' value="' . htmlspecialchars($defaultValue) . '">';
            break;
            
        case 'datetime':
            $html = '<input type="datetime-local" class="form-control" id="' . $fieldId . '" name="' . $fieldName . '" ' . $required . ' value="' . htmlspecialchars($defaultValue) . '">';
            break;
            
        case 'file':
            $html = '<input type="file" class="form-control" id="' . $fieldId . '" name="' . $fieldName . '" ' . $required . '>';
            break;
            
        case 'image':
            $html = '<input type="file" class="form-control" id="' . $fieldId . '" name="' . $fieldName . '" accept="image/*" ' . $required . '>';
            break;
            
        case 'url':
            $html = '<input type="url" class="form-control" id="' . $fieldId . '" name="' . $fieldName . '" ' . $required . ' ' . $placeholder . ' value="' . htmlspecialchars($defaultValue) . '">';
            break;
            
        default:
            $html = '<input type="text" class="form-control" id="' . $fieldId . '" name="' . $fieldName . '" ' . $required . ' ' . $placeholder . ' value="' . htmlspecialchars($defaultValue) . '">';
            break;
    }
    
    return $html;
}

/**
 * Format file size
 * 
 * @param int $bytes File size in bytes
 * @return string Formatted file size
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        return $bytes . ' bytes';
    } elseif ($bytes == 1) {
        return '1 byte';
    } else {
        return '0 bytes';
    }
}
