<?php
/**
 * Hotel & Resort Management System
 * Logger Class
 * 
 * Application logging with auto-creation of log files
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Note: This file is loaded by config.php, so constants are already defined
// Do NOT load config.php here to avoid circular dependency

class Logger {
    private static $instance = null;
    private $logPath;
    private $logLevel;
    private $logEnabled;
    
    // Log levels
    const DEBUG = 'DEBUG';
    const INFO = 'INFO';
    const WARNING = 'WARNING';
    const ERROR = 'ERROR';
    const CRITICAL = 'CRITICAL';
    
    /**
     * Private constructor
     */
    private function __construct() {
        $this->logPath = LOG_PATH;
        $this->logLevel = LOG_LEVEL;
        $this->logEnabled = LOG_ENABLED;
        
        // Ensure log directory exists
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }
    
    /**
     * Get logger instance
     * 
     * @return Logger
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Log message
     * 
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context
     * @return bool
     */
    public function log($level, $message, $context = []) {
        if (!$this->logEnabled) {
            return false;
        }
        
        // Check if level should be logged
        if (!$this->shouldLog($level)) {
            return false;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        $logMessage = "[{$timestamp}] [{$level}] {$message}{$contextStr}\n";
        
        // Determine log file
        $logFile = $this->getLogFile($level);
        
        // Write to log file
        return file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX) !== false;
    }
    
    /**
     * Log debug message
     * 
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function debug($message, $context = []) {
        return $this->log(self::DEBUG, $message, $context);
    }
    
    /**
     * Log info message
     * 
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function info($message, $context = []) {
        return $this->log(self::INFO, $message, $context);
    }
    
    /**
     * Log warning message
     * 
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function warning($message, $context = []) {
        return $this->log(self::WARNING, $message, $context);
    }
    
    /**
     * Log error message
     * 
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function error($message, $context = []) {
        return $this->log(self::ERROR, $message, $context);
    }
    
    /**
     * Log critical message
     * 
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function critical($message, $context = []) {
        return $this->log(self::CRITICAL, $message, $context);
    }
    
    /**
     * Log exception
     * 
     * @param Exception $exception
     * @return bool
     */
    public function exception($exception) {
        $context = [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];
        return $this->error($exception->getMessage(), $context);
    }
    
    /**
     * Check if level should be logged
     * 
     * @param string $level
     * @return bool
     */
    private function shouldLog($level) {
        $levels = [
            self::DEBUG => 0,
            self::INFO => 1,
            self::WARNING => 2,
            self::ERROR => 3,
            self::CRITICAL => 4
        ];
        
        $currentLevel = $levels[$this->logLevel] ?? 1;
        $messageLevel = $levels[$level] ?? 1;
        
        return $messageLevel >= $currentLevel;
    }
    
    /**
     * Get log file path based on level
     * 
     * @param string $level
     * @return string
     */
    private function getLogFile($level) {
        $date = date('Y-m-d');
        
        switch ($level) {
            case self::ERROR:
            case self::CRITICAL:
                return $this->logPath . '/error-' . $date . '.log';
            default:
                return $this->logPath . '/application-' . $date . '.log';
        }
    }
    
    /**
     * Clean old log files
     * 
     * @param int $days Number of days to keep
     * @return int Number of files deleted
     */
    public function cleanOldLogs($days = 30) {
        $count = 0;
        $cutoff = time() - ($days * 86400);
        
        $files = glob($this->logPath . '/*.log');
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                if (unlink($file)) {
                    $count++;
                }
            }
        }
        
        return $count;
    }
}

/**
 * Helper function to log message
 * 
 * @param string $level
 * @param string $message
 * @param array $context
 * @return bool
 */
function logMessage($level, $message, $context = []) {
    return Logger::getInstance()->log($level, $message, $context);
}

/**
 * Helper function to log debug
 * 
 * @param string $message
 * @param array $context
 * @return bool
 */
function logDebug($message, $context = []) {
    return Logger::getInstance()->debug($message, $context);
}

/**
 * Helper function to log info
 * 
 * @param string $message
 * @param array $context
 * @return bool
 */
function logInfo($message, $context = []) {
    return Logger::getInstance()->info($message, $context);
}

/**
 * Helper function to log warning
 * 
 * @param string $message
 * @param array $context
 * @return bool
 */
function logWarning($message, $context = []) {
    return Logger::getInstance()->warning($message, $context);
}

/**
 * Helper function to log error
 * 
 * @param string $message
 * @param array $context
 * @return bool
 */
function logError($message, $context = []) {
    return Logger::getInstance()->error($message, $context);
}

/**
 * Helper function to log exception
 * 
 * @param Exception $exception
 * @return bool
 */
function logException($exception) {
    return Logger::getInstance()->exception($exception);
}
