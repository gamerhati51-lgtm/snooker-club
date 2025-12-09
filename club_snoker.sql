-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 08, 2025 at 11:51 AM
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
-- Database: `club_snoker`
--

-- --------------------------------------------------------

--
-- Table structure for table `expanses`
--

CREATE TABLE `expanses` (
  `expanses_id` int(11) NOT NULL,
  `expanses_date` date NOT NULL,
  `category_id` int(11) NOT NULL,
  `details` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expanses`
--

INSERT INTO `expanses` (`expanses_id`, `expanses_date`, `category_id`, `details`, `amount`, `created_at`) VALUES
(1, '2025-11-22', 2, 'mothly electricity billl', 200.00, '2025-11-22 11:38:20'),
(5, '2025-11-24', 5, 'purchase a new bike', 30000.00, '2025-11-24 08:49:17'),
(6, '2025-12-01', 5, 'for latop', 34000.00, '2025-12-01 12:02:36');

-- --------------------------------------------------------

--
-- Table structure for table `expanses_categories`
--

CREATE TABLE `expanses_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expanses_categories`
--

INSERT INTO `expanses_categories` (`category_id`, `category_name`) VALUES
(5, 'Maintenance'),
(6, 'Marketing'),
(3, 'Rent'),
(2, 'Salary'),
(4, 'Supplies (Snacks/Drinks)'),
(1, 'Utilities');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `item_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `sale_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pos_products`
--

CREATE TABLE `pos_products` (
  `product_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `barcode_type` varchar(50) DEFAULT 'Code 128',
  `unit` varchar(50) DEFAULT 'Pieces (Pc(s))',
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `cost_price` decimal(10,2) NOT NULL COMMENT 'Price the club pays for the item',
  `selling_price` decimal(10,2) NOT NULL COMMENT 'Price the customer pays',
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `category` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `brand` varchar(100) DEFAULT NULL,
  `sub_category` varchar(100) DEFAULT NULL,
  `alert_quantity` int(11) DEFAULT 10,
  `is_service_product` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'If true, inventory is not tracked',
  `weight` decimal(10,2) DEFAULT NULL,
  `service_time_minutes` int(11) DEFAULT 0,
  `tax_id` int(11) DEFAULT NULL COMMENT 'Link to tax rate table (conceptual)',
  `selling_price_tax_type` varchar(20) DEFAULT 'Inclusive' COMMENT 'Inclusive or Exclusive',
  `product_type` varchar(20) DEFAULT 'Single' COMMENT 'Single, Variable, or Combo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `sku`, `barcode_type`, `unit`, `name`, `description`, `image_path`, `cost_price`, `selling_price`, `stock_quantity`, `category`, `is_active`, `created_at`, `updated_at`, `brand`, `sub_category`, `alert_quantity`, `is_service_product`, `weight`, `service_time_minutes`, `tax_id`, `selling_price_tax_type`, `product_type`) VALUES
(2, NULL, 'Code 128', 'Pieces (Pc(s))', 'Energy Drink', NULL, NULL, 1.20, 3.50, 26, 'Drinks', 1, '2025-11-28 10:51:59', '2025-12-07 10:46:45', NULL, NULL, 10, 0, NULL, 0, NULL, 'Inclusive', 'Single');

-- --------------------------------------------------------

--
-- Table structure for table `sales_transactions`
--

CREATE TABLE `sales_transactions` (
  `transaction_id` int(11) NOT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL COMMENT 'Staff member who handled the transaction (if tracking staff)',
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales_transactions`
--

INSERT INTO `sales_transactions` (`transaction_id`, `transaction_date`, `user_id`, `total_amount`, `payment_method`) VALUES
(1, '2025-11-28 11:03:28', 1, 7.00, 'Cash'),
(2, '2025-11-28 11:03:37', 1, 2.00, 'Cash');

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `sale_item_id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity_sold` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL COMMENT 'Selling price at time of sale',
  `line_total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `session_items`
--

CREATE TABLE `session_items` (
  `session_item_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_per_unit` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `session_items`
--

INSERT INTO `session_items` (`session_item_id`, `session_id`, `item_name`, `quantity`, `price_per_unit`) VALUES
(1, 38, 'pepsi', 1, 15.00),
(2, 55, 'pepsi', 1, 15.00),
(3, 55, 'pepsi', 1, 15.00),
(4, 55, 'Energy Drink', 1, 3.50),
(5, 55, 'Energy Drink', 1, 3.50),
(6, 55, 'pepsi', 1, 15.00),
(7, 55, 'Energy Drink', 1, 3.50),
(8, 55, 'Energy Drink', 1, 3.50),
(9, 57, 'Energy Drink', 1, 3.50),
(10, 57, 'Energy Drink', 1, 3.50),
(11, 57, 'Energy Drink', 1, 3.50),
(12, 57, 'Energy Drink', 1, 3.50),
(13, 58, 'Energy Drink', 1, 3.50),
(14, 58, 'Energy Drink', 1, 3.50),
(15, 58, 'Energy Drink', 1, 3.50),
(16, 58, 'Energy Drink', 1, 3.50),
(17, 58, 'Energy Drink', 1, 3.50),
(18, 58, 'Energy Drink', 1, 3.50),
(20, 56, 'Energy Drink', 2, 3.50),
(21, 56, 'Energy Drink', 1, 3.50),
(22, 65, 'Energy Drink', 2, 3.50),
(23, 70, 'Energy Drink', 1, 3.50),
(24, 70, 'Energy Drink', 1, 3.50),
(25, 70, 'Energy Drink', 1, 3.50),
(26, 70, 'Energy Drink', 1, 3.50);

-- --------------------------------------------------------

--
-- Table structure for table `snooker_bookings`
--

CREATE TABLE `snooker_bookings` (
  `booking_id` int(11) NOT NULL,
  `table_id` int(11) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `snooker_bookings`
--

INSERT INTO `snooker_bookings` (`booking_id`, `table_id`, `customer_name`, `booking_date`, `start_time`, `end_time`, `status`, `created_at`) VALUES
(1, 8, 'amir', '2025-11-21', '17:48:00', '18:48:00', 'Confirmed', '2025-11-21 10:46:30'),
(2, 8, 'khan', '2025-11-21', '20:00:00', '21:00:00', 'Confirmed', '2025-11-21 14:00:20'),
(3, 9, 'UBAID', '2025-11-25', '16:55:00', '17:55:00', 'Confirmed', '2025-11-24 08:51:38'),
(4, 8, 'khan', '2025-11-24', '14:53:00', '15:53:00', 'Confirmed', '2025-11-24 08:52:31'),
(5, 8, 'ubaid', '2025-11-28', '10:00:00', '12:00:00', 'Confirmed', '2025-11-27 14:23:05'),
(7, 9, 'amir saeed ullah', '2025-12-01', '19:09:00', '20:09:00', 'Confirmed', '2025-12-01 10:09:13'),
(9, 8, 'khan zada', '2025-12-08', '20:00:00', '21:00:00', 'Confirmed', '2025-12-07 12:47:56');

-- --------------------------------------------------------

--
-- Table structure for table `snooker_sessions`
--

CREATE TABLE `snooker_sessions` (
  `session_id` int(11) NOT NULL,
  `table_id` int(11) DEFAULT NULL,
  `id` int(11) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `rate_type` enum('Normal','Century') NOT NULL DEFAULT 'Normal',
  `session_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`session_items`)),
  `session_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('Active','Completed','Canceled') NOT NULL DEFAULT 'Active',
  `total_time_minutes` int(11) DEFAULT 0,
  `final_amount` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `snooker_sessions`
--

INSERT INTO `snooker_sessions` (`session_id`, `table_id`, `id`, `start_time`, `end_time`, `rate_type`, `session_items`, `session_cost`, `status`, `total_time_minutes`, `final_amount`) VALUES
(1, NULL, 8, '2025-11-21 11:02:57', '2025-11-21 11:08:33', 'Normal', NULL, 37.33, 'Completed', 0, 0.00),
(2, NULL, 8, '2025-11-21 11:08:56', '2025-11-21 11:09:00', 'Normal', NULL, 0.44, 'Completed', 0, 0.00),
(3, NULL, 8, '2025-11-21 11:09:14', '2025-11-21 11:13:59', 'Century', NULL, 109.25, 'Completed', 0, 0.00),
(4, NULL, 8, '2025-11-21 11:23:31', '2025-11-21 11:27:46', 'Normal', NULL, 28.33, 'Completed', 0, 0.00),
(5, NULL, 9, '2025-11-21 11:27:28', '2025-11-21 11:42:54', 'Normal', NULL, 128.61, 'Completed', 0, 0.00),
(6, NULL, 8, '2025-11-21 11:34:05', '2025-11-21 11:39:24', 'Normal', NULL, 26.58, 'Completed', 0, 0.00),
(10, 8, 0, '2025-11-22 12:01:46', NULL, 'Normal', '[]', 0.00, '', 0, 0.00),
(11, 9, 0, '2025-11-22 12:02:02', NULL, 'Normal', '[]', 0.00, '', 0, 0.00),
(12, NULL, 0, '2025-11-22 12:02:41', NULL, 'Century', '[]', 0.00, '', 0, 0.00),
(13, NULL, 8, '2025-11-22 12:04:54', '2025-11-22 12:05:30', 'Normal', NULL, 3.00, 'Completed', 0, 0.00),
(14, NULL, 8, '2025-11-22 12:22:54', '2025-11-22 12:23:00', 'Normal', NULL, 0.50, 'Completed', 0, 0.00),
(15, NULL, 8, '2025-11-24 09:50:41', '2025-11-24 09:50:58', 'Century', NULL, 6.52, 'Completed', 0, 0.00),
(16, NULL, 8, '2025-11-24 10:06:46', '2025-11-24 10:06:48', 'Normal', NULL, 0.17, 'Completed', 0, 0.00),
(17, NULL, 8, '2025-11-24 10:31:21', '2025-11-24 10:31:42', 'Normal', NULL, 1.75, 'Completed', 0, 0.00),
(18, NULL, 8, '2025-11-24 10:52:23', '2025-11-24 10:53:48', 'Normal', NULL, 7.08, 'Completed', 0, 0.00),
(19, NULL, 8, '2025-11-24 10:56:26', '2025-11-24 11:00:59', 'Normal', NULL, 22.75, 'Completed', 0, 0.00),
(20, 9, 0, '2025-11-25 10:25:44', NULL, 'Normal', '[]', 0.00, '', 0, 0.00),
(21, NULL, 0, '2025-11-25 10:25:55', NULL, 'Normal', '[]', 0.00, '', 0, 0.00),
(22, 8, 0, '2025-11-25 10:25:57', NULL, 'Normal', '[]', 0.00, '', 0, 0.00),
(23, NULL, 8, '2025-11-27 15:03:43', '2025-11-27 15:27:28', 'Normal', NULL, 158.33, 'Completed', 0, 0.00),
(24, NULL, 10, '2025-11-28 15:18:06', '2025-11-28 15:58:55', 'Normal', NULL, 136.06, 'Completed', 0, 0.00),
(25, NULL, 9, '2025-11-28 15:18:19', '2025-11-28 15:58:40', 'Normal', NULL, 336.25, 'Completed', 0, 0.00),
(26, NULL, 8, '2025-11-28 15:18:23', '2025-11-28 15:58:26', 'Normal', NULL, 267.00, 'Completed', 0, 0.00),
(27, NULL, 8, '2025-11-28 15:59:27', '2025-11-29 13:03:26', 'Normal', NULL, 8426.56, 'Completed', 0, 0.00),
(28, NULL, 8, '2025-12-01 12:51:01', '2025-12-01 12:51:49', 'Normal', NULL, 5.33, 'Completed', 0, 0.00),
(29, NULL, 9, '2025-12-01 12:51:55', '2025-12-01 12:52:06', 'Normal', NULL, 1.53, 'Completed', 0, 0.00),
(30, NULL, 8, '2025-12-01 12:52:14', '2025-12-01 12:53:10', 'Normal', NULL, 6.22, 'Completed', 0, 0.00),
(31, NULL, 8, '2025-12-01 12:53:42', '2025-12-01 12:53:55', 'Normal', NULL, 1.44, 'Completed', 0, 0.00),
(32, NULL, 8, '2025-12-01 12:55:04', '2025-12-01 13:08:12', 'Century', NULL, 26.27, 'Completed', 0, 0.00),
(33, NULL, 8, '2025-12-01 13:09:00', '2025-12-01 13:26:17', 'Normal', NULL, 115.22, 'Completed', 0, 0.00),
(34, NULL, 9, '2025-12-01 13:10:09', '2025-12-02 10:01:54', 'Normal', NULL, 10431.25, 'Completed', 0, 0.00),
(35, NULL, 8, '2025-12-02 10:24:48', '2025-12-03 09:55:25', 'Normal', NULL, 9404.11, 'Completed', 0, 0.00),
(36, NULL, 8, '2025-12-03 10:13:47', '2025-12-03 10:31:17', 'Normal', NULL, 116.67, 'Completed', 0, 0.00),
(37, NULL, 8, '2025-12-03 10:31:23', '2025-12-03 10:31:27', 'Normal', NULL, 0.44, 'Completed', 0, 0.00),
(38, NULL, 8, '2025-12-03 10:31:33', '2025-12-03 14:42:53', 'Century', NULL, 517.67, 'Completed', 0, 0.00),
(39, NULL, 9, '2025-12-03 10:41:26', '2025-12-03 15:09:59', 'Normal', NULL, 2237.92, 'Completed', 0, 0.00),
(40, NULL, 8, '2025-12-03 14:54:38', '2025-12-03 15:01:14', 'Normal', NULL, 44.00, 'Completed', 0, 0.00),
(41, NULL, 8, '2025-12-03 15:11:34', '2025-12-03 15:27:30', 'Normal', NULL, 106.22, 'Completed', 0, 0.00),
(42, NULL, 8, '2025-12-03 15:33:56', '2025-12-03 15:42:35', 'Normal', NULL, 57.67, 'Completed', 0, 0.00),
(43, NULL, 8, '2025-12-03 16:00:52', '2025-12-04 10:52:43', 'Normal', NULL, 7545.67, 'Completed', 0, 0.00),
(44, NULL, 9, '2025-12-04 10:51:27', '2025-12-04 10:51:38', 'Normal', NULL, 1.53, 'Completed', 0, 0.00),
(45, NULL, 8, '2025-12-04 10:54:56', '2025-12-04 10:55:41', 'Normal', NULL, 5.00, 'Completed', 0, 0.00),
(46, NULL, 8, '2025-12-04 10:59:13', '2025-12-04 11:11:25', 'Normal', NULL, 81.33, 'Completed', 0, 0.00),
(47, NULL, 8, '2025-12-04 11:23:06', '2025-12-04 11:23:15', 'Normal', NULL, 1.00, 'Completed', 0, 0.00),
(48, NULL, 8, '2025-12-04 11:23:28', '2025-12-04 12:00:25', 'Normal', NULL, 246.33, 'Completed', 0, 0.00),
(49, NULL, 8, '2025-12-04 12:05:48', '2025-12-04 12:10:16', 'Normal', NULL, 29.78, 'Completed', 0, 0.00),
(50, NULL, 8, '2025-12-04 12:23:56', '2025-12-04 12:23:58', 'Normal', NULL, 0.22, 'Completed', 0, 0.00),
(51, NULL, 8, '2025-12-05 12:26:18', '2025-12-05 12:26:25', 'Normal', NULL, 0.78, 'Completed', 0, 0.00),
(52, NULL, 9, '2025-12-05 12:54:56', '2025-12-05 13:04:50', 'Normal', NULL, 82.50, 'Completed', 0, 0.00),
(53, NULL, 12, '2025-12-05 12:55:28', '2025-12-05 12:55:31', 'Normal', NULL, 37.88, 'Completed', 0, 0.00),
(54, NULL, 9, '2025-12-05 13:19:27', '2025-12-05 13:19:51', 'Normal', NULL, 3.33, 'Completed', 0, 0.00),
(55, NULL, 8, '2025-12-05 13:20:12', '2025-12-05 14:01:18', 'Normal', NULL, 333.00, 'Completed', 0, 0.00),
(56, NULL, 9, '2025-12-05 13:31:11', '2025-12-07 11:03:58', 'Normal', NULL, 22783.69, 'Completed', 0, 0.00),
(57, NULL, 8, '2025-12-05 14:01:38', '2025-12-05 14:09:49', 'Normal', NULL, 68.56, 'Completed', 0, 0.00),
(58, NULL, 8, '2025-12-05 14:09:56', '2025-12-05 14:14:14', 'Normal', NULL, 49.67, 'Completed', 0, 0.00),
(59, NULL, 8, '2025-12-05 14:27:30', '2025-12-05 14:30:12', 'Normal', NULL, 18.00, 'Completed', 0, 0.00),
(60, NULL, 8, '2025-12-07 11:08:54', '2025-12-07 11:15:36', 'Normal', NULL, 44.67, 'Completed', 0, 0.00),
(61, NULL, 8, '2025-12-07 11:15:53', '2025-12-07 11:34:21', 'Normal', NULL, 123.11, 'Completed', 0, 0.00),
(62, NULL, 8, '2025-12-07 11:16:01', '2025-12-07 11:18:01', 'Normal', NULL, 13.33, 'Completed', 0, 0.00),
(63, NULL, 8, '2025-12-07 11:21:12', '2025-12-07 11:21:14', 'Normal', NULL, 0.22, 'Completed', 0, 0.00),
(64, NULL, 8, '2025-12-07 11:21:35', '2025-12-07 11:21:37', 'Normal', NULL, 0.22, 'Completed', 0, 0.00),
(65, NULL, 8, '2025-12-07 11:26:18', '2025-12-07 11:27:58', 'Normal', NULL, 18.11, 'Completed', 0, 0.00),
(66, NULL, 8, '2025-12-07 11:28:41', '2025-12-07 11:28:46', 'Normal', NULL, 0.56, 'Completed', 0, 0.00),
(67, NULL, 8, '2025-12-07 11:28:56', NULL, 'Normal', NULL, 0.00, 'Active', 0, 0.00),
(68, NULL, 9, '2025-12-07 11:29:04', '2025-12-07 11:42:59', 'Normal', NULL, 115.97, 'Completed', 0, 0.00),
(69, NULL, 8, '2025-12-07 11:43:29', '2025-12-07 11:43:32', 'Normal', NULL, 0.33, 'Completed', 0, 0.00),
(70, NULL, 8, '2025-12-07 11:44:03', '2025-12-07 11:46:54', 'Normal', NULL, 33.00, 'Completed', 0, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `snooker_tables`
--

CREATE TABLE `snooker_tables` (
  `id` int(11) NOT NULL,
  `table_name` varchar(100) NOT NULL,
  `rate_per_hour` decimal(10,2) NOT NULL,
  `century_rate` decimal(10,2) NOT NULL,
  `status` varchar(10) DEFAULT 'Free',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `snooker_tables`
--

INSERT INTO `snooker_tables` (`id`, `table_name`, `rate_per_hour`, `century_rate`, `status`, `created_at`, `is_active`) VALUES
(8, 'Table1', 400.00, 2.00, 'Free', '2025-11-21 09:47:38', 1),
(9, 'Table2', 500.00, 50.00, 'Free', '2025-11-21 10:10:03', 1),
(11, 'Table3', 23.00, 23.00, 'Free', '2025-12-05 11:49:38', 1);

-- --------------------------------------------------------

--
-- Table structure for table `stock_purchases`
--

CREATE TABLE `stock_purchases` (
  `purchase_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `purchase_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `quantity_bought` int(11) NOT NULL,
  `price_per_unit` decimal(10,2) NOT NULL COMMENT 'Cost price paid for this batch',
  `supplier_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','Cashier','Staff') DEFAULT 'Staff',
  `status` enum('Active','Inactive') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `email`, `password`, `role`, `status`) VALUES
(11, 'amir', '', 'khan@a.com', '$2y$10$mkUNHBiYJ3tpmFv2EtqoIOujW9VTcM0GPLegmuaprf7akGgsoBpla', 'Cashier', 'Active'),
(12, 'saeed', '', 'amir@a.com', '$2y$10$aqXuG.QPrkZLi0WWh9Xf3edyCOuTIgHUQEi02X8DKRmPDVa3Vw6ZS', 'Admin', 'Active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `expanses`
--
ALTER TABLE `expanses`
  ADD PRIMARY KEY (`expanses_id`),
  ADD KEY `fk_expanses_category` (`category_id`);

--
-- Indexes for table `expanses_categories`
--
ALTER TABLE `expanses_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`item_id`),
  ADD UNIQUE KEY `item_name` (`item_name`);

--
-- Indexes for table `pos_products`
--
ALTER TABLE `pos_products`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD UNIQUE KEY `sku` (`sku`);

--
-- Indexes for table `sales_transactions`
--
ALTER TABLE `sales_transactions`
  ADD PRIMARY KEY (`transaction_id`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`sale_item_id`),
  ADD KEY `transaction_id` (`transaction_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `session_items`
--
ALTER TABLE `session_items`
  ADD PRIMARY KEY (`session_item_id`),
  ADD KEY `session_id` (`session_id`);

--
-- Indexes for table `snooker_bookings`
--
ALTER TABLE `snooker_bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `table_id` (`table_id`);

--
-- Indexes for table `snooker_sessions`
--
ALTER TABLE `snooker_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `id` (`id`),
  ADD KEY `fk_session_table` (`table_id`);

--
-- Indexes for table `snooker_tables`
--
ALTER TABLE `snooker_tables`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stock_purchases`
--
ALTER TABLE `stock_purchases`
  ADD PRIMARY KEY (`purchase_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `expanses`
--
ALTER TABLE `expanses`
  MODIFY `expanses_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `expanses_categories`
--
ALTER TABLE `expanses_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pos_products`
--
ALTER TABLE `pos_products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `sales_transactions`
--
ALTER TABLE `sales_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `sale_item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `session_items`
--
ALTER TABLE `session_items`
  MODIFY `session_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `snooker_bookings`
--
ALTER TABLE `snooker_bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `snooker_sessions`
--
ALTER TABLE `snooker_sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `snooker_tables`
--
ALTER TABLE `snooker_tables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `stock_purchases`
--
ALTER TABLE `stock_purchases`
  MODIFY `purchase_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `expanses`
--
ALTER TABLE `expanses`
  ADD CONSTRAINT `fk_expanses_category` FOREIGN KEY (`category_id`) REFERENCES `expanses_categories` (`category_id`);

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `sales_transactions` (`transaction_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `session_items`
--
ALTER TABLE `session_items`
  ADD CONSTRAINT `session_items_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `snooker_sessions` (`session_id`);

--
-- Constraints for table `snooker_bookings`
--
ALTER TABLE `snooker_bookings`
  ADD CONSTRAINT `snooker_bookings_ibfk_1` FOREIGN KEY (`table_id`) REFERENCES `snooker_tables` (`id`);

--
-- Constraints for table `snooker_sessions`
--
ALTER TABLE `snooker_sessions`
  ADD CONSTRAINT `fk_session_table` FOREIGN KEY (`table_id`) REFERENCES `snooker_tables` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `stock_purchases`
--
ALTER TABLE `stock_purchases`
  ADD CONSTRAINT `stock_purchases_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
