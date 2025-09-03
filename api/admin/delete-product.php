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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$product_id = intval($input['id'] ?? 0);

if (!$product_id) {
    echo json_encode(['error' => 'Invalid product ID']);
    exit;
}

$db = getDB();

try {
    // Check if product exists
    $stmt = $db->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['error' => 'Product not found']);
        exit;
    }
    
    // Delete the product
    $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    
    // Delete the image file if it exists
    if ($product['image'] && file_exists('../../' . $product['image'])) {
        unlink('../../' . $product['image']);
    }
    
    echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to delete product: ' . $e->getMessage()]);
}
