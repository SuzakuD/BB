<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$cart = $input['cart'] ?? [];

if (empty($cart)) {
    echo json_encode(['items' => [], 'subtotal' => 0, 'tax' => 0, 'total' => 0]);
    exit;
}

$db = getDB();

try {
    $productIds = array_keys($cart);
    $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
    
    $stmt = $db->prepare("SELECT id, name, price, image, stock FROM products WHERE id IN ($placeholders)");
    $stmt->execute($productIds);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $items = [];
    $subtotal = 0;
    
    foreach ($products as $product) {
        $quantity = $cart[$product['id']];
        $itemTotal = $product['price'] * $quantity;
        $subtotal += $itemTotal;
        
        $items[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'image' => $product['image'],
            'quantity' => $quantity,
            'stock' => $product['stock']
        ];
    }
    
    $tax = $subtotal * 0.08; // 8% tax
    $total = $subtotal + $tax;
    
    echo json_encode([
        'items' => $items,
        'subtotal' => $subtotal,
        'tax' => $tax,
        'total' => $total
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load cart']);
}
