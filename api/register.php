<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$username = $_POST['username'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($username) || empty($email) || empty($password)) {
    echo json_encode(['error' => 'All fields are required']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['error' => 'Password must be at least 6 characters long']);
    exit;
}

$db = getDB();

try {
    // Check if username already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        echo json_encode(['error' => 'Username already exists']);
        exit;
    }
    
    // Check if email already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['error' => 'Email already exists']);
        exit;
    }
    
    // Hash password and create user
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
    $stmt->execute([$username, $email, $hashedPassword]);
    
    echo json_encode(['success' => true, 'message' => 'Registration successful']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Registration failed']);
}
