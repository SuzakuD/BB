<?php
require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $pdo = getDB();
    
    switch ($method) {
        case 'GET':
            handleGet($pdo, $action);
            break;
        case 'POST':
            handlePost($pdo, $action);
            break;
        case 'PUT':
            handlePut($pdo, $action);
            break;
        default:
            throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleGet($pdo, $action) {
    switch ($action) {
        case 'list':
            getOrders($pdo);
            break;
        case 'detail':
            getOrderDetail($pdo);
            break;
        case 'user':
            getUserOrders($pdo);
            break;
        case 'receipt':
            getReceipt($pdo);
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function handlePost($pdo, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'create':
            createOrder($pdo, $input);
            break;
        case 'checkout':
            processCheckout($pdo, $input);
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function handlePut($pdo, $action) {
    requireAdmin();
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'status':
            updateOrderStatus($pdo, $input);
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function getOrders($pdo) {
    requireAdmin();
    
    $page = $_GET['page'] ?? 1;
    $limit = $_GET['limit'] ?? 20;
    $offset = ($page - 1) * $limit;
    
    $sql = "SELECT o.*, u.username 
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            ORDER BY o.created_at DESC 
            LIMIT ? OFFSET ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$limit, $offset]);
    $orders = $stmt->fetchAll();
    
    // Get total count
    $countStmt = $pdo->query("SELECT COUNT(*) FROM orders");
    $total = $countStmt->fetchColumn();
    
    echo json_encode([
        'orders' => $orders,
        'total' => $total,
        'page' => $page,
        'pages' => ceil($total / $limit)
    ]);
}

function getOrderDetail($pdo) {
    $orderId = $_GET['id'] ?? 0;
    
    if (!$orderId) {
        throw new Exception('ไม่พบรหัสคำสั่งซื้อ');
    }
    
    // Check permission
    if (!isAdmin()) {
        requireLogin();
        $stmt = $pdo->prepare("SELECT user_id FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $orderUserId = $stmt->fetchColumn();
        
        if ($orderUserId != $_SESSION['user_id']) {
            throw new Exception('ไม่มีสิทธิ์เข้าถึงคำสั่งซื้อนี้');
        }
    }
    
    // Get order details
    $sql = "SELECT o.*, u.username 
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            WHERE o.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if (!$order) {
        throw new Exception('ไม่พบคำสั่งซื้อ');
    }
    
    // Get order items
    $sql = "SELECT oi.*, p.name as product_name, p.image 
            FROM order_items oi 
            LEFT JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$orderId]);
    $items = $stmt->fetchAll();
    
    $order['items'] = $items;
    
    echo json_encode(['order' => $order]);
}

function getUserOrders($pdo) {
    requireLogin();
    
    $page = $_GET['page'] ?? 1;
    $limit = $_GET['limit'] ?? 10;
    $offset = ($page - 1) * $limit;
    
    $sql = "SELECT * FROM orders 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['user_id'], $limit, $offset]);
    $orders = $stmt->fetchAll();
    
    // Get total count
    $countSql = "SELECT COUNT(*) FROM orders WHERE user_id = ?";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute([$_SESSION['user_id']]);
    $total = $countStmt->fetchColumn();
    
    echo json_encode([
        'orders' => $orders,
        'total' => $total,
        'page' => $page,
        'pages' => ceil($total / $limit)
    ]);
}

function processCheckout($pdo, $input) {
    requireLogin();
    
    $cart = $_SESSION['cart'] ?? [];
    
    if (empty($cart)) {
        throw new Exception('ตะกร้าสินค้าว่าง');
    }
    
    $pdo->beginTransaction();
    
    try {
        // Calculate total and validate stock
        $total = 0;
        $orderItems = [];
        
        $placeholders = str_repeat('?,', count($cart) - 1) . '?';
        $sql = "SELECT * FROM products WHERE id IN ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_keys($cart));
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($products as $product) {
            $quantity = $cart[$product['id']];
            
            if ($quantity > $product['stock']) {
                throw new Exception("สินค้า {$product['name']} มีในสต็อกไม่เพียงพอ");
            }
            
            $itemTotal = $product['price'] * $quantity;
            $total += $itemTotal;
            
            $orderItems[] = [
                'product_id' => $product['id'],
                'quantity' => $quantity,
                'price' => $product['price'],
                'total' => $itemTotal,
                'name' => $product['name'],
                'image' => $product['image']
            ];
        }
        
        // Apply promotion if provided
        $promotionCode = $input['promotion_code'] ?? '';
        $discount = 0;
        
        if (!empty($promotionCode)) {
            $stmt = $pdo->prepare("SELECT * FROM promotions WHERE code = ? AND expire_date > datetime('now')");
            $stmt->execute([$promotionCode]);
            $promotion = $stmt->fetch();
            
            if ($promotion) {
                $discount = ($total * $promotion['discount']) / 100;
                $total -= $discount;
            }
        }
        
        // Create order
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total, status) VALUES (?, ?, 'confirmed')");
        $stmt->execute([$_SESSION['user_id'], $total]);
        $orderId = $pdo->lastInsertId();
        
        // Create order items and update stock
        foreach ($orderItems as $item) {
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
            
            // Update product stock
            $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stmt->execute([$item['quantity'], $item['product_id']]);
        }
        
        // Generate receipt
        $receiptHtml = generateReceiptHtml($orderId, $orderItems, $total, $discount);
        $stmt = $pdo->prepare("INSERT INTO receipts (order_id, html) VALUES (?, ?)");
        $stmt->execute([$orderId, $receiptHtml]);
        
        $pdo->commit();
        
        // Clear cart
        $_SESSION['cart'] = [];
        
        echo json_encode([
            'success' => true,
            'message' => 'สั่งซื้อสำเร็จ',
            'order_id' => $orderId,
            'total' => $total
        ]);
        
    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }
}

function updateOrderStatus($pdo, $input) {
    $orderId = $input['order_id'] ?? 0;
    $status = $input['status'] ?? '';
    
    if (!$orderId || empty($status)) {
        throw new Exception('กรุณากรอกข้อมูลให้ครบถ้วน');
    }
    
    $validStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
    
    if (!in_array($status, $validStatuses)) {
        throw new Exception('สถานะไม่ถูกต้อง');
    }
    
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $orderId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'อัปเดตสถานะคำสั่งซื้อสำเร็จ'
    ]);
}

function getReceipt($pdo) {
    $orderId = $_GET['order_id'] ?? 0;
    
    if (!$orderId) {
        throw new Exception('ไม่พบรหัสคำสั่งซื้อ');
    }
    
    // Check permission
    if (!isAdmin()) {
        requireLogin();
        $stmt = $pdo->prepare("SELECT user_id FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $orderUserId = $stmt->fetchColumn();
        
        if ($orderUserId != $_SESSION['user_id']) {
            throw new Exception('ไม่มีสิทธิ์เข้าถึงใบเสร็จนี้');
        }
    }
    
    $stmt = $pdo->prepare("SELECT html FROM receipts WHERE order_id = ?");
    $stmt->execute([$orderId]);
    $receiptHtml = $stmt->fetchColumn();
    
    if (!$receiptHtml) {
        throw new Exception('ไม่พบใบเสร็จ');
    }
    
    echo json_encode(['receipt_html' => $receiptHtml]);
}

function generateReceiptHtml($orderId, $items, $total, $discount = 0) {
    $date = date('d/m/Y H:i:s');
    $subtotal = $total + $discount;
    
    $html = "
    <div class='receipt'>
        <div class='receipt-header'>
            <h2>ร้านอุปกรณ์ตกปลา</h2>
            <p>ใบเสร็จรับเงิน</p>
            <p>เลขที่: #{$orderId}</p>
            <p>วันที่: {$date}</p>
        </div>
        
        <table class='table table-bordered'>
            <thead>
                <tr>
                    <th>สินค้า</th>
                    <th>จำนวน</th>
                    <th>ราคา</th>
                    <th>รวม</th>
                </tr>
            </thead>
            <tbody>";
    
    foreach ($items as $item) {
        $html .= "
                <tr>
                    <td>{$item['name']}</td>
                    <td>{$item['quantity']}</td>
                    <td>" . number_format($item['price'], 2) . " บาท</td>
                    <td>" . number_format($item['total'], 2) . " บาท</td>
                </tr>";
    }
    
    $html .= "
            </tbody>
        </table>
        
        <div class='receipt-summary'>
            <div class='d-flex justify-content-between'>
                <span>ยอดรวม:</span>
                <span>" . number_format($subtotal, 2) . " บาท</span>
            </div>";
    
    if ($discount > 0) {
        $html .= "
            <div class='d-flex justify-content-between'>
                <span>ส่วนลด:</span>
                <span>-" . number_format($discount, 2) . " บาท</span>
            </div>";
    }
    
    $html .= "
            <div class='receipt-total d-flex justify-content-between'>
                <span>ยอดสุทธิ:</span>
                <span>" . number_format($total, 2) . " บาท</span>
            </div>
        </div>
        
        <div class='text-center mt-4'>
            <p>ขอบคุณที่ใช้บริการ</p>
        </div>
    </div>";
    
    return $html;
}
?>