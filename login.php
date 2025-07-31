<?php
session_start();
include 'db_connect.php';

$error_message = '';
$success_message = '';

// ถ้าล็อกอินแล้วให้ redirect ไป index หรือ admin
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['username'] === 'admin') {
        header("Location: admin.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    
    // ค้นหาผู้ใช้ในฐานข้อมูล
    $query = "SELECT id, username, password, full_name FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // ตรวจสอบรหัสผ่าน
        if (password_verify($password, $user['password'])) {
            // ล็อกอินสำเร็จ
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            
            // Redirect ตามประเภทผู้ใช้
            if ($username === 'admin') {
                header("Location: admin.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $error_message = "รหัสผ่านไม่ถูกต้อง";
        }
    } else {
        $error_message = "ไม่พบชื่อผู้ใช้นี้ในระบบ";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ร้านขายอุปกรณ์ตกปลา</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            transition: transform 0.2s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, #34495e 0%, #2980b9 100%);
        }
        
        .demo-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
        
        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-right: none;
        }
        
        .form-control {
            border-left: none;
        }
        
        .form-control:focus + .input-group-text,
        .input-group-text + .form-control:focus {
            border-color: #3498db;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-fish fa-3x mb-3"></i>
            <h3>ร้านขายอุปกรณ์ตกปลา</h3>
            <p class="mb-0">เข้าสู่ระบบ</p>
        </div>
        
        <div class="login-body">
            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">ชื่อผู้ใช้</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" class="form-control" name="username" required 
                               placeholder="กรอกชื่อผู้ใช้" value="<?php echo isset($_POST['username']) ? $_POST['username'] : ''; ?>">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">รหัสผ่าน</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" class="form-control" name="password" required 
                               placeholder="กรอกรหัสผ่าน" id="passwordInput">
                        <span class="input-group-text" onclick="togglePassword()" style="cursor: pointer;">
                            <i class="fas fa-eye" id="passwordToggle"></i>
                        </span>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-login w-100">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    เข้าสู่ระบบ
                </button>
            </form>
            
            <!-- ข้อมูลสำหรับทดสอบ -->
            <div class="demo-info">
                <h6><i class="fas fa-info-circle me-2"></i>ข้อมูลสำหรับทดสอบ:</h6>
                <div class="row">
                    <div class="col-6">
                        <strong>Admin:</strong><br>
                        Username: <code>admin</code><br>
                        Password: <code>password123</code>
                    </div>
                    <div class="col-6">
                        <strong>User ทั่วไป:</strong><br>
                        Username: <code>testuser</code><br>
                        Password: <code>password123</code>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-3">
                <p class="text-muted mb-0">ยังไม่มีบัญชี? 
                    <a href="register.php" class="text-decoration-none">สมัครสมาชิก</a>
                </p>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('passwordInput');
            const passwordToggle = document.getElementById('passwordToggle');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordToggle.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                passwordToggle.className = 'fas fa-eye';
            }
        }
        
        // Auto focus on username field
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('input[name="username"]').focus();
        });
        
        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>