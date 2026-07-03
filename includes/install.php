<?php
/**
 * Hotel & Resort Management System
 * Installation Check Helper
 * 
 * Checks system requirements and configuration
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Load configuration
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/config/database.php';

/**
 * Check if .env file exists
 * 
 * @return bool
 */
function checkEnvFile() {
    return file_exists(APP_ROOT . '/.env');
}

/**
 * Check required directories
 * 
 * @return array Result with success flag and missing directories
 */
function checkRequiredDirectories() {
    $requiredDirs = [
        APP_ROOT . '/uploads',
        APP_ROOT . '/uploads/rooms',
        APP_ROOT . '/logs',
        APP_ROOT . '/assets'
    ];
    
    $missing = [];
    
    foreach ($requiredDirs as $dir) {
        if (!is_dir($dir)) {
            $missing[] = $dir;
        }
    }
    
    return [
        'success' => empty($missing),
        'missing' => $missing
    ];
}

/**
 * Check upload directory permissions
 * 
 * @return array Result with success flag and permission issues
 */
function checkUploadPermissions() {
    $uploadDir = APP_ROOT . '/uploads';
    
    if (!is_dir($uploadDir)) {
        return [
            'success' => false,
            'message' => 'Upload directory does not exist'
        ];
    }
    
    if (!is_writable($uploadDir)) {
        return [
            'success' => false,
            'message' => 'Upload directory is not writable'
        ];
    }
    
    return [
        'success' => true,
        'message' => 'Upload directory is writable'
    ];
}

/**
 * Check database connection
 * 
 * @return array Result from verifyDatabaseConnection
 */
function checkDatabaseConnection() {
    return verifyDatabaseConnection();
}

/**
 * Check all system requirements
 * 
 * @return array Complete check results
 */
function checkSystemRequirements() {
    $results = [
        'env_file' => [
            'success' => checkEnvFile(),
            'message' => checkEnvFile() ? '.env file exists' : '.env file is missing'
        ],
        'directories' => checkRequiredDirectories(),
        'upload_permissions' => checkUploadPermissions(),
        'database' => checkDatabaseConnection()
    ];
    
    $allPassed = $results['env_file']['success'] && 
                  $results['directories']['success'] && 
                  $results['upload_permissions']['success'] && 
                  $results['database']['success'];
    
    return [
        'passed' => $allPassed,
        'checks' => $results
    ];
}

/**
 * Display installation error page
 * 
 * @param array $checks Check results
 * @return void
 */
function displayInstallError($checks) {
    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Installation Required</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: #f8f9fa;
                margin: 0;
                padding: 40px 20px;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
            }
            .container {
                max-width: 600px;
                background: white;
                padding: 40px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            h1 {
                color: #dc3545;
                margin-top: 0;
            }
            .check-item {
                padding: 10px 0;
                border-bottom: 1px solid #eee;
            }
            .check-item:last-child {
                border-bottom: none;
            }
            .success {
                color: #28a745;
            }
            .error {
                color: #dc3545;
            }
            .missing-list {
                margin-top: 5px;
                padding-left: 20px;
                color: #dc3545;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Installation Required</h1>
            <p>Please complete the following requirements before using the application:</p>
            
            <div class="check-item <?php echo $checks['env_file']['success'] ? 'success' : 'error'; ?>">
                <?php echo $checks['env_file']['success'] ? '✓' : '✗'; ?>
                <?php echo $checks['env_file']['message']; ?>
            </div>
            
            <div class="check-item <?php echo $checks['directories']['success'] ? 'success' : 'error'; ?>">
                <?php echo $checks['directories']['success'] ? '✓' : '✗'; ?>
                <?php echo $checks['directories']['success'] ? 'Required directories exist' : 'Required directories are missing'; ?>
                <?php if (!$checks['directories']['success']): ?>
                    <ul class="missing-list">
                        <?php foreach ($checks['directories']['missing'] as $dir): ?>
                            <li><?php echo htmlspecialchars($dir); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            
            <div class="check-item <?php echo $checks['upload_permissions']['success'] ? 'success' : 'error'; ?>">
                <?php echo $checks['upload_permissions']['success'] ? '✓' : '✗'; ?>
                <?php echo $checks['upload_permissions']['message']; ?>
            </div>
            
            <div class="check-item <?php echo $checks['database']['success'] ? 'success' : 'error'; ?>">
                <?php echo $checks['database']['success'] ? '✓' : '✗'; ?>
                <?php echo $checks['database']['message']; ?>
            </div>
            
            <p style="margin-top: 30px; color: #6c757d;">
                Please run the installer to configure your application.
            </p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

/**
 * Run installation check and display error if needed
 * 
 * @return void
 */
function runInstallCheck() {
    $checks = checkSystemRequirements();
    
    if (!$checks['passed']) {
        displayInstallError($checks['checks']);
    }
}
