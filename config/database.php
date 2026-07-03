<?php
/**
 * Hotel & Resort Management System
 * Database Configuration File
 * 
 * This file handles PDO database connection
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Load configuration
require_once APP_ROOT . '/config/config.php';

/**
 * Database Configuration
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'hotel_management');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATION', 'utf8mb4_unicode_ci');

/**
 * PDO Options
 */
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_PERSISTENT => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE " . DB_COLLATION
]);

/**
 * Database Connection Class
 */
class Database {
    private static $instance = null;
    private $connection = null;
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, DB_OPTIONS);
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die("Database Connection Error: " . $e->getMessage());
            } else {
                die("Database connection failed. Please contact system administrator.");
            }
        }
    }
    
    /**
     * Get database instance (Singleton pattern)
     * 
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get PDO connection
     * 
     * @return PDO
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization of the instance
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
    
    /**
     * Close database connection
     */
    public function closeConnection() {
        $this->connection = null;
    }
}

/**
 * Helper function to get database connection
 * 
 * @return PDO
 */
function getDB() {
    return Database::getInstance()->getConnection();
}

/**
 * Helper function to execute a query
 * 
 * @param string $query SQL query
 * @param array $params Parameters for prepared statement
 * @return PDOStatement
 */
function executeQuery($query, $params = []) {
    try {
        $db = getDB();
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            die("Query Error: " . $e->getMessage());
        } else {
            error_log("Database query error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Helper function to fetch a single row
 * 
 * @param string $query SQL query
 * @param array $params Parameters for prepared statement
 * @return array|false
 */
function fetchRow($query, $params = []) {
    $stmt = executeQuery($query, $params);
    return $stmt ? $stmt->fetch() : false;
}

/**
 * Helper function to fetch multiple rows
 * 
 * @param string $query SQL query
 * @param array $params Parameters for prepared statement
 * @return array
 */
function fetchAll($query, $params = []) {
    $stmt = executeQuery($query, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

/**
 * Helper function to insert data
 * 
 * @param string $table Table name
 * @param array $data Associative array of column => value
 * @return int|false Last insert ID or false on failure
 */
function insert($table, $data) {
    try {
        $db = getDB();
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $query = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $db->prepare($query);
        $stmt->execute($data);
        return $db->lastInsertId();
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            die("Insert Error: " . $e->getMessage());
        } else {
            error_log("Database insert error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Helper function to update data
 * 
 * @param string $table Table name
 * @param array $data Associative array of column => value
 * @param string $where WHERE clause
 * @param array $whereParams Parameters for WHERE clause
 * @return bool
 */
function update($table, $data, $where, $whereParams = []) {
    try {
        $db = getDB();
        $setClause = [];
        foreach (array_keys($data) as $column) {
            $setClause[] = "{$column} = :{$column}";
        }
        $setClause = implode(', ', $setClause);
        $query = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $stmt = $db->prepare($query);
        $params = array_merge($data, $whereParams);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            die("Update Error: " . $e->getMessage());
        } else {
            error_log("Database update error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Helper function to delete data
 * 
 * @param string $table Table name
 * @param string $where WHERE clause
 * @param array $params Parameters for WHERE clause
 * @return bool
 */
function delete($table, $where, $params = []) {
    try {
        $db = getDB();
        $query = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $db->prepare($query);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            die("Delete Error: " . $e->getMessage());
        } else {
            error_log("Database delete error: " . $e->getMessage());
            return false;
        }
    }
}
