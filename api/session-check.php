<?php
session_start();
header('Content-Type: application/json');

// Send JSON response
function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'session_data' => [
            'user_id' => $_SESSION['user_id'] ?? null,
            'user_name' => $_SESSION['user_name'] ?? null,
            'user_email' => $_SESSION['user_email'] ?? null,
            'logged_in' => $_SESSION['logged_in'] ?? false
        ],
        'local_storage_check' => [
            'note' => 'Check browser localStorage for is_logged_in, user_id, user_name'
        ]
    ]);
}

// Check if user is authenticated
if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    sendResponse(true, 'User is authenticated', [
        'user_id' => $_SESSION['user_id'],
        'user_name' => $_SESSION['user_name'],
        'user_email' => $_SESSION['user_email']
    ]);
} else {
    sendResponse(false, 'User is not authenticated - session missing or invalid');
}
?>
