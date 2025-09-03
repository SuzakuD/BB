<?php
require_once '../config/database.php';

header('Content-Type: application/json');

$db = getDB();

try {
    $whereConditions = [];
    $params = [];
    
    if (isset($_GET['category_id']) && !empty($_GET['category_id'])) {
        $whereConditions[] = "category_id = ?";
        $params[] = $_GET['category_id'];
    }
    
    if (isset($_GET['min_price']) && !empty($_GET['min_price'])) {
        $whereConditions[] = "price >= ?";
        $params[] = $_GET['min_price'];
    }
    
    if (isset($_GET['max_price']) && !empty($_GET['max_price'])) {
        $whereConditions[] = "price <= ?";
        $params[] = $_GET['max_price'];
    }
    
    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    }
    
    $sql = "SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            $whereClause 
            ORDER BY p.name";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($products);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load products']);
}
