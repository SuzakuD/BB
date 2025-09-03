#!/bin/bash

echo "Starting Modern E-Commerce System..."
echo "=================================="

# Check if PHP is available
if command -v php &> /dev/null; then
    echo "✓ PHP found"
    
    # Check if SQLite extension is available
    if php -m | grep -q sqlite; then
        echo "✓ SQLite extension found"
    else
        echo "✗ SQLite extension not found. Please install php-sqlite3"
        exit 1
    fi
    
    # Initialize database if needed
    if [ ! -f "data/app.db" ]; then
        echo "! Initializing database..."
        php data/init_db.php
    fi
    
    echo ""
    echo "Starting server on http://localhost:8000"
    echo "Press Ctrl+C to stop"
    echo ""
    
    # Start PHP development server
    php -S localhost:8000
    
else
    echo "✗ PHP not found. Please install PHP 7.4 or higher"
    echo ""
    echo "Installation commands:"
    echo "Ubuntu/Debian: sudo apt-get install php php-sqlite3"
    echo "CentOS/RHEL: sudo yum install php php-sqlite3"
    echo "macOS: brew install php"
    exit 1
fi
