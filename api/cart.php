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
        case 'DELETE':
            handleDelete($pdo, $action);
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
        case 'items':
            getCartItems();
            break;
        case 'count':
            getCartCount();
            break;
        default:
            getCartItems();
    }
}

function handlePost($pdo, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'add':
            addToCart($pdo, $input);
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function handlePut($pdo, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'update':
            updateCartItem($pdo, $input);
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function handleDelete($pdo, $action) {
    switch ($action) {
        case 'remove':
            removeFromCart();
            break;
        case 'clear':
            clearCart();
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function getCartItems() {
    $cart = $_SESSION['cart'] ?? [];
    $items = [];
    $total = 0;
    
    if (!empty($cart)) {
        $pdo = getDB();
        $placeholders = str_repeat('?,', count($cart) - 1) . '?';
        $sql = "SELECT * FROM products WHERE id IN ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_keys($cart));
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($products as $product) {
            $quantity = $cart[$product['id']];
            $itemTotal = $product['price'] * $quantity;
            $total += $itemTotal;
            
            $items[] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'image' => $product['image'],
                'quantity' => $quantity,
                'total' => $itemTotal,
                'stock' => $product['stock']
            ];
        }
    }
    
    echo json_encode([
        'items' => $items,
        'total' => $total,
        'count' => array_sum($cart)
    ]);
}

function getCartCount() {
    $cart = $_SESSION['cart'] ?? [];
    $count = array_sum($cart);
    
    echo json_encode(['count' => $count]);
}

function addToCart($pdo, $input) {
    $productId = $input['product_id'] ?? 0;
    $quantity = $input['quantity'] ?? 1;
    
    if (!$productId || $quantity <= 0) {
        throw new Exception('ข้อมูลไม่ถูกต้อง');
    }
    
    // Check if product exists and has enough stock
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        throw new Exception('ไม่พบสินค้า');
    }
    
    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    $currentQuantity = $_SESSION['cart'][$productId] ?? 0;
    $newQuantity = $currentQuantity + $quantity;
    
    if ($newQuantity > $product['stock']) {
        throw new Exception('สินค้าในสต็อกไม่เพียงพอ');
    }
    
    $_SESSION['cart'][$productId] = $newQuantity;
    
    echo json_encode([
        'success' => true,
        'message' => 'เพิ่มสินค้าในตะกร้าสำเร็จ',
        'count' => array_sum($_SESSION['cart'])
    ]);
}

function updateCartItem($pdo, $input) {
    $productId = $input['product_id'] ?? 0;
    $quantity = $input['quantity'] ?? 0;
    
    if (!$productId) {
        throw new Exception('ไม่พบรหัสสินค้า');
    }
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    if ($quantity <= 0) {
        unset($_SESSION['cart'][$productId]);
    } else {
        // Check stock
        $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $stock = $stmt->fetchColumn();
        
        if ($quantity > $stock) {
            throw new Exception('สินค้าในสต็อกไม่เพียงพอ');
        }
        
        $_SESSION['cart'][$productId] = $quantity;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'อัปเดตตะกร้าสำเร็จ',
        'count' => array_sum($_SESSION['cart'])
    ]);
}

function removeFromCart() {
    $productId = $_GET['product_id'] ?? 0;
    
    if (!$productId) {
        throw new Exception('ไม่พบรหัสสินค้า');
    }
    
    if (isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'ลบสินค้าจากตะกร้าสำเร็จ',
        'count' => array_sum($_SESSION['cart'] ?? [])
    ]);
}

function clearCart() {
    $_SESSION['cart'] = [];
    
    echo json_encode([
        'success' => true,
        'message' => 'ล้างตะกร้าสำเร็จ',
        'count' => 0
    ]);
}
?>