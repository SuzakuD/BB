<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_GET['q']) || empty(trim($_GET['q']))) {
    echo json_encode([]);
    exit;
}

$query = trim($_GET['q']);
$pdo = getConnection();

try {
    // Search for products containing the query
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.price, p.image, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.name LIKE :query OR p.description LIKE :query
        ORDER BY 
            CASE 
                WHEN p.name LIKE :exact_query THEN 1
                WHEN p.name LIKE :start_query THEN 2
                ELSE 3
            END,
            p.name
        LIMIT 10
    ");
    
    $searchTerm = '%' . $query . '%';
    $exactQuery = $query . '%';
    $startQuery = $query . '%';
    
    $stmt->execute([
        ':query' => $searchTerm,
        ':exact_query' => $exactQuery,
        ':start_query' => $startQuery
    ]);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format results for auto-suggest
    $suggestions = [];
    foreach ($results as $product) {
        $suggestions[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => number_format($product['price'], 2),
            'category' => $product['category_name'],
            'image' => $product['image'] ? 'uploads/' . $product['image'] : 'public/images/no-image.png'
        ];
    }
    
    echo json_encode($suggestions);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Search failed']);
}
?>