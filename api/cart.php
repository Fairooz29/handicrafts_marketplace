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

// Handle different request methods
$method = $_SERVER['REQUEST_METHOD'];
switch ($method) {
    case 'GET':
        getCart($userId);
        break;
    case 'POST':
        addToCart($userId);
        break;
    case 'PUT':
        updateCartItem($userId);
        break;
    case 'DELETE':
        removeFromCart($userId);
        break;
    default:
        sendResponse(false, 'Method not allowed', 405);
}

// Get cart items and summary
function getCart($userId) {
    try {
        // Create database connection
        $conn = new mysqli('localhost', 'root', '', 'handicrafts_marketplace');
        
        // Check connection
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        // Get cart items with product details
        $stmt = $conn->prepare("
            SELECT c.id as cart_id, c.product_id, c.quantity, 
                   p.name, p.price, p.image, p.stock_quantity 
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ?
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $items = [];
        $subtotal = 0;
        
        while ($row = $result->fetch_assoc()) {
            // Calculate item total
            $itemTotal = $row['price'] * $row['quantity'];
            $subtotal += $itemTotal;
            
            // Add item to array
            $items[] = [
                'id' => $row['cart_id'],
                'product_id' => $row['product_id'],
                'name' => $row['name'],
                'price' => (float)$row['price'],
                'image' => $row['image'],
                'quantity' => (int)$row['quantity'],
                'stock_quantity' => (int)$row['stock_quantity'],
                'total' => $itemTotal
            ];
        }
        
        // Calculate summary
        $shipping = count($items) > 0 ? 120 : 0; // Fixed shipping cost
        $tax = $subtotal * 0.05; // 5% tax
        $total = $subtotal + $shipping + $tax;
        
        // Return cart data
        sendResponse(true, 'Cart retrieved successfully', 200, [
            'items' => $items,
            'summary' => [
                'subtotal' => $subtotal,
                'shipping' => $shipping,
                'tax' => $tax,
                'total' => $total
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("Error getting cart: " . $e->getMessage());
        sendResponse(false, 'Failed to retrieve cart', 500);
    }
}

// Add item to cart
function addToCart($userId) {
    try {
        // Get request data
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['product_id']) || !isset($data['quantity'])) {
            sendResponse(false, 'Missing required fields', 400);
            return;
        }
        
        $productId = $data['product_id'];
        $quantity = max(1, (int)$data['quantity']);
        
        // Create database connection
        $conn = new mysqli('localhost', 'root', '', 'handicrafts_marketplace');
        
        // Check connection
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        // Check product exists and has stock
        $stmt = $conn->prepare("SELECT stock_quantity FROM products WHERE id = ?");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            sendResponse(false, 'Product not found', 404);
            return;
        }
        
        $product = $result->fetch_assoc();
        if ($product['stock_quantity'] < $quantity) {
            sendResponse(false, 'Not enough stock available', 400);
            return;
        }
        
        // Check if product already in cart
        $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $userId, $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing cart item
            $cartItem = $result->fetch_assoc();
            $newQuantity = $cartItem['quantity'] + $quantity;
            
            // Check if new quantity exceeds stock
            if ($newQuantity > $product['stock_quantity']) {
                $newQuantity = $product['stock_quantity'];
            }
            
            $stmt = $conn->prepare("UPDATE cart SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->bind_param("ii", $newQuantity, $cartItem['id']);
            $stmt->execute();
            
            sendResponse(true, 'Item quantity updated in cart', 200);
        } else {
            // Add new cart item
            $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $userId, $productId, $quantity);
            $stmt->execute();
            
            sendResponse(true, 'Item added to cart', 201);
        }
        
    } catch (Exception $e) {
        error_log("Error adding to cart: " . $e->getMessage());
        sendResponse(false, 'Failed to add item to cart', 500);
    }
}

// Update cart item quantity
function updateCartItem($userId) {
    try {
        // Get request data
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['cart_item_id']) || !isset($data['quantity'])) {
            sendResponse(false, 'Missing required fields', 400);
            return;
        }
        
        $cartItemId = $data['cart_item_id'];
        $quantity = max(1, (int)$data['quantity']);
        
        // Create database connection
        $conn = new mysqli('localhost', 'root', '', 'handicrafts_marketplace');
        
        // Check connection
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        // Verify cart item belongs to user
        $stmt = $conn->prepare("
            SELECT c.id, c.product_id, p.stock_quantity 
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.id = ? AND c.user_id = ?
        ");
        $stmt->bind_param("ii", $cartItemId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            sendResponse(false, 'Cart item not found', 404);
            return;
        }
        
        $cartItem = $result->fetch_assoc();
        
        // Check if quantity exceeds stock
        if ($quantity > $cartItem['stock_quantity']) {
            $quantity = $cartItem['stock_quantity'];
        }
        
        // Update quantity
        $stmt = $conn->prepare("UPDATE cart SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("ii", $quantity, $cartItemId);
        $stmt->execute();
        
        sendResponse(true, 'Cart item updated', 200);
        
    } catch (Exception $e) {
        error_log("Error updating cart item: " . $e->getMessage());
        sendResponse(false, 'Failed to update cart item', 500);
    }
}

// Remove item from cart
function removeFromCart($userId) {
    try {
        // Get cart item ID
        $cartItemId = $_GET['id'] ?? null;
        
        if (!$cartItemId) {
            sendResponse(false, 'Cart item ID is required', 400);
            return;
        }
        
        // Create database connection
        $conn = new mysqli('localhost', 'root', '', 'handicrafts_marketplace');
        
        // Check connection
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        // Verify cart item belongs to user
        $stmt = $conn->prepare("SELECT id FROM cart WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $cartItemId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            sendResponse(false, 'Cart item not found', 404);
            return;
        }
        
        // Delete cart item
        $stmt = $conn->prepare("DELETE FROM cart WHERE id = ?");
        $stmt->bind_param("i", $cartItemId);
        $stmt->execute();
        
        sendResponse(true, 'Item removed from cart', 200);
        
    } catch (Exception $e) {
        error_log("Error removing from cart: " . $e->getMessage());
        sendResponse(false, 'Failed to remove item from cart', 500);
    }
}

// Send JSON response
function sendResponse($success, $message, $statusCode = 200, $data = null) {
    http_response_code($statusCode);
    echo json_encode([
        'status' => $success ? 'success' : 'error',
        'success' => $success, // Add explicit success boolean for consistency
        'message' => $message,
        'data' => $data
    ]);
    exit;
}
?>