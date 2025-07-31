-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 22, 2025 at 02:37 AM
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
-- Database: `fishing_store`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'เบ็ดตกปลา'),
(2, 'เหยื่อปลอม'),
(3, 'รอกตกปลา'),
(4, 'สายเอ็น'),
(5, 'กล่องใส่อุปกรณ์'),
(6, 'ตะขอเบ็ด'),
(7, 'อุปกรณ์เสริม'),
(8, 'เสื้อผ้าตกปลา');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `discount` decimal(10,2) DEFAULT 0.00,
  `grand_total` decimal(10,2) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total`, `discount`, `grand_total`, `status`, `created_at`) VALUES
(1, 2, 3800.00, 0.00, 3800.00, 'shipped', '2025-01-10 14:49:45'),
(2, 3, 1250.00, 100.00, 1150.00, 'paid', '2025-01-12 10:30:00'),
(3, 4, 5600.00, 560.00, 5040.00, 'delivered', '2025-01-13 16:20:30'),
(4, 2, 2300.00, 0.00, 2300.00, 'pending', '2025-01-15 09:15:00'),
(5, 5, 850.00, 0.00, 850.00, 'paid', '2025-01-16 11:45:20');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 1, 1, 1500.00),
(2, 1, 3, 1, 2800.00),
(3, 2, 2, 2, 300.00),
(4, 2, 4, 3, 200.00),
(5, 3, 8, 1, 3500.00),
(6, 3, 9, 4, 250.00),
(7, 3, 10, 1, 700.00),
(8, 4, 11, 1, 1300.00),
(9, 4, 12, 2, 400.00),
(10, 5, 2, 1, 300.00),
(11, 5, 5, 1, 450.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_promotions`
--

CREATE TABLE `order_promotions` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `promotion_id` int(11) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_promotions`
--

INSERT INTO `order_promotions` (`id`, `order_id`, `promotion_id`, `discount_amount`, `created_at`) VALUES
(1, 2, 2, 100.00, '2025-01-12 10:30:00'),
(2, 3, 3, 560.00, '2025-01-13 16:20:30');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `stock`, `image`, `created_at`) VALUES
(1, 'เบ็ดตกปลา Shimano Expride', 'เบ็ดตกปลาคุณภาพสูง น้ำหนักเบา ความยาว 7 ฟุต เหมาะสำหรับตกปลาน้ำจืด', 1500.00, 15, 'rod1.jpg', '2025-01-01 10:00:00'),
(2, 'เหยื่อปลอม Rapala Original', 'เหยื่อปลอมรุ่นคลาสสิค ขนาด 7cm สีสันสดใส ว่ายน้ำเหมือนจริง', 300.00, 45, 'lure1.jpg', '2025-01-01 10:00:00'),
(3, 'รอกตกปลา Daiwa BG', 'รอกตกปลาทนทาน ระบบลากเรียบลื่น เหมาะสำหรับปลาขนาดกลาง-ใหญ่', 2800.00, 8, 'reel1.jpg', '2025-01-01 10:00:00'),
(4, 'สายเอ็น Monofilament 0.35mm', 'สายเอ็นใส เหนียวทนทาน ขนาด 0.35mm ยาว 100 เมตร', 200.00, 65, 'line1.jpg', '2025-01-01 10:00:00'),
(5, 'กล่องใส่อุปกรณ์ Plano 3700', 'กล่องเก็บอุปกรณ์ 4 ชั้น พร้อมช่องแบ่ง ขนาดพกพา', 450.00, 22, 'box1.jpg', '2025-01-01 10:00:00'),
(6, 'เบ็ดตกปลา Abu Garcia Vendetta', 'เบ็ดคาร์บอน 30T แข็งแรง เบา ความยาว 6.6 ฟุต', 1800.00, 12, 'rod2.jpg', '2025-01-02 10:00:00'),
(7, 'เหยื่อปลอม Lucky Craft Pointer', 'เหยื่อ Minnow คุณภาพสูง ลอยน้ำ ขนาด 10cm', 320.00, 30, 'lure2.jpg', '2025-01-02 10:00:00'),
(8, 'รอกตกปลา Penn Battle III', 'รอกตกปลาระดับมืออาชีพ ทนน้ำเค็ม ระบบ sealed bearing', 3500.00, 5, 'reel2.jpg', '2025-01-02 10:00:00'),
(9, 'สายเอ็น Fluorocarbon Leader', 'สายเอ็นมองไม่เห็นในน้ำ ขนาด 0.4mm ยาว 50 เมตร', 250.00, 40, 'line2.jpg', '2025-01-02 10:00:00'),
(10, 'กล่องเก็บอุปกรณ์ Meiho VS-7070', 'กล่องญี่ปุ่นคุณภาพสูง กันน้ำ พร้อมช่องเก็บหลากหลาย', 700.00, 18, 'box2.jpg', '2025-01-02 10:00:00'),
(11, 'เบ็ดตกปลา Okuma Helios', 'เบ็ดน้ำหนักเบาพิเศษ ความไวสูง เหมาะสำหรับเหยื่อปลอม', 1300.00, 7, 'rod3.jpg', '2025-01-03 10:00:00'),
(12, 'เหยื่อปลอม Megabass Vision 110', 'เหยื่อ Jerkbait ชื่อดัง แอ็คชั่นสมจริง', 400.00, 25, 'lure3.jpg', '2025-01-03 10:00:00'),
(13, 'รอกตกปลา Shimano Stella FK', 'รอกตกปลาเกรดพรีเมี่ยม เทคโนโลยี X-Ship ลากนุ่มนวล', 8000.00, 3, 'reel3.jpg', '2025-01-03 10:00:00'),
(14, 'สายเอ็น Braided PE 8X', 'สายถักแรงดึงสูง 8 เส้น ขนาด PE1.5 ยาว 150 เมตร', 500.00, 35, 'line3.jpg', '2025-01-03 10:00:00'),
(15, 'กล่องเก็บเหยื่อ Rapala Utility Box', 'กล่องใส่เหยื่อพร้อมฟองน้ำกันกระแทก 2 ชั้น', 550.00, 20, 'box3.jpg', '2025-01-03 10:00:00'),
(16, 'ตะขอเบ็ด Owner Cutting Point', 'ตะขอญี่ปุ่นคมพิเศษ ขนาด 1/0 บรรจุ 10 ตัว', 120.00, 100, 'hook1.jpg', '2025-01-04 10:00:00'),
(17, 'ที่จับปลา Fish Grip', 'ที่จับปลาสแตนเลส พร้อมตาชั่ง น้ำหนักเบา', 380.00, 15, 'grip1.jpg', '2025-01-04 10:00:00'),
(18, 'เสื้อตกปลา UV Protection', 'เสื้อแขนยาวป้องกัน UV ระบายอากาศดี Quick Dry', 650.00, 25, 'shirt1.jpg', '2025-01-04 10:00:00'),
(19, 'คีมถอดเบ็ด Stainless', 'คีมถอดเบ็ดสแตนเลส ปลายแหลม ด้ามกันลื่น', 280.00, 30, 'plier1.jpg', '2025-01-04 10:00:00'),
(20, 'ไฟฉายคาดหัว LED', 'ไฟฉาย LED ความสว่าง 1000 ลูเมน กันน้ำ IPX6', 450.00, 20, 'light1.jpg', '2025-01-04 10:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `product_category`
--

CREATE TABLE `product_category` (
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_category`
--

INSERT INTO `product_category` (`product_id`, `category_id`) VALUES
(1, 1),
(2, 2),
(3, 3),
(4, 4),
(5, 5),
(6, 1),
(7, 2),
(8, 3),
(9, 4),
(10, 5),
(11, 1),
(12, 2),
(13, 3),
(14, 4),
(15, 5),
(16, 6),
(17, 7),
(18, 8),
(19, 7),
(20, 7);

-- --------------------------------------------------------

--
-- Stand-in structure for view `product_rating_stats`
-- (See below for the actual view)
--
CREATE TABLE `product_rating_stats` (
`product_id` int(11)
,`product_name` varchar(255)
,`total_reviews` bigint(21)
,`average_rating` decimal(14,4)
,`five_star` decimal(22,0)
,`four_star` decimal(22,0)
,`three_star` decimal(22,0)
,`two_star` decimal(22,0)
,`one_star` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'approved',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_reviews`
--

INSERT INTO `product_reviews` (`id`, `product_id`, `user_id`, `rating`, `comment`, `status`, `created_at`) VALUES
(1, 1, 2, 5, 'เบ็ดคุณภาพดีมาก ใช้งานง่าย น้ำหนักเบา เหมาะสำหรับมือใหม่และมือโปร แนะนำเลยครับ', 'approved', '2025-01-11 10:30:00'),
(2, 1, 3, 4, 'สินค้าดี ส่งไว บรรจุภัณฑ์แน่นหนา แต่ราคาค่อนข้างสูงหน่อย', 'approved', '2025-01-12 14:20:00'),
(3, 2, 4, 5, 'เหยื่อปลอมสีสันสดใส ปลาชอบมาก ตกได้หลายตัวแล้ว คุ้มค่าครับ', 'approved', '2025-01-13 09:15:00'),
(4, 3, 2, 5, 'รอกทนทาน ระบบลากดีมาก ใช้ตกปลาใหญ่ได้สบาย คุ้มค่ากับราคา', 'approved', '2025-01-14 16:30:00'),
(5, 3, 5, 4, 'ใช้งานดี แต่ตัวรอกค่อนข้างหนักไปหน่อย อาจไม่เหมาะกับผู้หญิง', 'approved', '2025-01-15 11:45:00'),
(6, 4, 3, 3, 'สายเอ็นใช้ได้ แต่ไม่เหนียวเท่าที่คิด อาจจะต้องเปลี่ยนแบรนด์', 'approved', '2025-01-15 13:20:00'),
(7, 5, 2, 5, 'กล่องใส่อุปกรณ์ดีมาก มีช่องเยอะ จุของได้เยอะ วัสดุแข็งแรง', 'approved', '2025-01-16 08:30:00'),
(8, 8, 4, 5, 'รอกคุณภาพสูงมาก ลากลื่น ทนน้ำเค็มได้ดี ประทับใจครับ', 'approved', '2025-01-16 14:00:00'),
(9, 11, 5, 4, 'เบ็ดเบาดี แต่ปลายค่อนข้างอ่อนไปหน่อย เหมาะกับปลาขนาดเล็ก-กลาง', 'approved', '2025-01-17 09:00:00'),
(10, 13, 2, 5, 'รอก Stella คุณภาพเยี่ยม ราคาแพงแต่คุ้มค่า ใช้มา 3 เดือนยังใหม่เหมือนเดิม', 'approved', '2025-01-17 10:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `promotions`
--

CREATE TABLE `promotions` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `minimum_order` decimal(10,2) DEFAULT 0.00,
  `usage_limit` int(11) DEFAULT NULL,
  `usage_count` int(11) DEFAULT 0,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `promotions`
--

INSERT INTO `promotions` (`id`, `code`, `description`, `discount_type`, `discount_value`, `minimum_order`, `usage_limit`, `usage_count`, `start_date`, `end_date`, `status`, `created_at`) VALUES
(1, 'WELCOME10', 'ส่วนลด 10% สำหรับลูกค้าใหม่', 'percentage', 10.00, 500.00, 100, 2, '2025-01-01 00:00:00', '2025-12-31 23:59:59', 'active', '2025-07-21 19:57:04'),
(2, 'SAVE100', 'ลด 100 บาท เมื่อซื้อครบ 1,000', 'fixed', 100.00, 1000.00, 50, 1, '2025-01-01 00:00:00', '2025-12-31 23:59:59', 'active', '2025-07-21 19:57:04'),
(3, 'FISHING20', 'ลด 20% สินค้าทุกชิ้น', 'percentage', 20.00, 0.00, NULL, 1, '2025-07-01 00:00:00', '2025-07-31 23:59:59', 'active', '2025-07-21 19:57:04'),
(4, 'NEWYEAR2025', 'โปรปีใหม่ ลด 15%', 'percentage', 15.00, 800.00, 200, 0, '2025-01-01 00:00:00', '2025-01-31 23:59:59', 'active', '2025-07-21 19:57:04'),
(5, 'FREE50', 'ลด 50 บาท ไม่มีขั้นต่ำ', 'fixed', 50.00, 0.00, 500, 0, '2025-01-15 00:00:00', '2025-02-15 23:59:59', 'active', '2025-07-21 19:57:04');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `created_at`) VALUES
(1, 'admin', '$2y$10$m/I/e3fTvm4PcEYPeHBu0ebw2qucpjFPYimOXYnFHKGWJLTyDVcU6', 'admin@toomtamfishing.com', '2025-01-01 09:00:00'),
(2, 'somchai', '$2y$10$m/I/e3fTvm4PcEYPeHBu0ebw2qucpjFPYimOXYnFHKGWJLTyDVcU6', 'somchai@example.com', '2025-01-05 10:30:00'),
(3, 'malee', '$2y$10$m/I/e3fTvm4PcEYPeHBu0ebw2qucpjFPYimOXYnFHKGWJLTyDVcU6', 'malee@example.com', '2025-01-08 14:20:00'),
(4, 'nattapong', '$2y$10$m/I/e3fTvm4PcEYPeHBu0ebw2qucpjFPYimOXYnFHKGWJLTyDVcU6', 'nattapong@example.com', '2025-01-10 09:15:00'),
(5, 'siriwan', '$2y$10$m/I/e3fTvm4PcEYPeHBu0ebw2qucpjFPYimOXYnFHKGWJLTyDVcU6', 'siriwan@example.com', '2025-01-12 16:45:00');

-- --------------------------------------------------------

--
-- Structure for view `product_rating_stats`
--
DROP TABLE IF EXISTS `product_rating_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `product_rating_stats`  AS SELECT `p`.`id` AS `product_id`, `p`.`name` AS `product_name`, count(`pr`.`id`) AS `total_reviews`, avg(`pr`.`rating`) AS `average_rating`, sum(case when `pr`.`rating` = 5 then 1 else 0 end) AS `five_star`, sum(case when `pr`.`rating` = 4 then 1 else 0 end) AS `four_star`, sum(case when `pr`.`rating` = 3 then 1 else 0 end) AS `three_star`, sum(case when `pr`.`rating` = 2 then 1 else 0 end) AS `two_star`, sum(case when `pr`.`rating` = 1 then 1 else 0 end) AS `one_star` FROM (`products` `p` left join `product_reviews` `pr` on(`p`.`id` = `pr`.`product_id` and `pr`.`status` = 'approved')) GROUP BY `p`.`id` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `order_promotions`
--
ALTER TABLE `order_promotions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `promotion_id` (`promotion_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_category`
--
ALTER TABLE `product_category`
  ADD PRIMARY KEY (`product_id`,`category_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product_review` (`product_id`,`user_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `order_promotions`
--
ALTER TABLE `order_promotions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `order_promotions`
--
ALTER TABLE `order_promotions`
  ADD CONSTRAINT `order_promotions_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_promotions_ibfk_2` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`);

--
-- Constraints for table `product_category`
--
ALTER TABLE `product_category`
  ADD CONSTRAINT `product_category_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `product_category_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
