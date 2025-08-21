<?php
/**
 * Artisans API - Handle artisan-related operations
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
            getAllArtisans();
            break;
        case 'single':
            getSingleArtisan();
            break;
        default:
            getAllArtisans();
    }
}

function getAllArtisans() {
    global $db;
    
    try {
        $sql = "SELECT id, name, bio, location, speciality FROM artisans ORDER BY name ASC";
        $artisans = $db->fetchAll($sql);
        
        sendJsonResponse([
            'success' => true,
            'artisans' => $artisans,
            'count' => count($artisans)
        ]);
        
    } catch (Exception $e) {
        sendJsonResponse(['error' => 'Failed to fetch artisans: ' . $e->getMessage()], 500);
    }
}

function getSingleArtisan() {
    global $db;
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$id) {
        sendJsonResponse(['error' => 'Artisan ID is required'], 400);
        return;
    }
    
    try {
        $sql = "SELECT id, name, bio, location, speciality, image FROM artisans WHERE id = ?";
        $artisan = $db->fetch($sql, [$id]);
        
        if (!$artisan) {
            sendJsonResponse(['error' => 'Artisan not found'], 404);
            return;
        }
        
        // Get artisan's products
        $productsSql = "SELECT id, name, price, image FROM products WHERE artisan_id = ? AND status = 'active'";
        $products = $db->fetchAll($productsSql, [$id]);
        
        $artisan['products'] = $products;
        
        sendJsonResponse([
            'success' => true,
            'artisan' => $artisan
        ]);
        
    } catch (Exception $e) {
        sendJsonResponse(['error' => 'Failed to fetch artisan: ' . $e->getMessage()], 500);
    }
}
?>


