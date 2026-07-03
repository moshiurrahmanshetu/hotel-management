<?php
/**
 * Hotel & Resort Management System
 * Installer Checker Class
 * 
 * Comprehensive installation verification with auto-fix capabilities
 * This file runs BEFORE config.php to ensure all directories exist
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}
require_once APP_ROOT . '/includes/env.php';

class InstallerChecker {
    private $checks = [];
    private $errors = [];
    private $warnings = [];
    private $autoFixed = [];
    
    /**
     * Run all installation checks with auto-creation
     * 
     * @return array Check results
     */
    public function runAllChecks() {
        // Create all required directories first
        $this->createAllDirectories();
        
        // Then run checks
        $this->checkEnvFile();
        $this->checkDatabaseConnection();
        $this->checkRequiredDirectories();
        $this->checkDirectoryPermissions();
        $this->checkConfigFiles();
        $this->checkRequiredFiles();
        $this->checkHtaccess();
        $this->createLogFiles();
        
        return [
            'passed' => empty($this->errors),
            'checks' => $this->checks,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'auto_fixed' => $this->autoFixed
        ];
    }
    
    /**
     * Create all required directories
     */
    private function createAllDirectories() {
        $requiredDirs = [
            'logs' => APP_ROOT . '/logs',
            'uploads' => APP_ROOT . '/uploads',
            'uploads/rooms' => APP_ROOT . '/uploads/rooms',
            'uploads/users' => APP_ROOT . '/uploads/users',
            'uploads/temp' => APP_ROOT . '/uploads/temp',
            'cache' => APP_ROOT . '/cache',
            'assets' => APP_ROOT . '/assets',
            'assets/css' => APP_ROOT . '/assets/css',
            'assets/js' => APP_ROOT . '/assets/js',
            'assets/images' => APP_ROOT . '/assets/images',
            'modules' => APP_ROOT . '/modules',
            'includes' => APP_ROOT . '/includes',
            'config' => APP_ROOT . '/config',
            'database' => APP_ROOT . '/database',
            'api' => APP_ROOT . '/api'
        ];
        
        foreach ($requiredDirs as $name => $path) {
            if (!is_dir($path)) {
                if (mkdir($path, 0755, true)) {
                    $this->autoFixed[] = "Directory created: {$name}";
                }
            }
        }
    }
    
    /**
     * Check .env file
     */
    private function checkEnvFile() {
        $envFile = APP_ROOT . '/.env';
        $envExample = APP_ROOT . '/.env.example';
        
        $result = [
            'name' => '.env File',
            'required' => true,
            'passed' => file_exists($envFile),
            'message' => file_exists($envFile) ? '.env file exists' : '.env file is missing'
        ];
        
        if (!$result['passed']) {
            $this->errors[] = $result['message'];
            
            // Auto-fix: copy from example
            if (file_exists($envExample)) {
                if (copy($envExample, $envFile)) {
                    $result['passed'] = true;
                    $result['message'] = '.env file created from .env.example';
                    $result['auto_fixed'] = true;
                    $this->autoFixed[] = '.env file created';
                    $this->errors = array_diff($this->errors, ['.env file is missing']);
                }
            }
        }
        
        $this->checks['env_file'] = $result;
    }
    
    /**
     * Check database connection
     */
    private function checkDatabaseConnection() {
        $result = [
            'name' => 'Database Connection',
            'required' => true,
            'passed' => false,
            'message' => 'Database connection failed'
        ];
        
        try {
            $dsn = "mysql:host=" . env('DB_HOST', 'localhost') .
       ";port=" . env('DB_PORT', '3306') .
       ";dbname=" . env('DB_DATABASE', 'hotel_management') .
       ";charset=utf8mb4";

$pdo = new PDO(
    $dsn,
    env('DB_USERNAME', 'root'),
    env('DB_PASSWORD', ''),
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);
            
            // Test connection
            $pdo->query("SELECT 1");
            
            $result['passed'] = true;
            $result['message'] = 'Database connection successful';
        } catch (PDOException $e) {
            $this->errors[] = 'Database connection failed: ' . $e->getMessage();
            $result['message'] = 'Database connection failed: ' . $e->getMessage();
        }
        
        $this->checks['database'] = $result;
    }
    
    /**
     * Check required directories
     */
    private function checkRequiredDirectories() {
        $requiredDirs = [
            'logs' => APP_ROOT . '/logs',
            'uploads' => APP_ROOT . '/uploads',
            'uploads/rooms' => APP_ROOT . '/uploads/rooms',
            'uploads/users' => APP_ROOT . '/uploads/users',
            'uploads/temp' => APP_ROOT . '/uploads/temp',
            'cache' => APP_ROOT . '/cache',
            'assets' => APP_ROOT . '/assets',
            'modules' => APP_ROOT . '/modules',
            'includes' => APP_ROOT . '/includes',
            'config' => APP_ROOT . '/config',
            'database' => APP_ROOT . '/database'
        ];
        
        $missing = [];
        $created = [];
        
        foreach ($requiredDirs as $name => $path) {
            if (!is_dir($path)) {
                $missing[] = $name;
                
                // Auto-fix: create directory
                if (mkdir($path, 0755, true)) {
                    $created[] = $name;
                    $this->autoFixed[] = "Directory created: {$name}";
                }
            }
        }
        
        $result = [
            'name' => 'Required Directories',
            'required' => true,
            'passed' => empty($missing) || count($missing) === count($created),
            'message' => empty($missing) ? 'All required directories exist' : 'Missing directories: ' . implode(', ', $missing)
        ];
        
        if (!empty($missing) && count($missing) !== count($created)) {
            $this->errors[] = $result['message'];
        } elseif (!empty($created)) {
            $result['message'] .= ' (Auto-created: ' . implode(', ', $created) . ')';
            $this->warnings[] = 'Some directories were auto-created';
        }
        
        $this->checks['directories'] = $result;
    }
    
    /**
     * Check directory permissions
     */
    private function checkDirectoryPermissions() {
        $writableDirs = [
            'logs' => APP_ROOT . '/logs',
            'uploads' => APP_ROOT . '/uploads',
            'uploads/rooms' => APP_ROOT . '/uploads/rooms',
            'uploads/users' => APP_ROOT . '/uploads/users',
            'uploads/temp' => APP_ROOT . '/uploads/temp',
            'cache' => APP_ROOT . '/cache'
        ];
        
        $notWritable = [];
        
        foreach ($writableDirs as $name => $path) {
            if (is_dir($path) && !is_writable($path)) {
                $notWritable[] = $name;
                
                // Auto-fix: try to make writable
                if (chmod($path, 0755)) {
                    $this->autoFixed[] = "Made writable: {$name}";
                }
            }
        }
        
        $result = [
            'name' => 'Directory Permissions',
            'required' => true,
            'passed' => empty($notWritable),
            'message' => empty($notWritable) ? 'All directories are writable' : 'Not writable: ' . implode(', ', $notWritable)
        ];
        
        if (!empty($notWritable)) {
            $this->errors[] = $result['message'];
        }
        
        $this->checks['permissions'] = $result;
    }
    
    /**
     * Check config files
     */
    private function checkConfigFiles() {
        $configFiles = [
            'config.php' => APP_ROOT . '/config/config.php',
            'database.php' => APP_ROOT . '/config/database.php',
            'env.php' => APP_ROOT . '/includes/env.php',
            'logger.php' => APP_ROOT . '/includes/logger.php',
            'constants.php' => APP_ROOT . '/includes/constants.php',
            'error_handler.php' => APP_ROOT . '/includes/error_handler.php'
        ];
        
        $missing = [];
        
        foreach ($configFiles as $name => $path) {
            if (!file_exists($path)) {
                $missing[] = $name;
            }
        }
        
        $result = [
            'name' => 'Configuration Files',
            'required' => true,
            'passed' => empty($missing),
            'message' => empty($missing) ? 'All config files exist' : 'Missing config files: ' . implode(', ', $missing)
        ];
        
        if (!empty($missing)) {
            $this->errors[] = $result['message'];
        }
        
        $this->checks['config_files'] = $result;
    }
    
    /**
     * Check required root files
     */
    private function checkRequiredFiles() {
        $requiredFiles = [
            'index.php' => APP_ROOT . '/index.php',
            'login.php' => APP_ROOT . '/login.php',
            'dashboard.php' => APP_ROOT . '/dashboard.php',
            '404.php' => APP_ROOT . '/404.php'
        ];
        
        $missing = [];
        
        foreach ($requiredFiles as $name => $path) {
            if (!file_exists($path)) {
                $missing[] = $name;
            }
        }
        
        $result = [
            'name' => 'Required Root Files',
            'required' => true,
            'passed' => empty($missing),
            'message' => empty($missing) ? 'All required files exist' : 'Missing files: ' . implode(', ', $missing)
        ];
        
        if (!empty($missing)) {
            $this->errors[] = $result['message'];
        }
        
        $this->checks['root_files'] = $result;
    }
    
    /**
     * Check .htaccess file
     */
    private function checkHtaccess() {
        $htaccessFile = APP_ROOT . '/.htaccess';
        
        $result = [
            'name' => '.htaccess File',
            'required' => false,
            'passed' => file_exists($htaccessFile),
            'message' => file_exists($htaccessFile) ? '.htaccess file exists' : '.htaccess file is missing (optional)'
        ];
        
        if (!file_exists($htaccessFile)) {
            $this->warnings[] = '.htaccess file is missing (optional but recommended)';
        }
        
        $this->checks['htaccess'] = $result;
    }
    
    /**
     * Create log if missing
     */
    private function createLogFiles() {
        $logDir = APP_ROOT . '/logs';
        $date = date('Y-m-d');
        
        $logFiles = [
            'application-' . $date . '.log',
            'error-' . $date . '.log'
        ];
        
        foreach ($logFiles as $logFile) {
            $path = $logDir . '/' . $logFile;
            if (!file_exists($path)) {
                if (touch($path)) {
                    $this->autoFixed[] = "Log file created: {$logFile}";
                }
            }
        }
    }
    
    /**
     * Display installation error page
     * 
     * @param array $results Check results
     * @return void
     */
    public function displayErrorPage($results) {
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
                    max-width: 700px;
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
                    padding: 15px 0;
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
                .warning {
                    color: #ffc107;
                }
                .check-name {
                    font-weight: 600;
                    margin-bottom: 5px;
                }
                .check-message {
                    margin-left: 0;
                    padding-left: 0;
                    color: #6c757d;
                }
                .auto-fixed {
                    background: #d4edda;
                    color: #155724;
                    padding: 10px;
                    border-radius: 4px;
                    margin-top: 20px;
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
                
                <?php foreach ($results['checks'] as $check): ?>
                    <div class="check-item <?php echo $check['passed'] ? 'success' : 'error'; ?>">
                        <div class="check-name">
                            <?php echo $check['passed'] ? '✓' : '✗'; ?>
                            <?php echo htmlspecialchars($check['name']); ?>
                            <?php echo $check['required'] ? ' (Required)' : ''; ?>
                        </div>
                        <div class="check-message">
                            <?php echo htmlspecialchars($check['message']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (!empty($results['auto_fixed'])): ?>
                    <div class="auto-fixed">
                        <strong>Auto-Fixed:</strong>
                        <ul>
                            <?php foreach ($results['auto_fixed'] as $item): ?>
                                <li><?php echo htmlspecialchars($item); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($results['warnings'])): ?>
                    <div style="margin-top: 20px; color: #ffc107;">
                        <strong>Warnings:</strong>
                        <ul>
                            <?php foreach ($results['warnings'] as $warning): ?>
                                <li><?php echo htmlspecialchars($warning); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <p style="margin-top: 30px; color: #6c757d;">
                    Please fix the errors above and refresh the page.
                </p>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

/**
 * Run installation check and display error if needed
 * 
 * @return void
 */
function runInstallerCheck() {
    $checker = new InstallerChecker();
    $results = $checker->runAllChecks();
    
    if (!$results['passed']) {
        $checker->displayErrorPage($results);
    }
}
