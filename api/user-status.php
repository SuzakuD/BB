<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    $db = getDB();
    
    try {
        $stmt = $db->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo json_encode([
                'user' => $user,
                'isAdmin' => $user['role'] === 'admin'
            ]);
        } else {
            echo json_encode(['user' => null, 'isAdmin' => false]);
        }
    } catch (Exception $e) {
        echo json_encode(['user' => null, 'isAdmin' => false]);
    }
} else {
    echo json_encode(['user' => null, 'isAdmin' => false]);
}
