-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 18, 2025 at 08:08 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pro_marketplace`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `quantity`, `created_at`) VALUES
(7, 2, 8, 1, '2025-12-18 18:46:39'),
(8, 2, 6, 1, '2025-12-18 18:46:42'),
(9, 2, 5, 1, '2025-12-18 18:55:56');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `parent_id` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `parent_id`, `created_at`) VALUES
(1, 'Male', 0, '2025-12-18 12:44:48'),
(2, 'Female', 0, '2025-12-18 12:44:48'),
(4, 'Pant', 1, '2025-12-18 12:44:48'),
(5, 'Trouser', 1, '2025-12-18 12:44:48'),
(6, 'Electronics', 0, '2025-12-18 12:46:37'),
(7, 'charger', 6, '2025-12-18 12:46:56'),
(9, 'saree', 2, '2025-12-18 16:27:56'),
(10, 'saree', 2, '2025-12-18 16:28:01');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL COMMENT 'This is the Selling Price',
  `mrp` decimal(10,2) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('active','hidden','pending') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `vendor_id`, `category_id`, `name`, `description`, `price`, `mrp`, `stock`, `image`, `status`, `created_at`, `deleted_at`) VALUES
(5, 2, 4, 'stock-watch', 'very goog product', 87.00, 100.00, 10, NULL, 'active', '2025-12-18 13:23:58', NULL),
(6, 2, 5, 'Status page', 'oahdqwxqecerrw  wrt', 123.00, 7823.00, 15, NULL, 'active', '2025-12-18 13:29:57', NULL),
(8, 3, 7, 'Surendar Rajasekar', 'ladja', 2345.00, 234.00, 5, NULL, 'active', '2025-12-18 15:21:15', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_cover` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `is_cover`, `sort_order`) VALUES
(1, 5, '1766064238_Screenshot_(1).png', 1, 0),
(2, 5, '1766064238_Screenshot_(2).png', 0, 1),
(3, 5, '1766064238_Screenshot_2025-10-07_172513.png', 0, 2),
(4, 6, '1766064597_Screenshot_2025-12-04_130741.png', 1, 0),
(5, 6, '1766064597_Screenshot_2025-12-04_130832.png', 0, 1),
(6, 6, '1766064597_Screenshot_2025-12-04_131347.png', 0, 2),
(7, 6, '1766064597_Screenshot_2025-12-04_131512.png', 0, 3),
(8, 6, '1766064597_Screenshot_2025-12-04_131756.png', 0, 4),
(15, 8, 'prod_8_1766071275_0.png', 1, 0),
(16, 8, 'prod_8_1766071275_1.png', 0, 0),
(17, 8, 'prod_8_1766071275_2.png', 0, 0),
(18, 8, 'prod_8_1766071275_3.png', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `site_banners`
--

CREATE TABLE `site_banners` (
  `id` int(11) NOT NULL,
  `banner_image` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_banners`
--

INSERT INTO `site_banners` (`id`, `banner_image`, `sort_order`) VALUES
(8, '1766069042_Screenshot_2025-12-04_131347.png', 0),
(9, '1766069042_Screenshot_2025-12-04_131512.png', 0),
(10, '1766069042_Screenshot_2025-12-04_131756.png', 0);

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL,
  `app_name` varchar(100) DEFAULT 'Pro-Market',
  `app_logo` varchar(255) DEFAULT 'logo.png',
  `primary_color` varchar(10) DEFAULT '#2563eb',
  `smtp_host` varchar(100) DEFAULT NULL,
  `smtp_user` varchar(100) DEFAULT NULL,
  `smtp_pass` varchar(100) DEFAULT NULL,
  `smtp_port` int(11) DEFAULT 587,
  `razorpay_key_id` varchar(100) DEFAULT NULL,
  `razorpay_key_secret` varchar(100) DEFAULT NULL,
  `banner_title` varchar(255) DEFAULT NULL,
  `banner_subtitle` text DEFAULT NULL,
  `header_color` varchar(10) DEFAULT '#ffffff'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`id`, `app_name`, `app_logo`, `primary_color`, `smtp_host`, `smtp_user`, `smtp_pass`, `smtp_port`, `razorpay_key_id`, `razorpay_key_secret`, `banner_title`, `banner_subtitle`, `header_color`) VALUES
(1, 'Shopdrive', 'logo_1766065790_Screenshot_2025-12-04_131756.png', '#520000', '', '', '1234', 0, 'sfs', 'sdf', NULL, NULL, '#000000');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','vendor','customer') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `password`, `role`, `created_at`) VALUES
(2, 'Super Admin 1', 'sadmin@gmail.com', '$2y$10$B4CAwsKDWA81X97u3rO91O8vFeSION.BXaBF2PiXdyOwZCn/l9nd2', 'super_admin', '2025-12-18 12:37:08'),
(3, 'Admin', 'admin@gmail.com', '$2y$10$qc0JId5osFKg6mijtciAjeTvsH2p88/Jl2XHNhPn0C2YcjrSEmd6u', 'vendor', '2025-12-18 12:51:58');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `product_id`, `created_at`) VALUES
(21, 2, 8, '2025-12-18 18:47:02'),
(29, 2, 6, '2025-12-18 19:01:01');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vendor_id` (`vendor_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `site_banners`
--
ALTER TABLE `site_banners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `site_banners`
--
ALTER TABLE `site_banners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

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
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
