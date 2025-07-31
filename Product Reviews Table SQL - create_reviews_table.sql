-- เพิ่มตาราง product_reviews สำหรับระบบรีวิวสินค้า
CREATE TABLE IF NOT EXISTS `product_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
  `comment` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'approved',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_product_review` (`product_id`,`user_id`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- เพิ่มตัวอย่างรีวิว
INSERT INTO `product_reviews` (`product_id`, `user_id`, `rating`, `comment`, `status`) VALUES
(1, 2, 5, 'เบ็ดคุณภาพดีมาก ใช้งานง่าย น้ำหนักเบา เหมาะสำหรับมือใหม่และมือโปร', 'approved'),
(1, 3, 4, 'สินค้าดี ส่งไว แต่ราคาค่อนข้างสูงหน่อย', 'approved'),
(2, 1, 5, 'เหยื่อปลอมสีสันสดใส ปลาชอบมาก ตกได้หลายตัวแล้ว', 'approved'),
(3, 2, 5, 'รอกทนทาน ระบบลากดีมาก คุ้มค่ากับราคา', 'approved'),
(3, 3, 4, 'ใช้งานดี แต่ตัวรอกค่อนข้างหนักไปหน่อย', 'approved'),
(4, 1, 3, 'สายเอ็นใช้ได้ แต่ไม่เหนียวเท่าที่คิด', 'approved'),
(5, 2, 5, 'กล่องใส่อุปกรณ์ดีมาก มีช่องเยอะ จุของได้เยอะ', 'approved');

-- เพิ่มตาราง promotions สำหรับระบบโปรโมชั่น
CREATE TABLE IF NOT EXISTS `promotions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL UNIQUE,
  `description` varchar(255) DEFAULT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `minimum_order` decimal(10,2) DEFAULT 0,
  `usage_limit` int(11) DEFAULT NULL,
  `usage_count` int(11) DEFAULT 0,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `code` (`code`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- เพิ่มตัวอย่างโปรโมชั่น
INSERT INTO `promotions` (`code`, `description`, `discount_type`, `discount_value`, `minimum_order`, `usage_limit`, `start_date`, `end_date`) VALUES
('WELCOME10', 'ส่วนลด 10% สำหรับลูกค้าใหม่', 'percentage', 10.00, 500.00, 100, '2025-01-01 00:00:00', '2025-12-31 23:59:59'),
('SAVE100', 'ลด 100 บาท เมื่อซื้อครบ 1,000', 'fixed', 100.00, 1000.00, 50, '2025-01-01 00:00:00', '2025-12-31 23:59:59'),
('FISHING20', 'ลด 20% สินค้าทุกชิ้น', 'percentage', 20.00, 0.00, NULL, '2025-07-01 00:00:00', '2025-07-31 23:59:59');

-- เพิ่มตาราง order_promotions เพื่อเก็บประวัติการใช้โปรโมชั่น
CREATE TABLE IF NOT EXISTS `order_promotions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `promotion_id` int(11) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `promotion_id` (`promotion_id`),
  CONSTRAINT `order_promotions_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_promotions_ibfk_2` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- เพิ่มคอลัมน์ discount ในตาราง orders
ALTER TABLE `orders` 
ADD COLUMN `discount` decimal(10,2) DEFAULT 0 AFTER `total`,
ADD COLUMN `grand_total` decimal(10,2) DEFAULT NULL AFTER `discount`;

-- อัพเดต grand_total สำหรับ orders ที่มีอยู่
UPDATE `orders` SET `grand_total` = `total` - `discount` WHERE `grand_total` IS NULL;

-- สร้าง View สำหรับดูสถิติรีวิวของสินค้า
CREATE VIEW `product_rating_stats` AS
SELECT 
    p.id AS product_id,
    p.name AS product_name,
    COUNT(pr.id) AS total_reviews,
    AVG(pr.rating) AS average_rating,
    SUM(CASE WHEN pr.rating = 5 THEN 1 ELSE 0 END) AS five_star,
    SUM(CASE WHEN pr.rating = 4 THEN 1 ELSE 0 END) AS four_star,
    SUM(CASE WHEN pr.rating = 3 THEN 1 ELSE 0 END) AS three_star,
    SUM(CASE WHEN pr.rating = 2 THEN 1 ELSE 0 END) AS two_star,
    SUM(CASE WHEN pr.rating = 1 THEN 1 ELSE 0 END) AS one_star
FROM products p
LEFT JOIN product_reviews pr ON p.id = pr.product_id AND pr.status = 'approved'
GROUP BY p.id;