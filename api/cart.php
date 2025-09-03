<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        if ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!validateCSRFToken($input['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
                exit;
            }
            
            $productId = (int)($input['product_id'] ?? 0);
            $quantity = (int)($input['quantity'] ?? 1);
            
            if ($productId <= 0 || $quantity <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
                exit;
            }
            
            // Check product stock
            $product = getProduct($productId);
            if (!$product) {
                echo json_encode(['success' => false, 'message' => 'Product not found']);
                exit;
            }
            
            if ($product['stock_quantity'] < $quantity) {
                echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
                exit;
            }
            
            if (addToCart($productId, $quantity)) {
                $cartCount = getCartCount();
                echo json_encode([
                    'success' => true, 
                    'message' => 'Product added to cart',
                    'cart_count' => $cartCount
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add product to cart']);
            }
        }
        break;
        
    case 'get':
        $items = getCartItems();
        $total = getCartTotal();
        $count = getCartCount();
        
        echo json_encode([
            'success' => true,
            'items' => $items,
            'total' => $total,
            'count' => $count
        ]);
        break;
        
    case 'update':
        if ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!validateCSRFToken($input['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
                exit;
            }
            
            $cartId = (int)($input['cart_id'] ?? 0);
            $quantity = (int)($input['quantity'] ?? 0);
            
            if ($cartId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid cart item']);
                exit;
            }
            
            if (updateCartQuantity($cartId, $quantity)) {
                $items = getCartItems();
                $total = getCartTotal();
                $count = getCartCount();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Cart updated',
                    'items' => $items,
                    'total' => $total,
                    'count' => $count
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
            }
        }
        break;
        
    case 'remove':
        if ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!validateCSRFToken($input['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
                exit;
            }
            
            $cartId = (int)($input['cart_id'] ?? 0);
            
            if ($cartId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid cart item']);
                exit;
            }
            
            if (removeFromCart($cartId)) {
                $items = getCartItems();
                $total = getCartTotal();
                $count = getCartCount();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Item removed from cart',
                    'items' => $items,
                    'total' => $total,
                    'count' => $count
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to remove item']);
            }
        }
        break;
        
    case 'clear':
        if ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!validateCSRFToken($input['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
                exit;
            }
            
            if (clearCart()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Cart cleared',
                    'items' => [],
                    'total' => 0,
                    'count' => 0
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to clear cart']);
            }
        }
        break;
        
    case 'count':
        $count = getCartCount();
        echo json_encode(['success' => true, 'count' => $count]);
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Action not found']);
}
?>