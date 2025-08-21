<?php
/**
 * Common functions for Handicrafts Marketplace
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Get current user ID from session
 */
function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // Default to user ID 1 for demo
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Format price in BDT
 */
function formatPrice($price) {
    return '৳' . number_format($price, 0);
}

/**
 * Calculate discount percentage
 */
function calculateDiscount($originalPrice, $currentPrice) {
    if ($originalPrice <= 0) return 0;
    return round((($originalPrice - $currentPrice) / $originalPrice) * 100);
}

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Generate random order number
 */
function generateOrderNumber() {
    return 'ORD' . date('Ymd') . strtoupper(substr(uniqid(), -6));
}

/**
 * Send JSON response
 */
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Handle AJAX requests
 */
function handleAjaxRequest() {
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        sendJsonResponse(['error' => 'Invalid request'], 400);
    }
}

/**
 * Validate email format
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Hash password securely
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Truncate text to specified length
 */
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

/**
 * Get product image URL
 */
function getProductImage($imagePath) {
    return !empty($imagePath) ? $imagePath : 'assets/images/placeholder.jpg';
}

/**
 * Enhanced Authentication & Session Management Functions
 */

/**
 * Get current user from session token (enhanced version)
 */
function getCurrentUser() {
    global $db;
    
    $authToken = $_COOKIE['auth_token'] ?? '';
    
    if (empty($authToken)) {
        return null;
    }
    
    try {
        require_once '../config/database.php';
        
        $result = $db->fetch(
            'SELECT u.* FROM users u 
             INNER JOIN user_sessions s ON u.id = s.user_id 
             WHERE s.session_token = ? AND s.expires_at > NOW() AND u.status = "active"',
            [$authToken]
        );
        
        return $result;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Enhanced login check using token-based authentication
 */
function isLoggedInSecure() {
    return getCurrentUser() !== null;
}

/**
 * Create a new user session
 */
function createUserSession($userId, $longTerm = false) {
    global $db;
    
    // Generate secure session token
    $sessionToken = bin2hex(random_bytes(32));
    
    // Set expiration (7 days for normal, 30 days for "remember me")
    $expirationDays = $longTerm ? 30 : 7;
    $expiresAt = date('Y-m-d H:i:s', time() + (86400 * $expirationDays));
    
    // Get client info
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    try {
        require_once '../config/database.php';
        
        // Clean up old expired sessions for this user
        $db->query('DELETE FROM user_sessions WHERE expires_at < NOW() OR user_id = ?', [$userId]);
        
        // Insert new session
        $db->query(
            'INSERT INTO user_sessions (user_id, session_token, expires_at, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)',
            [$userId, $sessionToken, $expiresAt, $ipAddress, $userAgent]
        );
        
        return $sessionToken;
    } catch (Exception $e) {
        error_log("Session creation failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Invalidate user session
 */
function invalidateSession($sessionToken = null) {
    global $db;
    
    if ($sessionToken === null) {
        $sessionToken = $_COOKIE['auth_token'] ?? '';
    }
    
    if (!empty($sessionToken)) {
        try {
            require_once '../config/database.php';
            $db->query('DELETE FROM user_sessions WHERE session_token = ?', [$sessionToken]);
        } catch (Exception $e) {
            error_log("Session invalidation failed: " . $e->getMessage());
        }
    }
    
    // Clear cookie
    setcookie('auth_token', '', time() - 3600, '/', '', false, true);
}

/**
 * Clean up expired sessions
 */
function cleanupExpiredSessions() {
    global $db;
    try {
        require_once '../config/database.php';
        $db->query('DELETE FROM user_sessions WHERE expires_at < NOW()');
    } catch (Exception $e) {
        error_log("Session cleanup failed: " . $e->getMessage());
    }
}

/**
 * Require user authentication with redirect
 */
function requireAuth($redirectUrl = '/login.html') {
    if (!isLoggedInSecure()) {
        header('Location: ' . $redirectUrl);
        exit();
    }
}

/**
 * Check if password is strong enough
 */
function isStrongPassword($password) {
    return strlen($password) >= 8 && 
           preg_match('/[A-Z]/', $password) && 
           preg_match('/[a-z]/', $password) && 
           preg_match('/[0-9]/', $password);
}

/**
 * Generate secure token for password reset, email verification, etc.
 */
function generateSecureToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Rate limiting with database storage
 */
function checkRateLimit($key, $maxAttempts = 5, $timeWindow = 300) {
    global $db;
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $identifier = $ipAddress . '_' . $key;
    
    try {
        require_once '../config/database.php';
        
        // For now, use simple session-based rate limiting
        // In production, you should create a rate_limits table
        if (!isset($_SESSION['rate_limits'])) {
            $_SESSION['rate_limits'] = [];
        }
        
        $now = time();
        $attempts = $_SESSION['rate_limits'][$identifier] ?? [];
        
        // Remove old attempts outside the time window
        $attempts = array_filter($attempts, function($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) < $timeWindow;
        });
        
        // Check if limit exceeded
        if (count($attempts) >= $maxAttempts) {
            return false;
        }
        
        // Add current attempt
        $attempts[] = $now;
        $_SESSION['rate_limits'][$identifier] = $attempts;
        
        return true;
    } catch (Exception $e) {
        // If rate limiting fails, allow the request but log the error
        error_log("Rate limiting failed: " . $e->getMessage());
        return true;
    }
}

/**
 * Get user's full name
 */
function getUserFullName($user) {
    if (is_array($user)) {
        return trim($user['first_name'] . ' ' . $user['last_name']);
    }
    return '';
}

/**
 * Log security events
 */
function logSecurityEvent($userId, $event, $details = '', $ipAddress = null) {
    $ipAddress = $ipAddress ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    error_log("Security Event - User: $userId, Event: $event, Details: $details, IP: $ipAddress, UA: $userAgent");
}
?>
