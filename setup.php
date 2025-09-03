<?php
// Setup script for Fishing Gear Store
// Run this script to initialize the database

require_once 'config/database.php';

try {
    // Read and execute the SQL schema
    $sql = file_get_contents('database/schema.sql');
    
    // Split the SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "Database setup completed successfully!\n";
    echo "Admin credentials:\n";
    echo "Email: admin@fishingstore.com\n";
    echo "Password: password\n";
    echo "\nYou can now access the application at: http://localhost/index.php\n";
    
} catch (Exception $e) {
    echo "Error setting up database: " . $e->getMessage() . "\n";
}
?>