<?php
session_start();

// ลบ session ทั้งหมด
session_unset();
session_destroy();

// เริ่ม session ใหม่เพื่อแสดงข้อความ
session_start();
$_SESSION['logout_success'] = true;

// Redirect ไปหน้า login
header("Location: login.php");
exit();
?>