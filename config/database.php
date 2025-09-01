<?php
/**
 * Database configuration and connection
 */

class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        $dbPath = __DIR__ . '/../data/app.db';
        
        try {
            $this->pdo = new PDO('sqlite:' . $dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->pdo;
    }
}

// Helper function to get database connection
function getDB() {
    return Database::getInstance()->getConnection();
}

// Session management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'กรุณาเข้าสู่ระบบ']);
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['error' => 'ไม่มีสิทธิ์เข้าถึง']);
        exit;
    }
}
?>