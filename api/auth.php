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
        // Get and validate input
        $firstName = filter_input(INPUT_POST, 'firstName', FILTER_SANITIZE_STRING);
        $lastName = filter_input(INPUT_POST, 'lastName', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $password = $_POST['password'] ?? '';
        
        // Validate required fields
        if (!$firstName || !$lastName || !$email || !$password) {
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
        
        // Create database connection
        $conn = new mysqli('localhost', 'root', '', 'handicrafts_marketplace');
        
        // Check connection
        if ($conn->connect_error) {
            error_log("Connection failed: " . $conn->connect_error);
            sendResponse(false, 'Database connection failed');
            return;
        }
        
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
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
        
        $stmt->bind_param("sssss", $firstName, $lastName, $email, $phone, $hashedPassword);
        
        if ($stmt->execute()) {
            $userId = $conn->insert_id;
            
            // Log successful registration
            error_log("New user registered: ID = $userId, Email = $email");
            
            sendResponse(true, 'Registration successful! Please log in.', [
                'userId' => $userId,
                'email' => $email
            ]);
    } else {
            error_log("Registration failed: " . $stmt->error);
            sendResponse(false, 'Registration failed. Please try again.');
        }
        
        $stmt->close();
        $conn->close();
        
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        sendResponse(false, 'An error occurred during registration');
    }
}

function handleLogin() {
    try {
        // Get and validate input
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        
        // Validate required fields
        if (!$email || !$password) {
            sendResponse(false, 'Email and password are required');
            return;
        }
        
        // Create database connection
        $conn = new mysqli('localhost', 'root', '', 'handicrafts_marketplace');
        
        // Check connection
        if ($conn->connect_error) {
            error_log("Connection failed: " . $conn->connect_error);
            sendResponse(false, 'Database connection failed');
            return;
        }
        
        // Get user by email
        $stmt = $conn->prepare("SELECT id, first_name, last_name, email, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            sendResponse(false, 'Invalid email or password');
        return;
    }
    
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Create session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['logged_in'] = true;
            
            // Update last login time
            $updateStmt = $conn->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
            $updateStmt->bind_param("i", $user['id']);
            $updateStmt->execute();
            
            // Log successful login
            error_log("User logged in: ID = {$user['id']}, Email = {$user['email']}");
            
            sendResponse(true, 'Login successful! Redirecting to homepage...', [
                'userId' => $user['id'],
                'userName' => $user['first_name'] . ' ' . $user['last_name']
        ]);
    } else {
            sendResponse(false, 'Invalid email or password');
        }
        
        $stmt->close();
        $conn->close();
        
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