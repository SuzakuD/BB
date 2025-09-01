<?php
require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $pdo = getDB();
    
    switch ($method) {
        case 'GET':
            handleGet($pdo, $action);
            break;
        case 'POST':
            handlePost($pdo, $action);
            break;
        case 'PUT':
            handlePut($pdo, $action);
            break;
        case 'DELETE':
            handleDelete($pdo, $action);
            break;
        default:
            throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleGet($pdo, $action) {
    switch ($action) {
        case 'list':
            getProducts($pdo);
            break;
        case 'categories':
            getCategories($pdo);
            break;
        case 'search':
            searchProducts($pdo);
            break;
        case 'category':
            getProductsByCategory($pdo);
            break;
        case 'detail':
            getProductDetail($pdo);
            break;
        default:
            getProducts($pdo);
    }
}

function handlePost($pdo, $action) {
    requireAdmin();
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'create':
            createProduct($pdo, $input);
            break;
        case 'category':
            createCategory($pdo, $input);
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function handlePut($pdo, $action) {
    requireAdmin();
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'update':
            updateProduct($pdo, $input);
            break;
        case 'category':
            updateCategory($pdo, $input);
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function handleDelete($pdo, $action) {
    requireAdmin();
    
    switch ($action) {
        case 'product':
            deleteProduct($pdo);
            break;
        case 'category':
            deleteCategory($pdo);
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function getProducts($pdo) {
    $page = $_GET['page'] ?? 1;
    $limit = $_GET['limit'] ?? 12;
    $offset = ($page - 1) * $limit;
    
    $sql = "SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            ORDER BY p.created_at DESC 
            LIMIT ? OFFSET ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$limit, $offset]);
    $products = $stmt->fetchAll();
    
    // Get total count
    $countStmt = $pdo->query("SELECT COUNT(*) FROM products");
    $total = $countStmt->fetchColumn();
    
    echo json_encode([
        'products' => $products,
        'total' => $total,
        'page' => $page,
        'pages' => ceil($total / $limit)
    ]);
}

function getCategories($pdo) {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
    
    echo json_encode(['categories' => $categories]);
}

function searchProducts($pdo) {
    $query = $_GET['q'] ?? '';
    $page = $_GET['page'] ?? 1;
    $limit = $_GET['limit'] ?? 12;
    $offset = ($page - 1) * $limit;
    
    if (empty($query)) {
        getProducts($pdo);
        return;
    }
    
    $sql = "SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.name LIKE ? OR p.description LIKE ? 
            ORDER BY p.created_at DESC 
            LIMIT ? OFFSET ?";
    
    $searchTerm = '%' . $query . '%';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$searchTerm, $searchTerm, $limit, $offset]);
    $products = $stmt->fetchAll();
    
    // Get total count
    $countSql = "SELECT COUNT(*) FROM products WHERE name LIKE ? OR description LIKE ?";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute([$searchTerm, $searchTerm]);
    $total = $countStmt->fetchColumn();
    
    echo json_encode([
        'products' => $products,
        'total' => $total,
        'page' => $page,
        'pages' => ceil($total / $limit),
        'query' => $query
    ]);
}

function getProductsByCategory($pdo) {
    $categoryId = $_GET['category_id'] ?? 0;
    $page = $_GET['page'] ?? 1;
    $limit = $_GET['limit'] ?? 12;
    $offset = ($page - 1) * $limit;
    
    $sql = "SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.category_id = ? 
            ORDER BY p.created_at DESC 
            LIMIT ? OFFSET ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$categoryId, $limit, $offset]);
    $products = $stmt->fetchAll();
    
    // Get total count
    $countSql = "SELECT COUNT(*) FROM products WHERE category_id = ?";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute([$categoryId]);
    $total = $countStmt->fetchColumn();
    
    echo json_encode([
        'products' => $products,
        'total' => $total,
        'page' => $page,
        'pages' => ceil($total / $limit)
    ]);
}

function getProductDetail($pdo) {
    $productId = $_GET['id'] ?? 0;
    
    $sql = "SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        throw new Exception('ไม่พบสินค้า');
    }
    
    echo json_encode(['product' => $product]);
}

function createProduct($pdo, $input) {
    $name = $input['name'] ?? '';
    $description = $input['description'] ?? '';
    $price = $input['price'] ?? 0;
    $stock = $input['stock'] ?? 0;
    $categoryId = $input['category_id'] ?? null;
    $image = $input['image'] ?? '';
    
    if (empty($name) || $price <= 0) {
        throw new Exception('กรุณากรอกข้อมูลให้ครบถ้วน');
    }
    
    $sql = "INSERT INTO products (name, description, price, stock, category_id, image) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $description, $price, $stock, $categoryId, $image]);
    
    $productId = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'เพิ่มสินค้าสำเร็จ',
        'product_id' => $productId
    ]);
}

function updateProduct($pdo, $input) {
    $id = $input['id'] ?? 0;
    $name = $input['name'] ?? '';
    $description = $input['description'] ?? '';
    $price = $input['price'] ?? 0;
    $stock = $input['stock'] ?? 0;
    $categoryId = $input['category_id'] ?? null;
    $image = $input['image'] ?? '';
    
    if (!$id || empty($name) || $price <= 0) {
        throw new Exception('กรุณากรอกข้อมูลให้ครบถ้วน');
    }
    
    $sql = "UPDATE products 
            SET name = ?, description = ?, price = ?, stock = ?, category_id = ?, image = ? 
            WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $description, $price, $stock, $categoryId, $image, $id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'อัปเดตสินค้าสำเร็จ'
    ]);
}

function deleteProduct($pdo) {
    $id = $_GET['id'] ?? 0;
    
    if (!$id) {
        throw new Exception('ไม่พบรหัสสินค้า');
    }
    
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'ลบสินค้าสำเร็จ'
    ]);
}

function createCategory($pdo, $input) {
    $name = $input['name'] ?? '';
    
    if (empty($name)) {
        throw new Exception('กรุณากรอกชื่อหมวดหมู่');
    }
    
    $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
    $stmt->execute([$name]);
    
    $categoryId = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'เพิ่มหมวดหมู่สำเร็จ',
        'category_id' => $categoryId
    ]);
}

function updateCategory($pdo, $input) {
    $id = $input['id'] ?? 0;
    $name = $input['name'] ?? '';
    
    if (!$id || empty($name)) {
        throw new Exception('กรุณากรอกข้อมูลให้ครบถ้วน');
    }
    
    $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?");
    $stmt->execute([$name, $id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'อัปเดตหมวดหมู่สำเร็จ'
    ]);
}

function deleteCategory($pdo) {
    $id = $_GET['id'] ?? 0;
    
    if (!$id) {
        throw new Exception('ไม่พบรหัสหมวดหมู่');
    }
    
    // Check if category has products
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('ไม่สามารถลบหมวดหมู่ที่มีสินค้าอยู่');
    }
    
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'ลบหมวดหมู่สำเร็จ'
    ]);
}
?>