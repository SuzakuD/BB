<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'login':
            $username = $input['username'] ?? '';
            $password = $input['password'] ?? '';
            
            if (empty($username) || empty($password)) {
                echo json_encode(['success' => false, 'error' => 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน']);
                exit;
            }
            
            try {
                $pdo = getConnection();
                $stmt = $pdo->prepare("SELECT id, username, password, email, full_name FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['email'] = $user['email'];
                    
                    // Remove password from response
                    unset($user['password']);
                    
                    echo json_encode([
                        'success' => true, 
                        'message' => 'เข้าสู่ระบบสำเร็จ',
                        'user' => $user
                    ]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'เกิดข้อผิดพลาดในการเข้าสู่ระบบ']);
            }
            break;
            
        case 'register':
            $username = $input['username'] ?? '';
            $password = $input['password'] ?? '';
            $confirmPassword = $input['confirmPassword'] ?? '';
            
            if (empty($username) || empty($password) || empty($confirmPassword)) {
                echo json_encode(['success' => false, 'error' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
                exit;
            }
            
            if ($password !== $confirmPassword) {
                echo json_encode(['success' => false, 'error' => 'รหัสผ่านไม่ตรงกัน']);
                exit;
            }
            
            if (strlen($password) < 6) {
                echo json_encode(['success' => false, 'error' => 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร']);
                exit;
            }
            
            try {
                $pdo = getConnection();
                
                // Check if username already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    echo json_encode(['success' => false, 'error' => 'ชื่อผู้ใช้นี้ถูกใช้แล้ว']);
                    exit;
                }
                
                // Create new user
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$username, $hashedPassword]);
                
                $userId = $pdo->lastInsertId();
                
                // Get user data
                $stmt = $pdo->prepare("SELECT id, username, email, full_name FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'สมัครสมาชิกสำเร็จ',
                    'user' => $user
                ]);
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'เกิดข้อผิดพลาดในการสมัครสมาชิก']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'การกระทำไม่ถูกต้อง']);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'check':
            if (isset($_SESSION['user_id'])) {
                try {
                    $pdo = getConnection();
                    $stmt = $pdo->prepare("SELECT id, username, email, full_name FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($user) {
                        echo json_encode(['authenticated' => true, 'user' => $user]);
                    } else {
                        session_destroy();
                        echo json_encode(['authenticated' => false]);
                    }
                } catch (Exception $e) {
                    echo json_encode(['authenticated' => false]);
                }
            } else {
                echo json_encode(['authenticated' => false]);
            }
            break;
            
        default:
            echo json_encode(['authenticated' => false]);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'logout':
            session_destroy();
            echo json_encode(['success' => true, 'message' => 'ออกจากระบบสำเร็จ']);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'การกระทำไม่ถูกต้อง']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
?>