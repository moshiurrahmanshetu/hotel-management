<?php
/**
 * Hotel & Resort Management System
 * API Endpoint - Get Buildings by Property
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__FILE__));
}

// Load bootstrap (includes config, installer check, etc.)
require_once APP_ROOT . '/includes/bootstrap.php';
require_once APP_ROOT . '/includes/auth.php';

// Require authentication
requireAuth();

header('Content-Type: application/json');

$db = getDB();

$propertyId = (int)($_GET['property_id'] ?? 0);

if (!$propertyId) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $db->prepare("
        SELECT id, name, code 
        FROM buildings 
        WHERE property_id = ? AND deleted_at IS NULL AND is_active = 1 
        ORDER BY name ASC
    ");
    $stmt->execute([$propertyId]);
    $buildings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($buildings);
} catch (PDOException $e) {
    if (DEBUG_MODE) {
        error_log("Get buildings API error: " . $e->getMessage());
    }
    echo json_encode([]);
}
