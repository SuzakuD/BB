<?php
// Database configuration - Using SQLite for consistency
define("DB_PATH", __DIR__ . "/data/app.db");

// Create database connection
function getConnection() {
    try {
        $pdo = new PDO("sqlite:" . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Image upload configuration
define("UPLOAD_DIR", __DIR__ . "/uploads/");
define("MAX_FILE_SIZE", 5 * 1024 * 1024); // 5MB
define("ALLOWED_EXTENSIONS", ["jpg", "jpeg", "png", "gif", "webp"]);

// Create upload directory if it does not exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Helper functions
function formatPrice($price) {
    return number_format($price, 2) . " บาท";
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function isLoggedIn() {
    return isset($_SESSION["user_id"]);
}

function isAdmin() {
    return isset($_SESSION["role"]) && $_SESSION["role"] === "admin";
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: index.php");
        exit();
    }
}

// Image upload helper function
function uploadImage($file, $productName = "") {
    if (!isset($file["error"]) || $file["error"] !== UPLOAD_ERR_OK) {
        return false;
