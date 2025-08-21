<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
session_start();

header('Content-Type: application/json');

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'register':
            handleRegistration();
            break;
        case 'login':
            handleLogin();
            break;
        default:
            sendResponse(false, 'Invalid action');
    }
}

function handleRegistration() {
    try {
        // Get and validate input - use more compatible method
        $firstName = isset($_POST['firstName']) ? trim($_POST['firstName']) : '';
        $lastName = isset($_POST['lastName']) ? trim($_POST['lastName']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $password = $_POST['password'] ?? '';
        
        // Validate required fields
        if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
            sendResponse(false, 'All required fields must be filled out');
            return;
        }
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sendResponse(false, 'Please enter a valid email address');
            return;
        }
        
        // Validate password length
        if (strlen($password) < 6) {
            sendResponse(false, 'Password must be at least 6 characters long');
            return;
        }
        
        // Use the Database class
        global $db;
        $conn = $db->getConnection();
        
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            sendResponse(false, 'Email address is already registered');
            return;
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $conn->prepare("
            INSERT INTO users (first_name, last_name, email, phone, password, oauth_provider, is_verified, status) 
            VALUES (?, ?, ?, ?, ?, 'local', 0, 'active')
        ");
        
        if ($stmt->execute([$firstName, $lastName, $email, $phone, $hashedPassword])) {
            $userId = $conn->lastInsertId();
            
            // Log successful registration
            error_log("New user registered: ID = $userId, Email = $email");
            
            sendResponse(true, 'Registration successful! Please log in.', [
                'userId' => $userId,
                'email' => $email
            ]);
        } else {
            error_log("Registration failed: " . implode(", ", $stmt->errorInfo()));
            sendResponse(false, 'Registration failed. Please try again.');
        }
        
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        sendResponse(false, 'An error occurred during registration');
    }
}

function handleLogin() {
    try {
        // Get and validate input - use more compatible method
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = $_POST['password'] ?? '';
        
        // Validate required fields
        if (empty($email) || empty($password)) {
            sendResponse(false, 'Email and password are required');
            return;
        }
        
        // Use the Database class
        global $db;
        $conn = $db->getConnection();
        
        // Get user by email
        $stmt = $conn->prepare("SELECT id, first_name, last_name, email, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() === 0) {
            sendResponse(false, 'Invalid email or password');
            return;
        }
        
        $user = $stmt->fetch();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Create session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['logged_in'] = true;
            
            // Update last login time
            $updateStmt = $conn->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
            $updateStmt->execute([$user['id']]);
            
            // Log successful login
            error_log("User logged in: ID = {$user['id']}, Email = {$user['email']}");
            
            sendResponse(true, 'Login successful! Redirecting to homepage...', [
                'userId' => $user['id'],
                'userName' => $user['first_name'] . ' ' . $user['last_name']
            ]);
        } else {
            sendResponse(false, 'Invalid email or password');
        }
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        sendResponse(false, 'An error occurred during login');
    }
}

function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}
?>