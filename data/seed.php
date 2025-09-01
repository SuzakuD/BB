<?php
/**
 * Seed script for fishing equipment data
 */

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDB();
    
    // Clear existing data (except users)
    $pdo->exec("DELETE FROM order_items");
    $pdo->exec("DELETE FROM orders");
    $pdo->exec("DELETE FROM receipts");
    $pdo->exec("DELETE FROM products");
    $pdo->exec("DELETE FROM categories");
    $pdo->exec("DELETE FROM promotions");
    
    // Reset auto increment
    $pdo->exec("UPDATE sqlite_sequence SET seq = 0 WHERE name IN ('categories', 'products', 'orders', 'order_items', 'receipts', 'promotions')");
    
    echo "Cleared existing data...\n";
    
    // Insert categories
    $categories = [
        'คันเบ็ด',
        'รอกตกปลา',
        'เหยื่อและล่อ',
        'สายเอ็น',
        'เบ็ดตกปลา',
        'ตาข่าย',
        'อุปกรณ์เสริม',
        'กล่องเก็บของ',
        'เสื้อผ้า',
        'อุปกรณ์ความปลอดภัย'
    ];
    
    $categoryIds = [];
    foreach ($categories as $category) {
        $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->execute([$category]);
        $categoryIds[] = $pdo->lastInsertId();
    }
    
    echo "Inserted categories...\n";
    
    // Insert products
    $products = [
        // คันเบ็ด
        [
            'name' => 'คันเบ็ดตกปลาทะเล Professional Series',
            'description' => 'คันเบ็ดสำหรับตกปลาทะเล ความยาว 2.1 เมตร วัสดุคาร์บอนไฟเบอร์',
            'price' => 2500,
            'stock' => 15,
            'category_id' => $categoryIds[0],
            'image' => 'https://via.placeholder.com/300x200?text=Sea+Fishing+Rod'
        ],
        [
            'name' => 'คันเบ็ดตกปลาน้ำจืด Ultra Light',
            'description' => 'คันเบ็ดเบา สำหรับตกปลาน้ำจืด ความยาว 1.8 เมตร',
            'price' => 1200,
            'stock' => 25,
            'category_id' => $categoryIds[0],
            'image' => 'https://via.placeholder.com/300x200?text=Fresh+Water+Rod'
        ],
        [
            'name' => 'คันเบ็ดตกปลาคาร์ฟ Heavy Action',
            'description' => 'คันเบ็ดแข็งแรง สำหรับตกปลาคาร์ฟ ความยาว 3.6 เมตร',
            'price' => 3500,
            'stock' => 8,
            'category_id' => $categoryIds[0],
            'image' => 'https://via.placeholder.com/300x200?text=Carp+Fishing+Rod'
        ],
        
        // รอกตกปลา
        [
            'name' => 'รอกสปินนิ่ง Shimano FX 2500',
            'description' => 'รอกสปินนิ่งคุณภาพดี 5 ลูกปืน เหมาะสำหรับมือใหม่',
            'price' => 1800,
            'stock' => 20,
            'category_id' => $categoryIds[1],
            'image' => 'https://via.placeholder.com/300x200?text=Spinning+Reel'
        ],
        [
            'name' => 'รอกเบตแคสติ้ง Daiwa Tatula CT',
            'description' => 'รอกเบตแคสติ้งระดับมืออาชีพ ระบบเบรคแม่เหล็ก',
            'price' => 4500,
            'stock' => 12,
            'category_id' => $categoryIds[1],
            'image' => 'https://via.placeholder.com/300x200?text=Baitcasting+Reel'
        ],
        [
            'name' => 'รอกตกปลาทะเล Penn Battle III',
            'description' => 'รอกสำหรับตกปลาทะเล กันน้ำเค็ม ขนาด 4000',
            'price' => 6500,
            'stock' => 6,
            'category_id' => $categoryIds[1],
            'image' => 'https://via.placeholder.com/300x200?text=Saltwater+Reel'
        ],
        
        // เหยื่อและล่อ
        [
            'name' => 'เหยื่อปลอม Rapala Countdown 7cm',
            'description' => 'เหยื่อปลอมจมน้ำ สำหรับตกปลากะพง และปลาล่า',
            'price' => 350,
            'stock' => 50,
            'category_id' => $categoryIds[2],
            'image' => 'https://via.placeholder.com/300x200?text=Rapala+Lure'
        ],
        [
            'name' => 'เหยื่อยางนุ่ม Berkley PowerBait',
            'description' => 'เหยื่อยางนุ่มหอมกลิ่น เหมาะสำหรับปลาเทราต์',
            'price' => 180,
            'stock' => 80,
            'category_id' => $categoryIds[2],
            'image' => 'https://via.placeholder.com/300x200?text=Soft+Bait'
        ],
        [
            'name' => 'เหยื่อปลอมแปปเปอร์ Yo-Zuri 3D Popper',
            'description' => 'เหยื่อปลอมลอยน้ำ สำหรับตกปลาผิวน้ำ',
            'price' => 420,
            'stock' => 30,
            'category_id' => $categoryIds[2],
            'image' => 'https://via.placeholder.com/300x200?text=Popper+Lure'
        ],
        
        // สายเอ็น
        [
            'name' => 'สายเอ็นฟลูออโรคาร์บอน Seaguar 0.35mm',
            'description' => 'สายเอ็นใส ทนทาน ความแรงดึง 12 ปอนด์',
            'price' => 280,
            'stock' => 40,
            'category_id' => $categoryIds[3],
            'image' => 'https://via.placeholder.com/300x200?text=Fluorocarbon+Line'
        ],
        [
            'name' => 'สายเอ็นถัก PowerPro 20lb',
            'description' => 'สายเอ็นถัก 4 เส้น แข็งแรง เส้นผ่านศูนย์กลางเล็ก',
            'price' => 650,
            'stock' => 25,
            'category_id' => $categoryIds[3],
            'image' => 'https://via.placeholder.com/300x200?text=Braided+Line'
        ],
        
        // เบ็ดตกปลา
        [
            'name' => 'เบ็ดตกปลา Owner 5130 ขนาด 1/0',
            'description' => 'เบ็ดคุณภาพญี่ปุ่น คมแหลม แข็งแรง บรรจุ 10 ตัว',
            'price' => 120,
            'stock' => 100,
            'category_id' => $categoryIds[4],
            'image' => 'https://via.placeholder.com/300x200?text=Fishing+Hooks'
        ],
        [
            'name' => 'เบ็ดตกปลาคาร์ฟ Fox Edges Curve Shank',
            'description' => 'เบ็ดสำหรับตกปลาคาร์ฟ แบบโค้ง บรรจุ 10 ตัว',
            'price' => 250,
            'stock' => 60,
            'category_id' => $categoryIds[4],
            'image' => 'https://via.placeholder.com/300x200?text=Carp+Hooks'
        ],
        
        // ตาข่าย
        [
            'name' => 'ตาข่ายตักปลา แบบพับได้ 60cm',
            'description' => 'ตาข่ายตักปลา พับเก็บได้ ก้านยาว 1.5 เมตร',
            'price' => 450,
            'stock' => 15,
            'category_id' => $categoryIds[5],
            'image' => 'https://via.placeholder.com/300x200?text=Landing+Net'
        ],
        [
            'name' => 'ตาข่ายลอยปลา Keep Net 3m',
            'description' => 'ตาข่ายสำหรับเก็บปลาที่ตกได้ ยาว 3 เมตร',
            'price' => 680,
            'stock' => 10,
            'category_id' => $categoryIds[5],
            'image' => 'https://via.placeholder.com/300x200?text=Keep+Net'
        ],
        
        // อุปกรณ์เสริม
        [
            'name' => 'ที่วางคันเบ็ด Rod Pod 3 ขา',
            'description' => 'ที่วางคันเบ็ด 3 ขา ปรับระดับได้ วัสดุอลูมิเนียม',
            'price' => 1200,
            'stock' => 12,
            'category_id' => $categoryIds[6],
            'image' => 'https://via.placeholder.com/300x200?text=Rod+Pod'
        ],
        [
            'name' => 'เครื่องชั่งปลาดิจิตอล 40kg',
            'description' => 'เครื่องชั่งปลาดิจิตอล แม่นยำ ชั่งได้สูงสุด 40 กิโลกรัม',
            'price' => 350,
            'stock' => 25,
            'category_id' => $categoryIds[6],
            'image' => 'https://via.placeholder.com/300x200?text=Digital+Scale'
        ],
        [
            'name' => 'ไฟฉาย LED กันน้ำ',
            'description' => 'ไฟฉาย LED แสงสว่าง กันน้ำ IPX6 สำหรับตกปลากลางคืน',
            'price' => 280,
            'stock' => 35,
            'category_id' => $categoryIds[6],
            'image' => 'https://via.placeholder.com/300x200?text=LED+Flashlight'
        ],
        
        // กล่องเก็บของ
        [
            'name' => 'กล่องเก็บเหยื่อ Plano 3700',
            'description' => 'กล่องเก็บเหยื่อปลอม ช่องปรับได้ กันน้ำ',
            'price' => 320,
            'stock' => 30,
            'category_id' => $categoryIds[7],
            'image' => 'https://via.placeholder.com/300x200?text=Tackle+Box'
        ],
        [
            'name' => 'กระเป๋าตกปลา Shimano Tribal',
            'description' => 'กระเป๋าตกปลาขนาดใหญ่ มีช่องเยอะ กันน้ำ',
            'price' => 1800,
            'stock' => 8,
            'category_id' => $categoryIds[7],
            'image' => 'https://via.placeholder.com/300x200?text=Fishing+Bag'
        ],
        
        // เสื้อผ้า
        [
            'name' => 'เสื้อตกปลา UV Protection',
            'description' => 'เสื้อแขนยาว กันแดด ระบายอากาศดี ไซส์ L',
            'price' => 450,
            'stock' => 20,
            'category_id' => $categoryIds[8],
            'image' => 'https://via.placeholder.com/300x200?text=Fishing+Shirt'
        ],
        [
            'name' => 'หมวกตกปลา กันแดด',
            'description' => 'หมวกปีกกว้าง กันแดด กันลม มีเชือกรัดคาง',
            'price' => 250,
            'stock' => 40,
            'category_id' => $categoryIds[8],
            'image' => 'https://via.placeholder.com/300x200?text=Fishing+Hat'
        ],
        
        // อุปกรณ์ความปลอดภัย
        [
            'name' => 'เสื้อชูชีพ แบบเป่าลม',
            'description' => 'เสื้อชูชีพ เป่าลมอัตโนมัติ น้ำหนักเบา สะดวกสวมใส่',
            'price' => 2200,
            'stock' => 15,
            'category_id' => $categoryIds[9],
            'image' => 'https://via.placeholder.com/300x200?text=Life+Jacket'
        ],
        [
            'name' => 'นกหวีดกันฉุกเฉิน',
            'description' => 'นกหวีดเสียงดัง กันน้ำ มีสายคล้องคอ',
            'price' => 80,
            'stock' => 50,
            'category_id' => $categoryIds[9],
            'image' => 'https://via.placeholder.com/300x200?text=Emergency+Whistle'
        ]
    ];
    
    foreach ($products as $product) {
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock, category_id, image) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $product['name'],
            $product['description'],
            $product['price'],
            $product['stock'],
            $product['category_id'],
            $product['image']
        ]);
    }
    
    echo "Inserted " . count($products) . " products...\n";
    
    // Insert sample promotions
    $promotions = [
        [
            'code' => 'NEWBIE10',
            'discount' => 10,
            'expire_date' => date('Y-m-d', strtotime('+30 days'))
        ],
        [
            'code' => 'FISHING20',
            'discount' => 20,
            'expire_date' => date('Y-m-d', strtotime('+60 days'))
        ],
        [
            'code' => 'WEEKEND15',
            'discount' => 15,
            'expire_date' => date('Y-m-d', strtotime('+14 days'))
        ]
    ];
    
    foreach ($promotions as $promo) {
        $stmt = $pdo->prepare("INSERT INTO promotions (code, discount, expire_date) VALUES (?, ?, ?)");
        $stmt->execute([$promo['code'], $promo['discount'], $promo['expire_date']]);
    }
    
    echo "Inserted " . count($promotions) . " promotions...\n";
    
    echo "Seed data inserted successfully!\n";
    echo "\nDefault admin login:\n";
    echo "Username: admin\n";
    echo "Password: admin123\n";
    echo "\nPromotion codes:\n";
    foreach ($promotions as $promo) {
        echo "- {$promo['code']}: {$promo['discount']}% off (expires: {$promo['expire_date']})\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>