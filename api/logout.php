<?php
/**
 * Logout functionality for Handicrafts Marketplace
 */

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

try {
    global $db;
    
    // Get the session token from cookie
    $authToken = $_COOKIE['auth_token'] ?? '';
    
    if ($authToken) {
        // Log the logout event
        $user = getCurrentUser();
        if ($user) {
            logSecurityEvent($user['id'], 'logout', 'User logged out', $_SERVER['REMOTE_ADDR'] ?? null);
        }
        
        // Remove session from database
        $db->query('DELETE FROM user_sessions WHERE session_token = ?', [$authToken]);
        
        // Clear cookie
        setcookie('auth_token', '', time() - 3600, '/', '', false, true);
    }
    
    // Destroy PHP session
    session_destroy();
    
    // Clear any stored user data
    if (isset($_SESSION)) {
        $_SESSION = array();
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Logged out successfully',
        'redirect' => 'login.html'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Logout failed: ' . $e->getMessage()
    ]);
}
?>
