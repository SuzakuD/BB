<?php
session_start();
require_once 'config.php';

// ตรวจสอบว่า login เป็น admin หรือไม่
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$pdo = getConnection();

// ตรวจสอบว่าเป็น admin
$stmt = $pdo->prepare("SELECT username, role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['username'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// จัดการการกระทำต่างๆ
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // เพิ่มสินค้าใหม่
    if (isset($_POST['add_product'])) {
        $name = $_POST['product_name'];
        $description = $_POST['description'];
        $price = (float)$_POST['price'];
        $stock = (int)$_POST['stock'];
        $category_id = (int)$_POST['category_id'];
        
        // จัดการอัพโหลดรูปภาพ
        $image_url = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $image_url = uploadImage($_FILES['image'], $name);
            if (!$image_url) {
                $error_msg = "ไม่สามารถอัพโหลดรูปภาพได้";
            }
        }
        
        if (!isset($error_msg)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock, category_id, image) 
                                      VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $description, $price, $stock, $category_id, $image_url]);
                $success_msg = "เพิ่มสินค้าสำเร็จ!";
            } catch (Exception $e) {
                $error_msg = "เกิดข้อผิดพลาด: " . $e->getMessage();
            }
        }
    }
    
    // เพิ่มหมวดหมู่ใหม่
    if (isset($_POST['add_category'])) {
        $category_name = $_POST['category_name'];
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->execute([$category_name]);
            $success_msg = "เพิ่มหมวดหมู่สำเร็จ!";
        } catch (Exception $e) {
            $error_msg = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    }
    
    // อัพเดตสถานะคำสั่งซื้อ
    if (isset($_POST['update_order_status'])) {
        $order_id = (int)$_POST['order_id'];
        $status = $_POST['status'];
        try {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$status, $order_id]);
            $success_msg = "อัพเดตสถานะคำสั่งซื้อสำเร็จ!";
        } catch (Exception $e) {
            $error_msg = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    }
    
    // แก้ไขสินค้า
    if (isset($_POST['edit_product'])) {
        $product_id = (int)$_POST['edit_product_id'];
        $name = $_POST['edit_product_name'];
        $description = $_POST['edit_description'];
        $price = (float)$_POST['edit_price'];
        $stock = (int)$_POST['edit_stock'];
        $category_id = (int)$_POST['edit_category_id'];
        
        try {
            // ถ้ามีการอัพโหลดรูปภาพใหม่
            if (isset($_FILES['edit_image']) && $_FILES['edit_image']['error'] == 0) {
                $image_url = uploadImage($_FILES['edit_image'], $name);
                if ($image_url) {
                    $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, category_id = ?, image = ? WHERE id = ?");
                    $stmt->execute([$name, $description, $price, $stock, $category_id, $image_url, $product_id]);
                } else {
                    $error_msg = "ไม่สามารถอัพโหลดรูปภาพได้";
                }
            } else {
                // ไม่มีการอัพโหลดรูปภาพใหม่
                $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, category_id = ? WHERE id = ?");
                $stmt->execute([$name, $description, $price, $stock, $category_id, $product_id]);
            }
            
            if (!isset($error_msg)) {
                $success_msg = "แก้ไขสินค้าสำเร็จ!";
            }
        } catch (Exception $e) {
            $error_msg = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    }
    
    // ลบสินค้า
    if (isset($_POST['delete_product'])) {
        $product_id = (int)$_POST['product_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $success_msg = "ลบสินค้าสำเร็จ!";
        } catch (Exception $e) {
            $error_msg = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    }
}

// ดึงข้อมูลสถิติ
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total_products FROM products");
    $total_products = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_orders FROM orders");
    $total_orders = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE username != 'admin'");
    $total_users = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COALESCE(SUM(total), 0) as total_revenue FROM orders WHERE status = 'completed'");
    $total_revenue = $stmt->fetchColumn();
    
    $stats = [
        'total_products' => $total_products,
        'total_orders' => $total_orders,
        'total_users' => $total_users,
        'total_revenue' => $total_revenue
    ];
} catch (Exception $e) {
    $stats = [
        'total_products' => 0,
        'total_orders' => 0,
        'total_users' => 0,
        'total_revenue' => 0
    ];
}

// ดึงข้อมูลสินค้า
try {
    $stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p 
                         LEFT JOIN categories c ON p.category_id = c.id 
                         ORDER BY p.id DESC");
    $products = $stmt->fetchAll();
} catch (Exception $e) {
    $products = [];
}

// ดึงข้อมูลหมวดหมู่
try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
} catch (Exception $e) {
    $categories = [];
}

// ดึงข้อมูลคำสั่งซื้อ
try {
    $stmt = $pdo->query("SELECT o.*, u.username FROM orders o 
                         JOIN users u ON o.user_id = u.id 
                         ORDER BY o.created_at DESC LIMIT 10");
    $orders = $stmt->fetchAll();
} catch (Exception $e) {
    $orders = [];
}

// ดึงข้อมูลผู้ใช้
try {
    $stmt = $pdo->query("SELECT * FROM users WHERE username != 'admin' ORDER BY created_at DESC");
    $users = $stmt->fetchAll();
} catch (Exception $e) {
    $users = [];
}

// ดึงข้อมูลยอดขายรายเดือน
try {
    $stmt = $pdo->query("SELECT strftime('%Y-%m', created_at) as month, 
                         SUM(total) as total_sales 
                         FROM orders 
                         WHERE status = 'completed' 
                         GROUP BY strftime('%Y-%m', created_at) 
                         ORDER BY month DESC LIMIT 12");
    $sales = $stmt->fetchAll();
} catch (Exception $e) {
    $sales = [];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - ระบบจัดการร้านขายอุปกรณ์ตกปลา</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
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
            overflow-x: auto;
            max-width: calc(100vw - 250px);
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
                <?php echo htmlspecialchars($success_msg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo htmlspecialchars($error_msg); ?>
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
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['username']); ?></td>
                                    <td>฿<?php echo number_format($order['total'], 2); ?></td>
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
                                    <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
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
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
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

            <!-- Edit Product Modal -->
            <div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editProductModalLabel">แก้ไขสินค้า</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST" enctype="multipart/form-data" id="editProductForm">
                            <div class="modal-body">
                                <input type="hidden" name="edit_product_id" id="edit_product_id">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">ชื่อสินค้า</label>
                                            <input type="text" class="form-control" name="edit_product_name" id="edit_product_name" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">หมวดหมู่</label>
                                            <select class="form-select" name="edit_category_id" id="edit_category_id" required>
                                                <?php foreach ($categories as $category): ?>
                                                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">คำอธิบาย</label>
                                    <textarea class="form-control" name="edit_description" id="edit_description" rows="3"></textarea>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">ราคา (บาท)</label>
                                            <input type="number" class="form-control" name="edit_price" id="edit_price" step="0.01" min="0" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">จำนวนในคลัง</label>
                                            <input type="number" class="form-control" name="edit_stock" id="edit_stock" min="0" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">รูปภาพใหม่ (ถ้าต้องการเปลี่ยน)</label>
                                    <input type="file" class="form-control" name="edit_image" accept="image/*">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                                <button type="submit" name="edit_product" class="btn btn-primary">บันทึกการแก้ไข</button>
                            </div>
                        </form>
                    </div>
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
                                <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <?php if ($product['image']): ?>
                                            <img src="uploads/<?php echo $product['image']; ?>" 
                                                 style="width: 50px; height: 50px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-light d-flex align-items-center justify-content-center" 
                                                 style="width: 50px; height: 50px;">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td data-category-id="<?php echo $product['category_id']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></td>
                                    <td>฿<?php echo number_format($product['price'], 2); ?></td>
                                    <td>
                                        <span class="badge <?php echo $product['stock'] > 0 ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo $product['stock']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-primary btn-sm me-1" 
                                                onclick="editProduct(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>', '<?php echo htmlspecialchars($product['description']); ?>', <?php echo $product['price']; ?>, <?php echo $product['stock']; ?>, <?php echo $product['category_id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" name="delete_product" class="btn btn-danger btn-sm" 
                                                    onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบสินค้านี้?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
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
                                <?php foreach ($categories as $category): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center category-item" 
                                         onclick="showProductsForCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')"
                                         style="cursor: pointer;">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                        <span class="badge bg-primary rounded-pill">
                                            <?php
                                            try {
                                                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
                                                $stmt->execute([$category['id']]);
                                                $count = $stmt->fetchColumn();
                                                echo $count . ' สินค้า';
                                            } catch (Exception $e) {
                                                echo '0 สินค้า';
                                            }
                                            ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
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
                                try {
                                    $stmt = $pdo->query("SELECT o.*, u.username FROM orders o 
                                                         JOIN users u ON o.user_id = u.id 
                                                         ORDER BY o.created_at DESC");
                                    $all_orders = $stmt->fetchAll();
                                } catch (Exception $e) {
                                    $all_orders = [];
                                }
                                
                                foreach ($all_orders as $order): 
                                ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo $order['username']; ?></td>
                                    <td><?php echo $order['shipping_address']; ?></td>
                                    <td>฿<?php echo number_format($order['total'], 2); ?></td>
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
                                    <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
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
                                <?php endforeach; ?>
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
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($user['full_name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone'] ?? ''); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
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
                            try {
                                $stmt = $pdo->query("SELECT p.name, SUM(oi.quantity) as total_sold 
                                                   FROM order_items oi 
                                                   JOIN products p ON oi.product_id = p.id 
                                                   JOIN orders o ON oi.order_id = o.id 
                                                   WHERE o.status = 'completed' 
                                                   GROUP BY p.id, p.name 
                                                   ORDER BY total_sold DESC LIMIT 5");
                                $top_products = $stmt->fetchAll();
                            } catch (Exception $e) {
                                $top_products = [];
                            }
                            ?>
                            <div class="list-group list-group-flush">
                                <?php if (empty($top_products)): ?>
                                    <div class="text-muted text-center py-3">ยังไม่มีข้อมูลการขาย</div>
                                <?php else: ?>
                                    <?php 
                                    $rank = 1;
                                    foreach ($top_products as $product): 
                                    ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                            <div>
                                                <span class="badge bg-primary me-2"><?php echo $rank++; ?></span>
                                                <?php echo htmlspecialchars($product['name']); ?>
                                            </div>
                                            <span class="badge bg-success rounded-pill">
                                                <?php echo $product['total_sold']; ?> ชิ้น
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
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

        // Function to show products for a specific category
        function showProductsForCategory(categoryId, categoryName) {
            // Switch to products section
            showSection('products');
            
            // Update the products section title
            const productsTitle = document.querySelector('#products h2');
            productsTitle.innerHTML = `<i class="fas fa-box"></i> จัดการสินค้า - ${categoryName}`;
            
            // Filter products by category
            filterProductsByCategory(categoryId);
            
            // Add back button
            addBackToAllProductsButton();
        }

        // Function to filter products by category
        function filterProductsByCategory(categoryId) {
            const productRows = document.querySelectorAll('#products .table tbody tr');
            productRows.forEach(row => {
                const categoryCell = row.querySelector('td:nth-child(3)'); // Category is 3rd column
                if (categoryCell) {
                    const rowCategoryId = categoryCell.getAttribute('data-category-id');
                    if (rowCategoryId == categoryId) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        }

        // Function to add back button to show all products
        function addBackToAllProductsButton() {
            const productsSection = document.getElementById('products');
            let backButton = document.getElementById('back-to-all-products');
            
            if (!backButton) {
                backButton = document.createElement('button');
                backButton.id = 'back-to-all-products';
                backButton.className = 'btn btn-secondary mb-3';
                backButton.innerHTML = '<i class="fas fa-arrow-left"></i> ดูสินค้าทั้งหมด';
                backButton.onclick = showAllProducts;
                
                const productsTitle = document.querySelector('#products h2');
                productsTitle.parentNode.insertBefore(backButton, productsTitle.nextSibling);
            }
        }

        // Function to show all products
        function showAllProducts() {
            const productRows = document.querySelectorAll('#products .table tbody tr');
            productRows.forEach(row => {
                row.style.display = '';
            });
            
            // Update title back to original
            const productsTitle = document.querySelector('#products h2');
            productsTitle.innerHTML = '<i class="fas fa-box"></i> จัดการสินค้า';
            
            // Remove back button
            const backButton = document.getElementById('back-to-all-products');
            if (backButton) {
                backButton.remove();
            }
        }

        // Sales Chart
        const salesData = {
            labels: [
                <?php
                $months = [];
                $sales = [];
                foreach ($sales as $row) {
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

        // Function to edit product
        function editProduct(id, name, description, price, stock, categoryId) {
            document.getElementById('edit_product_id').value = id;
            document.getElementById('edit_product_name').value = name;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_price').value = price;
            document.getElementById('edit_stock').value = stock;
            document.getElementById('edit_category_id').value = categoryId;
            
            // Show the modal
            const editModal = new bootstrap.Modal(document.getElementById('editProductModal'));
            editModal.show();
        }

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

        .category-item {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .category-item:hover {
            background-color: #e3f2fd !important;
            transform: translateX(5px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
                margin-bottom: 1rem;
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .card-stat h3 {
                font-size: 1.5rem;
            }
            
            .table-responsive {
                font-size: 0.875rem;
            }
            
            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 0.5rem;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .table-responsive {
                font-size: 0.8rem;
            }
            
            .btn {
                font-size: 0.875rem;
                padding: 0.375rem 0.75rem;
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