-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 28, 2026 at 02:22 AM
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
-- Database: `cupofjoernal`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`) VALUES
(1, 'Coffee'),
(2, 'Tea'),
(3, 'Pastries'),
(4, 'Others'),
(5, 'Desserts');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `loyalty_points` int(11) NOT NULL DEFAULT 0,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `customer_name`, `contact_number`, `loyalty_points`, `email`, `phone`, `photo`) VALUES
(1, 'Hannah Lei', '09171234567', 8, NULL, NULL, NULL),
(2, 'Pearl Girao', '09181234567', 0, NULL, NULL, NULL),
(3, 'Dan Huertas', '09191234567', 0, NULL, NULL, NULL),
(4, 'Hannah Raymundo', '09201234567', 0, NULL, NULL, NULL),
(5, 'Amihan', '091111111111', 20, NULL, NULL, NULL),
(6, 'Pirena', '09272029202', 61, NULL, NULL, NULL),
(7, 'coco martin', '+639701396208', 10, NULL, NULL, NULL),
(8, 'lola flora', NULL, 49, 'floradelfin@gmail.com', '+639452595872', NULL),
(9, 'Mariah Curry', NULL, 21, 'mariacurry@gmail.com', '09123456789', NULL),
(12, 'Juana', NULL, 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `employee_id` int(11) NOT NULL,
  `employee_name` varchar(100) NOT NULL,
  `role` enum('Barista','Cashier','Manager') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`employee_id`, `employee_name`, `role`) VALUES
(1, 'Andrea Socito', 'Barista'),
(2, 'Cardo Dalisay', 'Cashier'),
(3, 'Clowie Geronimo', 'Manager'),
(4, 'Rene Descartes', 'Barista');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `order_date` datetime NOT NULL DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `customer_id`, `employee_id`, `order_date`, `total_amount`) VALUES
(1, 1, 1, '2026-10-06 22:20:05', 400.00),
(2, 5, 2, '2026-10-06 22:33:51', 1020.00),
(3, 6, 2, '2026-10-07 13:52:03', 3080.00),
(4, 7, NULL, '2026-10-27 22:06:35', 275.00),
(5, 7, NULL, '2026-10-27 22:10:12', 275.00),
(6, 8, NULL, '2026-10-27 23:07:34', 915.00),
(7, 8, NULL, '2026-10-27 23:08:14', 1405.00),
(8, 8, NULL, '2026-10-27 23:12:24', 150.00),
(9, 9, NULL, '2026-10-28 08:54:42', 1075.00),
(12, 12, NULL, '2026-10-28 09:04:10', 255.00),
(13, 8, NULL, '2026-10-28 09:04:44', 2890.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `product_id`, `quantity`, `unit_price`) VALUES
(1, 1, 1, 2, 90.00),
(2, 1, 5, 2, 110.00),
(3, 2, 1, 4, 90.00),
(4, 2, 7, 3, 145.00),
(5, 2, 14, 3, 75.00),
(6, 3, 1, 3, 90.00),
(7, 3, 10, 12, 135.00),
(8, 3, 12, 5, 90.00),
(9, 3, 14, 3, 75.00),
(10, 3, 18, 3, 90.00),
(11, 3, 19, 7, 35.00),
(12, 4, 1, 1, 90.00),
(13, 4, 16, 1, 150.00),
(14, 4, 19, 1, 35.00),
(15, 5, 1, 1, 90.00),
(16, 5, 16, 1, 150.00),
(17, 5, 19, 1, 35.00),
(18, 6, 16, 2, 150.00),
(19, 6, 19, 2, 35.00),
(20, 6, 10, 3, 135.00),
(21, 6, 3, 1, 140.00),
(22, 7, 16, 4, 150.00),
(23, 7, 19, 23, 35.00),
(24, 8, 16, 1, 150.00),
(25, 9, 2, 1, 130.00),
(26, 9, 5, 1, 110.00),
(27, 9, 3, 1, 140.00),
(28, 9, 6, 2, 135.00),
(29, 9, 17, 5, 85.00),
(30, 12, 9, 3, 85.00),
(31, 13, 4, 3, 120.00),
(32, 13, 2, 4, 130.00),
(33, 13, 8, 4, 70.00),
(34, 13, 15, 4, 80.00),
(35, 13, 18, 3, 90.00),
(36, 13, 11, 4, 95.00),
(37, 13, 9, 4, 85.00),
(38, 13, 20, 4, 105.00);

--
-- Triggers `order_items`
--
DELIMITER $$
CREATE TRIGGER `deduct_stock` AFTER INSERT ON `order_items` FOR EACH ROW BEGIN
    UPDATE products
    SET stock_quantity = stock_quantity - NEW.quantity
    WHERE product_id = NEW.product_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `product_name`, `price`, `stock_quantity`, `category_id`) VALUES
(1, 'Americano', 90.00, 39, 1),
(2, 'Caramel Latte', 130.00, 30, 1),
(3, 'Spanish Latte', 140.00, 42, 1),
(4, 'Cappuccino', 120.00, 29, 1),
(5, 'Cold Brew', 110.00, 21, 1),
(6, 'Vanilla Macchiato', 135.00, 26, 1),
(7, 'Mocha Fudge', 145.00, 17, 1),
(8, 'Espresso Shot', 70.00, 52, 1),
(9, 'Green Tea', 85.00, 16, 2),
(10, 'Matcha Latte', 135.00, 25, 2),
(11, 'Earl Grey Tea', 95.00, 27, 2),
(12, 'Peach Iced Tea', 90.00, 20, 2),
(13, 'Chamomile Tea', 85.00, 15, 2),
(14, 'Croissant', 75.00, 14, 3),
(15, 'Chocolate Muffin', 80.00, 17, 3),
(16, 'Blueberry Cheesecake', 150.00, 12, 3),
(17, 'Cinnamon Roll', 85.00, 10, 3),
(18, 'Red Velvet Cupcake', 90.00, 13, 3),
(19, 'Bottled Water', 35.00, 66, 4),
(20, 'Hot Chocolate', 105.00, 10, 4);

-- --------------------------------------------------------

--
-- Table structure for table `product_photos`
--

CREATE TABLE `product_photos` (
  `photo_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','customer') NOT NULL DEFAULT 'customer',
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `role`, `full_name`, `email`, `customer_id`) VALUES
(1, 'admin', 'password123', 'admin', 'Administrator', 'admin@cupofjoernal.local', NULL),
(2, 'customer1', 'password123', 'customer', NULL, NULL, NULL),
(3, 'cocomartin@gmail.com', '$2y$10$zjg5abH4TiV.KStYFoMQOOVZvPvPg9FFmgAQmISUILpl8bsNQL6oe', 'customer', 'coco martin', 'cocomartin@gmail.com', 7),
(4, 'floradelfin@gmail.com', '$2y$10$djuMSo/mXIN5VKm.RHlZ0.R88a6VuEkBAOKBwF9SXaoKaM6O39mZa', 'customer', 'lola flora', 'floradelfin@gmail.com', 8),
(5, 'mariacurry@gmail.com', '$2y$10$1InBeUwzA0VxHAYdabK27.Y1uguTsS9LAdmn1Fu192W15s4Aij3sK', 'customer', 'Mariah Curry', 'mariacurry@gmail.com', 9);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `product_photos`
--
ALTER TABLE `product_photos`
  ADD PRIMARY KEY (`photo_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `customer_id` (`customer_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `product_photos`
--
ALTER TABLE `product_photos`
  MODIFY `photo_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);

--
-- Constraints for table `product_photos`
--
ALTER TABLE `product_photos`
  ADD CONSTRAINT `product_photos_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
