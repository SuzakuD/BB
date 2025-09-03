<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

session_destroy();

echo json_encode(['success' => true]);
