<?php
/**
 * Script to update product images with proper URLs based on product names
 */

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDB();
    
    // Define image mappings based on product names
    $imageMap = [
        // Fishing rods
        'คันเบ็ดตกปลาทะเล Professional Series' => 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=400&h=300&fit=crop',
        'คันเบ็ดตกปลาน้ำจืด Ultra Light' => 'https://images.unsplash.com/photo-1551698618-1dfe5d97d256?w=400&h=300&fit=crop',
        'คันเบ็ดตกปลาคาร์ฟ Heavy Action' => 'https://images.unsplash.com/photo-1503919005314-30d93d07d823?w=400&h=300&fit=crop',
        
        // Reels
        'รอกสปินนิ่ง Shimano FX 2500' => 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400&h=300&fit=crop',
        'รอกเบตแคสติ้ง Daiwa Tatula CT' => 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400&h=300&fit=crop',
        'รอกตกปลาทะเล Penn Battle III' => 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400&h=300&fit=crop',
        
        // Lures and baits
        'เหยื่อปลอม Rapala Countdown 7cm' => 'https://images.unsplash.com/photo-1544551763-77ef2d0cfc6c?w=400&h=300&fit=crop',
        'เหยื่อยางนุ่ม Berkley PowerBait' => 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400&h=300&fit=crop',
        'เหยื่อปลอมแปปเปอร์ Yo-Zuri 3D Popper' => 'https://images.unsplash.com/photo-1544551763-77ef2d0cfc6c?w=400&h=300&fit=crop',
        
        // Lines
        'สายเอ็นฟลูออโรคาร์บอน Seaguar 0.35mm' => 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400&h=300&fit=crop',
        'สายเอ็นถัก PowerPro 20lb' => 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400&h=300&fit=crop',
        
        // Hooks
        'เบ็ดตกปลา Owner 5130 ขนาด 1/0' => 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400&h=300&fit=crop',
        'เบ็ดตกปลาคาร์ฟ Fox Edges Curve Shank' => 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400&h=300&fit=crop',
        
        // Nets
        'ตาข่ายตักปลา แบบพับได้ 60cm' => 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400&h=300&fit=crop',
        'ตาข่ายลอยปลา Keep Net 3m' => 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400&h=300&fit=crop',
        
        // Accessories
        'ที่วางคันเบ็ด Rod Pod 3 ขา' => 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400&h=300&fit=crop',
        'เครื่องชั่งปลาดิจิตอล 40kg' => 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400&h=300&fit=crop',
        'ไฟฉาย LED กันน้ำ' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&h=300&fit=crop',
        
        // Storage
        'กล่องเก็บเหยื่อ Plano 3700' => 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400&h=300&fit=crop',
        'กระเป๋าตกปลา Shimano Tribal' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=400&h=300&fit=crop',
        
        // Clothing
        'เสื้อตกปลา UV Protection' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=400&h=300&fit=crop',
        'หมวกตกปลา กันแดด' => 'https://images.unsplash.com/photo-1521369909029-2afed882baee?w=400&h=300&fit=crop',
        
        // Safety
        'เสื้อชูชีพ แบบเป่าลม' => 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400&h=300&fit=crop',
        'นกหวีดกันฉุกเฉิน' => 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400&h=300&fit=crop'
    ];
    
    // Get all products
    $stmt = $pdo->query("SELECT id, name FROM products");
    $products = $stmt->fetchAll();
    
    $updated = 0;
    
    foreach ($products as $product) {
        $imageUrl = null;
        
        // Try to find exact match first
        if (isset($imageMap[$product['name']])) {
            $imageUrl = $imageMap[$product['name']];
        } else {
            // Try to find partial matches for generic images
            $name = $product['name'];
            
            if (strpos($name, 'คันเบ็ด') !== false) {
                $imageUrl = 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=400&h=300&fit=crop';
            } elseif (strpos($name, 'รอก') !== false) {
                $imageUrl = 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400&h=300&fit=crop';
            } elseif (strpos($name, 'เหยื่อ') !== false) {
                $imageUrl = 'https://images.unsplash.com/photo-1544551763-77ef2d0cfc6c?w=400&h=300&fit=crop';
            } elseif (strpos($name, 'สายเอ็น') !== false || strpos($name, 'เบ็ด') !== false) {
                $imageUrl = 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400&h=300&fit=crop';
            } elseif (strpos($name, 'ตาข่าย') !== false) {
                $imageUrl = 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400&h=300&fit=crop';
            } elseif (strpos($name, 'กล่อง') !== false || strpos($name, 'กระเป๋า') !== false) {
                $imageUrl = 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=400&h=300&fit=crop';
            } elseif (strpos($name, 'เสื้อ') !== false) {
                $imageUrl = 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=400&h=300&fit=crop';
            } elseif (strpos($name, 'หมวก') !== false) {
                $imageUrl = 'https://images.unsplash.com/photo-1521369909029-2afed882baee?w=400&h=300&fit=crop';
            } elseif (strpos($name, 'ไฟฉาย') !== false) {
                $imageUrl = 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&h=300&fit=crop';
            } else {
                // Default fishing equipment image
                $imageUrl = 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400&h=300&fit=crop';
            }
        }
        
        if ($imageUrl) {
            $stmt = $pdo->prepare("UPDATE products SET image = ? WHERE id = ?");
            $stmt->execute([$imageUrl, $product['id']]);
            $updated++;
            echo "Updated image for: {$product['name']}\n";
        }
    }
    
    echo "\nSuccessfully updated images for {$updated} products!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>