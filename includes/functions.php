<?php
require_once 'config/database.php';

// Security functions
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// User functions
function loginUser($email, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? AND role != 'banned'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && verifyPassword($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        return true;
    }
    return false;
}

function registerUser($name, $email, $password) {
    global $pdo;
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return false; // Email already exists
    }
    
    $hashedPassword = hashPassword($password);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    return $stmt->execute([$name, $email, $hashedPassword]);
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Product functions
function getProducts($categoryId = null, $search = null, $limit = null, $offset = 0) {
    global $pdo;
    
    $sql = "SELECT p.*, c.name as category_name FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.is_active = 1";
    $params = [];
    
    if ($categoryId) {
        $sql .= " AND p.category_id = ?";
        $params[] = $categoryId;
    }
    
    if ($search) {
        $sql .= " AND (p.name LIKE ? OR p.description LIKE ? OR p.brand LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $sql .= " ORDER BY p.is_featured DESC, p.created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getProduct($id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p 
                          LEFT JOIN categories c ON p.category_id = c.id 
                          WHERE p.id = ? AND p.is_active = 1");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getCategories() {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
    $stmt->execute();
    return $stmt->fetchAll();
}

function getCategory($id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ? AND is_active = 1");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Cart functions
function addToCart($productId, $quantity = 1) {
    global $pdo;
    
    $userId = getCurrentUserId();
    $sessionId = session_id();
    
    // Get product details
    $product = getProduct($productId);
    if (!$product) {
        return false;
    }
    
    $price = $product['sale_price'] ?: $product['price'];
    
    // Check if item already exists in cart
    if ($userId) {
        $stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        $existingItem = $stmt->fetch();
        
        if ($existingItem) {
            $newQuantity = $existingItem['quantity'] + $quantity;
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ?, price = ? WHERE id = ?");
            return $stmt->execute([$newQuantity, $price, $existingItem['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            return $stmt->execute([$userId, $productId, $quantity, $price]);
        }
    } else {
        // Guest cart using session
        $stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE session_id = ? AND product_id = ?");
        $stmt->execute([$sessionId, $productId]);
        $existingItem = $stmt->fetch();
        
        if ($existingItem) {
            $newQuantity = $existingItem['quantity'] + $quantity;
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ?, price = ? WHERE id = ?");
            return $stmt->execute([$newQuantity, $price, $existingItem['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO cart (session_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            return $stmt->execute([$sessionId, $productId, $quantity, $price]);
        }
    }
}

function getCartItems() {
    global $pdo;
    
    $userId = getCurrentUserId();
    $sessionId = session_id();
    
    if ($userId) {
        $stmt = $pdo->prepare("SELECT c.*, p.name, p.images, p.stock_quantity FROM cart c 
                              JOIN products p ON c.product_id = p.id 
                              WHERE c.user_id = ? ORDER BY c.created_at DESC");
        $stmt->execute([$userId]);
    } else {
        $stmt = $pdo->prepare("SELECT c.*, p.name, p.images, p.stock_quantity FROM cart c 
                              JOIN products p ON c.product_id = p.id 
                              WHERE c.session_id = ? ORDER BY c.created_at DESC");
        $stmt->execute([$sessionId]);
    }
    
    return $stmt->fetchAll();
}

function updateCartQuantity($cartId, $quantity) {
    global $pdo;
    
    $userId = getCurrentUserId();
    $sessionId = session_id();
    
    if ($quantity <= 0) {
        return removeFromCart($cartId);
    }
    
    if ($userId) {
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
        return $stmt->execute([$quantity, $cartId, $userId]);
    } else {
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND session_id = ?");
        return $stmt->execute([$quantity, $cartId, $sessionId]);
    }
}

function removeFromCart($cartId) {
    global $pdo;
    
    $userId = getCurrentUserId();
    $sessionId = session_id();
    
    if ($userId) {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        return $stmt->execute([$cartId, $userId]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND session_id = ?");
        return $stmt->execute([$cartId, $sessionId]);
    }
}

function clearCart() {
    global $pdo;
    
    $userId = getCurrentUserId();
    $sessionId = session_id();
    
    if ($userId) {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        return $stmt->execute([$userId]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE session_id = ?");
        return $stmt->execute([$sessionId]);
    }
}

function getCartTotal() {
    $items = getCartItems();
    $total = 0;
    
    foreach ($items as $item) {
        $total += $item['quantity'] * $item['price'];
    }
    
    return $total;
}

function getCartCount() {
    $items = getCartItems();
    $count = 0;
    
    foreach ($items as $item) {
        $count += $item['quantity'];
    }
    
    return $count;
}

// Order functions
function createOrder($orderData) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        $orderNumber = 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $stmt = $pdo->prepare("INSERT INTO orders (order_number, user_id, subtotal, tax_amount, shipping_amount, 
                              discount_amount, total_amount, payment_method, shipping_address, billing_address, notes) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $orderNumber,
            $orderData['user_id'],
            $orderData['subtotal'],
            $orderData['tax_amount'],
            $orderData['shipping_amount'],
            $orderData['discount_amount'],
            $orderData['total_amount'],
            $orderData['payment_method'],
            json_encode($orderData['shipping_address']),
            json_encode($orderData['billing_address']),
            $orderData['notes'] ?? ''
        ]);
        
        $orderId = $pdo->lastInsertId();
        
        // Add order items
        $cartItems = getCartItems();
        foreach ($cartItems as $item) {
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, product_sku, 
                                  quantity, price, total) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $orderId,
                $item['product_id'],
                $item['name'],
                'SKU-' . $item['product_id'], // You might want to add SKU to products table
                $item['quantity'],
                $item['price'],
                $item['quantity'] * $item['price']
            ]);
        }
        
        // Clear cart
        clearCart();
        
        $pdo->commit();
        return $orderId;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

function getUserOrders($userId) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getOrder($orderId) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    return $stmt->fetch();
}

function getOrderItems($orderId) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt->execute([$orderId]);
    return $stmt->fetchAll();
}

// Promotion functions
function validatePromotionCode($code) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM promotions WHERE code = ? AND is_active = 1 
                          AND start_date <= NOW() AND end_date >= NOW()");
    $stmt->execute([$code]);
    return $stmt->fetch();
}

function applyPromotion($code, $orderAmount) {
    $promotion = validatePromotionCode($code);
    
    if (!$promotion) {
        return false;
    }
    
    if ($orderAmount < $promotion['min_order_amount']) {
        return false;
    }
    
    $discount = 0;
    
    switch ($promotion['type']) {
        case 'percentage':
            $discount = ($orderAmount * $promotion['value']) / 100;
            if ($promotion['max_discount_amount'] && $discount > $promotion['max_discount_amount']) {
                $discount = $promotion['max_discount_amount'];
            }
            break;
        case 'fixed':
            $discount = $promotion['value'];
            break;
        case 'free_shipping':
            $discount = 0; // This would be handled separately for shipping
            break;
    }
    
    return [
        'promotion' => $promotion,
        'discount' => $discount
    ];
}

// Utility functions
function formatPrice($price) {
    return '$' . number_format($price, 2);
}

function generateOrderNumber() {
    return 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function sendNotification($userId, $type, $title, $message, $data = null) {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, data) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$userId, $type, $title, $message, json_encode($data)]);
}

function getUserNotifications($userId, $limit = 10) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll();
}

function markNotificationAsRead($notificationId, $userId) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    return $stmt->execute([$notificationId, $userId]);
}
?>