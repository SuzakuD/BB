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
        case 'POST':
            handlePost($pdo, $action);
            break;
        case 'GET':
            handleGet($pdo, $action);
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

function handlePost($pdo, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'login':
            login($pdo, $input);
            break;
        case 'register':
            register($pdo, $input);
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function handleGet($pdo, $action) {
    switch ($action) {
        case 'check':
            checkAuth();
            break;
        case 'user':
            getCurrentUser();
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function handleDelete($pdo, $action) {
    switch ($action) {
        case 'logout':
            logout();
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function login($pdo, $input) {
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        throw new Exception('กรุณากรอกชื่อผู้ใช้และรหัสผ่าน');
    }
    
    $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password'])) {
        throw new Exception('ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง');
    }
    
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    
    echo json_encode([
        'success' => true,
        'message' => 'เข้าสู่ระบบสำเร็จ',
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role']
        ]
    ]);
}

function register($pdo, $input) {
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';
    $confirmPassword = $input['confirmPassword'] ?? '';
    
    if (empty($username) || empty($password)) {
        throw new Exception('กรุณากรอกข้อมูลให้ครบถ้วน');
    }
    
    if (strlen($username) < 3) {
        throw new Exception('ชื่อผู้ใช้ต้องมีอย่างน้อย 3 ตัวอักษร');
    }
    
    if (strlen($password) < 6) {
        throw new Exception('รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร');
    }
    
    if ($password !== $confirmPassword) {
        throw new Exception('รหัสผ่านไม่ตรงกัน');
    }
    
    // Check if username already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('ชื่อผู้ใช้นี้มีอยู่แล้ว');
    }
    
    // Create new user
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'user')");
    $stmt->execute([$username, $hashedPassword]);
    
    $userId = $pdo->lastInsertId();
    
    // Auto login after registration
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['role'] = 'user';
    
    echo json_encode([
        'success' => true,
        'message' => 'สมัครสมาชิกสำเร็จ',
        'user' => [
            'id' => $userId,
            'username' => $username,
            'role' => 'user'
        ]
    ]);
}

function checkAuth() {
    echo json_encode([
        'authenticated' => isLoggedIn(),
        'user' => isLoggedIn() ? [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role']
        ] : null
    ]);
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        throw new Exception('ไม่ได้เข้าสู่ระบบ');
    }
    
    echo json_encode([
        'user' => [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role']
        ]
    ]);
}

function logout() {
    session_destroy();
    echo json_encode([
        'success' => true,
        'message' => 'ออกจากระบบสำเร็จ'
    ]);
}
?>