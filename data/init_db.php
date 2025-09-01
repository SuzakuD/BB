<?php
/**
 * Database initialization and migration script
 * Creates SQLite database with all required tables
 */

$dbPath = __DIR__ . '/app.db';

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create tables
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            role TEXT CHECK(role IN ('user','admin')) DEFAULT 'user',
            created_at TEXT DEFAULT (datetime('now'))
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            category_id INTEGER,
            name TEXT NOT NULL,
            description TEXT,
            price REAL NOT NULL,
            stock INTEGER DEFAULT 0,
            image TEXT,
            created_at TEXT DEFAULT (datetime('now')),
            FOREIGN KEY (category_id) REFERENCES categories(id)
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            total REAL NOT NULL,
            status TEXT DEFAULT 'pending',
            created_at TEXT DEFAULT (datetime('now')),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER,
            product_id INTEGER,
            quantity INTEGER NOT NULL,
            price REAL NOT NULL,
            FOREIGN KEY (order_id) REFERENCES orders(id),
            FOREIGN KEY (product_id) REFERENCES products(id)
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS promotions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            code TEXT UNIQUE NOT NULL,
            discount REAL NOT NULL,
            expire_date TEXT NOT NULL
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS receipts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER UNIQUE,
            html TEXT NOT NULL,
            created_at TEXT DEFAULT (datetime('now')),
            FOREIGN KEY (order_id) REFERENCES orders(id)
        )
    ");
    
    // Create default admin user if not exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    
    if ($stmt->fetchColumn() == 0) {
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')");
        $stmt->execute(['admin', $adminPassword]);
    }
    
    echo "Database initialized successfully!\n";
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>