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
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_NAME', env('DB_DATABASE', 'hotel_management'));
define('DB_USER', env('DB_USERNAME', 'root'));
define('DB_PASS', env('DB_PASSWORD', ''));
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
 * Enhanced Database Connection Class with Transaction Support
 */
class Database {
    private static $instance = null;
    private $connection = null;
    private $transactionCount = 0;
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, DB_OPTIONS);
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die("Database Connection Error: " . $e->getMessage());
            } else {
                die("Database connection failed. Please check your configuration and ensure the database server is running.");
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
     * Begin transaction
     * 
     * @return bool
     */
    public function beginTransaction() {
        if ($this->transactionCount === 0) {
            $result = $this->connection->beginTransaction();
        } else {
            $result = $this->connection->exec("SAVEPOINT LEVEL{$this->transactionCount}");
        }
        
        if ($result) {
            $this->transactionCount++;
        }
        
        return $result;
    }
    
    /**
     * Commit transaction
     * 
     * @return bool
     */
    public function commit() {
        $this->transactionCount--;
        
        if ($this->transactionCount === 0) {
            return $this->connection->commit();
        } else {
            return $this->connection->exec("RELEASE SAVEPOINT LEVEL{$this->transactionCount}");
        }
    }
    
    /**
     * Rollback transaction
     * 
     * @return bool
     */
    public function rollback() {
        $this->transactionCount--;
        
        if ($this->transactionCount === 0) {
            return $this->connection->rollBack();
        } else {
            return $this->connection->exec("ROLLBACK TO SAVEPOINT LEVEL{$this->transactionCount}");
        }
    }
    
    /**
     * Execute prepared statement
     * 
     * @param string $query SQL query
     * @param array $params Parameters for prepared statement
     * @return PDOStatement|false
     */
    public function query($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                error_log("Query Error: " . $e->getMessage());
            }
            return false;
        }
    }
    
    /**
     * Fetch single row
     * 
     * @param string $query SQL query
     * @param array $params Parameters for prepared statement
     * @return array|false
     */
    public function fetch($query, $params = []) {
        $stmt = $this->query($query, $params);
        return $stmt ? $stmt->fetch() : false;
    }
    
    /**
     * Fetch all rows
     * 
     * @param string $query SQL query
     * @param array $params Parameters for prepared statement
     * @return array
     */
    public function fetchAll($query, $params = []) {
        $stmt = $this->query($query, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    /**
     * Fetch single column value
     * 
     * @param string $query SQL query
     * @param array $params Parameters for prepared statement
     * @return mixed|false
     */
    public function fetchColumn($query, $params = []) {
        $stmt = $this->query($query, $params);
        return $stmt ? $stmt->fetchColumn() : false;
    }
    
    /**
     * Insert record
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @return int|false Last insert ID or false on failure
     */
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $query = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        if ($this->query($query, $data)) {
            return $this->connection->lastInsertId();
        }
        return false;
    }
    
    /**
     * Update record
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @param string $where WHERE clause
     * @param array $whereParams Parameters for WHERE clause
     * @return bool
     */
    public function update($table, $data, $where, $whereParams = []) {
        $setClause = [];
        foreach (array_keys($data) as $column) {
            $setClause[] = "{$column} = :{$column}";
        }
        $setClause = implode(', ', $setClause);
        $query = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $params = array_merge($data, $whereParams);
        
        return $this->query($query, $params) !== false;
    }
    
    /**
     * Delete record
     * 
     * @param string $table Table name
     * @param string $where WHERE clause
     * @param array $params Parameters for WHERE clause
     * @return bool
     */
    public function delete($table, $where, $params = []) {
        $query = "DELETE FROM {$table} WHERE {$where}";
        return $this->query($query, $params) !== false;
    }
    
    /**
     * Get count of records
     * 
     * @param string $table Table name
     * @param string $where WHERE clause (optional)
     * @param array $params Parameters for WHERE clause
     * @return int
     */
    public function count($table, $where = '1=1', $params = []) {
        $query = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
        return (int) $this->fetchColumn($query, $params);
    }
    
    /**
     * Check if record exists
     * 
     * @param string $table Table name
     * @param string $where WHERE clause
     * @param array $params Parameters for WHERE clause
     * @return bool
     */
    public function exists($table, $where, $params = []) {
        return $this->count($table, $where, $params) > 0;
    }
    
    /**
     * Get last insert ID
     * 
     * @return string
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
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

/**
 * Verify database connection
 * 
 * @return array Result with success flag and message
 */
function verifyDatabaseConnection() {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Test connection with a simple query
        $stmt = $db->query("SELECT 1");
        $stmt->fetch();
        
        return [
            'success' => true,
            'message' => 'Database connection successful'
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Database connection failed: ' . $e->getMessage()
        ];
    }
}
