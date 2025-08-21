<?php
/**
 * Simple Products API Test
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Add CORS headers for testing
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

echo "Testing Products API...\n";

try {
    // Include required files
    require_once '../config/database.php';
    require_once '../includes/functions.php';
    
    echo "✓ Files included successfully\n";
    
    // Test database connection
    $testQuery = $db->fetch("SELECT COUNT(*) as count FROM products");
    echo "✓ Database connected, found " . $testQuery['count'] . " products\n";
    
    // Test getting all products
    $sql = "SELECT 
                p.id, p.name, p.short_description, p.price, p.original_price, 
                p.discount_percentage, p.image, p.stock_quantity,
                c.name as category_name,
                a.name as artisan_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN artisans a ON p.artisan_id = a.id
            WHERE p.status = 'active'
            ORDER BY p.created_at DESC
            LIMIT 5";
    
    $products = $db->fetchAll($sql);
    
    echo "✓ Products query successful\n";
    
    // Return JSON response
    $response = [
        'success' => true,
        'message' => 'API test successful',
        'products_count' => count($products),
        'products' => $products,
        'test_time' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    $error = [
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ];
    
    echo json_encode($error, JSON_PRETTY_PRINT);
}
?>





