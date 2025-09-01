<?php
require_once 'config/database.php';

// Check if database exists, if not initialize it
if (!file_exists(__DIR__ . '/data/app.db')) {
    include 'data/init_db.php';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ร้านอุปกรณ์ตกปลา</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="public/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#" onclick="loadPage('home')">
                <i class="fas fa-fish"></i> ร้านอุปกรณ์ตกปลา
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="loadPage('home')">หน้าแรก</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="showCart()">
                            <i class="fas fa-shopping-cart"></i> ตะกร้า 
                            <span id="cart-count" class="badge bg-danger">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="loadPage('contact')">ติดต่อ</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav" id="auth-nav">
                    <!-- Authentication menu will be loaded here -->
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Left Sidebar -->
            <div class="col-md-3 bg-light p-3">
                <!-- Search Box -->
                <div class="mb-4">
                    <h5>ค้นหาสินค้า</h5>
                    <div class="input-group">
                        <input type="text" id="search-input" class="form-control" placeholder="ค้นหา...">
                        <button class="btn btn-outline-secondary" onclick="searchProducts()">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>

                <!-- Categories -->
                <div class="mb-4">
                    <h5>หมวดหมู่สินค้า</h5>
                    <div id="categories-list">
                        <!-- Categories will be loaded here -->
                    </div>
                </div>

                <!-- Admin Panel (only visible for admin users) -->
                <div id="admin-panel" class="mb-4" style="display: none;">
                    <h5 class="text-danger">แผงควบคุมผู้ดูแล</h5>
                    <div class="list-group">
                        <button class="list-group-item list-group-item-action" onclick="showAdminTab('products')">
                            <i class="fas fa-box"></i> จัดการสินค้า
                        </button>
                        <button class="list-group-item list-group-item-action" onclick="showAdminTab('categories')">
                            <i class="fas fa-tags"></i> จัดการหมวดหมู่
                        </button>
                        <button class="list-group-item list-group-item-action" onclick="showAdminTab('users')">
                            <i class="fas fa-users"></i> จัดการผู้ใช้
                        </button>
                        <button class="list-group-item list-group-item-action" onclick="showAdminTab('orders')">
                            <i class="fas fa-shopping-bag"></i> จัดการคำสั่งซื้อ
                        </button>
                        <button class="list-group-item list-group-item-action" onclick="showAdminTab('promotions')">
                            <i class="fas fa-percent"></i> จัดการโปรโมชั่น
                        </button>
                        <button class="list-group-item list-group-item-action" onclick="showAdminTab('reports')">
                            <i class="fas fa-chart-bar"></i> รายงาน
                        </button>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="col-md-9">
                <div id="main-content">
                    <!-- Main content will be loaded here -->
                </div>

                <!-- Admin Content Area -->
                <div id="admin-content" style="display: none;">
                    <!-- Admin content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">เข้าสู่ระบบ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="login-form">
                        <div class="mb-3">
                            <label for="login-username" class="form-label">ชื่อผู้ใช้</label>
                            <input type="text" class="form-control" id="login-username" required>
                        </div>
                        <div class="mb-3">
                            <label for="login-password" class="form-label">รหัสผ่าน</label>
                            <input type="password" class="form-control" id="login-password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">เข้าสู่ระบบ</button>
                    </form>
                    <div id="login-message" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Register Modal -->
    <div class="modal fade" id="registerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">สมัครสมาชิก</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="register-form">
                        <div class="mb-3">
                            <label for="register-username" class="form-label">ชื่อผู้ใช้</label>
                            <input type="text" class="form-control" id="register-username" required>
                        </div>
                        <div class="mb-3">
                            <label for="register-password" class="form-label">รหัสผ่าน</label>
                            <input type="password" class="form-control" id="register-password" required>
                        </div>
                        <div class="mb-3">
                            <label for="register-confirm-password" class="form-label">ยืนยันรหัสผ่าน</label>
                            <input type="password" class="form-control" id="register-confirm-password" required>
                        </div>
                        <button type="submit" class="btn btn-success w-100">สมัครสมาชิก</button>
                    </form>
                    <div id="register-message" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cart Modal -->
    <div class="modal fade" id="cartModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ตะกร้าสินค้า</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="cart-items">
                        <!-- Cart items will be loaded here -->
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <h5>รวม: <span id="cart-total">0</span> บาท</h5>
                        <button class="btn btn-success" onclick="showCheckout()" id="checkout-btn">ชำระเงิน</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Checkout Modal -->
    <div class="modal fade" id="checkoutModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ชำระเงิน</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="checkout-content">
                        <!-- Checkout content will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Confirmation Modal -->
    <div class="modal fade" id="orderConfirmModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ยืนยันการสั่งซื้อ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="order-confirmation-content">
                        <!-- Order confirmation content will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Receipt Modal -->
    <div class="modal fade" id="receiptModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ใบเสร็จรับเงิน</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="receipt-content">
                        <!-- Receipt content will be loaded here -->
                    </div>
                    <div class="text-center mt-3">
                        <button class="btn btn-primary" onclick="printReceipt()">
                            <i class="fas fa-print"></i> พิมพ์ใบเสร็จ
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Product Modal -->
    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productModalTitle">เพิ่มสินค้า</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="product-form">
                        <input type="hidden" id="product-id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="product-name" class="form-label">ชื่อสินค้า *</label>
                                    <input type="text" class="form-control" id="product-name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="product-category" class="form-label">หมวดหมู่</label>
                                    <select class="form-control" id="product-category">
                                        <option value="">เลือกหมวดหมู่</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="product-price" class="form-label">ราคา (บาท) *</label>
                                    <input type="number" class="form-control" id="product-price" min="0" step="0.01" required>
                                </div>
                                <div class="mb-3">
                                    <label for="product-stock" class="form-label">จำนวนในสต็อก *</label>
                                    <input type="number" class="form-control" id="product-stock" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="product-image" class="form-label">URL รูปภาพ</label>
                                    <input type="url" class="form-control" id="product-image" placeholder="https://example.com/image.jpg">
                                    <div class="form-text">หรือใช้ชื่อไฟล์ เช่น fishing-rod.jpg</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">ตัวอย่างรูปภาพ</label>
                                    <div id="image-preview" class="border rounded p-2 text-center">
                                        <img id="preview-img" src="" alt="Preview" style="max-width: 100%; height: 150px; object-fit: cover; display: none;">
                                        <p id="no-preview" class="text-muted mb-0">ไม่มีรูปภาพ</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="product-description" class="form-label">รายละเอียดสินค้า</label>
                            <textarea class="form-control" id="product-description" rows="3"></textarea>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="submit" class="btn btn-primary">บันทึก</button>
                        </div>
                    </form>
                    <div id="product-message" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Category Modal -->
    <div class="modal fade" id="categoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalTitle">เพิ่มหมวดหมู่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="category-form">
                        <input type="hidden" id="category-id">
                        <div class="mb-3">
                            <label for="category-name" class="form-label">ชื่อหมวดหมู่ *</label>
                            <input type="text" class="form-control" id="category-name" required>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="submit" class="btn btn-primary">บันทึก</button>
                        </div>
                    </form>
                    <div id="category-message" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin User Modal -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalTitle">เพิ่มผู้ใช้</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="user-form">
                        <input type="hidden" id="user-id">
                        <div class="mb-3">
                            <label for="user-username" class="form-label">ชื่อผู้ใช้ *</label>
                            <input type="text" class="form-control" id="user-username" required>
                        </div>
                        <div class="mb-3">
                            <label for="user-password" class="form-label">รหัสผ่าน <span id="password-required">*</span></label>
                            <input type="password" class="form-control" id="user-password">
                            <div class="form-text" id="password-help">สำหรับการแก้ไข: เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน</div>
                        </div>
                        <div class="mb-3">
                            <label for="user-role" class="form-label">บทบาท *</label>
                            <select class="form-control" id="user-role" required>
                                <option value="user">ผู้ใช้ทั่วไป</option>
                                <option value="admin">ผู้ดูแลระบบ</option>
                            </select>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="submit" class="btn btn-primary">บันทึก</button>
                        </div>
                    </form>
                    <div id="user-message" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Promotion Modal -->
    <div class="modal fade" id="promotionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="promotionModalTitle">เพิ่มโปรโมชั่น</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="promotion-form">
                        <input type="hidden" id="promotion-id">
                        <div class="mb-3">
                            <label for="promotion-code" class="form-label">รหัสโปรโมชั่น *</label>
                            <input type="text" class="form-control" id="promotion-code" required placeholder="เช่น SALE20">
                        </div>
                        <div class="mb-3">
                            <label for="promotion-discount" class="form-label">ส่วนลด (%) *</label>
                            <input type="number" class="form-control" id="promotion-discount" min="1" max="100" required>
                        </div>
                        <div class="mb-3">
                            <label for="promotion-expire" class="form-label">วันหมดอายุ *</label>
                            <input type="date" class="form-control" id="promotion-expire" required>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="submit" class="btn btn-primary">บันทึก</button>
                        </div>
                    </form>
                    <div id="promotion-message" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="public/js/app.js"></script>
</body>
</html>