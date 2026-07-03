<?php
/**
 * Hotel & Resort Management System
 * Environment Loader
 * 
 * Lightweight .env file parser and loader
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

class EnvLoader {
    private static $loaded = false;
    private static $values = [];
    
    /**
     * Load .env file
     * 
     * @param string $path Path to .env file
     * @return bool
     */
    public static function load($path = null) {
        if (self::$loaded) {
            return true;
        }
        
        $path = $path ?? APP_ROOT . '/.env';
        
        if (!file_exists($path)) {
            return false;
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse key=value
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
                    $value = $matches[2];
                }
                
                self::$values[$key] = $value;
            }
        }
        
        self::$loaded = true;
        return true;
    }
    
    /**
     * Get environment value
     * 
     * @param string $key Key to retrieve
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public static function get($key, $default = null) {
        if (!self::$loaded) {
            self::load();
        }
        
        return self::$values[$key] ?? $default;
    }
    
    /**
     * Check if environment key exists
     * 
     * @param string $key Key to check
     * @return bool
     */
    public static function has($key) {
        if (!self::$loaded) {
            self::load();
        }
        
        return isset(self::$values[$key]);
    }
    
    /**
     * Set environment value
     * 
     * @param string $key Key to set
     * @param mixed $value Value to set
     * @return void
     */
    public static function set($key, $value) {
        self::$values[$key] = $value;
    }
    
    /**
     * Get all environment values
     * 
     * @return array
     */
    public static function all() {
        if (!self::$loaded) {
            self::load();
        }
        
        return self::$values;
    }
    
    /**
     * Check if .env file exists
     * 
     * @param string $path Path to .env file
     * @return bool
     */
    public static function exists($path = null) {
        $path = $path ?? APP_ROOT . '/.env';
        return file_exists($path);
    }
}

/**
 * Helper function to get environment value
 * 
 * @param string $key Key to retrieve
 * @param mixed $default Default value if not found
 * @return mixed
 */
function env($key, $default = null) {
    return EnvLoader::get($key, $default);
}

/**
 * Helper function to check if environment key exists
 * 
 * @param string $key Key to check
 * @return bool
 */
function env_has($key) {
    return EnvLoader::has($key);
}

// Auto-load .env file
EnvLoader::load();
