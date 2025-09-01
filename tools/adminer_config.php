<?php
/**
 * Adminer configuration for SQLite database access
 * Access this via: http://yoursite.com/tools/adminer.php
 */

// Set default values for Adminer
$_GET['sqlite'] = $_GET['sqlite'] ?? '../data/app.db';
$_GET['username'] = $_GET['username'] ?? '';

// Include the main Adminer file
require_once 'adminer.php';
?>