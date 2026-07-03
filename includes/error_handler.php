<?php
/**
 * Hotel & Resort Management System
 * Global Error and Exception Handlers
 * 
 * Handles all PHP errors and exceptions with logging
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Note: This file is loaded by config.php, so constants are already defined
// Do NOT load config.php here to avoid circular dependency

/**
 * Global exception handler
 * 
 * @param Throwable $exception
 * @return void
 */
function globalExceptionHandler($exception) {
    // Log the exception
    if (class_exists('Logger')) {
        Logger::getInstance()->exception($exception);
    } else {
        // Fallback to error log if Logger not available
        $logMessage = "Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . ":" . $exception->getLine();
        error_log($logMessage);
        
        // Try to write to error log file directly
        $logDir = APP_ROOT . '/logs';
        if (is_dir($logDir) || mkdir($logDir, 0755, true)) {
            $logFile = $logDir . '/error-' . date('Y-m-d') . '.log';
            $timestamp = date('Y-m-d H:i:s');
            file_put_contents($logFile, "[{$timestamp}] [CRITICAL] {$logMessage}\n", FILE_APPEND | LOCK_EX);
        }
    }
    
    // Show friendly error page
    $debugMode = defined('APP_DEBUG') ? APP_DEBUG : true;
    
    if ($debugMode) {
        // Show detailed error in debug mode
        echo "<h1>Application Error</h1>";
        echo "<p><strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($exception->getFile()) . "</p>";
        echo "<p><strong>Line:</strong> " . $exception->getLine() . "</p>";
        echo "<pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
    } else {
        // Show generic error in production
        http_response_code(500);
        echo "<h1>Internal Server Error</h1>";
        echo "<p>An error occurred. Please try again later or contact support.</p>";
    }
    
    exit;
}

/**
 * Global error handler
 * 
 * @param int $errno
 * @param string $errstr
 * @param string $errfile
 * @param int $errline
 * @return bool
 */
function globalErrorHandler($errno, $errstr, $errfile, $errline) {
    // Skip if error reporting is disabled
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    $errorTypes = [
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parse Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Strict Notice',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated'
    ];
    
    $errorType = $errorTypes[$errno] ?? 'Unknown Error';
    
    // Log the error
    if (class_exists('Logger')) {
        $context = [
            'file' => $errfile,
            'line' => $errline,
            'type' => $errorType
        ];
        
        if ($errno === E_ERROR || $errno === E_USER_ERROR) {
            Logger::getInstance()->error($errstr, $context);
        } else {
            Logger::getInstance()->warning($errstr, $context);
        }
    } else {
        // Fallback to error log if Logger not available
        $logMessage = "PHP Error [{$errorType}]: {$errstr} in {$errfile}:{$errline}";
        error_log($logMessage);
        
        // Try to write to error log file directly
        $logDir = APP_ROOT . '/logs';
        if (is_dir($logDir) || mkdir($logDir, 0755, true)) {
            $logFile = $logDir . '/error-' . date('Y-m-d') . '.log';
            $timestamp = date('Y-m-d H:i:s');
            file_put_contents($logFile, "[{$timestamp}] [ERROR] {$logMessage}\n", FILE_APPEND | LOCK_EX);
        }
    }
    
    // Show error in debug mode
    $debugMode = defined('APP_DEBUG') ? APP_DEBUG : true;
    if ($debugMode) {
        echo "<p><strong>[{$errorType}]</strong> {$errstr} in {$errfile}:{$errline}</p>";
    }
    
    // Don't execute PHP internal error handler
    return true;
}

/**
 * Shutdown handler for fatal errors
 * 
 * @return void
 */
function shutdownHandler() {
    $error = error_get_last();
    
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        // Log the fatal error
        if (class_exists('Logger')) {
            $context = [
                'file' => $error['file'],
                'line' => $error['line'],
                'type' => 'Fatal Error'
            ];
            Logger::getInstance()->critical($error['message'], $context);
        } else {
            error_log("Fatal Error: {$error['message']} in {$error['file']}:{$error['line']}");
        }
        
        // Show friendly error page
        if (APP_DEBUG) {
            echo "<h1>Fatal Error</h1>";
            echo "<p><strong>Message:</strong> " . htmlspecialchars($error['message']) . "</p>";
            echo "<p><strong>File:</strong> " . htmlspecialchars($error['file']) . "</p>";
            echo "<p><strong>Line:</strong> " . $error['line'] . "</p>";
        } else {
            http_response_code(500);
            echo "<h1>Internal Server Error</h1>";
            echo "<p>A fatal error occurred. Please try again later or contact support.</p>";
        }
    }
}

// Register handlers
set_exception_handler('globalExceptionHandler');
set_error_handler('globalErrorHandler');
register_shutdown_function('shutdownHandler');
