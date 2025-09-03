<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['q']) || empty($_GET['q'])) {
    echo json_encode([]);
    exit;
}

$query = $_GET['q'];
$db = getDB();

try {
    $stmt = $db->prepare("
        SELECT id, name, price, image, description 
        FROM products 
        WHERE name LIKE ? OR description LIKE ? 
        LIMIT 10
    ");
    
    $searchTerm = "%{$query}%";
    $stmt->execute([$searchTerm, $searchTerm]);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($results);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Search failed']);
}
