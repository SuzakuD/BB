<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check if user is admin
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'dashboard':
        // Get dashboard statistics
        $stats = [];
        
        // Total users
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
        $stmt->execute();
        $stats['total_users'] = $stmt->fetch()['count'];
        
        // Total products
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products");
        $stmt->execute();
        $stats['total_products'] = $stmt->fetch()['count'];
        
        // Total orders
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders");
        $stmt->execute();
        $stats['total_orders'] = $stmt->fetch()['count'];
        
        // Total revenue
        $stmt = $pdo->prepare("SELECT SUM(total_amount) as total FROM orders WHERE status != 'cancelled'");
        $stmt->execute();
        $stats['total_revenue'] = $stmt->fetch()['total'] ?? 0;
        
        // Recent orders
        $stmt = $pdo->prepare("SELECT o.*, u.name as user_name FROM orders o 
                              LEFT JOIN users u ON o.user_id = u.id 
                              ORDER BY o.created_at DESC LIMIT 10");
        $stmt->execute();
        $stats['recent_orders'] = $stmt->fetchAll();
        
        // Low stock products
        $stmt = $pdo->prepare("SELECT * FROM products WHERE stock_quantity <= min_stock_level AND is_active = 1");
        $stmt->execute();
        $stats['low_stock_products'] = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'stats' => $stats]);
        break;
        
    case 'products':
        switch ($method) {
            case 'GET':
                $page = (int)($_GET['page'] ?? 1);
                $limit = (int)($_GET['limit'] ?? 20);
                $offset = ($page - 1) * $limit;
                
                $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p 
                                      LEFT JOIN categories c ON p.category_id = c.id 
                                      ORDER BY p.created_at DESC LIMIT ? OFFSET ?");
                $stmt->execute([$limit, $offset]);
                $products = $stmt->fetchAll();
                
                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products");
                $stmt->execute();
                $total = $stmt->fetch()['total'];
                
                echo json_encode([
                    'success' => true,
                    'products' => $products,
                    'pagination' => [
                        'current_page' => $page,
                        'total_pages' => ceil($total / $limit),
                        'total_items' => $total
                    ]
                ]);
                break;
                
            case 'POST':
                $input = json_decode(file_get_contents('php://input'), true);
                
                if (!validateCSRFToken($input['csrf_token'] ?? '')) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
                    exit;
                }
                
                $name = sanitizeInput($input['name'] ?? '');
                $description = sanitizeInput($input['description'] ?? '');
                $price = (float)($input['price'] ?? 0);
                $salePrice = !empty($input['sale_price']) ? (float)$input['sale_price'] : null;
                $sku = sanitizeInput($input['sku'] ?? '');
                $stockQuantity = (int)($input['stock_quantity'] ?? 0);
                $categoryId = (int)($input['category_id'] ?? 0);
                $brand = sanitizeInput($input['brand'] ?? '');
                $isActive = (bool)($input['is_active'] ?? true);
                $isFeatured = (bool)($input['is_featured'] ?? false);
                
                if (empty($name) || $price <= 0 || empty($sku)) {
                    echo json_encode(['success' => false, 'message' => 'Required fields missing']);
                    exit;
                }
                
                $stmt = $pdo->prepare("INSERT INTO products (name, description, price, sale_price, sku, stock_quantity, 
                                      category_id, brand, is_active, is_featured) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                if ($stmt->execute([$name, $description, $price, $salePrice, $sku, $stockQuantity, 
                                   $categoryId, $brand, $isActive, $isFeatured])) {
                    echo json_encode(['success' => true, 'message' => 'Product created successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to create product']);
                }
                break;
                
            case 'PUT':
                $input = json_decode(file_get_contents('php://input'), true);
                $id = (int)($input['id'] ?? 0);
                
                if (!validateCSRFToken($input['csrf_token'] ?? '')) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
                    exit;
                }
                
                if ($id <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
                    exit;
                }
                
                $name = sanitizeInput($input['name'] ?? '');
                $description = sanitizeInput($input['description'] ?? '');
                $price = (float)($input['price'] ?? 0);
                $salePrice = !empty($input['sale_price']) ? (float)$input['sale_price'] : null;
                $sku = sanitizeInput($input['sku'] ?? '');
                $stockQuantity = (int)($input['stock_quantity'] ?? 0);
                $categoryId = (int)($input['category_id'] ?? 0);
                $brand = sanitizeInput($input['brand'] ?? '');
                $isActive = (bool)($input['is_active'] ?? true);
                $isFeatured = (bool)($input['is_featured'] ?? false);
                
                $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, sale_price = ?, 
                                      sku = ?, stock_quantity = ?, category_id = ?, brand = ?, is_active = ?, 
                                      is_featured = ? WHERE id = ?");
                
                if ($stmt->execute([$name, $description, $price, $salePrice, $sku, $stockQuantity, 
                                   $categoryId, $brand, $isActive, $isFeatured, $id])) {
                    echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update product']);
                }
                break;
                
            case 'DELETE':
                $id = (int)($_GET['id'] ?? 0);
                
                if ($id <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
                    exit;
                }
                
                $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                if ($stmt->execute([$id])) {
                    echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to delete product']);
                }
                break;
        }
        break;
        
    case 'categories':
        switch ($method) {
            case 'GET':
                $categories = getCategories();
                echo json_encode(['success' => true, 'categories' => $categories]);
                break;
                
            case 'POST':
                $input = json_decode(file_get_contents('php://input'), true);
                
                if (!validateCSRFToken($input['csrf_token'] ?? '')) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
                    exit;
                }
                
                $name = sanitizeInput($input['name'] ?? '');
                $description = sanitizeInput($input['description'] ?? '');
                $parentId = !empty($input['parent_id']) ? (int)$input['parent_id'] : null;
                
                if (empty($name)) {
                    echo json_encode(['success' => false, 'message' => 'Category name required']);
                    exit;
                }
                
                $stmt = $pdo->prepare("INSERT INTO categories (name, description, parent_id) VALUES (?, ?, ?)");
                if ($stmt->execute([$name, $description, $parentId])) {
                    echo json_encode(['success' => true, 'message' => 'Category created successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to create category']);
                }
                break;
        }
        break;
        
    case 'users':
        switch ($method) {
            case 'GET':
                $page = (int)($_GET['page'] ?? 1);
                $limit = (int)($_GET['limit'] ?? 20);
                $offset = ($page - 1) * $limit;
                
                $stmt = $pdo->prepare("SELECT id, name, email, role, created_at FROM users 
                                      WHERE role = 'user' ORDER BY created_at DESC LIMIT ? OFFSET ?");
                $stmt->execute([$limit, $offset]);
                $users = $stmt->fetchAll();
                
                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
                $stmt->execute();
                $total = $stmt->fetch()['total'];
                
                echo json_encode([
                    'success' => true,
                    'users' => $users,
                    'pagination' => [
                        'current_page' => $page,
                        'total_pages' => ceil($total / $limit),
                        'total_items' => $total
                    ]
                ]);
                break;
        }
        break;
        
    case 'orders':
        switch ($method) {
            case 'GET':
                $page = (int)($_GET['page'] ?? 1);
                $limit = (int)($_GET['limit'] ?? 20);
                $offset = ($page - 1) * $limit;
                
                $stmt = $pdo->prepare("SELECT o.*, u.name as user_name FROM orders o 
                                      LEFT JOIN users u ON o.user_id = u.id 
                                      ORDER BY o.created_at DESC LIMIT ? OFFSET ?");
                $stmt->execute([$limit, $offset]);
                $orders = $stmt->fetchAll();
                
                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders");
                $stmt->execute();
                $total = $stmt->fetch()['total'];
                
                echo json_encode([
                    'success' => true,
                    'orders' => $orders,
                    'pagination' => [
                        'current_page' => $page,
                        'total_pages' => ceil($total / $limit),
                        'total_items' => $total
                    ]
                ]);
                break;
                
            case 'PUT':
                $input = json_decode(file_get_contents('php://input'), true);
                $id = (int)($input['id'] ?? 0);
                $status = sanitizeInput($input['status'] ?? '');
                
                if (!validateCSRFToken($input['csrf_token'] ?? '')) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
                    exit;
                }
                
                if ($id <= 0 || !in_array($status, ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])) {
                    echo json_encode(['success' => false, 'message' => 'Invalid data']);
                    exit;
                }
                
                $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
                if ($stmt->execute([$status, $id])) {
                    echo json_encode(['success' => true, 'message' => 'Order status updated']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update order']);
                }
                break;
        }
        break;
        
    case 'promotions':
        switch ($method) {
            case 'GET':
                $stmt = $pdo->prepare("SELECT * FROM promotions ORDER BY created_at DESC");
                $stmt->execute();
                $promotions = $stmt->fetchAll();
                echo json_encode(['success' => true, 'promotions' => $promotions]);
                break;
                
            case 'POST':
                $input = json_decode(file_get_contents('php://input'), true);
                
                if (!validateCSRFToken($input['csrf_token'] ?? '')) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
                    exit;
                }
                
                $code = sanitizeInput($input['code'] ?? '');
                $name = sanitizeInput($input['name'] ?? '');
                $type = sanitizeInput($input['type'] ?? '');
                $value = (float)($input['value'] ?? 0);
                $minOrderAmount = (float)($input['min_order_amount'] ?? 0);
                $startDate = $input['start_date'] ?? '';
                $endDate = $input['end_date'] ?? '';
                
                if (empty($code) || empty($name) || empty($type) || $value <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Required fields missing']);
                    exit;
                }
                
                $stmt = $pdo->prepare("INSERT INTO promotions (code, name, type, value, min_order_amount, start_date, end_date) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$code, $name, $type, $value, $minOrderAmount, $startDate, $endDate])) {
                    echo json_encode(['success' => true, 'message' => 'Promotion created successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to create promotion']);
                }
                break;
        }
        break;
        
    case 'contact_messages':
        switch ($method) {
            case 'GET':
                $page = (int)($_GET['page'] ?? 1);
                $limit = (int)($_GET['limit'] ?? 20);
                $offset = ($page - 1) * $limit;
                
                $stmt = $pdo->prepare("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT ? OFFSET ?");
                $stmt->execute([$limit, $offset]);
                $messages = $stmt->fetchAll();
                
                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM contact_messages");
                $stmt->execute();
                $total = $stmt->fetch()['total'];
                
                echo json_encode([
                    'success' => true,
                    'messages' => $messages,
                    'pagination' => [
                        'current_page' => $page,
                        'total_pages' => ceil($total / $limit),
                        'total_items' => $total
                    ]
                ]);
                break;
        }
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Action not found']);
}
?>