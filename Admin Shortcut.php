<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Shortcut</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .container {
            text-align: center;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .btn {
            display: inline-block;
            padding: 15px 40px;
            margin: 10px;
            font-size: 18px;
            text-decoration: none;
            border-radius: 30px;
            transition: transform 0.3s;
        }
        .btn:hover {
            transform: translateY(-3px);
        }
        .btn-admin {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        .btn-shop {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }
        .btn-logout {
            background: #e74c3c;
            color: white;
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
        }
        .user-info {
            background: #f8f9fa;
            padding: 10px 20px;
            border-radius: 20px;
            margin-bottom: 20px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-fish"></i> Toomtam Fishing</h1>
        
        <?php if (isset($_SESSION['username'])): ?>
            <div class="user-info">
                <i class="fas fa-user"></i> สวัสดี, <?php echo $_SESSION['username']; ?>
            </div>
            
            <?php if ($_SESSION['username'] == 'admin'): ?>
                <h2>เลือกระบบที่ต้องการใช้งาน</h2>
                <a href="admin.php" class="btn btn-admin">
                    <i class="fas fa-cog"></i> ระบบ Admin
                </a>
                <a href="index.php" class="btn btn-shop">
                    <i class="fas fa-shopping-cart"></i> หน้าร้านค้า
                </a>
            <?php else: ?>
                <p>คุณไม่มีสิทธิ์เข้าใช้ระบบ Admin</p>
                <a href="index.php" class="btn btn-shop">
                    <i class="fas fa-home"></i> กลับหน้าร้าน
                </a>
            <?php endif; ?>
            
            <br><br>
            <a href="logout.php" class="btn btn-logout">
                <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
            </a>
        <?php else: ?>
            <p>กรุณา Login ก่อน</p>
            <a href="login.php" class="btn btn-admin">
                <i class="fas fa-sign-in-alt"></i> Login
            </a>
        <?php endif; ?>
    </div>
</body>
</html>