<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        if ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!validateCSRFToken($input['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
                exit;
            }
            
            $email = sanitizeInput($input['email'] ?? '');
            $password = $input['password'] ?? '';
            
            if (!validateEmail($email)) {
                echo json_encode(['success' => false, 'message' => 'Invalid email format']);
                exit;
            }
            
            if (loginUser($email, $password)) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Login successful',
                    'user' => [
                        'id' => $_SESSION['user_id'],
                        'name' => $_SESSION['user_name'],
                        'email' => $_SESSION['user_email'],
                        'role' => $_SESSION['user_role']
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
            }
        }
        break;
        
    case 'register':
        if ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!validateCSRFToken($input['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
                exit;
            }
            
            $name = sanitizeInput($input['name'] ?? '');
            $email = sanitizeInput($input['email'] ?? '');
            $password = $input['password'] ?? '';
            $confirmPassword = $input['confirm_password'] ?? '';
            
            if (empty($name) || empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'All fields are required']);
                exit;
            }
            
            if (!validateEmail($email)) {
                echo json_encode(['success' => false, 'message' => 'Invalid email format']);
                exit;
            }
            
            if ($password !== $confirmPassword) {
                echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
                exit;
            }
            
            if (strlen($password) < 6) {
                echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
                exit;
            }
            
            if (registerUser($name, $email, $password)) {
                echo json_encode(['success' => true, 'message' => 'Registration successful. Please login.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Email already exists']);
            }
        }
        break;
        
    case 'logout':
        if ($method === 'POST') {
            session_destroy();
            echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
        }
        break;
        
    case 'check':
        echo json_encode([
            'logged_in' => isLoggedIn(),
            'user' => isLoggedIn() ? [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name'],
                'email' => $_SESSION['user_email'],
                'role' => $_SESSION['user_role']
            ] : null
        ]);
        break;
        
    case 'csrf_token':
        echo json_encode(['csrf_token' => generateCSRFToken()]);
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Action not found']);
}
?>