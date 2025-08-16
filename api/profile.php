<?php
session_start();
require_once '../config/database.php';

// Set content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check if user is authenticated
function checkAuth() {
    error_log("Session check - user_id: " . ($_SESSION['user_id'] ?? 'not set') . ", logged_in: " . ($_SESSION['logged_in'] ?? 'not set'));
    
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        error_log("Authentication failed - redirecting to login");
        sendResponse(false, 'Authentication required. Please log in.', null, 401);
        exit();
    }
    
    error_log("Authentication successful for user ID: " . $_SESSION['user_id']);
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
        // Use same connection method as auth.php
        $conn = new mysqli('localhost', 'root', '', 'handicrafts_marketplace');
        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
            sendResponse(false, 'Database connection failed', null, 500);
            exit();
        }
        return $conn;
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        sendResponse(false, 'Database error', null, 500);
        exit();
    }
}

// Get user profile
function getUserProfile($userId) {
    error_log("Getting profile for user ID: " . $userId);
    $conn = getConnection();
    
    try {
        $stmt = $conn->prepare("SELECT id, first_name, last_name, email, phone, address, city, postal_code, profile_image, last_login, created_at FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        error_log("Query executed. Number of rows found: " . $result->num_rows);
        
        if ($result->num_rows === 0) {
            error_log("User not found in database for ID: " . $userId);
            sendResponse(false, 'User not found', null, 404);
        }
        
        $user = $result->fetch_assoc();
        error_log("User data retrieved: " . json_encode($user));
        
        $stmt->close();
        $conn->close();
        
        sendResponse(true, 'Profile retrieved successfully', $user);
    } catch (Exception $e) {
        error_log("Get profile error: " . $e->getMessage());
        sendResponse(false, 'Failed to retrieve profile', null, 500);
    }
}

// Update user profile
function updateUserProfile($userId) {
    $conn = getConnection();
    
    try {
        // Get and validate input data
        $input = json_decode(file_get_contents('php://input'), true);
        
        $firstName = filter_var($input['first_name'] ?? '', FILTER_SANITIZE_STRING);
        $lastName = filter_var($input['last_name'] ?? '', FILTER_SANITIZE_STRING);
        $email = filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $phone = filter_var($input['phone'] ?? '', FILTER_SANITIZE_STRING);
        $address = filter_var($input['address'] ?? '', FILTER_SANITIZE_STRING);
        $city = filter_var($input['city'] ?? '', FILTER_SANITIZE_STRING);
        $postalCode = filter_var($input['postal_code'] ?? '', FILTER_SANITIZE_STRING);
        
        // Validate required fields
        if (!$firstName || !$lastName || !$email) {
            sendResponse(false, 'First name, last name, and email are required');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sendResponse(false, 'Invalid email format');
        }
        
        // Check if email is already taken by another user
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            sendResponse(false, 'Email address is already in use');
        }
        $stmt->close();
        
        // Update user profile
        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, city = ?, postal_code = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("sssssssi", $firstName, $lastName, $email, $phone, $address, $city, $postalCode, $userId);
        
        if ($stmt->execute()) {
            // Update session data
            $_SESSION['user_name'] = $firstName . ' ' . $lastName;
            $_SESSION['user_email'] = $email;
            
            $stmt->close();
            $conn->close();
            
            sendResponse(true, 'Profile updated successfully');
        } else {
            sendResponse(false, 'Failed to update profile');
        }
        
    } catch (Exception $e) {
        error_log("Update profile error: " . $e->getMessage());
        sendResponse(false, 'Failed to update profile', null, 500);
    }
}

// Change password
function changePassword($userId) {
    $conn = getConnection();
    
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $currentPassword = $input['current_password'] ?? '';
        $newPassword = $input['new_password'] ?? '';
        $confirmPassword = $input['confirm_password'] ?? '';
        
        // Validate input
        if (!$currentPassword || !$newPassword || !$confirmPassword) {
            sendResponse(false, 'All password fields are required');
        }
        
        if ($newPassword !== $confirmPassword) {
            sendResponse(false, 'New passwords do not match');
        }
        
        if (strlen($newPassword) < 6) {
            sendResponse(false, 'New password must be at least 6 characters long');
        }
        
        // Get current password hash
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            sendResponse(false, 'User not found', null, 404);
        }
        
        $user = $result->fetch_assoc();
        $stmt->close();
        
        // Verify current password
        if (!password_verify($currentPassword, $user['password'])) {
            sendResponse(false, 'Current password is incorrect');
        }
        
        // Hash new password
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update password
        $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("si", $newPasswordHash, $userId);
        
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            
            sendResponse(true, 'Password changed successfully');
        } else {
            sendResponse(false, 'Failed to change password');
        }
        
    } catch (Exception $e) {
        error_log("Change password error: " . $e->getMessage());
        sendResponse(false, 'Failed to change password', null, 500);
    }
}

// Get user orders
function getUserOrders($userId) {
    $conn = getConnection();
    
    try {
        $stmt = $conn->prepare("
            SELECT o.id, o.order_number, o.total_amount, o.status, o.created_at,
                   GROUP_CONCAT(
                       CONCAT(oi.product_id, ':', oi.quantity, ':', oi.price, ':', p.name, ':', p.image_url)
                       SEPARATOR '|'
                   ) as items
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE o.user_id = ?
            GROUP BY o.id
            ORDER BY o.created_at DESC
            LIMIT 20
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $order = [
                'id' => $row['id'],
                'order_number' => $row['order_number'],
                'total_amount' => $row['total_amount'],
                'status' => $row['status'],
                'created_at' => $row['created_at'],
                'items' => []
            ];
            
            if ($row['items']) {
                $items = explode('|', $row['items']);
                foreach ($items as $item) {
                    $itemParts = explode(':', $item);
                    if (count($itemParts) >= 5) {
                        $order['items'][] = [
                            'product_id' => $itemParts[0],
                            'quantity' => $itemParts[1],
                            'price' => $itemParts[2],
                            'name' => $itemParts[3],
                            'image_url' => $itemParts[4]
                        ];
                    }
                }
            }
            
            $orders[] = $order;
        }
        
        $stmt->close();
        $conn->close();
        
        sendResponse(true, 'Orders retrieved successfully', $orders);
    } catch (Exception $e) {
        error_log("Get orders error: " . $e->getMessage());
        sendResponse(false, 'Failed to retrieve orders', null, 500);
    }
}

// Main request handling
$method = $_SERVER['REQUEST_METHOD'];
$userId = checkAuth();

switch ($method) {
    case 'GET':
        $action = $_GET['action'] ?? 'profile';
        
        switch ($action) {
            case 'profile':
                getUserProfile($userId);
                break;
            case 'orders':
                getUserOrders($userId);
                break;
            default:
                sendResponse(false, 'Invalid action', null, 400);
        }
        break;
        
    case 'PUT':
        $action = $_GET['action'] ?? 'profile';
        
        switch ($action) {
            case 'profile':
                updateUserProfile($userId);
                break;
            case 'password':
                changePassword($userId);
                break;
            default:
                sendResponse(false, 'Invalid action', null, 400);
        }
        break;
        
    default:
        sendResponse(false, 'Method not allowed', null, 405);
}
?>
