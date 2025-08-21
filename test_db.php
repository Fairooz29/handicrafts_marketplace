<?php
/**
 * Database Connection Test
 * This file tests if the database connection is working and if data exists
 */

echo "<h2>Database Connection Test</h2>";

// Test 1: Check if database connection works
try {
    require_once 'config/database.php';
    echo "<p style='color: green;'>✓ Database connection successful!</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Test 2: Check if database exists
try {
    $result = $db->fetch("SELECT DATABASE() as current_db");
    echo "<p style='color: green;'>✓ Connected to database: " . $result['current_db'] . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database check failed: " . $e->getMessage() . "</p>";
}

// Test 3: Check if tables exist
try {
    $tables = $db->fetchAll("SHOW TABLES");
    echo "<p style='color: green;'>✓ Found " . count($tables) . " tables:</p>";
    echo "<ul>";
    foreach ($tables as $table) {
        $tableName = array_values($table)[0];
        echo "<li>$tableName</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Tables check failed: " . $e->getMessage() . "</p>";
}

// Test 4: Check products table specifically
try {
    $productCount = $db->fetch("SELECT COUNT(*) as count FROM products");
    echo "<p style='color: green;'>✓ Products table has " . $productCount['count'] . " records</p>";
    
    if ($productCount['count'] > 0) {
        echo "<h3>Sample Products:</h3>";
        $sampleProducts = $db->fetchAll("SELECT id, name, price FROM products LIMIT 5");
        echo "<ul>";
        foreach ($sampleProducts as $product) {
            echo "<li>ID: {$product['id']}, Name: {$product['name']}, Price: ৳{$product['price']}</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Products table check failed: " . $e->getMessage() . "</p>";
    
    // If products table doesn't exist, show create table suggestion
    echo "<p><strong>Solution:</strong> You need to import the database SQL file. Go to phpMyAdmin and import 'database/handicrafts_marketplace.sql'</p>";
}

// Test 5: Test the API endpoint
echo "<h3>API Test:</h3>";
echo "<p>Try accessing: <a href='api/products.php?action=all' target='_blank'>api/products.php?action=all</a></p>";

// Test 6: Check PHP version and extensions
echo "<h3>PHP Environment:</h3>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>PDO MySQL Extension: " . (extension_loaded('pdo_mysql') ? '✓ Available' : '✗ Missing') . "</p>";
echo "<p>JSON Extension: " . (extension_loaded('json') ? '✓ Available' : '✗ Missing') . "</p>";

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Make sure XAMPP MySQL is running</li>";
echo "<li>Import database/handicrafts_marketplace.sql into phpMyAdmin</li>";
echo "<li>Check that the database name is 'handicrafts_marketplace'</li>";
echo "<li>Test the API endpoint above</li>";
echo "</ol>";
?>





