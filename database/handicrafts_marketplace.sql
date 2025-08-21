-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 21, 2025 at 02:42 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `handicrafts_marketplace`
--

-- --------------------------------------------------------

--
-- Table structure for table `artisans`
--

CREATE TABLE `artisans` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `bio` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `speciality` varchar(100) DEFAULT NULL,
  `experience_years` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `artisans`
--

INSERT INTO `artisans` (`id`, `name`, `bio`, `image`, `location`, `speciality`, `experience_years`, `created_at`) VALUES
(1, 'Rahim Ali', 'Rahim, a third-generation potter, creates exquisite terracotta pieces.', 'assets/images/artisan1.jpg', 'Bishnupur, West Bengal', 'Terracotta Pottery', 25, '2025-08-21 11:47:33'),
(2, 'Fatima Begum', 'Fatima\'s intricate Nakshi Kantha embroidery tells stories of rural life.', 'assets/images/artisan2.jpg', 'Rajshahi, Bangladesh', 'Nakshi Kantha', 18, '2025-08-21 11:47:33'),
(3, 'Karim Hossain', 'Karim weaves sustainable jute baskets, blending tradition with modern design.', 'assets/images/artisan3.jpg', 'Tangail, Bangladesh', 'Jute Weaving', 15, '2025-08-21 11:47:33'),
(4, 'Shahin Ahmed', 'Shahin\'s Dhokra metal casting preserves an ancient art form.', 'assets/images/artisan4.jpg', 'Midnapore, West Bengal', 'Dhokra Metal Casting', 20, '2025-08-21 11:47:33');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Pottery', 'Traditional terracotta and ceramic items', '2025-08-21 11:47:33'),
(2, 'Embroidery', 'Hand-embroidered textiles and clothing', '2025-08-21 11:47:33'),
(3, 'Jute', 'Eco-friendly jute products and crafts', '2025-08-21 11:47:33'),
(4, 'Metal Craft', 'Dhokra and other metal art pieces', '2025-08-21 11:47:33'),
(5, 'Textiles', 'Traditional Bengali textiles and fabrics', '2025-08-21 11:47:33'),
(6, 'Wood Craft', 'Carved wooden items and furniture', '2025-08-21 11:47:33');

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `favorites`
--

INSERT INTO `favorites` (`id`, `user_id`, `product_id`, `created_at`) VALUES
(2, 3, 3, '2025-08-21 11:53:54'),
(3, 5, 1, '2025-08-21 12:05:02'),
(4, 5, 3, '2025-08-21 12:05:04'),
(5, 6, 1, '2025-08-21 12:10:07'),
(6, 6, 2, '2025-08-21 12:10:08'),
(7, 6, 3, '2025-08-21 12:10:10');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `order_number` varchar(50) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `shipping_cost` decimal(10,2) DEFAULT 120.00,
  `tax_amount` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `shipping_address` text DEFAULT NULL,
  `billing_address` text DEFAULT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `shipping_method` varchar(50) DEFAULT 'standard',
  `order_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_number`, `subtotal`, `shipping_cost`, `tax_amount`, `total_amount`, `status`, `payment_method`, `payment_status`, `shipping_address`, `billing_address`, `customer_email`, `customer_phone`, `shipping_method`, `order_notes`, `created_at`, `updated_at`) VALUES
(1, 2, 'ORD202508217750', 1500.00, 120.00, 75.00, 1695.00, 'pending', 'cash', 'pending', 'null, null, null null', 'null, null, null null', 'fairoozprapti29@gmail.com', '01876554677', 'standard', '', '2025-08-21 11:50:29', '2025-08-21 11:50:29'),
(2, 3, 'ORD202508212088', 10450.00, 120.00, 523.00, 11093.00, 'pending', 'cash', 'pending', 'null, null, null null', 'null, null, null null', 'fairoozprapti290@gmail.com', '01876554677', 'standard', '', '2025-08-21 11:55:11', '2025-08-21 11:55:11'),
(3, 5, 'ORD202508213856', 7400.00, 120.00, 370.00, 7890.00, 'pending', 'cash', 'pending', 'null, null, null null', 'null, null, null null', 'fairoozprapti29011@gmail.com', '01876554677', 'standard', '', '2025-08-21 12:06:07', '2025-08-21 12:06:07'),
(4, 6, 'ORD202508213373', 10450.00, 120.00, 523.00, 11093.00, 'pending', 'cash', 'pending', 'null, null, null null', 'null, null, null null', 'fairoozprapti290112@gmail.com', '01876554677', 'standard', '', '2025-08-21 12:11:10', '2025-08-21 12:11:10');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`, `total`) VALUES
(1, 1, 1, 1, 1500.00, 1500.00),
(2, 2, 2, 2, 1500.00, 3000.00),
(3, 2, 3, 2, 3500.00, 7000.00),
(4, 2, 4, 1, 450.00, 450.00),
(5, 3, 5, 2, 1500.00, 3000.00),
(6, 3, 6, 2, 450.00, 900.00),
(7, 3, 7, 1, 3500.00, 3500.00),
(10, 4, 10, 1, 450.00, 450.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_payments`
--

CREATE TABLE `order_payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_details` text DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `card_type` varchar(50) DEFAULT NULL,
  `card_last_four` varchar(4) DEFAULT NULL,
  `provider` varchar(50) DEFAULT NULL,
  `status` enum('pending','processing','completed','failed','refunded') DEFAULT 'pending',
  `amount` decimal(10,2) DEFAULT NULL,
  `payment_date` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_payments`
--

INSERT INTO `order_payments` (`id`, `order_id`, `payment_method`, `payment_details`, `transaction_id`, `card_type`, `card_last_four`, `provider`, `status`, `amount`, `payment_date`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'cash', '{\"payment_timestamp\":\"2025-08-21T11:50:27.260Z\"}', 'COD_1755777029', NULL, NULL, NULL, 'pending', 1695.00, '2025-08-21 07:50:29', 'Cash on delivery payment', '2025-08-21 11:50:29', '2025-08-21 11:50:29'),
(2, 2, 'cash', '{\"payment_timestamp\":\"2025-08-21T11:55:08.862Z\"}', 'COD_1755777311', NULL, NULL, NULL, 'pending', 11093.00, '2025-08-21 07:55:11', 'Cash on delivery payment', '2025-08-21 11:55:11', '2025-08-21 11:55:11'),
(3, 3, 'cash', '{\"payment_timestamp\":\"2025-08-21T12:06:04.705Z\"}', 'COD_1755777967', NULL, NULL, NULL, 'pending', 7890.00, '2025-08-21 08:06:07', 'Cash on delivery payment', '2025-08-21 12:06:07', '2025-08-21 12:06:07'),
(4, 4, 'cash', '{\"payment_timestamp\":\"2025-08-21T12:11:07.982Z\"}', 'COD_1755778270', NULL, NULL, NULL, 'pending', 11093.00, '2025-08-21 08:11:10', 'Cash on delivery payment', '2025-08-21 12:11:10', '2025-08-21 12:11:10');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `short_description` varchar(500) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `original_price` decimal(10,2) DEFAULT NULL,
  `discount_percentage` int(11) DEFAULT 0,
  `image` varchar(255) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `artisan_id` int(11) DEFAULT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `status` enum('active','inactive','out_of_stock') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `short_description`, `price`, `original_price`, `discount_percentage`, `image`, `category_id`, `artisan_id`, `stock_quantity`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Nakshi Kantha Embroidered Quilt', 'Exquisite hand-embroidered quilt telling stories of Bengali rural life through intricate stitching and colorful threads.', 'Hand-embroidered quilt with traditional Bengali motifs', 1500.00, 2000.00, 25, 'assets/images/handicraft1.jpg', 2, 2, 14, 'active', '2025-08-21 11:47:33', '2025-08-21 12:37:00'),
(2, 'Traditional Terracotta Vase', 'A beautiful handcrafted terracotta vase perfect for home decoration. Made using traditional techniques passed down through generations.\r\n\r\n', 'Beautiful handcrafted terracotta vase for home decoration\r\n\r\n', 3500.00, 4000.00, 12, 'assets/images/tera.jpg', 1, 1, 6, 'active', '2025-08-21 11:47:33', '2025-08-21 12:37:57'),
(3, 'Eco-Friendly Jute Shopping Bag', 'Sustainable and durable jute shopping bag perfect for everyday use. Handwoven with natural jute fibers.', 'Sustainable handwoven jute shopping bag', 450.00, 500.00, 10, 'assets/images/handicraft9.jpg', 3, 3, 28, 'active', '2025-08-21 11:47:33', '2025-08-21 12:38:26'),
(4, 'Dhokra Metal Horse Figurine', 'Traditional Dhokra metal casting technique used to create this beautiful horse figurine with intricate details.', 'Traditional Dhokra metal horse figurine', 2200.00, 2500.00, 12, 'assets/images/Bastar-Dhokra-Swan-Art-GiTAGGED-6.jpg', 4, 4, 11, 'active', '2025-08-21 11:47:33', '2025-08-21 12:38:53'),
(5, 'Hand-painted Clay Pot', 'Decorative clay pot with traditional Bengali folk art paintings. Perfect for plants or as decorative piece.', 'Decorative clay pot with folk art paintings', 800.00, 1000.00, 20, 'assets/images/artisian_product1.jpg', 1, 1, 18, 'active', '2025-08-21 11:47:33', '2025-08-21 12:39:47'),
(6, 'Silk Saree with Embroidery', 'Pure silk saree with delicate hand embroidery work. A perfect blend of tradition and elegance.', 'Pure silk saree with hand embroidery', 8500.00, 10000.00, 15, 'assets/images/cart_item1.jpg', 2, 2, 3, 'active', '2025-08-21 11:47:33', '2025-08-21 12:40:16'),
(7, 'Bamboo Handicraft Basket', 'Intricately woven bamboo basket perfect for storage and decoration. Made using traditional weaving techniques.', 'Handwoven bamboo basket for storage', 650.00, 750.00, 13, 'assets/images/cart_item3.jpg', 6, 3, 17, 'active', '2025-08-21 11:47:33', '2025-08-21 12:40:56'),
(10, 'Wooden Jewelry Box', 'Handcrafted wooden jewelry box with intricate carvings. Perfect for storing precious items.', 'Handcrafted wooden jewelry box with carvings', 2800.00, 3200.00, 12, 'assets/images/Screenshot 2025-08-21 134749.jpg', 6, 1, 9, 'active', '2025-08-21 11:47:33', '2025-08-21 12:41:59');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `google_id` varchar(100) DEFAULT NULL,
  `oauth_provider` enum('local','google') DEFAULT 'local',
  `is_verified` tinyint(1) DEFAULT 0,
  `last_login` timestamp NULL DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password`, `phone`, `address`, `city`, `postal_code`, `profile_image`, `google_id`, `oauth_provider`, `is_verified`, `last_login`, `status`, `created_at`, `updated_at`) VALUES
(1, 'John', 'Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8801234567890', '123 Main Street', 'Dhaka', '1000', NULL, NULL, 'local', 0, NULL, 'active', '2025-08-21 11:47:33', '2025-08-21 11:47:33'),
(2, 'Fairooz', 'maliha', 'fairoozprapti29@gmail.com', '$2y$10$nzJWRx/cXTDRO1oakbxawettt6OMH7f6B03DzZkPYta3ixdZKQD2q', '01876554677', NULL, NULL, NULL, NULL, NULL, 'local', 0, '2025-08-21 11:49:35', 'active', '2025-08-21 11:49:19', '2025-08-21 11:49:35'),
(3, 'Fairooz', 'maliha', 'fairoozprapti290@gmail.com', '$2y$10$bz5ibe9dgzCaN8t2WLApN.7cjQhetwR5f37eyUmur50ybo7JL3/R2', '01876554677', NULL, NULL, NULL, 'uploads/avatar_3_1755777419.png', NULL, 'local', 0, '2025-08-21 11:52:53', 'active', '2025-08-21 11:52:40', '2025-08-21 11:56:59'),
(4, 'Fairooz', 'maliha', 'fairoozprapti2901@gmail.com', '$2y$10$Vh/s7OfHcf6ZcUXCyCUJiusj3mr5Mm6xR0rCu99OfOqu77SiQ7cP.', '01876554677', NULL, NULL, NULL, 'uploads/avatar_4_1755777621.png', NULL, 'local', 0, '2025-08-21 11:58:51', 'active', '2025-08-21 11:58:37', '2025-08-21 12:00:21'),
(5, 'Fairooz', 'maliha', 'fairoozprapti29011@gmail.com', '$2y$10$MmOtvWNtedc3QvXfPkPRHOSnK5x8e7P2AgZ2LODlrz.ZnGDZvC9Li', '01876554677', NULL, NULL, NULL, NULL, NULL, 'local', 0, '2025-08-21 12:03:56', 'active', '2025-08-21 12:03:41', '2025-08-21 12:03:56'),
(6, 'Fairooz', 'maliha', 'fairoozprapti290112@gmail.com', '$2y$10$deijsZh9BO7sq4FhUM3eH.5gHVRU.42TcYo.XPIZDmJ9sNLy/.e6.', '01876554677', NULL, NULL, NULL, 'uploads/avatar_6_1755778418.png', NULL, 'local', 0, '2025-08-21 12:09:19', 'active', '2025-08-21 12:08:56', '2025-08-21 12:13:38'),
(7, 'Fairooz', 'Maliha', 'prapti123@gmail.com', '$2y$10$.eQ2wbWlLis1GiC1bmDZ4.0c7C50ZUOTrGiDQ6M607H123Mmix7d2', '01716591180', NULL, NULL, NULL, NULL, NULL, 'local', 0, '2025-08-21 12:34:21', 'active', '2025-08-21 12:34:13', '2025-08-21 12:34:21');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `artisans`
--
ALTER TABLE `artisans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_cart_item` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_favorite` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `order_payments`
--
ALTER TABLE `order_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_transaction_id` (`transaction_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `artisan_id` (`artisan_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `google_id` (`google_id`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `idx_session_token` (`session_token`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `artisans`
--
ALTER TABLE `artisans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `order_payments`
--
ALTER TABLE `order_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_payments`
--
ALTER TABLE `order_payments`
  ADD CONSTRAINT `order_payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`artisan_id`) REFERENCES `artisans` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
