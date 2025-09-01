<?php
require_once '../config/database.php';

header('Content-Type: text/html; charset=UTF-8');

$orderId = $_GET['order_id'] ?? 0;

if (!$orderId) {
    die('ไม่พบรหัสคำสั่งซื้อ');
}

try {
    $pdo = getDB();
    
    // Check permission
    if (!isAdmin()) {
        requireLogin();
        $stmt = $pdo->prepare("SELECT user_id FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $orderUserId = $stmt->fetchColumn();
        
        if ($orderUserId != $_SESSION['user_id']) {
            die('ไม่มีสิทธิ์เข้าถึงใบเสร็จนี้');
        }
    }
    
    // Get receipt HTML
    $stmt = $pdo->prepare("SELECT html FROM receipts WHERE order_id = ?");
    $stmt->execute([$orderId]);
    $receiptHtml = $stmt->fetchColumn();
    
    if (!$receiptHtml) {
        die('ไม่พบใบเสร็จ');
    }
    
    // Output receipt HTML for printing
    echo "<!DOCTYPE html>";
    echo "<html lang='th'>";
    echo "<head>";
    echo "<meta charset='UTF-8'>";
    echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
    echo "<title>ใบเสร็จ #{$orderId}</title>";
    echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>";
    echo "<style>";
    echo "@media print { body { margin: 0; } .no-print { display: none; } }";
    echo "</style>";
    echo "</head>";
    echo "<body>";
    echo "<div class='container mt-3'>";
    echo "<div class='no-print mb-3'>";
    echo "<button class='btn btn-primary' onclick='window.print()'><i class='fas fa-print'></i> พิมพ์</button>";
    echo "<button class='btn btn-secondary ms-2' onclick='window.close()'>ปิด</button>";
    echo "</div>";
    echo $receiptHtml;
    echo "</div>";
    echo "</body>";
    echo "</html>";
    
} catch (Exception $e) {
    die('เกิดข้อผิดพลาด: ' . $e->getMessage());
}
?>