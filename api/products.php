<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        $categoryId = $_GET['category_id'] ?? null;
        $search = $_GET['search'] ?? null;
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 12);
        $offset = ($page - 1) * $limit;
        
        $products = getProducts($categoryId, $search, $limit, $offset);
        
        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as total FROM products WHERE is_active = 1";
        $countParams = [];
        
        if ($categoryId) {
            $countSql .= " AND category_id = ?";
            $countParams[] = $categoryId;
        }
        
        if ($search) {
            $countSql .= " AND (name LIKE ? OR description LIKE ? OR brand LIKE ?)";
            $searchTerm = "%$search%";
            $countParams[] = $searchTerm;
            $countParams[] = $searchTerm;
            $countParams[] = $searchTerm;
        }
        
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($countParams);
        $total = $stmt->fetch()['total'];
        
        echo json_encode([
            'success' => true,
            'products' => $products,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($total / $limit),
                'total_items' => $total,
                'items_per_page' => $limit
            ]
        ]);
        break;
        
    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
            exit;
        }
        
        $product = getProduct($id);
        if ($product) {
            // Get product reviews
            $stmt = $pdo->prepare("SELECT r.*, u.name as user_name FROM product_reviews r 
                                  JOIN users u ON r.user_id = u.id 
                                  WHERE r.product_id = ? AND r.is_approved = 1 
                                  ORDER BY r.created_at DESC");
            $stmt->execute([$id]);
            $reviews = $stmt->fetchAll();
            
            // Calculate average rating
            $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count 
                                  FROM product_reviews WHERE product_id = ? AND is_approved = 1");
            $stmt->execute([$id]);
            $ratingData = $stmt->fetch();
            
            $product['reviews'] = $reviews;
            $product['avg_rating'] = round($ratingData['avg_rating'] ?? 0, 1);
            $product['review_count'] = $ratingData['review_count'] ?? 0;
            
            echo json_encode(['success' => true, 'product' => $product]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
        }
        break;
        
    case 'categories':
        $categories = getCategories();
        echo json_encode(['success' => true, 'categories' => $categories]);
        break;
        
    case 'featured':
        $products = getProducts(null, null, 8);
        $featured = array_filter($products, function($product) {
            return $product['is_featured'] == 1;
        });
        echo json_encode(['success' => true, 'products' => array_values($featured)]);
        break;
        
    case 'search':
        $query = $_GET['q'] ?? '';
        if (empty($query)) {
            echo json_encode(['success' => false, 'message' => 'Search query required']);
            exit;
        }
        
        $products = getProducts(null, $query, 20);
        echo json_encode(['success' => true, 'products' => $products]);
        break;
        
    case 'add_review':
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
            
            $productId = (int)($input['product_id'] ?? 0);
            $rating = (int)($input['rating'] ?? 0);
            $title = sanitizeInput($input['title'] ?? '');
            $comment = sanitizeInput($input['comment'] ?? '');
            
            if ($productId <= 0 || $rating < 1 || $rating > 5) {
                echo json_encode(['success' => false, 'message' => 'Invalid data']);
                exit;
            }
            
            // Check if user already reviewed this product
            $stmt = $pdo->prepare("SELECT id FROM product_reviews WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$_SESSION['user_id'], $productId]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'You have already reviewed this product']);
                exit;
            }
            
            $stmt = $pdo->prepare("INSERT INTO product_reviews (product_id, user_id, rating, title, comment, is_approved) 
                                  VALUES (?, ?, ?, ?, ?, 1)");
            if ($stmt->execute([$productId, $_SESSION['user_id'], $rating, $title, $comment])) {
                echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to submit review']);
            }
        }
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Action not found']);
}
?>