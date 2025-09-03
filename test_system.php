<?php
echo "Testing E-Commerce System...\n\n";

// Test database connection
echo "1. Testing database connection...\n";
try {
    require_once 'config/database.php';
    $db = getDB();
    echo "✓ Database connection successful\n";
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
}

// Test file structure
echo "\n2. Testing file structure...\n";
$requiredFiles = [
    'index.php',
    'public/css/style.css',
    'public/js/app.js',
    'api/search.php',
    'api/cart.php',
    'api/products.php',
    'api/categories.php',
    'pages/home.php',
    'pages/products.php',
    'admin/dashboard.php',
    'admin/products.php'
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "✓ $file exists\n";
    } else {
        echo "✗ $file missing\n";
    }
}

// Test database initialization
echo "\n3. Testing database initialization...\n";
if (file_exists('data/app.db')) {
    echo "✓ Database file exists\n";
} else {
    echo "! Database file doesn't exist - will be created on first access\n";
}

echo "\n4. Testing admin creation...\n";
try {
    $db = getDB();
    $stmt = $db->query("SELECT username, role FROM users WHERE role = 'admin' LIMIT 1");
    $admin = $stmt->fetch();
    if ($admin) {
        echo "✓ Admin user exists: " . $admin['username'] . "\n";
    } else {
        echo "! No admin user found - run data/init_db.php to create\n";
    }
} catch (Exception $e) {
    echo "✗ Error checking admin user: " . $e->getMessage() . "\n";
}

echo "\nSystem test completed!\n";
echo "To run the system:\n";
echo "1. Make sure you have PHP with SQLite support\n";
echo "2. Run: php -S localhost:8000\n";
echo "3. Open http://localhost:8000 in your browser\n";
echo "4. Login with admin/admin123\n";
