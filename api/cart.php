<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'clear':
            $_SESSION['cart'] = [];
            echo json_encode(['success' => true, 'message' => 'ล้างตะกร้าเรียบร้อยแล้ว']);
            break;
            
        case 'update':
            $product_id = (int)($_POST['product_id'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 0);
            
            if ($product_id > 0) {
                if ($quantity <= 0) {
                    unset($_SESSION['cart'][$product_id]);
                } else {
                    $_SESSION['cart'][$product_id] = $quantity;
                }
                echo json_encode(['success' => true, 'message' => 'อัพเดตตะกร้าเรียบร้อยแล้ว']);
            } else {
                echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
            }
            break;
            
        case 'add':
            $product_id = (int)($_POST['product_id'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 1);
            
            if ($product_id > 0) {
                if (!isset($_SESSION['cart'])) {
                    $_SESSION['cart'] = [];
                }
                
                if (isset($_SESSION['cart'][$product_id])) {
                    $_SESSION['cart'][$product_id] += $quantity;
                } else {
                    $_SESSION['cart'][$product_id] = $quantity;
                }
                
                echo json_encode(['success' => true, 'message' => 'เพิ่มสินค้าลงตะกร้าเรียบร้อยแล้ว']);
            } else {
                echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'การกระทำไม่ถูกต้อง']);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'count':
            $count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
            echo json_encode(['count' => $count]);
            break;
            
        case 'items':
            $pdo = getConnection();
            $cart_items = [];
            $total = 0;
            
            if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
                $product_ids = array_keys($_SESSION['cart']);
                $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
                
                $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
                $stmt->execute($product_ids);
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($products as $product) {
                    $quantity = $_SESSION['cart'][$product['id']];
                    $subtotal = $product['price'] * $quantity;
                    $total += $subtotal;
                    
                    $cart_items[] = [
                        'id' => $product['id'],
                        'name' => $product['name'],
                        'price' => $product['price'],
                        'quantity' => $quantity,
                        'subtotal' => $subtotal,
                        'image' => $product['image'] ? 'uploads/' . $product['image'] : null
                    ];
                }
            }
            
            echo json_encode(['items' => $cart_items, 'total' => $total]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'การกระทำไม่ถูกต้อง']);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'update':
            $product_id = (int)($input['product_id'] ?? 0);
            $quantity = (int)($input['quantity'] ?? 0);
            
            if ($product_id > 0) {
                if ($quantity <= 0) {
                    unset($_SESSION['cart'][$product_id]);
                } else {
                    $_SESSION['cart'][$product_id] = $quantity;
                }
                
                $count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
                echo json_encode(['success' => true, 'count' => $count, 'message' => 'อัพเดตตะกร้าเรียบร้อยแล้ว']);
            } else {
                echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'การกระทำไม่ถูกต้อง']);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'remove':
            $product_id = (int)($_GET['product_id'] ?? 0);
            
            if ($product_id > 0 && isset($_SESSION['cart'][$product_id])) {
                unset($_SESSION['cart'][$product_id]);
                $count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
                echo json_encode(['success' => true, 'count' => $count, 'message' => 'ลบสินค้าจากตะกร้าเรียบร้อยแล้ว']);
            } else {
                echo json_encode(['success' => false, 'message' => 'ไม่พบสินค้าในตะกร้า']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'การกระทำไม่ถูกต้อง']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>