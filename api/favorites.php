<?php
require_once '../config/database.php';
session_start();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    sendResponse(false, 'User not authenticated', 401);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'all';

    switch ($action) {
        case 'all':
        getFavorites($userId);
            break;
        case 'check':
        checkFavorite($userId);
        break;
    case 'toggle':
        toggleFavorite($userId);
            break;
        default:
        sendResponse(false, 'Invalid action', 400);
}

// Get all favorites for a user
function getFavorites($userId) {
    try {
        // Create database connection
        $conn = new mysqli('localhost', 'root', '', 'handicrafts_marketplace');
        
        // Check connection
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        // Get favorites with product details
        $stmt = $conn->prepare("
            SELECT p.id, p.name, p.description, p.short_description, p.price, p.original_price, 
                   p.image, p.stock_quantity, a.name as artisan_name
            FROM favorites f
            JOIN products p ON f.product_id = p.id
            LEFT JOIN artisans a ON p.artisan_id = a.id
            WHERE f.user_id = ?
            ORDER BY f.created_at DESC
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $favorites = [];
        while ($row = $result->fetch_assoc()) {
            $favorites[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'description' => $row['description'],
                'short_description' => $row['short_description'] ?? substr($row['description'], 0, 100) . '...',
                'price' => (float)$row['price'],
                'original_price' => (float)($row['original_price'] ?? $row['price']),
                'image' => $row['image'],
                'stock_quantity' => (int)$row['stock_quantity'],
                'artisan_name' => $row['artisan_name']
            ];
        }
        
        sendResponse(true, 'Favorites retrieved successfully', 200, ['favorites' => $favorites]);
        
    } catch (Exception $e) {
        error_log("Error getting favorites: " . $e->getMessage());
        sendResponse(false, 'Failed to retrieve favorites', 500);
    }
}

// Check if a product is in favorites
function checkFavorite($userId) {
    try {
        $productId = $_GET['product_id'] ?? null;
    
    if (!$productId) {
            sendResponse(false, 'Product ID is required', 400);
            return;
        }
        
        // Create database connection
        $conn = new mysqli('localhost', 'root', '', 'handicrafts_marketplace');
        
        // Check connection
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        // Check if product is in favorites
        $stmt = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $userId, $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $isFavorite = $result->num_rows > 0;
        
        sendResponse(true, 'Favorite status checked', 200, ['is_favorite' => $isFavorite]);
        
    } catch (Exception $e) {
        error_log("Error checking favorite: " . $e->getMessage());
        sendResponse(false, 'Failed to check favorite status', 500);
    }
}

// Toggle favorite status
function toggleFavorite($userId) {
    try {
        // Get request data
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['product_id'])) {
            sendResponse(false, 'Product ID is required', 400);
            return;
        }
        
        $productId = $data['product_id'];
        
        // Create database connection
        $conn = new mysqli('localhost', 'root', '', 'handicrafts_marketplace');
        
        // Check connection
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        // Check if product exists
        $stmt = $conn->prepare("SELECT id FROM products WHERE id = ?");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            sendResponse(false, 'Product not found', 404);
            return;
    }
    
    // Check if already in favorites
        $stmt = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $userId, $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
        // Remove from favorites
            $favoriteId = $result->fetch_assoc()['id'];
            $stmt = $conn->prepare("DELETE FROM favorites WHERE id = ?");
            $stmt->bind_param("i", $favoriteId);
            $stmt->execute();
            
            sendResponse(true, 'Product removed from favorites', 200, ['action' => 'removed']);
    } else {
        // Add to favorites
            $stmt = $conn->prepare("INSERT INTO favorites (user_id, product_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $userId, $productId);
            $stmt->execute();
            
            sendResponse(true, 'Product added to favorites', 200, ['action' => 'added']);
        }
        
    } catch (Exception $e) {
        error_log("Error toggling favorite: " . $e->getMessage());
        sendResponse(false, 'Failed to update favorites', 500);
    }
}

// Send JSON response
function sendResponse($success, $message, $statusCode = 200, $data = null) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}
?>