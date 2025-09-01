/**
 * Main JavaScript file for the fishing equipment e-commerce SPA
 */

// Global state
let currentUser = null;
let currentPage = 'home';
let cartCount = 0;
let currentCategory = null;
let searchQuery = '';

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

async function initializeApp() {
    try {
        // Check authentication status
        await checkAuthStatus();
        
        // Load initial data
        await Promise.all([
            loadCategories(),
            updateCartCount(),
            loadPage('home')
        ]);
        
        // Set up event listeners
        setupEventListeners();
        
    } catch (error) {
        console.error('Failed to initialize app:', error);
        showAlert('เกิดข้อผิดพลาดในการโหลดแอปพลิเคชัน', 'danger');
    }
}

function setupEventListeners() {
    // Search functionality
    document.getElementById('search-input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            searchProducts();
        }
    });
    
    // Form submissions
    document.getElementById('login-form').addEventListener('submit', handleLogin);
    document.getElementById('register-form').addEventListener('submit', handleRegister);
}

// Authentication functions
async function checkAuthStatus() {
    try {
        const response = await fetch('api/auth.php?action=check');
        const data = await response.json();
        
        if (data.authenticated) {
            currentUser = data.user;
            updateAuthUI();
        } else {
            currentUser = null;
            updateAuthUI();
        }
    } catch (error) {
        console.error('Auth check failed:', error);
    }
}

function updateAuthUI() {
    const authNav = document.getElementById('auth-nav');
    const adminPanel = document.getElementById('admin-panel');
    
    if (currentUser) {
        authNav.innerHTML = `
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                    <i class="fas fa-user"></i> ${currentUser.username}
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" onclick="showUserOrders()">คำสั่งซื้อของฉัน</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#" onclick="logout()">ออกจากระบบ</a></li>
                </ul>
            </li>
        `;
        
        // Show admin panel if user is admin
        if (currentUser.role === 'admin') {
            adminPanel.style.display = 'block';
        }
    } else {
        authNav.innerHTML = `
            <li class="nav-item">
                <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#loginModal">เข้าสู่ระบบ</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#registerModal">สมัครสมาชิก</a>
            </li>
        `;
        
        adminPanel.style.display = 'none';
    }
}

async function handleLogin(e) {
    e.preventDefault();
    
    const username = document.getElementById('login-username').value;
    const password = document.getElementById('login-password').value;
    const messageDiv = document.getElementById('login-message');
    
    try {
        const response = await fetch('api/auth.php?action=login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ username, password })
        });
        
        const data = await response.json();
        
        if (data.success) {
            currentUser = data.user;
            updateAuthUI();
            bootstrap.Modal.getInstance(document.getElementById('loginModal')).hide();
            showAlert(data.message, 'success');
            document.getElementById('login-form').reset();
        } else {
            messageDiv.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
        }
    } catch (error) {
        messageDiv.innerHTML = `<div class="alert alert-danger">เกิดข้อผิดพลาด</div>`;
    }
}

async function handleRegister(e) {
    e.preventDefault();
    
    const username = document.getElementById('register-username').value;
    const password = document.getElementById('register-password').value;
    const confirmPassword = document.getElementById('register-confirm-password').value;
    const messageDiv = document.getElementById('register-message');
    
    try {
        const response = await fetch('api/auth.php?action=register', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ username, password, confirmPassword })
        });
        
        const data = await response.json();
        
        if (data.success) {
            currentUser = data.user;
            updateAuthUI();
            bootstrap.Modal.getInstance(document.getElementById('registerModal')).hide();
            showAlert(data.message, 'success');
            document.getElementById('register-form').reset();
        } else {
            messageDiv.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
        }
    } catch (error) {
        messageDiv.innerHTML = `<div class="alert alert-danger">เกิดข้อผิดพลาด</div>`;
    }
}

async function logout() {
    try {
        await fetch('api/auth.php?action=logout', { method: 'DELETE' });
        currentUser = null;
        updateAuthUI();
        cartCount = 0;
        updateCartCount();
        showAlert('ออกจากระบบสำเร็จ', 'success');
        loadPage('home');
    } catch (error) {
        showAlert('เกิดข้อผิดพลาดในการออกจากระบบ', 'danger');
    }
}

// Page loading functions
async function loadPage(page) {
    currentPage = page;
    const mainContent = document.getElementById('main-content');
    const adminContent = document.getElementById('admin-content');
    
    // Hide admin content when loading regular pages
    adminContent.style.display = 'none';
    mainContent.style.display = 'block';
    
    switch (page) {
        case 'home':
            await loadHomePage();
            break;
        case 'contact':
            loadContactPage();
            break;
        default:
            await loadHomePage();
    }
}

async function loadHomePage() {
    try {
        const response = await fetch('api/products.php?action=list');
        const data = await response.json();
        
        const mainContent = document.getElementById('main-content');
        
        let html = `
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>สินค้าทั้งหมด</h2>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted">แสดง ${data.products.length} จาก ${data.total} รายการ</span>
                </div>
            </div>
            <div class="row" id="products-container">
        `;
        
        if (data.products.length === 0) {
            html += `
                <div class="col-12 text-center py-5">
                    <i class="fas fa-fish fa-3x text-muted mb-3"></i>
                    <h4>ยังไม่มีสินค้า</h4>
                    <p class="text-muted">กรุณารอสักครู่ เรากำลังเพิ่มสินค้าใหม่</p>
                </div>
            `;
        } else {
            data.products.forEach(product => {
                html += createProductCard(product);
            });
        }
        
        html += `</div>`;
        
        // Add pagination if needed
        if (data.pages > 1) {
            html += createPagination(data.page, data.pages);
        }
        
        mainContent.innerHTML = html;
    } catch (error) {
        console.error('Failed to load products:', error);
        showAlert('ไม่สามารถโหลดสินค้าได้', 'danger');
    }
}

function createProductCard(product) {
    const stockClass = product.stock < 10 ? 'stock-low' : product.stock < 50 ? 'stock-medium' : 'stock-high';
    const stockText = product.stock === 0 ? 'สินค้าหมด' : `คงเหลือ ${product.stock} ชิ้น`;
    
    return `
        <div class="col-md-4 col-lg-3 mb-4">
            <div class="card product-card h-100">
                <img src="${product.image || 'https://via.placeholder.com/300x200?text=No+Image'}" 
                     class="card-img-top product-image" alt="${product.name}">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">${product.name}</h5>
                    <p class="card-text text-muted small flex-grow-1">${product.description || ''}</p>
                    <div class="mb-2">
                        <span class="price">${Number(product.price).toLocaleString()} บาท</span>
                    </div>
                    <div class="mb-3">
                        <small class="${stockClass}">${stockText}</small>
                    </div>
                    <button class="btn btn-primary w-100" 
                            onclick="addToCart(${product.id})" 
                            ${product.stock === 0 ? 'disabled' : ''}>
                        <i class="fas fa-cart-plus"></i> เพิ่มลงตะกร้า
                    </button>
                </div>
            </div>
        </div>
    `;
}

function loadContactPage() {
    const mainContent = document.getElementById('main-content');
    mainContent.innerHTML = `
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <h2 class="mb-4">ติดต่อเรา</h2>
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5><i class="fas fa-map-marker-alt"></i> ที่อยู่</h5>
                                    <p>123 ถนนตกปลา<br>
                                    เขตปลาใหญ่ กรุงเทพฯ 10110</p>
                                    
                                    <h5><i class="fas fa-phone"></i> โทรศัพท์</h5>
                                    <p>02-123-4567</p>
                                    
                                    <h5><i class="fas fa-envelope"></i> อีเมล</h5>
                                    <p>info@fishingstore.com</p>
                                    
                                    <h5><i class="fas fa-clock"></i> เวลาทำการ</h5>
                                    <p>จันทร์-ศุกร์: 8:00-18:00<br>
                                    เสาร์-อาทิตย์: 9:00-17:00</p>
                                </div>
                                <div class="col-md-6">
                                    <h5>ส่งข้อความถึงเรา</h5>
                                    <form>
                                        <div class="mb-3">
                                            <label class="form-label">ชื่อ</label>
                                            <input type="text" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">อีเมล</label>
                                            <input type="email" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">ข้อความ</label>
                                            <textarea class="form-control" rows="5" required></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary">ส่งข้อความ</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Category functions
async function loadCategories() {
    try {
        const response = await fetch('api/products.php?action=categories');
        const data = await response.json();
        
        const categoriesList = document.getElementById('categories-list');
        let html = `
            <div class="category-item ${currentCategory === null ? 'active' : ''}" onclick="filterByCategory(null)">
                <i class="fas fa-th"></i> ทั้งหมด
            </div>
        `;
        
        data.categories.forEach(category => {
            html += `
                <div class="category-item ${currentCategory === category.id ? 'active' : ''}" 
                     onclick="filterByCategory(${category.id})">
                    <i class="fas fa-tag"></i> ${category.name}
                </div>
            `;
        });
        
        categoriesList.innerHTML = html;
    } catch (error) {
        console.error('Failed to load categories:', error);
    }
}

async function filterByCategory(categoryId) {
    currentCategory = categoryId;
    
    try {
        let url = 'api/products.php?action=list';
        if (categoryId) {
            url = `api/products.php?action=category&category_id=${categoryId}`;
        }
        
        const response = await fetch(url);
        const data = await response.json();
        
        updateProductsDisplay(data);
        updateCategoryUI();
    } catch (error) {
        console.error('Failed to filter products:', error);
        showAlert('ไม่สามารถกรองสินค้าได้', 'danger');
    }
}

function updateCategoryUI() {
    document.querySelectorAll('.category-item').forEach(item => {
        item.classList.remove('active');
    });
    
    if (currentCategory === null) {
        document.querySelector('.category-item').classList.add('active');
    } else {
        document.querySelector(`[onclick="filterByCategory(${currentCategory})"]`).classList.add('active');
    }
}

// Search functions
async function searchProducts() {
    const searchInput = document.getElementById('search-input');
    searchQuery = searchInput.value.trim();
    
    if (searchQuery === '') {
        loadPage('home');
        return;
    }
    
    try {
        const response = await fetch(`api/products.php?action=search&q=${encodeURIComponent(searchQuery)}`);
        const data = await response.json();
        
        updateProductsDisplay(data, `ผลการค้นหา "${searchQuery}"`);
    } catch (error) {
        console.error('Search failed:', error);
        showAlert('ไม่สามารถค้นหาได้', 'danger');
    }
}

function updateProductsDisplay(data, title = 'สินค้าทั้งหมด') {
    const mainContent = document.getElementById('main-content');
    
    let html = `
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>${title}</h2>
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted">แสดง ${data.products.length} จาก ${data.total} รายการ</span>
            </div>
        </div>
        <div class="row" id="products-container">
    `;
    
    if (data.products.length === 0) {
        html += `
            <div class="col-12 text-center py-5">
                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                <h4>ไม่พบสินค้า</h4>
                <p class="text-muted">ลองค้นหาด้วยคำอื่น หรือดูสินค้าทั้งหมด</p>
            </div>
        `;
    } else {
        data.products.forEach(product => {
            html += createProductCard(product);
        });
    }
    
    html += `</div>`;
    
    if (data.pages > 1) {
        html += createPagination(data.page, data.pages);
    }
    
    mainContent.innerHTML = html;
}

// Cart functions
async function updateCartCount() {
    try {
        const response = await fetch('api/cart.php?action=count');
        const data = await response.json();
        
        cartCount = data.count || 0;
        document.getElementById('cart-count').textContent = cartCount;
    } catch (error) {
        console.error('Failed to update cart count:', error);
    }
}

async function addToCart(productId, quantity = 1) {
    try {
        const response = await fetch('api/cart.php?action=add', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ product_id: productId, quantity })
        });
        
        const data = await response.json();
        
        if (data.success) {
            cartCount = data.count;
            document.getElementById('cart-count').textContent = cartCount;
            showAlert(data.message, 'success');
        } else {
            showAlert(data.error, 'danger');
        }
    } catch (error) {
        console.error('Failed to add to cart:', error);
        showAlert('ไม่สามารถเพิ่มสินค้าลงตะกร้าได้', 'danger');
    }
}

async function showCart() {
    try {
        const response = await fetch('api/cart.php?action=items');
        const data = await response.json();
        
        const cartItems = document.getElementById('cart-items');
        const cartTotal = document.getElementById('cart-total');
        const checkoutBtn = document.getElementById('checkout-btn');
        
        if (data.items.length === 0) {
            cartItems.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                    <h5>ตะกร้าสินค้าว่าง</h5>
                    <p class="text-muted">เพิ่มสินค้าลงตะกร้าเพื่อเริ่มต้นการสั่งซื้อ</p>
                </div>
            `;
            checkoutBtn.disabled = true;
        } else {
            let html = '';
            data.items.forEach(item => {
                html += `
                    <div class="cart-item d-flex align-items-center">
                        <img src="${item.image || 'https://via.placeholder.com/80x80?text=No+Image'}" 
                             class="me-3" style="width: 80px; height: 80px; object-fit: cover;" alt="${item.name}">
                        <div class="flex-grow-1">
                            <h6>${item.name}</h6>
                            <p class="text-muted mb-1">${Number(item.price).toLocaleString()} บาท</p>
                            <div class="quantity-controls">
                                <button class="quantity-btn" onclick="updateCartQuantity(${item.id}, ${item.quantity - 1})">-</button>
                                <span class="mx-2">${item.quantity}</span>
                                <button class="quantity-btn" onclick="updateCartQuantity(${item.id}, ${item.quantity + 1})" 
                                        ${item.quantity >= item.stock ? 'disabled' : ''}>+</button>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold">${Number(item.total).toLocaleString()} บาท</div>
                            <button class="btn btn-sm btn-outline-danger mt-1" onclick="removeFromCart(${item.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            
            cartItems.innerHTML = html;
            checkoutBtn.disabled = false;
        }
        
        cartTotal.textContent = Number(data.total).toLocaleString();
        
        new bootstrap.Modal(document.getElementById('cartModal')).show();
        
    } catch (error) {
        console.error('Failed to load cart:', error);
        showAlert('ไม่สามารถโหลดตะกร้าสินค้าได้', 'danger');
    }
}

async function updateCartQuantity(productId, quantity) {
    try {
        const response = await fetch('api/cart.php?action=update', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ product_id: productId, quantity })
        });
        
        const data = await response.json();
        
        if (data.success) {
            cartCount = data.count;
            document.getElementById('cart-count').textContent = cartCount;
            showCart(); // Refresh cart display
        } else {
            showAlert(data.error, 'danger');
        }
    } catch (error) {
        console.error('Failed to update cart:', error);
        showAlert('ไม่สามารถอัปเดตตะกร้าได้', 'danger');
    }
}

async function removeFromCart(productId) {
    try {
        const response = await fetch(`api/cart.php?action=remove&product_id=${productId}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.success) {
            cartCount = data.count;
            document.getElementById('cart-count').textContent = cartCount;
            showCart(); // Refresh cart display
            showAlert(data.message, 'success');
        } else {
            showAlert(data.error, 'danger');
        }
    } catch (error) {
        console.error('Failed to remove from cart:', error);
        showAlert('ไม่สามารถลบสินค้าจากตะกร้าได้', 'danger');
    }
}

// Checkout functions
async function showCheckout() {
    if (!currentUser) {
        showAlert('กรุณาเข้าสู่ระบบก่อนทำการสั่งซื้อ', 'warning');
        new bootstrap.Modal(document.getElementById('loginModal')).show();
        return;
    }
    
    bootstrap.Modal.getInstance(document.getElementById('cartModal')).hide();
    
    const checkoutContent = document.getElementById('checkout-content');
    checkoutContent.innerHTML = `
        <div class="row">
            <div class="col-md-8">
                <h5>ข้อมูลการสั่งซื้อ</h5>
                <form id="checkout-form">
                    <div class="mb-3">
                        <label class="form-label">รหัสโปรโมชั่น (ถ้ามี)</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="promotion-code" placeholder="กรอกรหัสโปรโมชั่น">
                            <button class="btn btn-outline-secondary" type="button" onclick="validatePromotion()">ตรวจสอบ</button>
                        </div>
                        <div id="promotion-message" class="mt-2"></div>
                    </div>
                    <button type="submit" class="btn btn-success w-100">ยืนยันการสั่งซื้อ</button>
                </form>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6>สรุปคำสั่งซื้อ</h6>
                    </div>
                    <div class="card-body" id="checkout-summary">
                        <div class="text-center">
                            <div class="spinner"></div>
                            <p>กำลังโหลด...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Load checkout summary
    await loadCheckoutSummary();
    
    // Set up form handler
    document.getElementById('checkout-form').addEventListener('submit', processCheckout);
    
    new bootstrap.Modal(document.getElementById('checkoutModal')).show();
}

async function loadCheckoutSummary() {
    try {
        const response = await fetch('api/cart.php?action=items');
        const data = await response.json();
        
        const summaryDiv = document.getElementById('checkout-summary');
        
        if (data.items.length === 0) {
            summaryDiv.innerHTML = '<p class="text-muted">ตะกร้าสินค้าว่าง</p>';
            return;
        }
        
        let html = '';
        data.items.forEach(item => {
            html += `
                <div class="d-flex justify-content-between mb-2">
                    <span>${item.name} x${item.quantity}</span>
                    <span>${Number(item.total).toLocaleString()} บาท</span>
                </div>
            `;
        });
        
        html += `
            <hr>
            <div class="d-flex justify-content-between fw-bold">
                <span>รวมทั้งสิ้น</span>
                <span>${Number(data.total).toLocaleString()} บาท</span>
            </div>
        `;
        
        summaryDiv.innerHTML = html;
    } catch (error) {
        console.error('Failed to load checkout summary:', error);
    }
}

async function processCheckout(e) {
    e.preventDefault();
    
    const promotionCode = document.getElementById('promotion-code').value;
    
    try {
        const response = await fetch('api/orders.php?action=checkout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ promotion_code: promotionCode })
        });
        
        const data = await response.json();
        
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('checkoutModal')).hide();
            showOrderConfirmation(data.order_id, data.total);
            updateCartCount();
        } else {
            showAlert(data.error, 'danger');
        }
    } catch (error) {
        console.error('Checkout failed:', error);
        showAlert('ไม่สามารถทำการสั่งซื้อได้', 'danger');
    }
}

function showOrderConfirmation(orderId, total) {
    const confirmationContent = document.getElementById('order-confirmation-content');
    confirmationContent.innerHTML = `
        <div class="text-center">
            <i class="fas fa-check-circle fa-4x text-success mb-4"></i>
            <h4>สั่งซื้อสำเร็จ!</h4>
            <p class="text-muted">เลขที่คำสั่งซื้อ: #${orderId}</p>
            <p class="text-muted">ยอดรวม: ${Number(total).toLocaleString()} บาท</p>
            <div class="mt-4">
                <button class="btn btn-primary me-2" onclick="showReceipt(${orderId})">
                    <i class="fas fa-receipt"></i> ดูใบเสร็จ
                </button>
                <button class="btn btn-outline-secondary" onclick="loadPage('home')">
                    กลับหน้าแรก
                </button>
            </div>
        </div>
    `;
    
    new bootstrap.Modal(document.getElementById('orderConfirmModal')).show();
}

// Receipt functions
async function showReceipt(orderId) {
    try {
        const response = await fetch(`api/orders.php?action=receipt&order_id=${orderId}`);
        const data = await response.json();
        
        if (data.receipt_html) {
            const receiptContent = document.getElementById('receipt-content');
            receiptContent.innerHTML = data.receipt_html;
            receiptContent.dataset.orderId = orderId;
            new bootstrap.Modal(document.getElementById('receiptModal')).show();
        } else {
            showAlert(data.error, 'danger');
        }
    } catch (error) {
        console.error('Failed to load receipt:', error);
        showAlert('ไม่สามารถโหลดใบเสร็จได้', 'danger');
    }
}

function printReceipt() {
    const orderId = document.querySelector('#receipt-content').dataset.orderId;
    if (orderId) {
        // Open receipt in new window for better printing
        window.open(`api/receipt.php?order_id=${orderId}`, '_blank', 'width=800,height=600');
    } else {
        window.print();
    }
}

// Admin functions
async function showAdminTab(tab) {
    const mainContent = document.getElementById('main-content');
    const adminContent = document.getElementById('admin-content');
    
    mainContent.style.display = 'none';
    adminContent.style.display = 'block';
    
    switch (tab) {
        case 'products':
            await loadAdminProducts();
            break;
        case 'categories':
            await loadAdminCategories();
            break;
        case 'users':
            await loadAdminUsers();
            break;
        case 'orders':
            await loadAdminOrders();
            break;
        case 'promotions':
            await loadAdminPromotions();
            break;
        case 'reports':
            await loadAdminReports();
            break;
    }
}

async function loadAdminProducts() {
    try {
        const response = await fetch('api/products.php?action=list');
        const data = await response.json();
        
        const adminContent = document.getElementById('admin-content');
        
        let html = `
            <div class="admin-tab">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3>จัดการสินค้า</h3>
                    <button class="btn btn-primary" onclick="showAddProductModal()">
                        <i class="fas fa-plus"></i> เพิ่มสินค้า
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped admin-table">
                        <thead>
                            <tr>
                                <th>รูปภาพ</th>
                                <th>ชื่อสินค้า</th>
                                <th>หมวดหมู่</th>
                                <th>ราคา</th>
                                <th>คงเหลือ</th>
                                <th>การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
        `;
        
        data.products.forEach(product => {
            const stockClass = product.stock < 10 ? 'text-danger' : product.stock < 50 ? 'text-warning' : 'text-success';
            
            html += `
                <tr>
                    <td>
                        <img src="${product.image || 'https://via.placeholder.com/50x50?text=No+Image'}" 
                             style="width: 50px; height: 50px; object-fit: cover;" alt="${product.name}">
                    </td>
                    <td>${product.name}</td>
                    <td>${product.category_name || '-'}</td>
                    <td>${Number(product.price).toLocaleString()} บาท</td>
                    <td class="${stockClass}">${product.stock}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary me-1" onclick="editProduct(${product.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteProduct(${product.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        html += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        
        adminContent.innerHTML = html;
    } catch (error) {
        console.error('Failed to load admin products:', error);
        showAlert('ไม่สามารถโหลดข้อมูลสินค้าได้', 'danger');
    }
}

// Utility functions
function createPagination(currentPage, totalPages) {
    if (totalPages <= 1) return '';
    
    let html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous button
    if (currentPage > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="loadPage(${currentPage - 1})">ก่อนหน้า</a></li>`;
    }
    
    // Page numbers
    for (let i = Math.max(1, currentPage - 2); i <= Math.min(totalPages, currentPage + 2); i++) {
        html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="loadPage(${i})">${i}</a>
                 </li>`;
    }
    
    // Next button
    if (currentPage < totalPages) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="loadPage(${currentPage + 1})">ถัดไป</a></li>`;
    }
    
    html += '</ul></nav>';
    return html;
}

function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-float alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Auto dismiss after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.parentNode.removeChild(alertDiv);
        }
    }, 5000);
}

// Placeholder functions for admin features
async function loadAdminCategories() {
    document.getElementById('admin-content').innerHTML = '<div class="admin-tab"><h3>จัดการหมวดหมู่</h3><p>ฟีเจอร์นี้กำลังพัฒนา</p></div>';
}

async function loadAdminUsers() {
    document.getElementById('admin-content').innerHTML = '<div class="admin-tab"><h3>จัดการผู้ใช้</h3><p>ฟีเจอร์นี้กำลังพัฒนา</p></div>';
}

async function loadAdminOrders() {
    document.getElementById('admin-content').innerHTML = '<div class="admin-tab"><h3>จัดการคำสั่งซื้อ</h3><p>ฟีเจอร์นี้กำลังพัฒนา</p></div>';
}

async function loadAdminPromotions() {
    document.getElementById('admin-content').innerHTML = '<div class="admin-tab"><h3>จัดการโปรโมชั่น</h3><p>ฟีเจอร์นี้กำลังพัฒนา</p></div>';
}

async function loadAdminReports() {
    document.getElementById('admin-content').innerHTML = '<div class="admin-tab"><h3>รายงาน</h3><p>ฟีเจอร์นี้กำลังพัฒนา</p></div>';
}

function showAddProductModal() {
    showAlert('ฟีเจอร์นี้กำลังพัฒนา', 'info');
}

function editProduct(id) {
    showAlert('ฟีเจอร์นี้กำลังพัฒนา', 'info');
}

function deleteProduct(id) {
    showAlert('ฟีเจอร์นี้กำลังพัฒนา', 'info');
}

function showUserOrders() {
    showAlert('ฟีเจอร์นี้กำลังพัฒนา', 'info');
}

async function validatePromotion() {
    const code = document.getElementById('promotion-code').value;
    const messageDiv = document.getElementById('promotion-message');
    
    if (!code) {
        messageDiv.innerHTML = '<div class="alert alert-warning">กรุณากรอกรหัสโปรโมชั่น</div>';
        return;
    }
    
    try {
        const response = await fetch(`api/admin/promotions.php?action=validate&code=${encodeURIComponent(code)}`);
        const data = await response.json();
        
        if (data.valid) {
            messageDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
        } else {
            messageDiv.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
        }
    } catch (error) {
        messageDiv.innerHTML = '<div class="alert alert-danger">ไม่สามารถตรวจสอบรหัสโปรโมชั่นได้</div>';
    }
}