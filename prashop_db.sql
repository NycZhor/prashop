-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 08, 2026 at 06:16 AM
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
-- Database: `prashop_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `status` enum('active','empty') NOT NULL DEFAULT 'active',
  `price` decimal(15,2) NOT NULL,
  `discount` int(11) DEFAULT 0,
  `stock` int(11) NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT 'nb530.png',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_name`, `category`, `status`, `price`, `discount`, `stock`, `image`, `created_at`) VALUES
(10, 'New Balance 530', 'Casual', 'active', 100000.00, 0, 20, 'uploads/product_699ed1000fa9b_1772015872.png', '2026-02-25 03:44:46'),
(15, 'New Balance 574', 'Casual', 'active', 150000.00, 0, 16, 'uploads/product_699ed11e968f0_1772015902.png', '2026-02-25 10:38:22'),
(16, 'Jersey vintage', 'Casual', 'active', 350000.00, 0, 29, 'uploads/product_69d5c9578c594_1775618391.png', '2026-04-08 03:19:04'),
(17, 'Kaos Boxy', 'Casual', 'active', 250000.00, 0, 50, 'uploads/product_69d5c97ea74b6_1775618430.png', '2026-04-08 03:20:30'),
(18, 'Raw Denim -Driftcolny', 'Casual', 'active', 500000.00, 25, 50, 'uploads/product_69d5c9a9e6a29_1775618473.png', '2026-04-08 03:21:13'),
(19, 'Salomon X Force', 'Sports', 'active', 1200000.00, 10, 30, 'uploads/product_69d5c9c92ee04_1775618505.png', '2026-04-08 03:21:45'),
(20, 'Knitwear Vobia Clasic France', 'Fashion', 'active', 258000.00, 15, 25, 'uploads/product_69d5c9fd0203a_1775618557.png', '2026-04-08 03:22:37'),
(21, 'Raket  Felet Nano Fatex', 'Sports', 'active', 150000.00, 5, 15, 'uploads/product_69d5ca19efd88_1775618585.png', '2026-04-08 03:23:05'),
(22, 'Antarestar Series Pangrango', 'Sports', 'active', 580000.00, 25, 25, 'uploads/product_69d5ca4be56d8_1775618635.png', '2026-04-08 03:23:55'),
(23, 'Eiger Equator 45L', 'Sports', 'active', 480000.00, 0, 25, 'uploads/product_69d5cac1a4ebe_1775618753.png', '2026-04-08 03:25:53'),
(24, 'Eiger Diaro 19L', 'Sports', 'active', 350000.00, 10, 23, 'uploads/product_69d5caec03e68_1775618796.png', '2026-04-08 03:26:36'),
(25, 'Sleeping Bag Antarestar Series Merbabu', 'Sports', 'active', 240000.00, 5, 19, 'uploads/product_69d5cb15d14cf_1775618837.png', '2026-04-08 03:27:17'),
(26, 'Ortuseight HYPERBLAST Evo', 'Sports', 'active', 234000.00, 0, 50, 'uploads/product_69d5cb3e2dab9_1775618878.png', '2026-04-08 03:27:58'),
(27, 'New Balance Fuelcell', 'Sports', 'active', 450000.00, 0, 37, 'uploads/product_69d5cb9485f38_1775618964.png', '2026-04-08 03:29:24'),
(28, 'Baggy Jeans', 'Casual', 'active', 145000.00, 0, 34, 'uploads/product_69d5cbb18d028_1775618993.png', '2026-04-08 03:29:53'),
(29, 'Tenda Enigma', 'Sports', 'active', 1300000.00, 15, 12, 'uploads/product_69d5cbeb3f8e3_1775619051.png', '2026-04-08 03:30:51'),
(30, 'The Nort Face Summit Superior', 'Sports', 'active', 348000.00, 15, 25, 'uploads/product_69d5cc240df18_1775619108.png', '2026-04-08 03:31:48'),
(31, 'Topi MLB Indians Claveland', 'Casual', 'active', 140000.00, 0, 32, 'uploads/product_69d5cc48d6dac_1775619144.png', '2026-04-08 03:32:24'),
(32, 'Gorp-Core Antarestar Manusela', 'Sports', 'active', 150000.00, 0, 20, 'uploads/product_69d5cc94aa534_1775619220.png', '2026-04-08 03:33:40');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `payment_method` enum('cod','transfer') DEFAULT 'cod',
  `address` text DEFAULT NULL,
  `proof_path` varchar(255) DEFAULT NULL,
  `total_amount` int(11) DEFAULT NULL,
  `status` enum('pending','processing','shipped','completed','cancelled') DEFAULT 'pending',
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transaction_details`
--

CREATE TABLE `transaction_details` (
  `id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(100) DEFAULT NULL,
  `price` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaction_details`
--

INSERT INTO `transaction_details` (`id`, `transaction_id`, `product_id`, `product_name`, `price`, `quantity`) VALUES
(1, 23, 25, 'Sleeping Bag Antarestar Series Merbabu', 228000, 1),
(2, 24, 25, 'Sleeping Bag Antarestar Series Merbabu', 228000, 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','petugas','user') DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `address`, `phone`, `role`) VALUES
(1, 'admin', 'admin123', 'admin@prashop.com', NULL, NULL, 'admin'),
(3, 'dahlan', '123', 'cnmantap91@gmail.com', 'cihuymantap', '08776278881', 'user'),
(10, 'ucup', '1234', 'yyagaming@gmail.com', NULL, NULL, 'petugas');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `transaction_details`
--
ALTER TABLE `transaction_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaction_id` (`transaction_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `transaction_details`
--
ALTER TABLE `transaction_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `transaction_details`
--
ALTER TABLE `transaction_details`
  ADD CONSTRAINT `transaction_details_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
