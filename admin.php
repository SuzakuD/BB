<?php
session_start();
include 'db_connect.php';

// ตรวจสอบว่า login เป็น admin หรือไม่
if (!isset($_SESSION['user_id']) || $_SESSION['username'] != 'admin') {
    header("Location: login.php");
    exit();
}

// จัดการการกระทำต่างๆ
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // เพิ่มสินค้าใหม่
    if (isset($_POST['add_product'])) {
        $name = mysqli_real_escape_string($conn, $_POST['product_name']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $price = (float)$_POST['price'];
        $stock = (int)$_POST['stock'];
        $category_id = (int)$_POST['category_id'];
        
        // จัดการอัพโหลดรูปภาพ
        $image_url = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "uploads/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $image_url = $target_dir . basename($_FILES["image"]["name"]);
            move_uploaded_file($_FILES["image"]["tmp_name"], $image_url);
        }
        
        $query = "INSERT INTO products (name, description, price, stock_quantity, category_id, image_url) 
                  VALUES ('$name', '$description', '$price', '$stock', '$category_id', '$image_url')";
        mysqli_query($conn, $query);
        $success_msg = "เพิ่มสินค้าสำเร็จ!";
    }
    
    // เพิ่มหมวดหมู่ใหม่
    if (isset($_POST['add_category'])) {
        $category_name = mysqli_real_escape_string($conn, $_POST['category_name']);
        $query = "INSERT INTO categories (name) VALUES ('$category_name')";
        mysqli_query($conn, $query);
        $success_msg = "เพิ่มหมวดหมู่สำเร็จ!";
    }
    
    // อัพเดตสถานะคำสั่งซื้อ
    if (isset($_POST['update_order_status'])) {
        $order_id = (int)$_POST['order_id'];
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $query = "UPDATE orders SET status = '$status' WHERE id = $order_id";
        mysqli_query($conn, $query);
        $success_msg = "อัพเดตสถานะคำสั่งซื้อสำเร็จ!";
    }
    
    // ลบสินค้า
    if (isset($_POST['delete_product'])) {
        $product_id = (int)$_POST['product_id'];
        $query = "DELETE FROM products WHERE id = $product_id";
        mysqli_query($conn, $query);
        $success_msg = "ลบสินค้าสำเร็จ!";
    }
}

// ดึงข้อมูลสถิติ
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM products) as total_products,
    (SELECT COUNT(*) FROM orders) as total_orders,
    (SELECT COUNT(*) FROM users WHERE username != 'admin') as total_users,
    (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status = 'completed') as total_revenue";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// ดึงข้อมูลสินค้า
$products_query = "SELECT p.*, c.name as category_name FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.id 
                   ORDER BY p.id DESC";
$products_result = mysqli_query($conn, $products_query);

// ดึงข้อมูลหมวดหมู่
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = mysqli_query($conn, $categories_query);

// ดึงข้อมูลคำสั่งซื้อ
$orders_query = "SELECT o.*, u.username FROM orders o 
                 JOIN users u ON o.user_id = u.id 
                 ORDER BY o.order_date DESC LIMIT 10";
$orders_result = mysqli_query($conn, $orders_query);

// ดึงข้อมูลผู้ใช้
$users_query = "SELECT * FROM users WHERE username != 'admin' ORDER BY created_at DESC";
$users_result = mysqli_query($conn, $users_query);

// ดึงข้อมูลยอดขายรายเดือน
$sales_query = "SELECT DATE_FORMAT(order_date, '%Y-%m') as month, 
                SUM(total_amount) as total_sales 
                FROM orders 
                WHERE status = 'completed' 
                GROUP BY DATE_FORMAT(order_date, '%Y-%m') 
                ORDER BY month DESC LIMIT 12";
$sales_result = mysqli_query($conn, $sales_query);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - ระบบจัดการร้านขายอุปกรณ์ตกปลา</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            height: 100vh;
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            overflow-y: auto;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .nav-link {
            color: white !important;
            border-radius: 5px;
            margin: 2px 0;
        }
        .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
        }
        .nav-link.active {
            background-color: rgba(255,255,255,0.2);
        }
        .card-stat {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }
        .table-responsive {
            max-height: 500px;
            overflow-y: auto;
        }
        .status-pending { color: #f39c12; }
        .status-processing { color: #3498db; }
        .status-completed { color: #27ae60; }
        .status-cancelled { color: #e74c3c; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="p-3">
            <h4><i class="fas fa-fish"></i> Admin Panel</h4>
            <hr>
            <p class="mb-0">สวัสดี, <?php echo $_SESSION['username']; ?>!</p>
        </div>
        
        <ul class="nav flex-column px-3">
            <li class="nav-item">
                <a class="nav-link active" href="#dashboard" onclick="showSection('dashboard')">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#products" onclick="showSection('products')">
                    <i class="fas fa-box"></i> จัดการสินค้า
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#categories" onclick="showSection('categories')">
                    <i class="fas fa-tags"></i> จัดการหมวดหมู่
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#orders" onclick="showSection('orders')">
                    <i class="fas fa-shopping-cart"></i> จัดการคำสั่งซื้อ
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#users" onclick="showSection('users')">
                    <i class="fas fa-users"></i> จัดการผู้ใช้
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#reports" onclick="showSection('reports')">
                    <i class="fas fa-chart-bar"></i> รายงาน
                </a>
            </li>
            <hr>
            <li class="nav-item">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-store"></i> กลับไปหน้าร้าน
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <?php if (isset($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $success_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Dashboard Section -->
        <div id="dashboard" class="section">
            <h2><i class="fas fa-tachometer-alt"></i> Dashboard</h2>
            
            <!-- สถิติ -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card card-stat">
                        <div class="card-body text-center">
                            <i class="fas fa-box fa-2x mb-2"></i>
                            <h3><?php echo $stats['total_products']; ?></h3>
                            <p>จำนวนสินค้า</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-stat">
                        <div class="card-body text-center">
                            <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                            <h3><?php echo $stats['total_orders']; ?></h3>
                            <p>จำนวนออเดอร์</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-stat">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-2x mb-2"></i>
                            <h3><?php echo $stats['total_users']; ?></h3>
                            <p>จำนวนสมาชิก</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-stat">
                        <div class="card-body text-center">
                            <i class="fas fa-money-bill-wave fa-2x mb-2"></i>
                            <h3>฿<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                            <p>ยอดขายรวม</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- คำสั่งซื้อล่าสุด -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-clock"></i> คำสั่งซื้อล่าสุด</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>ลูกค้า</th>
                                    <th>ยอดรวม</th>
                                    <th>สถานะ</th>
                                    <th>วันที่</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = mysqli_fetch_assoc($orders_result)): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo $order['username']; ?></td>
                                    <td>฿<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status-<?php echo $order['status']; ?>">
                                            <?php
                                            $status_text = [
                                                'pending' => 'รอดำเนินการ',
                                                'processing' => 'กำลังจัดส่ง',
                                                'completed' => 'เสร็จสิ้น',
                                                'cancelled' => 'ยกเลิก'
                                            ];
                                            echo $status_text[$order['status']] ?? $order['status'];
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Products Section -->
        <div id="products" class="section" style="display: none;">
            <h2><i class="fas fa-box"></i> จัดการสินค้า</h2>
            
            <!-- เพิ่มสินค้าใหม่ -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-plus"></i> เพิ่มสินค้าใหม่</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">ชื่อสินค้า</label>
                                    <input type="text" class="form-control" name="product_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">หมวดหมู่</label>
                                    <select class="form-control" name="category_id" required>
                                        <option value="">เลือกหมวดหมู่</option>
                                        <?php 
                                        mysqli_data_seek($categories_result, 0);
                                        while ($category = mysqli_fetch_assoc($categories_result)): 
                                        ?>
                                            <option value="<?php echo $category['id']; ?>">
                                                <?php echo $category['name']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">ราคา (บาท)</label>
                                    <input type="number" class="form-control" name="price" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">จำนวนในคลัง</label>
                                    <input type="number" class="form-control" name="stock" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">รูปภาพ</label>
                                    <input type="file" class="form-control" name="image" accept="image/*">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">รายละเอียด</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <button type="submit" name="add_product" class="btn btn-primary">
                            <i class="fas fa-plus"></i> เพิ่มสินค้า
                        </button>
                    </form>
                </div>
            </div>

            <!-- รายการสินค้า -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-list"></i> รายการสินค้าทั้งหมด</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>รูปภาพ</th>
                                    <th>ชื่อสินค้า</th>
                                    <th>หมวดหมู่</th>
                                    <th>ราคา</th>
                                    <th>คลัง</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
                                <tr>
                                    <td>
                                        <?php if ($product['image_url']): ?>
                                            <img src="<?php echo $product['image_url']; ?>" 
                                                 style="width: 50px; height: 50px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-light d-flex align-items-center justify-content-center" 
                                                 style="width: 50px; height: 50px;">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $product['name']; ?></td>
                                    <td><?php echo $product['category_name']; ?></td>
                                    <td>฿<?php echo number_format($product['price'], 2); ?></td>
                                    <td>
                                        <span class="badge <?php echo $product['stock_quantity'] > 0 ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo $product['stock_quantity']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" name="delete_product" class="btn btn-danger btn-sm" 
                                                    onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบสินค้านี้?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Categories Section -->
        <div id="categories" class="section" style="display: none;">
            <h2><i class="fas fa-tags"></i> จัดการหมวดหมู่</h2>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-plus"></i> เพิ่มหมวดหมู่ใหม่</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">ชื่อหมวดหมู่</label>
                                    <input type="text" class="form-control" name="category_name" required>
                                </div>
                                <button type="submit" name="add_category" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> เพิ่มหมวดหมู่
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-list"></i> รายการหมวดหมู่</h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                <?php 
                                mysqli_data_seek($categories_result, 0);
                                while ($category = mysqli_fetch_assoc($categories_result)): 
                                ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <?php echo $category['name']; ?>
                                        <span class="badge bg-primary rounded-pill">
                                            <?php
                                            $count_query = "SELECT COUNT(*) as count FROM products WHERE category_id = " . $category['id'];
                                            $count_result = mysqli_query($conn, $count_query);
                                            $count = mysqli_fetch_assoc($count_result)['count'];
                                            echo $count . ' สินค้า';
                                            ?>
                                        </span>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders Section -->
        <div id="orders" class="section" style="display: none;">
            <h2><i class="fas fa-shopping-cart"></i> จัดการคำสั่งซื้อ</h2>
            
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>ลูกค้า</th>
                                    <th>ที่อยู่</th>
                                    <th>ยอดรวม</th>
                                    <th>สถานะ</th>
                                    <th>วันที่</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $all_orders_query = "SELECT o.*, u.username FROM orders o 
                                                     JOIN users u ON o.user_id = u.id 
                                                     ORDER BY o.order_date DESC";
                                $all_orders_result = mysqli_query($conn, $all_orders_query);
                                while ($order = mysqli_fetch_assoc($all_orders_result)): 
                                ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo $order['username']; ?></td>
                                    <td><?php echo $order['shipping_address']; ?></td>
                                    <td>฿<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status-<?php echo $order['status']; ?>">
                                            <?php
                                            $status_text = [
                                                'pending' => 'รอดำเนินการ',
                                                'processing' => 'กำลังจัดส่ง',
                                                'completed' => 'เสร็จสิ้น',
                                                'cancelled' => 'ยกเลิก'
                                            ];
                                            echo $status_text[$order['status']] ?? $order['status'];
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <select name="status" class="form-select form-select-sm" style="width: auto; display: inline-block;">
                                                <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>รอดำเนินการ</option>
                                                <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>กำลังจัดส่ง</option>
                                                <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>เสร็จสิ้น</option>
                                                <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>ยกเลิก</option>
                                            </select>
                                            <button type="submit" name="update_order_status" class="btn btn-primary btn-sm">
                                                <i class="fas fa-save"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Section -->
        <div id="users" class="section" style="display: none;">
            <h2><i class="fas fa-users"></i> จัดการผู้ใช้</h2>
            
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>ชื่อผู้ใช้</th>
                                    <th>อีเมล</th>
                                    <th>ชื่อ-นามสกุล</th>
                                    <th>เบอร์โทร</th>
                                    <th>วันที่สมัคร</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo $user['username']; ?></td>
                                    <td><?php echo $user['email']; ?></td>
                                    <td><?php echo $user['full_name']; ?></td>
                                    <td><?php echo $user['phone']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reports Section -->
        <div id="reports" class="section" style="display: none;">
            <h2><i class="fas fa-chart-bar"></i> รายงาน</h2>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-line"></i> ยอดขายรายเดือน</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="salesChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-trophy"></i> สินค้าขายดี</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $top_products_query = "SELECT p.name, SUM(oi.quantity) as total_sold 
                                                   FROM order_items oi 
                                                   JOIN products p ON oi.product_id = p.id 
                                                   JOIN orders o ON oi.order_id = o.id 
                                                   WHERE o.status = 'completed' 
                                                   GROUP BY p.id, p.name 
                                                   ORDER BY total_sold DESC LIMIT 5";
                            $top_products_result = mysqli_query($conn, $top_products_query);
                            ?>
                            <div class="list-group list-group-flush">
                                <?php 
                                $rank = 1;
                                while ($product = mysqli_fetch_assoc($top_products_result)): 
                                ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                        <div>
                                            <span class="badge bg-primary me-2"><?php echo $rank++; ?></span>
                                            <?php echo $product['name']; ?>
                                        </div>
                                        <span class="badge bg-success rounded-pill">
                                            <?php echo $product['total_sold']; ?> ชิ้น
                                        </span>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        // Function to show different sections
        function showSection(sectionId) {
            // Hide all sections
            const sections = document.querySelectorAll('.section');
            sections.forEach(section => {
                section.style.display = 'none';
            });
            
            // Remove active class from all nav links
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.classList.remove('active');
            });
            
            // Show selected section
            document.getElementById(sectionId).style.display = 'block';
            
            // Add active class to clicked nav link
            event.target.classList.add('active');
        }

        // Sales Chart
        const salesData = {
            labels: [
                <?php
                $months = [];
                $sales = [];
                mysqli_data_seek($sales_result, 0);
                while ($row = mysqli_fetch_assoc($sales_result)) {
                    $months[] = "'" . date('M Y', strtotime($row['month'] . '-01')) . "'";
                    $sales[] = $row['total_sales'];
                }
                echo implode(', ', array_reverse($months));
                ?>
            ],
            datasets: [{
                label: 'ยอดขาย (บาท)',
                data: [<?php echo implode(', ', array_reverse($sales)); ?>],
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1,
                fill: true
            }]
        };

        const salesConfig = {
            type: 'line',
            data: salesData,
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'ยอดขายรายเดือน'
                    },
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '฿' + value.toLocaleString();
                            }
                        }
                    }
                },
                elements: {
                    point: {
                        radius: 6,
                        hoverRadius: 8
                    }
                }
            }
        };

        // Initialize chart when reports section is shown
        let salesChart = null;
        
        // Override showSection function to handle chart initialization
        const originalShowSection = showSection;
        showSection = function(sectionId) {
            originalShowSection(sectionId);
            
            if (sectionId === 'reports' && !salesChart) {
                setTimeout(() => {
                    const ctx = document.getElementById('salesChart').getContext('2d');
                    salesChart = new Chart(ctx, salesConfig);
                }, 100);
            }
        };

        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Add confirmation for dangerous actions
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('button[name="delete_product"]');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!confirm('คุณแน่ใจหรือไม่ที่จะลบสินค้านี้? การกระทำนี้ไม่สามารถย้อนกลับได้')) {
                        e.preventDefault();
                    }
                });
            });
        });

        // Real-time search for products (bonus feature)
        function searchProducts() {
            const input = document.getElementById('productSearch');
            const filter = input.value.toUpperCase();
            const table = document.querySelector('#products .table tbody');
            const rows = table.getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const productName = rows[i].getElementsByTagName('td')[1];
                if (productName) {
                    const textValue = productName.textContent || productName.innerText;
                    if (textValue.toUpperCase().indexOf(filter) > -1) {
                        rows[i].style.display = '';
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
            }
        }
    </script>

    <style>
        /* Additional CSS for better UX */
        .section {
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid rgba(0, 0, 0, 0.125);
            transition: box-shadow 0.15s ease-in-out;
        }

        .card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .table th {
            background-color: #f8f9fa;
            border-top: none;
            font-weight: 600;
        }

        .btn {
            transition: all 0.2s ease-in-out;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .badge {
            font-size: 0.75em;
        }

        .list-group-item {
            transition: background-color 0.15s ease-in-out;
        }

        .list-group-item:hover {
            background-color: #f8f9fa;
        }

        /* Custom scrollbar for sidebar */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 3px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.5);
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .card-stat h3 {
                font-size: 1.5rem;
            }
        }

        /* Loading animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</body>
</html>