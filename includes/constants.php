<?php
/**
 * Hotel & Resort Management System
 * Constants File
 * 
 * Application-wide constants
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Note: This file is loaded by config.php, so config is already available

// Status constants
define('STATUS_ACTIVE', 1);
define('STATUS_INACTIVE', 0);

// User roles
define('ROLE_ADMIN', 'admin');
define('ROLE_MANAGER', 'manager');
define('ROLE_STAFF', 'staff');

// Permission types
define('PERMISSION_CREATE', 'create');
define('PERMISSION_READ', 'read');
define('PERMISSION_UPDATE', 'update');
define('PERMISSION_DELETE', 'delete');

// Date formats
define('DATE_SHORT', 'Y-m-d');
define('DATE_LONG', 'F j, Y');
define('DATETIME_SHORT', 'Y-m-d H:i');
define('DATETIME_LONG', 'F j, Y g:i A');

// File size limits
define('MAX_FILE_SIZE', 10485760); // 10MB
define('MAX_IMAGE_SIZE', 5242880); // 5MB

// Pagination
define('DEFAULT_PAGE', 1);
define('DEFAULT_PER_PAGE', 20);
define('MAX_PER_PAGE', 100);
