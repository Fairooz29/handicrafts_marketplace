<?php
// Disable displaying PHP errors to the browser
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Start output buffering to catch any unexpected output
ob_start();

try {
    session_start();
require_once '../config/database.php';

    // Set content type to JSON
header('Content-Type: application/json');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-Id');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check if user is authenticated
function checkAuth() {
    // Debug session information
    error_log("Session data: " . print_r($_SESSION, true));
    error_log("Cookies: " . print_r($_COOKIE, true));
    
    // Check for user ID in session
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        // Try to get user ID from headers (robust to header key casing)
        $headers = getallheaders();
        error_log("Request headers: " . print_r($headers, true));
        $normalized = [];
        foreach ($headers as $k => $v) {
            $normalized[strtolower($k)] = $v;
        }
        if (isset($normalized['x-user-id']) && $normalized['x-user-id'] !== '') {
            $userId = intval($normalized['x-user-id']);
            error_log("Using user ID from header: " . $userId);
            return $userId;
        }

        // Fallback to query param
        $userId = $_GET['user_id'] ?? null;
        if ($userId) {
            error_log("Using user ID from query param: " . $userId);
            return intval($userId);
        }
        
        error_log("Authentication failed: No valid user ID found");
        sendResponse(false, 'Authentication required. Please log in.', null, 401);
        exit();
    }
    
    error_log("User authenticated from session: " . $_SESSION['user_id']);
    return $_SESSION['user_id'];
}

// Send JSON response
function sendResponse($success, $message, $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

// Get database connection
function getConnection() {
    try {
        global $db;
        if (!isset($db) || !($db instanceof Database)) {
            // If global $db is not available, create a new Database instance
            $db = new Database();
        }
        error_log("Using PDO connection");
        return $db->getConnection();
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        throw new Exception("Database connection error: " . $e->getMessage());
    }
}

// Generate unique order number
function generateOrderNumber() {
    return 'ORD' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

// Create new order
function createOrder($userId) {
    $conn = getConnection();
    
    try {
        // Log the raw input for debugging
        $rawInput = file_get_contents('php://input');
        error_log("Raw order input: " . $rawInput);
        
        // Get and validate input data
        $input = json_decode($rawInput, true);
        
        if (!$input) {
            error_log("JSON decode error: " . json_last_error_msg());
            sendResponse(false, 'Invalid JSON data: ' . json_last_error_msg());
        }
        
        // Log parsed input
        error_log("Parsed order input: " . print_r($input, true));
        
        $customerInfo = $input['customer_info'] ?? [];
        $shippingAddress = $input['shipping_address'] ?? [];
        $orderItems = $input['order_items'] ?? [];
        $orderSummary = $input['order_summary'] ?? [];
        $shippingMethod = $input['shipping_method'] ?? 'standard';
        $paymentMethod = $input['payment_method'] ?? 'cash';
        $paymentDetails = $input['payment_details'] ?? [];
        $orderNotes = $input['order_notes'] ?? '';
        
        // Log extracted data
        error_log("Customer info: " . print_r($customerInfo, true));
        error_log("Shipping address: " . print_r($shippingAddress, true));
        error_log("Order items count: " . count($orderItems));
        error_log("Payment method: " . $paymentMethod);
        
        // Validate required data
        if (empty($orderItems)) {
            error_log("Order validation failed: Empty order items");
            sendResponse(false, 'Order items are required');
        }
        
        // Check for email in customer info
        if (empty($customerInfo['email'])) {
            error_log("Order validation failed: Missing email");
            sendResponse(false, 'Email address is required');
        }
        
        // Check for address in shipping address
        // We now accept either the 'address' field or the 'full_address' field
        $hasAddress = !empty($shippingAddress['address']) || !empty($shippingAddress['full_address']);
        if (!$hasAddress) {
            error_log("Order validation failed: Missing shipping address");
            sendResponse(false, 'Shipping address is required');
        }
        
        // Start transaction
        $conn->beginTransaction();
        
        // Generate order number
        $orderNumber = generateOrderNumber();
        
        // Ensure unique order number
        $checkStmt = $conn->prepare("SELECT id FROM orders WHERE order_number = ?");
        while (true) {
            $checkStmt->execute([$orderNumber]);
            if ($checkStmt->rowCount() === 0) {
                break;
            }
            $orderNumber = generateOrderNumber();
        }
        
        // Prepare shipping address
        // Use full_address if available, otherwise construct from components
        if (!empty($shippingAddress['full_address'])) {
            $shippingAddressText = $shippingAddress['full_address'];
        } else {
            $shippingAddressText = implode(', ', array_filter([
                $shippingAddress['address'] ?? '',
                $shippingAddress['apartment'] ?? '',
                $shippingAddress['city'] ?? '',
                $shippingAddress['division'] ?? '',
                $shippingAddress['postal_code'] ?? ''
            ]));
        }
        
        error_log("Shipping address text: " . $shippingAddressText);
        
        // Prepare billing address
        $billingAddress = $input['billing_address'] ?? [];
        
        if (!empty($billingAddress['full_address'])) {
            $billingAddressText = $billingAddress['full_address'];
        } else if (!empty($billingAddress['address'])) {
            $billingAddressText = implode(', ', array_filter([
                $billingAddress['address'] ?? '',
                $billingAddress['apartment'] ?? '',
                $billingAddress['city'] ?? '',
                $billingAddress['division'] ?? '',
                $billingAddress['postal_code'] ?? ''
            ]));
    } else {
            // Default to shipping address if billing not provided
            $billingAddressText = $shippingAddressText;
        }
        
        error_log("Billing address text: " . $billingAddressText);
        
        // Calculate totals
        $subtotal = $orderSummary['subtotal'] ?? 0;
        $shippingCost = $orderSummary['shipping_cost'] ?? 120;
        $taxAmount = $orderSummary['tax'] ?? 0;
        $totalAmount = $orderSummary['total'] ?? ($subtotal + $shippingCost + $taxAmount);
        
        // Determine payment status
        $paymentStatus = 'pending';
        if ($paymentMethod === 'cash') {
            $paymentStatus = 'pending'; // Will be paid on delivery
        }
        
        // Insert order
        $orderStmt = $conn->prepare("
            INSERT INTO orders (
                user_id, order_number, subtotal, shipping_cost, tax_amount, total_amount,
                status, payment_method, payment_status, shipping_address, billing_address,
                customer_email, customer_phone, shipping_method, order_notes, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        if (!$orderStmt->execute([
            $userId,
            $orderNumber,
            $subtotal,
            $shippingCost,
            $taxAmount,
            $totalAmount,
            $paymentMethod,
            $paymentStatus,
            $shippingAddressText,
            $billingAddressText,
            $customerInfo['email'],
            $customerInfo['phone'],
            $shippingMethod,
            $orderNotes
        ])) {
            throw new Exception("Failed to create order: " . implode(" ", $orderStmt->errorInfo()));
        }
        
        $orderId = $conn->lastInsertId();
        
        // Insert order items
        $itemStmt = $conn->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, price, total)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($orderItems as $item) {
            // Prefer product_id; some clients send cart row id as id
            $productId = isset($item['product_id']) ? (int)$item['product_id'] : (isset($item['id']) ? (int)$item['id'] : 0);
            if ($productId <= 0) {
                throw new Exception('Order item missing product_id');
            }
            // Validate product exists to avoid FK violation
            $prodCheck = $conn->prepare("SELECT id FROM products WHERE id = ?");
            $prodCheck->execute([$productId]);
            if ($prodCheck->rowCount() === 0) {
                throw new Exception('Invalid product_id in order item: ' . $productId);
            }
            $quantity = (int)$item['quantity'];
            $price = (float)$item['price'];
            $totalPrice = $price * $quantity;
            if (!$itemStmt->execute([$orderId, $productId, $quantity, $price, $totalPrice])) {
                throw new Exception("Failed to add order item: " . implode(" ", $itemStmt->errorInfo()));
            }
            // Update product stock (optional)
            $updateStockStmt = $conn->prepare("
                UPDATE products SET stock_quantity = stock_quantity - ? 
                WHERE id = ? AND stock_quantity >= ?
            ");
            $updateStockStmt->execute([$quantity, $productId, $quantity]);
        }
        
        // Store payment details (encrypt sensitive data in production)
        try {
            $paymentDetailsJson = json_encode($paymentDetails);
            $transactionId = '';
            $cardType = null;
            $cardLastFour = null;
            $provider = null;
            $paymentDate = date('Y-m-d H:i:s');
            $notes = null;
            
            // Extract payment-specific details based on payment method
            if ($paymentMethod === 'card' && isset($paymentDetails['card_number'])) {
                // Extract card details
                $cardLastFour = substr($paymentDetails['card_number'], -4);
                $cardType = isset($paymentDetails['card_type']) ? $paymentDetails['card_type'] : 'unknown';
                $transactionId = 'CARD_' . $cardLastFour . '_' . time();
                $notes = "Card payment processed on " . date('Y-m-d H:i:s');
            } 
            else if ($paymentMethod === 'mobile') {
                // Extract mobile banking details
                $provider = isset($paymentDetails['provider']) ? $paymentDetails['provider'] : 'unknown';
                $transactionId = isset($paymentDetails['transaction_id']) ? $paymentDetails['transaction_id'] : 'MOB_' . time();
                $notes = "Mobile banking payment via $provider";
            } 
            else if ($paymentMethod === 'cash') {
                $transactionId = 'COD_' . time();
                $notes = "Cash on delivery payment";
            }
            
            // Set payment amount
            $paymentAmount = $totalAmount;
            
            // Log payment details for debugging
            error_log("Payment details: " . print_r([
                'method' => $paymentMethod,
                'transaction_id' => $transactionId,
                'card_type' => $cardType,
                'card_last_four' => $cardLastFour,
                'provider' => $provider,
                'amount' => $paymentAmount,
                'payment_date' => $paymentDate,
                'notes' => $notes
            ], true));
            
            $paymentStmt = $conn->prepare("
                INSERT INTO order_payments (
                    order_id, 
                    payment_method, 
                    payment_details, 
                    transaction_id,
                    card_type,
                    card_last_four,
                    provider,
                    status, 
                    amount,
                    payment_date,
                    notes,
                    created_at
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            if (!$paymentStmt->execute([
                $orderId, 
                $paymentMethod, 
                $paymentDetailsJson, 
                $transactionId, 
                $cardType,
                $cardLastFour,
                $provider,
                $paymentStatus, 
                $paymentAmount,
                $paymentDate,
                $notes
            ])) {
                throw new Exception("Failed to save payment details: " . implode(" ", $paymentStmt->errorInfo()));
            }
            
            // Log payment information
            error_log("Payment saved: Order #$orderNumber, Method: $paymentMethod, Transaction ID: $transactionId, Amount: $paymentAmount");
        } catch (Exception $e) {
            error_log("Payment processing error: " . $e->getMessage());
            // Continue with order processing even if payment recording fails
        }
        
        // Clear user's cart
        $clearCartStmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $clearCartStmt->execute([$userId]);
        
        // Commit transaction
        $conn->commit();
        
        // Log successful order
        error_log("Order created successfully: Order #$orderNumber, User ID: $userId, Total: $$totalAmount");
        
        // Send email confirmation (implement separately)
        // sendOrderConfirmationEmail($customerInfo['email'], $orderNumber);
        
        sendResponse(true, 'Order placed successfully', [
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'total_amount' => $totalAmount,
            'status' => 'pending'
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        
        error_log("Order creation error: " . $e->getMessage());
        sendResponse(false, 'Failed to create order: ' . $e->getMessage(), null, 500);
    }
}

// Get user orders
function getUserOrders($userId) {
    $conn = getConnection();
    
    try {
        $stmt = $conn->prepare("
            SELECT o.*, 
                   COUNT(oi.id) as item_count,
                   GROUP_CONCAT(
                       CONCAT(p.name, ' (', oi.quantity, ')')
                       SEPARATOR ', '
                   ) as items_summary
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE o.user_id = ?
            GROUP BY o.id
            ORDER BY o.created_at DESC
            LIMIT 50
        ");
        
        $stmt->execute([$userId]);
        $result = $stmt->fetchAll();
        
        sendResponse(true, 'Orders retrieved successfully', $result);
        
    } catch (Exception $e) {
        error_log("Get orders error: " . $e->getMessage());
        sendResponse(false, 'Failed to retrieve orders', null, 500);
    }
}

// Get single order details
function getOrderDetails($userId, $orderId) {
    $conn = getConnection();
    
    try {
        // Get order details
        $orderStmt = $conn->prepare("
            SELECT * FROM orders 
            WHERE id = ? AND user_id = ?
        ");
        $orderStmt->execute([$orderId, $userId]);
        $order = $orderStmt->fetch();
        
        if (!$order) {
            sendResponse(false, 'Order not found', null, 404);
        }
        
        // Get order items
        $itemsStmt = $conn->prepare("
            SELECT oi.*, p.name, p.image, p.description
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $itemsStmt->execute([$orderId]);
        $items = $itemsStmt->fetchAll();
        
        $order['items'] = $items;
        
        sendResponse(true, 'Order details retrieved successfully', $order);
        
    } catch (Exception $e) {
        error_log("Get order details error: " . $e->getMessage());
        sendResponse(false, 'Failed to retrieve order details', null, 500);
    }
}

// Main request handling
$method = $_SERVER['REQUEST_METHOD'];

// Try to get user ID with fallbacks
try {
    $userId = checkAuth();
    error_log("User authenticated with ID: $userId");
} catch (Exception $e) {
    error_log("Authentication error: " . $e->getMessage());
    
    // Emergency fallback - use a default user for testing
    // ONLY FOR DEVELOPMENT - REMOVE IN PRODUCTION
    $userId = 1; // Default test user
    error_log("FALLBACK: Using default user ID: $userId");
}

switch ($method) {
    case 'GET':
        $orderId = $_GET['order_id'] ?? null;
        
        if ($orderId) {
            getOrderDetails($userId, $orderId);
        } else {
            getUserOrders($userId);
        }
        break;
        
    case 'POST':
        createOrder($userId);
        break;
        
    default:
        sendResponse(false, 'Method not allowed', null, 405);
}

// End of main try block
} catch (Exception $e) {
    // Clear any output that might have been generated
    ob_clean();
    
    // Log the error
    error_log("Critical API error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Send a proper JSON error response
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An internal server error occurred. Please try again.',
        'error' => $e->getMessage()
    ]);
}

// End output buffering and clean any unexpected output
$output = ob_get_clean();
if (!empty($output)) {
    error_log("Unexpected output in orders.php: " . $output);
}
?>