<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';

// Test database connection
try {
    $db = new Database();
    echo "Database connection successful!\n";
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Test user registration
function testRegistration() {
    global $db;
    
    $testUser = [
        'firstName' => 'Test',
        'lastName' => 'User',
        'email' => 'test' . time() . '@example.com', // Unique email
        'phone' => '+1234567890',
        'password' => password_hash('testpassword123', PASSWORD_DEFAULT)
    ];
    
    try {
        // Check if email exists
        $existingUser = $db->fetch("SELECT id FROM users WHERE email = ?", [$testUser['email']]);
        if ($existingUser) {
            echo "Error: Email already exists\n";
            return false;
        }
        
        // Insert new user
        $sql = "INSERT INTO users (first_name, last_name, email, phone, password) VALUES (?, ?, ?, ?, ?)";
        $db->query($sql, [
            $testUser['firstName'],
            $testUser['lastName'],
            $testUser['email'],
            $testUser['phone'],
            $testUser['password']
        ]);
        
        $userId = $db->lastInsertId();
        echo "Test user registered successfully with ID: " . $userId . "\n";
        
        // Verify the user was created
        $newUser = $db->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
        if ($newUser) {
            echo "User verification successful!\n";
            echo "User details:\n";
            print_r($newUser);
            return true;
        } else {
            echo "Error: User verification failed\n";
            return false;
        }
        
    } catch (Exception $e) {
        echo "Registration test failed: " . $e->getMessage() . "\n";
        return false;
    }
}

// Test user login
function testLogin($email, $password) {
    global $db;
    
    try {
        $user = $db->fetch("SELECT id, password FROM users WHERE email = ? AND oauth_provider = 'local'", [$email]);
        
        if (!$user) {
            echo "Login test failed: User not found\n";
            return false;
        }
        
        if (password_verify($password, $user['password'])) {
            echo "Login test successful!\n";
            return true;
        } else {
            echo "Login test failed: Invalid password\n";
            return false;
        }
        
    } catch (Exception $e) {
        echo "Login test failed: " . $e->getMessage() . "\n";
        return false;
    }
}

// Run tests
echo "Starting authentication tests...\n\n";

echo "Testing registration...\n";
$registrationSuccess = testRegistration();
echo "\n";

echo "Testing login with sample user...\n";
$loginSuccess = testLogin('john@example.com', 'password');
echo "\n";

// Test database tables
echo "Checking database tables...\n";
try {
    $tables = $db->fetchAll("SHOW TABLES");
    echo "Available tables:\n";
    print_r($tables);
    
    echo "\nChecking users table structure:\n";
    $columns = $db->fetchAll("SHOW COLUMNS FROM users");
    print_r($columns);
    
} catch (Exception $e) {
    echo "Database structure check failed: " . $e->getMessage() . "\n";
}
?>
