-- Handicrafts Marketplace Database
-- Created for XAMPP MySQL

DROP DATABASE IF EXISTS handicrafts_marketplace;
CREATE DATABASE handicrafts_marketplace;
USE handicrafts_marketplace;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NULL,  -- Allow NULL for Google OAuth users
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(50),
    postal_code VARCHAR(10),
    profile_image VARCHAR(255),
    google_id VARCHAR(100) UNIQUE NULL,  -- For Google OAuth
    oauth_provider ENUM('local', 'google') DEFAULT 'local',
    is_verified BOOLEAN DEFAULT FALSE,
    last_login TIMESTAMP NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- User sessions table for secure session management
CREATE TABLE user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_session_token (session_token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
);

-- Categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Artisans table
CREATE TABLE artisans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    bio TEXT,
    image VARCHAR(255),
    location VARCHAR(100),
    speciality VARCHAR(100),
    experience_years INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    short_description VARCHAR(500),
    price DECIMAL(10,2) NOT NULL,
    original_price DECIMAL(10,2),
    discount_percentage INT DEFAULT 0,
    image VARCHAR(255) NOT NULL,
    category_id INT,
    artisan_id INT,
    stock_quantity INT DEFAULT 0,
    status ENUM('active', 'inactive', 'out_of_stock') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (artisan_id) REFERENCES artisans(id) ON DELETE SET NULL
);

-- Favorites table
CREATE TABLE favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    product_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite (user_id, product_id)
);

-- Cart table
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    product_id INT,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (user_id, product_id)
);

-- Orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    shipping_cost DECIMAL(10,2) DEFAULT 120.00,
    tax_amount DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_method VARCHAR(50),
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    shipping_address TEXT,
    billing_address TEXT,
    customer_email VARCHAR(100),
    customer_phone VARCHAR(20),
    shipping_method VARCHAR(50) DEFAULT 'standard',
    order_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Order items table
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Insert sample categories
INSERT INTO categories (name, description) VALUES
('Pottery', 'Traditional terracotta and ceramic items'),
('Embroidery', 'Hand-embroidered textiles and clothing'),
('Jute', 'Eco-friendly jute products and crafts'),
('Metal Craft', 'Dhokra and other metal art pieces'),
('Textiles', 'Traditional Bengali textiles and fabrics'),
('Wood Craft', 'Carved wooden items and furniture');

-- Insert sample artisans
INSERT INTO artisans (name, bio, image, location, speciality, experience_years) VALUES
('Rahim Ali', 'Rahim, a third-generation potter, creates exquisite terracotta pieces.', 'assets/images/artisan1.jpg', 'Bishnupur, West Bengal', 'Terracotta Pottery', 25),
('Fatima Begum', 'Fatima\'s intricate Nakshi Kantha embroidery tells stories of rural life.', 'assets/images/artisan2.jpg', 'Rajshahi, Bangladesh', 'Nakshi Kantha', 18),
('Karim Hossain', 'Karim weaves sustainable jute baskets, blending tradition with modern design.', 'assets/images/artisan3.jpg', 'Tangail, Bangladesh', 'Jute Weaving', 15),
('Shahin Ahmed', 'Shahin\'s Dhokra metal casting preserves an ancient art form.', 'assets/images/artisan4.jpg', 'Midnapore, West Bengal', 'Dhokra Metal Casting', 20);

-- Insert sample products
INSERT INTO products (name, description, short_description, price, original_price, discount_percentage, image, category_id, artisan_id, stock_quantity) VALUES
('Traditional Terracotta Vase', 'A beautiful handcrafted terracotta vase perfect for home decoration. Made using traditional techniques passed down through generations.', 'Beautiful handcrafted terracotta vase for home decoration', 1500.00, 2000.00, 25, 'assets/images/handicraft1.jpg', 1, 1, 15),
('Nakshi Kantha Embroidered Quilt', 'Exquisite hand-embroidered quilt telling stories of Bengali rural life through intricate stitching and colorful threads.', 'Hand-embroidered quilt with traditional Bengali motifs', 3500.00, 4000.00, 12, 'assets/images/handicraft2.jpg', 2, 2, 8),
('Eco-Friendly Jute Shopping Bag', 'Sustainable and durable jute shopping bag perfect for everyday use. Handwoven with natural jute fibers.', 'Sustainable handwoven jute shopping bag', 450.00, 500.00, 10, 'assets/images/handicraft3.jpg', 3, 3, 30),
('Dhokra Metal Horse Figurine', 'Traditional Dhokra metal casting technique used to create this beautiful horse figurine with intricate details.', 'Traditional Dhokra metal horse figurine', 2200.00, 2500.00, 12, 'assets/images/handicraft4.jpg', 4, 4, 12),
('Hand-painted Clay Pot', 'Decorative clay pot with traditional Bengali folk art paintings. Perfect for plants or as decorative piece.', 'Decorative clay pot with folk art paintings', 800.00, 1000.00, 20, 'assets/images/handicraft5.jpg', 1, 1, 20),
('Silk Saree with Embroidery', 'Pure silk saree with delicate hand embroidery work. A perfect blend of tradition and elegance.', 'Pure silk saree with hand embroidery', 8500.00, 10000.00, 15, 'assets/images/handicraft6.jpg', 2, 2, 5),
('Bamboo Handicraft Basket', 'Intricately woven bamboo basket perfect for storage and decoration. Made using traditional weaving techniques.', 'Handwoven bamboo basket for storage', 650.00, 750.00, 13, 'assets/images/handicraft7.jpg', 6, 3, 18),
('Copper Water Bottle', 'Traditional copper water bottle with health benefits. Handcrafted with beautiful engravings.', 'Handcrafted copper water bottle with engravings', 1200.00, 1400.00, 14, 'assets/images/handicraft8.jpg', 4, 4, 25),
('Batik Print Cotton Shirt', 'Traditional batik print cotton shirt with natural dyes. Comfortable and stylish for casual wear.', 'Batik print cotton shirt with natural dyes', 1800.00, 2200.00, 18, 'assets/images/handicraft9.jpg', 5, 2, 22),
('Wooden Jewelry Box', 'Handcrafted wooden jewelry box with intricate carvings. Perfect for storing precious items.', 'Handcrafted wooden jewelry box with carvings', 2800.00, 3200.00, 12, 'assets/images/handicraft10.jpg', 6, 1, 10),
('Clay Tea Cup Set', 'Traditional clay tea cup set for 4 people. Enhances the taste of tea with natural clay flavor.', 'Traditional clay tea cup set for 4 people', 950.00, 1100.00, 14, 'assets/images/handicraft11.jpg', 1, 1, 16),
('Embroidered Table Runner', 'Beautiful table runner with traditional embroidery work. Adds elegance to your dining table.', 'Embroidered table runner with traditional motifs', 1350.00, 1500.00, 10, 'assets/images/handicraft12.jpg', 2, 2, 14);

-- Insert a sample user for testing
-- Order payments table for storing payment details
CREATE TABLE order_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    payment_details TEXT,
    transaction_id VARCHAR(100),
    card_type VARCHAR(50),
    card_last_four VARCHAR(4),
    provider VARCHAR(50),
    status ENUM('pending', 'processing', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    amount DECIMAL(10,2),
    payment_date TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_order_id (order_id),
    INDEX idx_status (status),
    INDEX idx_transaction_id (transaction_id)
);

INSERT INTO users (first_name, last_name, email, password, phone, address, city, postal_code) VALUES
('John', 'Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8801234567890', '123 Main Street', 'Dhaka', '1000');



