<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Login required']);
            exit;
        }
        
        if ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!validateCSRFToken($input['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
                exit;
            }
            
            $cartItems = getCartItems();
            if (empty($cartItems)) {
                echo json_encode(['success' => false, 'message' => 'Cart is empty']);
                exit;
            }
            
            // Calculate totals
            $subtotal = getCartTotal();
            $taxRate = 0.08; // 8% tax
            $taxAmount = $subtotal * $taxRate;
            $shippingAmount = $subtotal >= 75 ? 0 : 9.99; // Free shipping over $75
            $discountAmount = 0;
            
            // Apply promotion if provided
            if (!empty($input['promotion_code'])) {
                $promotionResult = applyPromotion($input['promotion_code'], $subtotal);
                if ($promotionResult) {
                    $discountAmount = $promotionResult['discount'];
                    if ($promotionResult['promotion']['type'] === 'free_shipping') {
                        $shippingAmount = 0;
                    }
                }
            }
            
            $totalAmount = $subtotal + $taxAmount + $shippingAmount - $discountAmount;
            
            $orderData = [
                'user_id' => $_SESSION['user_id'],
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'shipping_amount' => $shippingAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'payment_method' => $input['payment_method'] ?? 'credit_card',
                'shipping_address' => $input['shipping_address'] ?? [],
                'billing_address' => $input['billing_address'] ?? [],
                'notes' => $input['notes'] ?? ''
            ];
            
            $orderId = createOrder($orderData);
            
            if ($orderId) {
                // Send notification
                sendNotification(
                    $_SESSION['user_id'],
                    'order_created',
                    'Order Confirmed',
                    'Your order has been placed successfully. Order #' . $orderId,
                    ['order_id' => $orderId]
                );
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Order created successfully',
                    'order_id' => $orderId
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create order']);
            }
        }
        break;
        
    case 'list':
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Login required']);
            exit;
        }
        
        $orders = getUserOrders($_SESSION['user_id']);
        echo json_encode(['success' => true, 'orders' => $orders]);
        break;
        
    case 'get':
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Login required']);
            exit;
        }
        
        $orderId = (int)($_GET['id'] ?? 0);
        if ($orderId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
            exit;
        }
        
        $order = getOrder($orderId);
        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            exit;
        }
        
        // Check if user owns this order or is admin
        if ($order['user_id'] != $_SESSION['user_id'] && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
        
        $orderItems = getOrderItems($orderId);
        $order['items'] = $orderItems;
        
        echo json_encode(['success' => true, 'order' => $order]);
        break;
        
    case 'validate_promotion':
        if ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $code = $input['code'] ?? '';
            $orderAmount = (float)($input['order_amount'] ?? 0);
            
            if (empty($code)) {
                echo json_encode(['success' => false, 'message' => 'Promotion code required']);
                exit;
            }
            
            $result = applyPromotion($code, $orderAmount);
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'promotion' => $result['promotion'],
                    'discount' => $result['discount']
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid or expired promotion code']);
            }
        }
        break;
        
    case 'receipt':
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Login required']);
            exit;
        }
        
        $orderId = (int)($_GET['id'] ?? 0);
        if ($orderId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
            exit;
        }
        
        $order = getOrder($orderId);
        if (!$order || $order['user_id'] != $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            exit;
        }
        
        $orderItems = getOrderItems($orderId);
        
        // Generate receipt HTML
        $receiptHtml = generateReceiptHTML($order, $orderItems);
        
        echo json_encode([
            'success' => true,
            'receipt' => $receiptHtml,
            'order_number' => $order['order_number']
        ]);
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Action not found']);
}

function generateReceiptHTML($order, $orderItems) {
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <title>Receipt - Order ' . htmlspecialchars($order['order_number']) . '</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 20px; }
            .order-info { margin-bottom: 20px; }
            .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            .items-table th, .items-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            .items-table th { background-color: #f2f2f2; }
            .totals { text-align: right; }
            .footer { margin-top: 30px; text-align: center; color: #666; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Fishing Gear Store</h1>
            <h2>Order Receipt</h2>
        </div>
        
        <div class="order-info">
            <p><strong>Order Number:</strong> ' . htmlspecialchars($order['order_number']) . '</p>
            <p><strong>Order Date:</strong> ' . date('F j, Y g:i A', strtotime($order['created_at'])) . '</p>
            <p><strong>Status:</strong> ' . ucfirst($order['status']) . '</p>
        </div>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($orderItems as $item) {
        $html .= '
                <tr>
                    <td>' . htmlspecialchars($item['product_name']) . '</td>
                    <td>' . htmlspecialchars($item['product_sku']) . '</td>
                    <td>' . $item['quantity'] . '</td>
                    <td>$' . number_format($item['price'], 2) . '</td>
                    <td>$' . number_format($item['total'], 2) . '</td>
                </tr>';
    }
    
    $html .= '
            </tbody>
        </table>
        
        <div class="totals">
            <p><strong>Subtotal:</strong> $' . number_format($order['subtotal'], 2) . '</p>
            <p><strong>Tax:</strong> $' . number_format($order['tax_amount'], 2) . '</p>
            <p><strong>Shipping:</strong> $' . number_format($order['shipping_amount'], 2) . '</p>';
    
    if ($order['discount_amount'] > 0) {
        $html .= '<p><strong>Discount:</strong> -$' . number_format($order['discount_amount'], 2) . '</p>';
    }
    
    $html .= '
            <p><strong>Total:</strong> $' . number_format($order['total_amount'], 2) . '</p>
        </div>
        
        <div class="footer">
            <p>Thank you for your business!</p>
            <p>Fishing Gear Store - Premium Fishing Equipment</p>
        </div>
    </body>
    </html>';
    
    return $html;
}
?>