<?php
require_once '../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

requireAdmin();

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
            getUsers($pdo);
            break;
        case 'stats':
            getUserStats($pdo);
            break;
        default:
            getUsers($pdo);
    }
}

function handlePost($pdo, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'create':
            createUser($pdo, $input);
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function handlePut($pdo, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'update':
            updateUser($pdo, $input);
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function handleDelete($pdo, $action) {
    switch ($action) {
        case 'delete':
            deleteUser($pdo);
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function getUsers($pdo) {
    $page = $_GET['page'] ?? 1;
    $limit = $_GET['limit'] ?? 20;
    $offset = ($page - 1) * $limit;
    $search = $_GET['search'] ?? '';
    
    $sql = "SELECT id, username, role, created_at FROM users";
    $params = [];
    
    if (!empty($search)) {
        $sql .= " WHERE username LIKE ?";
        $params[] = '%' . $search . '%';
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
    // Get total count
    $countSql = "SELECT COUNT(*) FROM users";
    $countParams = [];
    
    if (!empty($search)) {
        $countSql .= " WHERE username LIKE ?";
        $countParams[] = '%' . $search . '%';
    }
    
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($countParams);
    $total = $countStmt->fetchColumn();
    
    echo json_encode([
        'users' => $users,
        'total' => $total,
        'page' => $page,
        'pages' => ceil($total / $limit)
    ]);
}

function getUserStats($pdo) {
    $stats = [];
    
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $stats['total_users'] = $stmt->fetchColumn();
    
    // Admin users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $stats['admin_users'] = $stmt->fetchColumn();
    
    // Regular users
    $stats['regular_users'] = $stats['total_users'] - $stats['admin_users'];
    
    // New users this month
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= date('now', 'start of month')");
    $stats['new_users_month'] = $stmt->fetchColumn();
    
    echo json_encode(['stats' => $stats]);
}

function createUser($pdo, $input) {
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';
    $role = $input['role'] ?? 'user';
    
    if (empty($username) || empty($password)) {
        throw new Exception('กรุณากรอกข้อมูลให้ครบถ้วน');
    }
    
    if (strlen($username) < 3) {
        throw new Exception('ชื่อผู้ใช้ต้องมีอย่างน้อย 3 ตัวอักษร');
    }
    
    if (strlen($password) < 6) {
        throw new Exception('รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร');
    }
    
    if (!in_array($role, ['user', 'admin'])) {
        throw new Exception('บทบาทไม่ถูกต้อง');
    }
    
    // Check if username already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('ชื่อผู้ใช้นี้มีอยู่แล้ว');
    }
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->execute([$username, $hashedPassword, $role]);
    
    $userId = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'เพิ่มผู้ใช้สำเร็จ',
        'user_id' => $userId
    ]);
}

function updateUser($pdo, $input) {
    $id = $input['id'] ?? 0;
    $username = $input['username'] ?? '';
    $role = $input['role'] ?? '';
    $password = $input['password'] ?? '';
    
    if (!$id || empty($username) || empty($role)) {
        throw new Exception('กรุณากรอกข้อมูลให้ครบถ้วน');
    }
    
    if (!in_array($role, ['user', 'admin'])) {
        throw new Exception('บทบาทไม่ถูกต้อง');
    }
    
    // Check if trying to modify self
    if ($id == $_SESSION['user_id']) {
        throw new Exception('ไม่สามารถแก้ไขข้อมูลของตนเองได้');
    }
    
    // Check if username already exists (excluding current user)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
    $stmt->execute([$username, $id]);
    
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('ชื่อผู้ใช้นี้มีอยู่แล้ว');
    }
    
    if (!empty($password)) {
        if (strlen($password) < 6) {
            throw new Exception('รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร');
        }
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET username = ?, role = ?, password = ? WHERE id = ?");
        $stmt->execute([$username, $role, $hashedPassword, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
        $stmt->execute([$username, $role, $id]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'อัปเดตผู้ใช้สำเร็จ'
    ]);
}

function deleteUser($pdo) {
    $id = $_GET['id'] ?? 0;
    
    if (!$id) {
        throw new Exception('ไม่พบรหัสผู้ใช้');
    }
    
    if ($id == $_SESSION['user_id']) {
        throw new Exception('ไม่สามารถลบตนเองได้');
    }
    
    // Check if user has orders
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('ไม่สามารถลบผู้ใช้ที่มีคำสั่งซื้อ');
    }
    
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'ลบผู้ใช้สำเร็จ'
    ]);
}
?>