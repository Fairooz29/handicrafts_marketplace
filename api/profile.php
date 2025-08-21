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
        // Align with PDO like other endpoints
        global $db;
        if (!isset($db) || !($db instanceof Database)) {
            $db = new Database();
        }
        return $db->getConnection();
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
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            error_log("User not found in database for ID: " . $userId);
            sendResponse(false, 'User not found', null, 404);
        }

        error_log("User data retrieved: " . json_encode($user));
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
        $stmt->execute([$email, $userId]);
        $result = $stmt->fetchAll();
        if (count($result) > 0) {
            sendResponse(false, 'Email address is already in use');
        }
        
        // Update user profile
        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, city = ?, postal_code = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        if ($stmt->execute([$firstName, $lastName, $email, $phone, $address, $city, $postalCode, $userId])) {
            // Update session data
            $_SESSION['user_name'] = $firstName . ' ' . $lastName;
            $_SESSION['user_email'] = $email;
            
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
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user) {
            sendResponse(false, 'User not found', null, 404);
        }
        
        // Verify current password
        if (!password_verify($currentPassword, $user['password'])) {
            sendResponse(false, 'Current password is incorrect');
        }
        
        // Hash new password
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update password
        $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        if ($stmt->execute([$newPasswordHash, $userId])) {
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
            SELECT 
                o.id AS order_id,
                o.order_number,
                o.total_amount,
                o.status,
                o.created_at,
                oi.product_id,
                oi.quantity,
                oi.price,
                p.name AS product_name,
                p.image AS product_image
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE o.user_id = ?
            ORDER BY o.created_at DESC, o.id DESC
            LIMIT 200
        ");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();

        $ordersById = [];
        foreach ($rows as $row) {
            $oid = $row['order_id'];
            if (!isset($ordersById[$oid])) {
                $ordersById[$oid] = [
                    'id' => $oid,
                    'order_number' => $row['order_number'],
                    'total_amount' => $row['total_amount'],
                    'status' => $row['status'],
                    'created_at' => $row['created_at'],
                    'items' => []
                ];
            }
            if (!empty($row['product_id'])) {
                $ordersById[$oid]['items'][] = [
                    'product_id' => $row['product_id'],
                    'quantity' => (int)$row['quantity'],
                    'price' => (float)$row['price'],
                    'name' => $row['product_name'],
                    'image_url' => $row['product_image'],
                    'image' => $row['product_image']
                ];
            }
        }

        // Re-index and sort by created_at desc
        $orders = array_values($ordersById);
        usort($orders, function($a, $b) {
            return strtotime($b['created_at']) <=> strtotime($a['created_at']);
        });

        sendResponse(true, 'Orders retrieved successfully', $orders);
    } catch (Exception $e) {
        error_log("Get orders error: " . $e->getMessage());
        sendResponse(false, 'Failed to retrieve orders', null, 500);
    }
}

// Upload and update avatar image
function updateAvatar($userId) {
    $conn = getConnection();
    try {
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            sendResponse(false, 'No image uploaded or upload error');
        }
        $file = $_FILES['avatar'];
        // Basic validations
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
        $mime = mime_content_type($file['tmp_name']);
        if (!isset($allowed[$mime])) {
            sendResponse(false, 'Unsupported image type');
        }
        if ($file['size'] > 2 * 1024 * 1024) { // 2MB
            sendResponse(false, 'Image too large (max 2MB)');
        }

        // Prepare upload directory
        $uploadDir = realpath(__DIR__ . '/../uploads');
        if ($uploadDir === false) {
            // Try to create uploads directory
            $target = __DIR__ . '/../uploads';
            if (!is_dir($target)) {
                mkdir($target, 0755, true);
            }
            $uploadDir = realpath($target);
        }
        if ($uploadDir === false) {
            sendResponse(false, 'Failed to prepare upload directory');
        }

        // Generate filename
        $ext = $allowed[$mime];
        $fileName = 'avatar_' . $userId . '_' . time() . '.' . $ext;
        $destPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;
        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            sendResponse(false, 'Failed to save image');
        }

        // Build public URL relative to web root
        $publicUrl = 'uploads/' . $fileName;

        // Update DB
        $stmt = $conn->prepare("UPDATE users SET profile_image = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        if (!$stmt->execute([$publicUrl, $userId])) {
            sendResponse(false, 'Failed to update profile image');
        }

        // Return new image URL
        sendResponse(true, 'Profile image updated', ['profile_image' => $publicUrl]);
    } catch (Exception $e) {
        error_log('Avatar upload error: ' . $e->getMessage());
        sendResponse(false, 'Failed to update avatar', null, 500);
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
    case 'POST':
        $action = $_GET['action'] ?? '';
        if ($action === 'avatar') {
            updateAvatar($userId);
        } else {
            sendResponse(false, 'Invalid action', null, 400);
        }
        break;
        
    default:
        sendResponse(false, 'Method not allowed', null, 405);
}
?>
