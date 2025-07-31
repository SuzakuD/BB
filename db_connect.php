<?php
// การตั้งค่าเชื่อมต่อฐานข้อมูล MySQL
$host = 'localhost';      // ชื่อเซิร์ฟเวอร์
$username = 'root';       // ชื่อผู้ใช้ MySQL (default ของ XAMPP)
$password = '';           // รหัสผ่าน MySQL (default ของ XAMPP เป็นค่าว่าง)
$database = 'fishing_store'; // ชื่อฐานข้อมูล

// เชื่อมต่อฐานข้อมูล
$conn = mysqli_connect($host, $username, $password, $database);

// ตรวจสอบการเชื่อมต่อ
if (!$conn) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . mysqli_connect_error());
}

// ตั้งค่า charset เป็น UTF-8 สำหรับภาษาไทย
mysqli_set_charset($conn, "utf8");

// แสดงข้อความยืนยันการเชื่อมต่อ (สำหรับ debug - ลบออกในการใช้งานจริง)
// echo "เชื่อมต่อฐานข้อมูลสำเร็จ!<br>";
?>