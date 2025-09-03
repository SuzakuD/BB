<?php
require_once '../config/database.php';

$db = getDB();

try {
    // Create users table
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(20) DEFAULT 'user',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create categories table
    $db->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create products table
    $db->exec("
        CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(200) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            stock INTEGER DEFAULT 0,
            category_id INTEGER,
            image VARCHAR(255),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id)
        )
    ");
    
    // Create orders table
    $db->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            total DECIMAL(10,2) NOT NULL,
            status VARCHAR(50) DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");
    
    // Create order_items table
    $db->exec("
        CREATE TABLE IF NOT EXISTS order_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER,
            product_id INTEGER,
            quantity INTEGER NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (order_id) REFERENCES orders(id),
            FOREIGN KEY (product_id) REFERENCES products(id)
        )
    ");
    
    // Insert default admin user
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $db->exec("
        INSERT OR IGNORE INTO users (username, email, password, role) 
        VALUES ('admin', 'admin@store.com', '$adminPassword', 'admin')
    ");
    
    // Insert default categories
    $defaultCategories = [
        'Electronics',
        'Clothing',
        'Books',
        'Home & Garden',
        'Sports',
        'Toys',
        'Automotive',
        'Health & Beauty'
    ];
    
    foreach ($defaultCategories as $category) {
        $db->exec("INSERT OR IGNORE INTO categories (name) VALUES ('$category')");
    }
    
    // Insert sample products
    $sampleProducts = [
        ['Smartphone', 'Latest smartphone with advanced features', 599.99, 50, 1, 'public/images/phone.jpg'],
        ['Laptop', 'High-performance laptop for work and gaming', 999.99, 30, 1, 'public/images/laptop.jpg'],
        ['T-Shirt', 'Comfortable cotton t-shirt', 19.99, 100, 2, 'public/images/tshirt.jpg'],
        ['Book', 'Bestselling novel', 14.99, 200, 3, 'public/images/book.jpg'],
        ['Garden Tool', 'Essential gardening equipment', 29.99, 75, 4, 'public/images/garden.jpg']
    ];
    
    foreach ($sampleProducts as $product) {
        $db->exec("
            INSERT OR IGNORE INTO products (name, description, price, stock, category_id, image) 
            VALUES ('{$product[0]}', '{$product[1]}', {$product[2]}, {$product[3]}, {$product[4]}, '{$product[5]}')
        ");
    }
    
    echo "Database initialized successfully!";
    
} catch (Exception $e) {
    echo "Error initializing database: " . $e->getMessage();
}
