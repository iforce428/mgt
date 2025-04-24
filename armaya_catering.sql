-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 23, 2025 at 09:56 PM
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
-- Database: `armaya_catering`
--

-- --------------------------------------------------------

--
-- Table structure for table `financial_records`
--

CREATE TABLE `financial_records` (
  `record_id` int(11) NOT NULL,
  `record_date` date NOT NULL,
  `description` varchar(255) NOT NULL,
  `type` enum('Income','Expense') NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `related_order_id` int(11) DEFAULT NULL,
  `recorded_by_staff_id` int(11) DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `item_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `price_per_pax` decimal(10,2) NOT NULL,
  `min_pax` int(11) DEFAULT 1,
  `max_pax` int(11) DEFAULT 1000,
  `serving_methods` varchar(100) DEFAULT NULL,
  `event_types` varchar(255) DEFAULT NULL,
  `meal_tags` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `category` varchar(50) NOT NULL,
  `subcategory` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`item_id`, `name`, `description`, `image_url`, `price_per_pax`, `min_pax`, `max_pax`, `serving_methods`, `event_types`, `meal_tags`, `is_available`, `created_at`, `updated_at`, `category`, `subcategory`) VALUES
(58, 'Nasi Lemak', 'nasi berlemak', NULL, 3.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:59:55', 'Nasi', 'Tradisional'),
(59, 'Nasi Goreng', NULL, NULL, 3.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Nasi', 'Goreng'),
(60, 'Nasi Putih', NULL, NULL, 1.50, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Nasi', 'Tradisional'),
(61, 'Nasi Ayam Hainan', NULL, NULL, 5.00, 1, 500, 'Buffet,Packed,Social', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Nasi', 'Ayam'),
(62, 'Nasi Ayam Geprek', NULL, NULL, 7.00, 1, 300, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Nasi', 'Ayam'),
(63, 'Nasi Ayam Gepuk', NULL, NULL, 7.00, 1, 300, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Nasi', 'Ayam'),
(64, 'Nasi Ayam Penyet', NULL, NULL, 7.00, 1, 300, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Nasi', 'Ayam'),
(65, 'Nasi Padang', NULL, NULL, 7.00, 1, 300, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Nasi', 'Tradisional'),
(66, 'Kuey Teow Goreng', NULL, NULL, 3.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Mi/Bihun/Kuey Teow', 'Kuey Teow'),
(67, 'Mi Goreng', NULL, NULL, 3.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Mi/Bihun/Kuey Teow', 'Mi'),
(68, 'Maggi Goreng', NULL, NULL, 3.00, 1, 1000, 'Buffet,Packed,Social', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Mi/Bihun/Kuey Teow', 'Mi'),
(69, 'Bihun Goreng', NULL, NULL, 3.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Mi/Bihun/Kuey Teow', 'Bihun'),
(70, 'Sambal Goreng', NULL, NULL, 3.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Lauk Pauk', 'Seafood'),
(71, 'Sambal Telur', NULL, NULL, 3.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Lauk Pauk', 'Telur'),
(72, 'Telur Goreng', NULL, NULL, 1.50, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Lauk Pauk', 'Telur'),
(73, 'Sambal Sardin', NULL, NULL, 3.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Lauk Pauk', 'Ikan'),
(74, 'Sambal Ayam', NULL, NULL, 5.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Lauk Pauk', 'Ayam'),
(75, 'Kari Ayam', NULL, NULL, 5.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Lauk Pauk', 'Ayam'),
(76, 'Ayam Masak Lemak', NULL, NULL, 5.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Lauk Pauk', 'Ayam'),
(77, 'Ayam Goreng', NULL, NULL, 4.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Lauk Pauk', 'Ayam'),
(78, 'Ayam Buttermilk', NULL, NULL, 5.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Lauk Pauk', 'Ayam'),
(79, 'Sayur Campur', NULL, NULL, 3.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Sayur', 'Campur'),
(80, 'Kangkung Goreng Belacan', NULL, NULL, 3.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Sayur', 'Goreng'),
(81, 'Terung Goreng Berlada', NULL, NULL, 3.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Sayur', 'Goreng'),
(82, 'Air Teh O', NULL, NULL, 1.50, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Air', 'Teh'),
(83, 'Air Sirap', NULL, NULL, 1.50, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Air', 'Sirap'),
(84, 'Air Teh Tarik', NULL, NULL, 1.50, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Air', 'Teh'),
(85, 'Nasi Lemak + Sambal Kerang', NULL, NULL, 5.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Package', 'Nasi Lemak'),
(86, 'Nasi Lemak + Sambal Telur', NULL, NULL, 5.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Package', 'Nasi Lemak'),
(87, 'Nasi Lemak + Sambal Sardin', NULL, NULL, 5.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Package', 'Nasi Lemak'),
(88, 'Nasi Lemak + Sambal Ayam', NULL, NULL, 6.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Package', 'Nasi Lemak'),
(89, 'Nasi Lemak + Sambal Kerang + Air Teh O', NULL, NULL, 6.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Package', 'Nasi Lemak Set'),
(90, 'Nasi Lemak + Sambal Telur + Air Teh O', NULL, NULL, 6.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Package', 'Nasi Lemak Set'),
(91, 'Nasi Lemak + Sambal Sardin + Air Teh O', NULL, NULL, 6.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Package', 'Nasi Lemak Set'),
(92, 'Nasi Lemak + Sambal Ayam + Air Teh O', NULL, NULL, 7.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Package', 'Nasi Lemak Set'),
(93, 'Nasi Lemak + Sambal Kerang + Air Sirap', NULL, NULL, 6.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Package', 'Nasi Lemak Set'),
(94, 'Nasi Lemak + Sambal Telur + Air Sirap', NULL, NULL, 6.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Package', 'Nasi Lemak Set'),
(95, 'Nasi Lemak + Sambal Sardin + Air Sirap', NULL, NULL, 6.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Package', 'Nasi Lemak Set'),
(96, 'Nasi Lemak + Sambal Ayam + Air Sirap', NULL, NULL, 7.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Package', 'Nasi Lemak Set'),
(97, 'Nasi Goreng + Telur Goreng', NULL, NULL, 4.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Package', 'Goreng Set'),
(98, 'Mi Goreng + Telur Goreng', NULL, NULL, 4.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Package', 'Goreng Set'),
(99, 'Kuey Teow Goreng + Telur Goreng', NULL, NULL, 4.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Package', 'Goreng Set'),
(100, 'Maggi Goreng + Telur Goreng', NULL, NULL, 4.00, 1, 1000, 'Buffet,Packed,Social', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Package', 'Goreng Set'),
(101, 'Bihun Goreng + Telur Goreng', NULL, NULL, 4.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Package', 'Goreng Set'),
(102, 'Nasi Putih + Sambal Ayam + Sayur Campur', NULL, NULL, 7.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Package', 'Nasi Putih Set'),
(103, 'Nasi Putih + Kari Ayam + Sayur Campur', NULL, NULL, 7.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Package', 'Nasi Putih Set'),
(104, 'Nasi Putih + Ayam Masak Lemak + Sayur Campur', NULL, NULL, 7.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Package', 'Nasi Putih Set'),
(105, 'Nasi Putih + Ayam Goreng + Sayur Campur', NULL, NULL, 7.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Package', 'Nasi Putih Set'),
(106, 'Nasi Putih + Ayam Buttermilk', NULL, NULL, 6.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', NULL, 1, '2025-04-23 17:55:52', '2025-04-23 17:55:52', 'Package', 'Nasi Putih Set');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `delivery_date` date NOT NULL,
  `delivery_time` time NOT NULL,
  `order_date` datetime NOT NULL,
  `total_pax` int(11) NOT NULL,
  `delivery_option` enum('Ambil Sendiri','Penghantaran') NOT NULL,
  `delivery_location` varchar(255) DEFAULT NULL,
  `serving_method` enum('Hidang (Buffet)','Bungkus') NOT NULL,
  `event_type` varchar(50) DEFAULT NULL,
  `budget_per_pax` decimal(10,2) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('Pending','Confirmed','Preparing','Ready','Delivered','Completed','Cancelled') DEFAULT 'Pending',
  `placed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `staff_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `customer_id`, `delivery_date`, `delivery_time`, `order_date`, `total_pax`, `delivery_option`, `delivery_location`, `serving_method`, `event_type`, `budget_per_pax`, `total_amount`, `status`, `placed_at`, `updated_at`, `staff_notes`) VALUES
(4, 4, '2025-04-25', '15:12:00', '0000-00-00 00:00:00', 10, '', 'asdf', '', 'Corporate', 0.00, 25.00, 'Pending', '2025-04-23 19:14:47', '2025-04-23 19:14:47', 'asdfd'),
(5, 4, '2025-04-26', '15:15:00', '0000-00-00 00:00:00', 10, '', 'asdf', '', 'Corporate', 0.00, 25.00, 'Pending', '2025-04-23 19:15:33', '2025-04-23 19:15:33', 'asdf'),
(6, 4, '2025-04-25', '15:18:00', '0000-00-00 00:00:00', 10, '', 'asdf', '', 'Corporate', 0.00, 25.00, 'Pending', '2025-04-23 19:17:32', '2025-04-23 19:17:32', 'adsfadf'),
(7, 4, '2025-04-25', '15:18:00', '0000-00-00 00:00:00', 10, '', 'adsf', '', 'Corporate', 0.00, 25.00, 'Pending', '2025-04-23 19:18:48', '2025-04-23 19:18:48', 'adfadsf'),
(8, 4, '2025-04-24', '15:23:00', '0000-00-00 00:00:00', 10, '', 'adf', '', 'Corporate', 0.00, 25.00, 'Pending', '2025-04-23 19:20:53', '2025-04-23 19:20:53', 'asdf');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_at_order` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `item_id`, `quantity`, `price_at_order`) VALUES
(1, 4, 77, 4, 4.00),
(2, 4, 82, 2, 1.50),
(3, 4, 83, 4, 1.50),
(4, 5, 77, 4, 4.00),
(5, 5, 82, 2, 1.50),
(6, 5, 83, 4, 1.50),
(7, 6, 77, 4, 4.00),
(8, 6, 82, 2, 1.50),
(9, 6, 83, 4, 1.50),
(10, 7, 77, 4, 4.00),
(11, 7, 82, 2, 1.50),
(12, 7, 83, 4, 1.50),
(13, 8, 77, 4, 4.00),
(14, 8, 82, 2, 1.50),
(15, 8, 83, 4, 1.50);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `role` enum('Admin','Staff','Customer') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `full_name`, `phone_number`, `role`, `created_at`) VALUES
(4, 'webtest', '$2y$10$sDIANUhf.ct.lLLcjYHe2.8nL7OomQ9skP2agk1A36fvDn7T01Q6q', 'web bin test', '0193254004', 'Customer', '2025-04-23 18:23:01');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `financial_records`
--
ALTER TABLE `financial_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `related_order_id` (`related_order_id`),
  ADD KEY `recorded_by_staff_id` (`recorded_by_staff_id`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`item_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `financial_records`
--
ALTER TABLE `financial_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `financial_records`
--
ALTER TABLE `financial_records`
  ADD CONSTRAINT `financial_records_ibfk_1` FOREIGN KEY (`related_order_id`) REFERENCES `orders` (`order_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `financial_records_ibfk_2` FOREIGN KEY (`recorded_by_staff_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `menu_items` (`item_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
