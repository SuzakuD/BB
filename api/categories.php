<?php
require_once '../config/database.php';

header('Content-Type: application/json');

$db = getDB();

try {
    $stmt = $db->query("SELECT id, name FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($categories);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load categories']);
}
