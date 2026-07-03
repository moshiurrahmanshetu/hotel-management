<?php
/**
 * Hotel & Resort Management System
 * API Endpoint - Get Floors by Building
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__FILE__));
}

// Load configuration and authentication
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/includes/auth.php';

// Require authentication
requireAuth();

header('Content-Type: application/json');

$db = getDB();

$buildingId = (int)($_GET['building_id'] ?? 0);

if (!$buildingId) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $db->prepare("
        SELECT id, name, floor_number 
        FROM floors 
        WHERE building_id = ? AND deleted_at IS NULL AND is_active = 1 
        ORDER BY floor_number ASC
    ");
    $stmt->execute([$buildingId]);
    $floors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($floors);
} catch (PDOException $e) {
    if (DEBUG_MODE) {
        error_log("Get floors API error: " . $e->getMessage());
    }
    echo json_encode([]);
}
