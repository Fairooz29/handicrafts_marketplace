<?php
/**
 * Categories API - Handle category-related operations
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($action);
            break;
        default:
            sendJsonResponse(['error' => 'Method not allowed'], 405);
    }
} catch (Exception $e) {
    sendJsonResponse(['error' => $e->getMessage()], 500);
}

function handleGetRequest($action) {
    switch ($action) {
        case 'all':
            getAllCategories();
            break;
        default:
            getAllCategories();
    }
}

function getAllCategories() {
    global $db;
    
    try {
        $sql = "SELECT id, name, description FROM categories ORDER BY name ASC";
        $categories = $db->fetchAll($sql);
        
        sendJsonResponse([
            'success' => true,
            'categories' => $categories,
            'count' => count($categories)
        ]);
        
    } catch (Exception $e) {
        sendJsonResponse(['error' => 'Failed to fetch categories: ' . $e->getMessage()], 500);
    }
}
?>





