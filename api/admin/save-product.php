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

$db = getDB();

try {
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $category_id = intval($_POST['category_id'] ?? 0);
    
    if (empty($name) || $price <= 0 || $stock < 0) {
        echo json_encode(['error' => 'Invalid input data']);
        exit;
    }
    
    // Handle image upload
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../public/images/products/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            $image_path = 'public/images/products/' . $file_name;
        }
    }
    
    if ($id) {
        // Update existing product
        $sql = "UPDATE products SET name = ?, description = ?, price = ?, stock = ?, category_id = ?";
        $params = [$name, $description, $price, $stock, $category_id];
        
        if ($image_path) {
            $sql .= ", image = ?";
            $params[] = $image_path;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
    } else {
        // Insert new product
        $stmt = $db->prepare("
            INSERT INTO products (name, description, price, stock, category_id, image) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$name, $description, $price, $stock, $category_id, $image_path]);
        
        echo json_encode(['success' => true, 'message' => 'Product added successfully']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save product: ' . $e->getMessage()]);
}
