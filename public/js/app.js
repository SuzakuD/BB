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

function createProductCard(product, index = 0) {
    const stockClass = product.stock < 10 ? 'stock-low' : product.stock < 50 ? 'stock-medium' : 'stock-high';
    const stockText = product.stock === 0 ? 'สินค้าหมด' : `คงเหลือ ${product.stock} ชิ้น`;
    const stockIcon = product.stock === 0 ? 'fas fa-times-circle' : product.stock < 10 ? 'fas fa-exclamation-triangle' : 'fas fa-check-circle';
    
    return `
        <div class="col-md-4 col-lg-3 mb-4" style="animation: fadeIn 0.5s ease-in ${index * 0.1}s both;">
            <div class="card product-card h-100">
                <div class="position-relative">
                    <img src="${product.image || 'https://via.placeholder.com/300x200?text=No+Image'}" 
                         class="card-img-top product-image" alt="${product.name}">
                    ${product.stock < 10 ? '<div class="position-absolute top-0 end-0 m-2"><span class="badge bg-warning"><i class="fas fa-exclamation-triangle me-1"></i>สต็อกต่ำ</span></div>' : ''}
                    ${product.category_name ? `<div class="position-absolute top-0 start-0 m-2"><span class="badge" style="background: var(--accent-gradient);">${product.category_name}</span></div>` : ''}
                </div>
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">${product.name}</h5>
                    <p class="card-text text-muted small flex-grow-1">${product.description || 'ไม่มีรายละเอียด'}</p>
                    <div class="mb-2">
                        <span class="price">${Number(product.price).toLocaleString()}</span>
                        <span style="color: var(--text-muted); font-size: 0.9rem;"> บาท</span>
                    </div>
                    <div class="mb-3 d-flex align-items-center gap-2">
                        <i class="${stockIcon} ${stockClass}"></i>
                        <small class="${stockClass}">${stockText}</small>
                    </div>
                    <button class="btn btn-primary w-100" 
                            onclick="addToCartWithAnimation(${product.id}, this)" 
                            ${product.stock === 0 ? 'disabled' : ''}>
                        <i class="fas fa-cart-plus me-2"></i>
                        ${product.stock === 0 ? 'สินค้าหมด' : 'เพิ่มลงตะกร้า'}
                    </button>
                </div>
            </div>
        </div>
    `;
}

function loadContactPage() {
    const mainContent = document.getElementById('main-content');
    mainContent.innerHTML = `
        <div class="container fade-in">
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <div class="text-center mb-5">
                        <h2 style="font-family: 'Poppins', sans-serif; font-weight: 700; color: var(--text-primary);">
                            <i class="fas fa-envelope me-3" style="color: var(--primary-color);"></i>ติดต่อเรา
                        </h2>
                        <p class="text-muted fs-5">เรายินดีที่จะได้รับฟังจากคุณ ติดต่อเราได้ทุกช่องทาง</p>
                    </div>
                    
                    <div class="row g-4 mb-5">
                        <div class="col-md-3 col-sm-6">
                            <div class="card h-100 text-center" style="border-radius: 20px; border: 2px solid var(--border-color); transition: var(--transition-fast);">
                                <div class="card-body p-4">
                                    <div class="mb-3" style="width: 60px; height: 60px; background: var(--primary-gradient); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                        <i class="fas fa-map-marker-alt fa-lg text-white"></i>
                                    </div>
                                    <h6 class="fw-bold">ที่อยู่</h6>
                                    <p class="text-muted small mb-0">123 ถนนตกปลา<br>เขตปลาใหญ่ กรุงเทพฯ 10110</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 col-sm-6">
                            <div class="card h-100 text-center" style="border-radius: 20px; border: 2px solid var(--border-color); transition: var(--transition-fast);">
                                <div class="card-body p-4">
                                    <div class="mb-3" style="width: 60px; height: 60px; background: var(--success-gradient); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                        <i class="fas fa-phone fa-lg text-white"></i>
                                    </div>
                                    <h6 class="fw-bold">โทรศัพท์</h6>
                                    <p class="text-muted small mb-0">02-123-4567<br>081-234-5678</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 col-sm-6">
                            <div class="card h-100 text-center" style="border-radius: 20px; border: 2px solid var(--border-color); transition: var(--transition-fast);">
                                <div class="card-body p-4">
                                    <div class="mb-3" style="width: 60px; height: 60px; background: var(--warning-gradient); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                        <i class="fas fa-envelope fa-lg text-white"></i>
                                    </div>
                                    <h6 class="fw-bold">อีเมล</h6>
                                    <p class="text-muted small mb-0">info@fishingstore.com<br>support@fishingstore.com</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 col-sm-6">
                            <div class="card h-100 text-center" style="border-radius: 20px; border: 2px solid var(--border-color); transition: var(--transition-fast);">
                                <div class="card-body p-4">
                                    <div class="mb-3" style="width: 60px; height: 60px; background: var(--accent-gradient); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                        <i class="fas fa-clock fa-lg text-white"></i>
                                    </div>
                                    <h6 class="fw-bold">เวลาทำการ</h6>
                                    <p class="text-muted small mb-0">จ.-ศ.: 8:00-18:00<br>ส.-อ.: 9:00-17:00</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card" style="border-radius: 20px; border: none; box-shadow: var(--shadow-lg);">
                        <div class="card-header text-center" style="background: var(--primary-gradient); color: white; border-radius: 20px 20px 0 0; padding: 2rem;">
                            <h4 class="mb-0" style="font-family: 'Poppins', sans-serif; font-weight: 600;">
                                <i class="fas fa-paper-plane me-2"></i>ส่งข้อความถึงเรา
                            </h4>
                            <p class="mb-0 opacity-75">เราจะตอบกลับภายใน 24 ชั่วโมง</p>
                        </div>
                        <div class="card-body p-4">
                            <form onsubmit="handleContactSubmit(event)">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">ชื่อ *</label>
                                        <input type="text" class="form-control" required placeholder="กรุณากรอกชื่อของคุณ">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">อีเมล *</label>
                                        <input type="email" class="form-control" required placeholder="example@email.com">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">หัวข้อ</label>
                                    <input type="text" class="form-control" placeholder="หัวข้อที่ต้องการสอบถาม">
                                </div>
                                <div class="mb-4">
                                    <label class="form-label fw-bold">ข้อความ *</label>
                                    <textarea class="form-control" rows="6" required placeholder="กรุณาระบุรายละเอียดที่ต้องการสอบถาม..."></textarea>
                                </div>
                                <div class="text-center">
                                    <button type="submit" class="btn btn-primary btn-lg" style="border-radius: 25px; padding: 1rem 3rem;">
                                        <i class="fas fa-paper-plane me-2"></i>ส่งข้อความ
                                    </button>
                                </div>
                            </form>
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
            <div class="category-item ${currentCategory === null ? 'active' : ''}" onclick="filterByCategory(null)" style="cursor: pointer;">
                <i class="fas fa-th"></i> ทั้งหมด
            </div>
        `;
        
        data.categories.forEach(category => {
            html += `
                <div class="category-item ${currentCategory === category.id ? 'active' : ''}" 
                     onclick="filterByCategory(${category.id})" style="cursor: pointer;">
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
        <div class="d-flex justify-content-between align-items-center mb-4 fade-in">
            <h2 style="font-family: 'Poppins', sans-serif; font-weight: 600; color: var(--text-primary);">
                <i class="fas fa-store me-2" style="color: var(--primary-color);"></i>${title}
            </h2>
            <div class="d-flex align-items-center gap-2">
                <span class="badge" style="background: var(--accent-gradient); color: white; padding: 0.5rem 1rem; border-radius: 20px;">
                    แสดง ${data.products.length} จาก ${data.total} รายการ
                </span>
            </div>
        </div>
        <div class="row" id="products-container">
    `;
    
    if (data.products.length === 0) {
        html += `
            <div class="col-12 text-center py-5 fade-in">
                <div style="background: var(--accent-gradient); width: 120px; height: 120px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 2rem;">
                    <i class="fas fa-search fa-3x text-white"></i>
                </div>
                <h4 style="font-family: 'Poppins', sans-serif; font-weight: 600; color: var(--text-primary);">ไม่พบสินค้า</h4>
                <p class="text-muted">ลองค้นหาด้วยคำอื่น หรือดูสินค้าทั้งหมด</p>
                <button class="btn btn-primary mt-3" onclick="loadPage('home')" style="border-radius: 25px; padding: 0.75rem 2rem;">
                    <i class="fas fa-home me-2"></i>กลับหน้าแรก
                </button>
            </div>
        `;
    } else {
        data.products.forEach((product, index) => {
            html += createProductCard(product, index);
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
            const cartCountElement = document.getElementById('cart-count');
            cartCountElement.textContent = cartCount;
            
            // Animate cart badge
            cartCountElement.style.animation = 'pulse 0.6s ease-in-out';
            setTimeout(() => {
                cartCountElement.style.animation = '';
            }, 600);
            
            showAlert(data.message, 'success');
        } else {
            showAlert(data.error, 'danger');
        }
    } catch (error) {
        console.error('Failed to add to cart:', error);
        showAlert('ไม่สามารถเพิ่มสินค้าลงตะกร้าได้', 'danger');
    }
}

async function addToCartWithAnimation(productId, buttonElement) {
    // Animate button
    const originalText = buttonElement.innerHTML;
    buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>กำลังเพิ่ม...';
    buttonElement.disabled = true;
    
    try {
        await addToCart(productId);
        
        // Success animation
        buttonElement.innerHTML = '<i class="fas fa-check me-2"></i>เพิ่มแล้ว!';
        buttonElement.style.background = 'var(--success-gradient)';
        
        setTimeout(() => {
            buttonElement.innerHTML = originalText;
            buttonElement.style.background = '';
            buttonElement.disabled = false;
        }, 1500);
        
    } catch (error) {
        buttonElement.innerHTML = originalText;
        buttonElement.disabled = false;
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

// Complete admin functions
async function loadAdminCategories() {
    try {
        const response = await fetch('api/products.php?action=categories');
        const data = await response.json();
        
        const adminContent = document.getElementById('admin-content');
        
        let html = `
            <div class="admin-tab">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3>จัดการหมวดหมู่</h3>
                    <button class="btn btn-primary" onclick="showAddCategoryModal()">
                        <i class="fas fa-plus"></i> เพิ่มหมวดหมู่
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>ชื่อหมวดหมู่</th>
                                <th>จำนวนสินค้า</th>
                                <th>การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
        `;
        
        if (data.categories.length === 0) {
            html += `
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">
                        <i class="fas fa-tags fa-2x mb-2"></i><br>
                        ยังไม่มีหมวดหมู่
                    </td>
                </tr>
            `;
        } else {
            // Get product counts for each category
            const productCountsResponse = await fetch('api/products.php?action=list&limit=1000');
            const productData = await productCountsResponse.json();
            
            data.categories.forEach(category => {
                const productCount = productData.products.filter(p => p.category_id == category.id).length;
                
                html += `
                    <tr>
                        <td>${category.id}</td>
                        <td>${category.name}</td>
                        <td><span class="badge bg-info">${productCount}</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary me-1" onclick="editCategory(${category.id}, '${category.name.replace(/'/g, "\\'")}')"
                                    title="แก้ไข">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteCategory(${category.id}, '${category.name.replace(/'/g, "\\'")}')"
                                    title="ลบ" ${productCount > 0 ? 'disabled' : ''}>
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
        }
        
        html += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        
        adminContent.innerHTML = html;
        
    } catch (error) {
        console.error('Failed to load admin categories:', error);
        showAlert('ไม่สามารถโหลดข้อมูลหมวดหมู่ได้', 'danger');
    }
}

async function loadAdminUsers() {
    try {
        const response = await fetch('api/admin/users.php?action=list');
        const data = await response.json();
        
        const adminContent = document.getElementById('admin-content');
        
        let html = `
            <div class="admin-tab">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3>จัดการผู้ใช้</h3>
                    <button class="btn btn-primary" onclick="showAddUserModal()">
                        <i class="fas fa-plus"></i> เพิ่มผู้ใช้
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>ชื่อผู้ใช้</th>
                                <th>บทบาท</th>
                                <th>วันที่สมัคร</th>
                                <th>การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
        `;
        
        if (data.users.length === 0) {
            html += `
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        <i class="fas fa-users fa-2x mb-2"></i><br>
                        ยังไม่มีผู้ใช้
                    </td>
                </tr>
            `;
        } else {
            data.users.forEach(user => {
                const roleClass = user.role === 'admin' ? 'bg-danger' : 'bg-primary';
                const roleText = user.role === 'admin' ? 'ผู้ดูแลระบบ' : 'ผู้ใช้ทั่วไป';
                const createdDate = new Date(user.created_at).toLocaleDateString('th-TH');
                
                html += `
                    <tr>
                        <td>${user.id}</td>
                        <td>${user.username}</td>
                        <td><span class="badge ${roleClass}">${roleText}</span></td>
                        <td>${createdDate}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary me-1" onclick="editUser(${user.id})"
                                    title="แก้ไข">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(${user.id}, '${user.username.replace(/'/g, "\\'")}')"
                                    title="ลบ" ${user.id == currentUser.id ? 'disabled' : ''}>
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
        }
        
        html += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        
        adminContent.innerHTML = html;
        
    } catch (error) {
        console.error('Failed to load admin users:', error);
        showAlert('ไม่สามารถโหลดข้อมูลผู้ใช้ได้', 'danger');
    }
}

async function loadAdminOrders() {
    try {
        const response = await fetch('api/orders.php?action=list');
        const data = await response.json();
        
        const adminContent = document.getElementById('admin-content');
        
        let html = `
            <div class="admin-tab">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3>จัดการคำสั่งซื้อ</h3>
                    <div>
                        <span class="text-muted">รวม ${data.total} คำสั่งซื้อ</span>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped admin-table">
                        <thead>
                            <tr>
                                <th>เลขที่</th>
                                <th>ผู้สั่งซื้อ</th>
                                <th>ยอดรวม</th>
                                <th>สถานะ</th>
                                <th>วันที่สั่งซื้อ</th>
                                <th>การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
        `;
        
        if (data.orders.length === 0) {
            html += `
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="fas fa-shopping-bag fa-2x mb-2"></i><br>
                        ยังไม่มีคำสั่งซื้อ
                    </td>
                </tr>
            `;
        } else {
            data.orders.forEach(order => {
                const statusClass = {
                    'pending': 'bg-warning',
                    'confirmed': 'bg-info',
                    'processing': 'bg-primary',
                    'shipped': 'bg-success',
                    'delivered': 'bg-success',
                    'cancelled': 'bg-danger'
                }[order.status] || 'bg-secondary';
                
                const statusText = {
                    'pending': 'รอดำเนินการ',
                    'confirmed': 'ยืนยันแล้ว',
                    'processing': 'กำลังเตรียม',
                    'shipped': 'จัดส่งแล้ว',
                    'delivered': 'ส่งถึงแล้ว',
                    'cancelled': 'ยกเลิก'
                }[order.status] || order.status;
                
                const orderDate = new Date(order.created_at).toLocaleDateString('th-TH');
                
                html += `
                    <tr>
                        <td>#${order.id}</td>
                        <td>${order.username || 'ไม่ระบุ'}</td>
                        <td>${Number(order.total).toLocaleString()} บาท</td>
                        <td><span class="badge ${statusClass}">${statusText}</span></td>
                        <td>${orderDate}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-info me-1" onclick="viewOrderDetail(${order.id})"
                                    title="ดูรายละเอียด">
                                <i class="fas fa-eye"></i>
                            </button>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" onclick="updateOrderStatus(${order.id}, 'confirmed')">ยืนยัน</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="updateOrderStatus(${order.id}, 'processing')">เตรียมสินค้า</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="updateOrderStatus(${order.id}, 'shipped')">จัดส่ง</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="updateOrderStatus(${order.id}, 'delivered')">ส่งถึงแล้ว</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="#" onclick="updateOrderStatus(${order.id}, 'cancelled')">ยกเลิก</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                `;
            });
        }
        
        html += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        
        adminContent.innerHTML = html;
        
    } catch (error) {
        console.error('Failed to load admin orders:', error);
        showAlert('ไม่สามารถโหลดข้อมูลคำสั่งซื้อได้', 'danger');
    }
}

async function loadAdminPromotions() {
    try {
        const response = await fetch('api/admin/promotions.php?action=list');
        const data = await response.json();
        
        const adminContent = document.getElementById('admin-content');
        
        let html = `
            <div class="admin-tab">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3>จัดการโปรโมชั่น</h3>
                    <button class="btn btn-primary" onclick="showAddPromotionModal()">
                        <i class="fas fa-plus"></i> เพิ่มโปรโมชั่น
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped admin-table">
                        <thead>
                            <tr>
                                <th>รหัส</th>
                                <th>ส่วนลด</th>
                                <th>วันหมดอายุ</th>
                                <th>สถานะ</th>
                                <th>การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
        `;
        
        if (data.promotions.length === 0) {
            html += `
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        <i class="fas fa-percent fa-2x mb-2"></i><br>
                        ยังไม่มีโปรโมชั่น
                    </td>
                </tr>
            `;
        } else {
            data.promotions.forEach(promo => {
                const expireDate = new Date(promo.expire_date).toLocaleDateString('th-TH');
                const isExpired = promo.is_expired;
                const statusClass = isExpired ? 'bg-danger' : 'bg-success';
                const statusText = isExpired ? 'หมดอายุ' : 'ใช้งานได้';
                
                html += `
                    <tr ${isExpired ? 'class="table-secondary"' : ''}>
                        <td><code>${promo.code}</code></td>
                        <td>${promo.discount}%</td>
                        <td>${expireDate}</td>
                        <td><span class="badge ${statusClass}">${statusText}</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary me-1" onclick="editPromotion(${promo.id})"
                                    title="แก้ไข">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deletePromotion(${promo.id}, '${promo.code.replace(/'/g, "\\'")}')"
                                    title="ลบ">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
        }
        
        html += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        
        adminContent.innerHTML = html;
        
    } catch (error) {
        console.error('Failed to load admin promotions:', error);
        showAlert('ไม่สามารถโหลดข้อมูลโปรโมชั่นได้', 'danger');
    }
}

async function loadAdminReports() {
    try {
        const response = await fetch('api/admin/reports.php?action=dashboard');
        const data = await response.json();
        
        const adminContent = document.getElementById('admin-content');
        
        let html = `
            <div class="admin-tab">
                <h3 class="mb-4">รายงานและสถิติ</h3>
                
                <!-- Stats Cards -->
                <div class="admin-stats mb-4">
                    <div class="stat-card">
                        <div class="stat-number">${Number(data.stats.total_sales).toLocaleString()}</div>
                        <div class="stat-label">ยอดขายรวม (บาท)</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">${data.stats.total_orders}</div>
                        <div class="stat-label">คำสั่งซื้อทั้งหมด</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">${data.stats.total_products}</div>
                        <div class="stat-label">สินค้าทั้งหมด</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">${data.stats.total_customers}</div>
                        <div class="stat-label">ลูกค้า</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number text-warning">${data.stats.low_stock}</div>
                        <div class="stat-label">สินค้าใกล้หมด</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number text-success">${Number(data.stats.month_sales).toLocaleString()}</div>
                        <div class="stat-label">ยอดขายเดือนนี้ (บาท)</div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-shopping-bag"></i> คำสั่งซื้อล่าสุด</h5>
                            </div>
                            <div class="card-body">
        `;
        
        if (data.stats.recent_orders.length === 0) {
            html += '<p class="text-muted text-center">ยังไม่มีคำสั่งซื้อ</p>';
        } else {
            html += '<div class="table-responsive"><table class="table table-sm">';
            html += '<thead><tr><th>เลขที่</th><th>ลูกค้า</th><th>ยอดรวม</th><th>สถานะ</th></tr></thead><tbody>';
            
            data.stats.recent_orders.forEach(order => {
                const statusClass = {
                    'pending': 'warning',
                    'confirmed': 'info',
                    'processing': 'primary',
                    'shipped': 'success',
                    'delivered': 'success',
                    'cancelled': 'danger'
                }[order.status] || 'secondary';
                
                html += `
                    <tr>
                        <td>#${order.id}</td>
                        <td>${order.username || 'ไม่ระบุ'}</td>
                        <td>${Number(order.total).toLocaleString()} บาท</td>
                        <td><span class="badge bg-${statusClass}">${order.status}</span></td>
                    </tr>
                `;
            });
            
            html += '</tbody></table></div>';
        }
        
        html += `
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-star"></i> สินค้าขายดี</h5>
                            </div>
                            <div class="card-body">
        `;
        
        if (data.stats.top_products.length === 0) {
            html += '<p class="text-muted text-center">ยังไม่มีข้อมูลการขาย</p>';
        } else {
            html += '<div class="table-responsive"><table class="table table-sm">';
            html += '<thead><tr><th>สินค้า</th><th>ขายได้</th><th>รายได้</th></tr></thead><tbody>';
            
            data.stats.top_products.forEach(product => {
                html += `
                    <tr>
                        <td>${product.name}</td>
                        <td><span class="badge bg-primary">${product.total_sold}</span></td>
                        <td>${Number(product.total_revenue).toLocaleString()} บาท</td>
                    </tr>
                `;
            });
            
            html += '</tbody></table></div>';
        }
        
        html += `
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        adminContent.innerHTML = html;
        
    } catch (error) {
        console.error('Failed to load admin reports:', error);
        showAlert('ไม่สามารถโหลดรายงานได้', 'danger');
    }
}

// Product management functions
function showAddProductModal() {
    document.getElementById('productModalTitle').textContent = 'เพิ่มสินค้า';
    document.getElementById('product-form').reset();
    document.getElementById('product-id').value = '';
    document.getElementById('password-required').style.display = 'inline';
    document.getElementById('password-help').style.display = 'none';
    
    // Load categories for dropdown
    loadCategoriesForSelect();
    
    // Reset image preview
    resetImagePreview();
    
    // Set up image preview listener
    setupImagePreview();
    
    new bootstrap.Modal(document.getElementById('productModal')).show();
}

async function editProduct(id) {
    try {
        const response = await fetch(`api/products.php?action=detail&id=${id}`);
        const data = await response.json();
        
        if (data.product) {
            const product = data.product;
            
            document.getElementById('productModalTitle').textContent = 'แก้ไขสินค้า';
            document.getElementById('product-id').value = product.id;
            document.getElementById('product-name').value = product.name;
            document.getElementById('product-description').value = product.description || '';
            document.getElementById('product-price').value = product.price;
            document.getElementById('product-stock').value = product.stock;
            document.getElementById('product-image').value = product.image || '';
            
            // Load categories and set selected
            await loadCategoriesForSelect();
            document.getElementById('product-category').value = product.category_id || '';
            
            // Update image preview
            updateImagePreview(product.image);
            setupImagePreview();
            
            new bootstrap.Modal(document.getElementById('productModal')).show();
        } else {
            showAlert(data.error, 'danger');
        }
    } catch (error) {
        console.error('Failed to load product:', error);
        showAlert('ไม่สามารถโหลดข้อมูลสินค้าได้', 'danger');
    }
}

async function deleteProduct(id) {
    if (confirm('คุณแน่ใจว่าต้องการลบสินค้านี้หรือไม่?')) {
        try {
            const response = await fetch(`api/products.php?action=delete&id=${id}`, {
                method: 'DELETE'
            });
            
            const data = await response.json();
            
            if (data.success) {
                showAlert(data.message, 'success');
                loadAdminProducts(); // Reload the products list
            } else {
                showAlert(data.error, 'danger');
            }
        } catch (error) {
            console.error('Failed to delete product:', error);
            showAlert('ไม่สามารถลบสินค้าได้', 'danger');
        }
    }
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

// Supporting functions for admin system

// Image handling functions
function setupImagePreview() {
    const imageInput = document.getElementById('product-image');
    imageInput.addEventListener('input', function() {
        updateImagePreview(this.value);
    });
}

function updateImagePreview(imageUrl) {
    const previewImg = document.getElementById('preview-img');
    const noPreview = document.getElementById('no-preview');
    
    if (imageUrl) {
        // Check if it's a full URL or just a filename
        let fullImageUrl = imageUrl;
        if (!imageUrl.startsWith('http')) {
            // If it's just a filename, create a placeholder URL
            fullImageUrl = `https://via.placeholder.com/300x200?text=${encodeURIComponent(imageUrl)}`;
        }
        
        previewImg.src = fullImageUrl;
        previewImg.style.display = 'block';
        noPreview.style.display = 'none';
    } else {
        previewImg.style.display = 'none';
        noPreview.style.display = 'block';
    }
}

function resetImagePreview() {
    document.getElementById('preview-img').style.display = 'none';
    document.getElementById('no-preview').style.display = 'block';
    document.getElementById('product-image').value = '';
}

async function loadCategoriesForSelect() {
    try {
        const response = await fetch('api/products.php?action=categories');
        const data = await response.json();
        
        const select = document.getElementById('product-category');
        select.innerHTML = '<option value="">เลือกหมวดหมู่</option>';
        
        data.categories.forEach(category => {
            select.innerHTML += `<option value="${category.id}">${category.name}</option>`;
        });
    } catch (error) {
        console.error('Failed to load categories for select:', error);
    }
}

// Product form submission
document.addEventListener('DOMContentLoaded', function() {
    // Add form submission handler when DOM is loaded
    setTimeout(() => {
        const productForm = document.getElementById('product-form');
        if (productForm) {
            productForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                await handleProductSubmit();
            });
        }
        
        const categoryForm = document.getElementById('category-form');
        if (categoryForm) {
            categoryForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                await handleCategorySubmit();
            });
        }
        
        const userForm = document.getElementById('user-form');
        if (userForm) {
            userForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                await handleUserSubmit();
            });
        }
        
        const promotionForm = document.getElementById('promotion-form');
        if (promotionForm) {
            promotionForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                await handlePromotionSubmit();
            });
        }
    }, 1000);
});

async function handleProductSubmit() {
    const productId = document.getElementById('product-id').value;
    const name = document.getElementById('product-name').value;
    const description = document.getElementById('product-description').value;
    const price = document.getElementById('product-price').value;
    const stock = document.getElementById('product-stock').value;
    const categoryId = document.getElementById('product-category').value;
    const image = document.getElementById('product-image').value;
    const messageDiv = document.getElementById('product-message');
    
    try {
        const isEdit = productId !== '';
        const url = isEdit ? 'api/products.php?action=update' : 'api/products.php?action=create';
        const method = isEdit ? 'PUT' : 'POST';
        
        const payload = {
            name,
            description,
            price: parseFloat(price),
            stock: parseInt(stock),
            category_id: categoryId || null,
            image
        };
        
        if (isEdit) {
            payload.id = parseInt(productId);
        }
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });
        
        const data = await response.json();
        
        if (data.success) {
            messageDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
            setTimeout(() => {
                bootstrap.Modal.getInstance(document.getElementById('productModal')).hide();
                loadAdminProducts();
            }, 1500);
        } else {
            messageDiv.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
        }
    } catch (error) {
        console.error('Product submit failed:', error);
        messageDiv.innerHTML = '<div class="alert alert-danger">เกิดข้อผิดพลาดในการบันทึก</div>';
    }
}

// Category management functions
function showAddCategoryModal() {
    document.getElementById('categoryModalTitle').textContent = 'เพิ่มหมวดหมู่';
    document.getElementById('category-form').reset();
    document.getElementById('category-id').value = '';
    new bootstrap.Modal(document.getElementById('categoryModal')).show();
}

function editCategory(id, name) {
    document.getElementById('categoryModalTitle').textContent = 'แก้ไขหมวดหมู่';
    document.getElementById('category-id').value = id;
    document.getElementById('category-name').value = name;
    new bootstrap.Modal(document.getElementById('categoryModal')).show();
}

async function deleteCategory(id, name) {
    if (confirm(`คุณแน่ใจว่าต้องการลบหมวดหมู่ "${name}" หรือไม่?`)) {
        try {
            const response = await fetch(`api/products.php?action=category&id=${id}`, {
                method: 'DELETE'
            });
            
            const data = await response.json();
            
            if (data.success) {
                showAlert(data.message, 'success');
                loadAdminCategories();
                loadCategories(); // Reload sidebar categories
            } else {
                showAlert(data.error, 'danger');
            }
        } catch (error) {
            console.error('Failed to delete category:', error);
            showAlert('ไม่สามารถลบหมวดหมู่ได้', 'danger');
        }
    }
}

async function handleCategorySubmit() {
    const categoryId = document.getElementById('category-id').value;
    const name = document.getElementById('category-name').value;
    const messageDiv = document.getElementById('category-message');
    
    try {
        const isEdit = categoryId !== '';
        const url = isEdit ? 'api/products.php?action=category' : 'api/products.php?action=category';
        const method = isEdit ? 'PUT' : 'POST';
        
        const payload = { name };
        if (isEdit) {
            payload.id = parseInt(categoryId);
        }
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });
        
        const data = await response.json();
        
        if (data.success) {
            messageDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
            setTimeout(() => {
                bootstrap.Modal.getInstance(document.getElementById('categoryModal')).hide();
                loadAdminCategories();
                loadCategories(); // Reload sidebar categories
            }, 1500);
        } else {
            messageDiv.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
        }
    } catch (error) {
        console.error('Category submit failed:', error);
        messageDiv.innerHTML = '<div class="alert alert-danger">เกิดข้อผิดพลาดในการบันทึก</div>';
    }
}

// User management functions
function showAddUserModal() {
    document.getElementById('userModalTitle').textContent = 'เพิ่มผู้ใช้';
    document.getElementById('user-form').reset();
    document.getElementById('user-id').value = '';
    document.getElementById('password-required').style.display = 'inline';
    document.getElementById('password-help').style.display = 'none';
    document.getElementById('user-password').required = true;
    new bootstrap.Modal(document.getElementById('userModal')).show();
}

async function editUser(id) {
    try {
        // We'll need to get user data first
        const response = await fetch(`api/admin/users.php?action=list`);
        const data = await response.json();
        const user = data.users.find(u => u.id == id);
        
        if (user) {
            document.getElementById('userModalTitle').textContent = 'แก้ไขผู้ใช้';
            document.getElementById('user-id').value = user.id;
            document.getElementById('user-username').value = user.username;
            document.getElementById('user-role').value = user.role;
            document.getElementById('user-password').value = '';
            document.getElementById('password-required').style.display = 'none';
            document.getElementById('password-help').style.display = 'block';
            document.getElementById('user-password').required = false;
            new bootstrap.Modal(document.getElementById('userModal')).show();
        }
    } catch (error) {
        console.error('Failed to load user:', error);
        showAlert('ไม่สามารถโหลดข้อมูลผู้ใช้ได้', 'danger');
    }
}

async function deleteUser(id, username) {
    if (confirm(`คุณแน่ใจว่าต้องการลบผู้ใช้ "${username}" หรือไม่?`)) {
        try {
            const response = await fetch(`api/admin/users.php?action=delete&id=${id}`, {
                method: 'DELETE'
            });
            
            const data = await response.json();
            
            if (data.success) {
                showAlert(data.message, 'success');
                loadAdminUsers();
            } else {
                showAlert(data.error, 'danger');
            }
        } catch (error) {
            console.error('Failed to delete user:', error);
            showAlert('ไม่สามารถลบผู้ใช้ได้', 'danger');
        }
    }
}

async function handleUserSubmit() {
    const userId = document.getElementById('user-id').value;
    const username = document.getElementById('user-username').value;
    const password = document.getElementById('user-password').value;
    const role = document.getElementById('user-role').value;
    const messageDiv = document.getElementById('user-message');
    
    try {
        const isEdit = userId !== '';
        const url = isEdit ? 'api/admin/users.php?action=update' : 'api/admin/users.php?action=create';
        const method = isEdit ? 'PUT' : 'POST';
        
        const payload = { username, role };
        if (password) {
            payload.password = password;
        }
        if (isEdit) {
            payload.id = parseInt(userId);
        }
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });
        
        const data = await response.json();
        
        if (data.success) {
            messageDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
            setTimeout(() => {
                bootstrap.Modal.getInstance(document.getElementById('userModal')).hide();
                loadAdminUsers();
            }, 1500);
        } else {
            messageDiv.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
        }
    } catch (error) {
        console.error('User submit failed:', error);
        messageDiv.innerHTML = '<div class="alert alert-danger">เกิดข้อผิดพลาดในการบันทึก</div>';
    }
}

// Promotion management functions
function showAddPromotionModal() {
    document.getElementById('promotionModalTitle').textContent = 'เพิ่มโปรโมชั่น';
    document.getElementById('promotion-form').reset();
    document.getElementById('promotion-id').value = '';
    // Set default expire date to 30 days from now
    const futureDate = new Date();
    futureDate.setDate(futureDate.getDate() + 30);
    document.getElementById('promotion-expire').value = futureDate.toISOString().split('T')[0];
    new bootstrap.Modal(document.getElementById('promotionModal')).show();
}

async function editPromotion(id) {
    try {
        const response = await fetch(`api/admin/promotions.php?action=list`);
        const data = await response.json();
        const promotion = data.promotions.find(p => p.id == id);
        
        if (promotion) {
            document.getElementById('promotionModalTitle').textContent = 'แก้ไขโปรโมชั่น';
            document.getElementById('promotion-id').value = promotion.id;
            document.getElementById('promotion-code').value = promotion.code;
            document.getElementById('promotion-discount').value = promotion.discount;
            document.getElementById('promotion-expire').value = promotion.expire_date;
            new bootstrap.Modal(document.getElementById('promotionModal')).show();
        }
    } catch (error) {
        console.error('Failed to load promotion:', error);
        showAlert('ไม่สามารถโหลดข้อมูลโปรโมชั่นได้', 'danger');
    }
}

async function deletePromotion(id, code) {
    if (confirm(`คุณแน่ใจว่าต้องการลบโปรโมชั่น "${code}" หรือไม่?`)) {
        try {
            const response = await fetch(`api/admin/promotions.php?action=delete&id=${id}`, {
                method: 'DELETE'
            });
            
            const data = await response.json();
            
            if (data.success) {
                showAlert(data.message, 'success');
                loadAdminPromotions();
            } else {
                showAlert(data.error, 'danger');
            }
        } catch (error) {
            console.error('Failed to delete promotion:', error);
            showAlert('ไม่สามารถลบโปรโมชั่นได้', 'danger');
        }
    }
}

async function handlePromotionSubmit() {
    const promotionId = document.getElementById('promotion-id').value;
    const code = document.getElementById('promotion-code').value;
    const discount = document.getElementById('promotion-discount').value;
    const expireDate = document.getElementById('promotion-expire').value;
    const messageDiv = document.getElementById('promotion-message');
    
    try {
        const isEdit = promotionId !== '';
        const url = isEdit ? 'api/admin/promotions.php?action=update' : 'api/admin/promotions.php?action=create';
        const method = isEdit ? 'PUT' : 'POST';
        
        const payload = {
            code,
            discount: parseFloat(discount),
            expire_date: expireDate
        };
        
        if (isEdit) {
            payload.id = parseInt(promotionId);
        }
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });
        
        const data = await response.json();
        
        if (data.success) {
            messageDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
            setTimeout(() => {
                bootstrap.Modal.getInstance(document.getElementById('promotionModal')).hide();
                loadAdminPromotions();
            }, 1500);
        } else {
            messageDiv.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
        }
    } catch (error) {
        console.error('Promotion submit failed:', error);
        messageDiv.innerHTML = '<div class="alert alert-danger">เกิดข้อผิดพลาดในการบันทึก</div>';
    }
}

// Order management functions
async function viewOrderDetail(orderId) {
    try {
        const response = await fetch(`api/orders.php?action=detail&id=${orderId}`);
        const data = await response.json();
        
        if (data.order) {
            const order = data.order;
            let itemsHtml = '';
            
            order.items.forEach(item => {
                itemsHtml += `
                    <tr>
                        <td>${item.product_name}</td>
                        <td>${item.quantity}</td>
                        <td>${Number(item.price).toLocaleString()} บาท</td>
                        <td>${Number(item.quantity * item.price).toLocaleString()} บาท</td>
                    </tr>
                `;
            });
            
            const orderDate = new Date(order.created_at).toLocaleDateString('th-TH');
            
            const modalHtml = `
                <div class="modal fade" id="orderDetailModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">รายละเอียดคำสั่งซื้อ #${order.id}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <strong>ผู้สั่งซื้อ:</strong> ${order.username}<br>
                                        <strong>วันที่สั่งซื้อ:</strong> ${orderDate}<br>
                                        <strong>สถานะ:</strong> ${order.status}
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <strong>ยอดรวม:</strong> ${Number(order.total).toLocaleString()} บาท
                                    </div>
                                </div>
                                <h6>รายการสินค้า:</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>สินค้า</th>
                                                <th>จำนวน</th>
                                                <th>ราคาต่อหน่วย</th>
                                                <th>รวม</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${itemsHtml}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                                <button type="button" class="btn btn-primary" onclick="showReceipt(${order.id})">ดูใบเสร็จ</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove existing modal if any
            const existingModal = document.getElementById('orderDetailModal');
            if (existingModal) {
                existingModal.remove();
            }
            
            // Add new modal to body
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            
            // Show the modal
            new bootstrap.Modal(document.getElementById('orderDetailModal')).show();
            
        } else {
            showAlert(data.error, 'danger');
        }
    } catch (error) {
        console.error('Failed to load order detail:', error);
        showAlert('ไม่สามารถโหลดรายละเอียดคำสั่งซื้อได้', 'danger');
    }
}

async function updateOrderStatus(orderId, status) {
    try {
        const response = await fetch('api/orders.php?action=status', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                order_id: orderId,
                status: status
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert(data.message, 'success');
            loadAdminOrders(); // Reload orders list
        } else {
            showAlert(data.error, 'danger');
        }
    } catch (error) {
        console.error('Failed to update order status:', error);
        showAlert('ไม่สามารถอัปเดตสถานะคำสั่งซื้อได้', 'danger');
    }
}

// Contact form handler
function handleContactSubmit(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const button = form.querySelector('button[type="submit"]');
    const originalText = button.innerHTML;
    
    // Animate button
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>กำลังส่ง...';
    button.disabled = true;
    
    // Simulate sending (in real app, this would call an API)
    setTimeout(() => {
        button.innerHTML = '<i class="fas fa-check me-2"></i>ส่งสำเร็จ!';
        button.style.background = 'var(--success-gradient)';
        
        showAlert('ข้อความของคุณถูกส่งเรียบร้อยแล้ว เราจะติดต่อกลับภายใน 24 ชั่วโมง', 'success');
        
        form.reset();
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.style.background = '';
            button.disabled = false;
        }, 2000);
    }, 1500);
}

// Enhanced alert function with better animations
function showAlert(message, type = 'info') {
    // Remove existing alerts
    document.querySelectorAll('.alert-float').forEach(alert => alert.remove());
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-float alert-dismissible fade show`;
    
    const icon = {
        'success': 'fas fa-check-circle',
        'danger': 'fas fa-exclamation-triangle',
        'warning': 'fas fa-exclamation-circle',
        'info': 'fas fa-info-circle'
    }[type] || 'fas fa-info-circle';
    
    alertDiv.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="${icon} me-2"></i>
            <span>${message}</span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Auto dismiss after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.classList.add('fade');
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 150);
        }
    }, 5000);
}

// Add CSS hover effects to contact cards
document.addEventListener('DOMContentLoaded', function() {
    // Add hover effects to contact cards when they're loaded
    setTimeout(() => {
        const contactCards = document.querySelectorAll('.card');
        contactCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.boxShadow = 'var(--shadow-xl)';
                this.style.borderColor = 'var(--primary-color)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = '';
                this.style.boxShadow = '';
                this.style.borderColor = '';
            });
        });
    }, 1000);
});