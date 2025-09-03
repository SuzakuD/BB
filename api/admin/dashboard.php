<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

$db = getDB();

try {
    // Get total products
    $stmt = $db->query("SELECT COUNT(*) as total FROM products");
    $totalProducts = $stmt->fetchColumn();
    
    // Get total orders
    $stmt = $db->query("SELECT COUNT(*) as total FROM orders");
    $totalOrders = $stmt->fetchColumn();
    
    // Get total users (excluding admin)
    $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role != 'admin'");
    $totalUsers = $stmt->fetchColumn();
    
    // Get total revenue
    $stmt = $db->query("SELECT SUM(grand_total) as total FROM orders WHERE status IN ('paid', 'shipped', 'delivered')");
    $totalRevenue = $stmt->fetchColumn() ?: 0;
    
    // Get recent orders
    $stmt = $db->query("
        SELECT o.*, u.username as customer_name 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get low stock products
    $stmt = $db->query("SELECT id, name, stock FROM products WHERE stock <= 10 ORDER BY stock ASC LIMIT 5");
    $lowStock = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'totalProducts' => $totalProducts,
        'totalOrders' => $totalOrders,
        'totalUsers' => $totalUsers,
        'totalRevenue' => number_format($totalRevenue, 2),
        'recentOrders' => $recentOrders,
        'lowStock' => $lowStock
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load dashboard data']);
}
