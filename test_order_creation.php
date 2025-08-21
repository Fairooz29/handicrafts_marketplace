<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing Order Creation Process</h2>";

try {
    require_once 'config/database.php';
    echo "<p style='color: green;'>✓ Database configuration loaded</p>";
    
    $db = new Database();
    $conn = $db->getConnection();
    echo "<p style='color: green;'>✓ Database connection established</p>";
    
    // Test 1: Check if orders table exists
    $stmt = $conn->prepare("SHOW TABLES LIKE 'orders'");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result) {
        echo "<p style='color: green;'>✓ Orders table exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Orders table does not exist</p>";
        exit;
    }
    
    // Test 2: Check orders table structure
    $stmt = $conn->prepare("DESCRIBE orders");
    $stmt->execute();
    $columns = $stmt->fetchAll();
    
    echo "<h3>Orders Table Structure:</h3>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li><strong>{$column['Field']}</strong> - {$column['Type']} " . 
             ($column['Null'] === 'NO' ? '(NOT NULL)' : '(NULL)') . "</li>";
    }
    echo "</ul>";
    
    // Test 3: Check if there are any existing orders
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders");
    $stmt->execute();
    $result = $stmt->fetch();
    
    echo "<p>Current orders in database: " . $result['count'] . "</p>";
    
    // Test 4: Check if there are any users
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users");
    $stmt->execute();
    $result = $stmt->fetch();
    
    echo "<p>Current users in database: " . $result['count'] . "</p>";
    
    // Test 5: Check if there are any products
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products");
    $stmt->execute();
    $result = $stmt->fetch();
    
    echo "<p>Current products in database: " . $result['count'] . "</p>";
    
    // Test 6: Check if there are any cart items
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart");
    $stmt->execute();
    $result = $stmt->fetch();
    
    echo "<p>Current cart items in database: " . $result['count'] . "</p>";
    
    // Test 7: Try to create a test order
    echo "<h3>Testing Order Creation:</h3>";
    
    // Check if we have a user
    $stmt = $conn->prepare("SELECT id, email FROM users LIMIT 1");
    $stmt->execute();
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<p>Using test user: ID = {$user['id']}, Email = {$user['email']}</p>";
        
        // Check if we have a product
        $stmt = $conn->prepare("SELECT id, name, price FROM products LIMIT 1");
        $stmt->execute();
        $product = $stmt->fetch();
        
        if ($product) {
            echo "<p>Using test product: ID = {$product['id']}, Name = {$product['name']}, Price = {$product['price']}</p>";
            
            // Try to create a test order
            try {
                $conn->beginTransaction();
                
                // Generate test order number
                $orderNumber = 'TEST' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                
                // Insert test order
                $orderStmt = $conn->prepare("
                    INSERT INTO orders (
                        user_id, order_number, subtotal, shipping_cost, tax_amount, total_amount,
                        status, payment_method, payment_status, shipping_address, billing_address,
                        customer_email, customer_phone, shipping_method, order_notes
                    ) VALUES (?, ?, ?, ?, ?, ?, 'pending', 'test', 'pending', ?, ?, ?, ?, 'standard', 'Test order')
                ");
                
                $shippingAddress = "123 Test Street, Test City, Test Division 1234";
                $billingAddress = "123 Test Street, Test City, Test Division 1234";
                $subtotal = $product['price'];
                $shippingCost = 120.00;
                $tax = round($subtotal * 0.05);
                $total = $subtotal + $shippingCost + $tax;
                
                if ($orderStmt->execute([
                    $user['id'],
                    $orderNumber,
                    $subtotal,
                    $shippingCost,
                    $tax,
                    $total,
                    $shippingAddress,
                    $billingAddress,
                    $user['email'],
                    '+8801234567890'
                ])) {
                    $orderId = $conn->lastInsertId();
                    echo "<p style='color: green;'>✓ Test order created successfully with ID: $orderId</p>";
                    
                    // Insert test order item
                    $itemStmt = $conn->prepare("
                        INSERT INTO order_items (order_id, product_id, quantity, price, total)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    
                    if ($itemStmt->execute([$orderId, $product['id'], 1, $product['price'], $product['price']])) {
                        echo "<p style='color: green;'>✓ Test order item created successfully</p>";
                    } else {
                        echo "<p style='color: red;'>✗ Failed to create test order item</p>";
                    }
                    
                    // Commit transaction
                    $conn->commit();
                    echo "<p style='color: green;'>✓ Test order transaction committed successfully</p>";
                    
                    // Clean up - delete test order
                    $deleteStmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
                    $deleteStmt->execute([$orderId]);
                    
                    $deleteStmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
                    $deleteStmt->execute([$orderId]);
                    
                    echo "<p style='color: green;'>✓ Test order cleaned up successfully</p>";
                    
                } else {
                    echo "<p style='color: red;'>✗ Failed to create test order</p>";
                    print_r($orderStmt->errorInfo());
                }
                
            } catch (Exception $e) {
                $conn->rollBack();
                echo "<p style='color: red;'>✗ Test order creation failed: " . $e->getMessage() . "</p>";
            }
            
        } else {
            echo "<p style='color: red;'>✗ No products found in database</p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ No users found in database</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
