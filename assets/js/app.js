// Fishing Gear Store - Main JavaScript Application

class FishingStoreApp {
    constructor() {
        this.csrfToken = null;
        this.currentPage = 'home';
        this.cartCount = 0;
        this.init();
    }

    async init() {
        await this.getCSRFToken();
        this.setupEventListeners();
        this.loadCartCount();
    }

    async getCSRFToken() {
        try {
            const response = await fetch('api/auth.php?action=csrf_token');
            const data = await response.json();
            this.csrfToken = data.csrf_token;
        } catch (error) {
            console.error('Failed to get CSRF token:', error);
        }
    }

    setupEventListeners() {
        // Search functionality
        document.getElementById('searchInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.searchProducts();
            }
        });

        // Login form
        document.getElementById('loginForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleLogin();
        });

        // Register form
        document.getElementById('registerForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleRegister();
        });
    }

    async loadCartCount() {
        try {
            const response = await fetch('api/cart.php?action=count');
            const data = await response.json();
            if (data.success) {
                this.cartCount = data.count;
                document.getElementById('cartCount').textContent = this.cartCount;
            }
        } catch (error) {
            console.error('Failed to load cart count:', error);
        }
    }

    showNotification(message, type = 'info') {
        const toast = document.getElementById('notificationToast');
        const toastMessage = document.getElementById('toastMessage');
        
        toastMessage.textContent = message;
        toast.className = `toast ${type}`;
        
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
    }

    async showHome() {
        this.currentPage = 'home';
        const content = document.getElementById('mainContent');
        
        try {
            // Get featured products
            const response = await fetch('api/products.php?action=featured');
            const data = await response.json();
            
            let html = `
                <div class="container">
                    <div class="hero-section text-center mb-5">
                        <h1 class="display-4 text-gradient mb-3">Welcome to Fishing Gear Store</h1>
                        <p class="lead">Premium fishing equipment for every angler</p>
                        <button class="btn btn-primary btn-lg" onclick="app.showProducts()">Shop Now</button>
                    </div>
                    
                    <div class="row mb-5">
                        <div class="col-md-4 mb-4">
                            <div class="card text-center h-100">
                                <div class="card-body">
                                    <i class="fas fa-fish fa-3x text-primary mb-3"></i>
                                    <h5 class="card-title">Quality Products</h5>
                                    <p class="card-text">Premium fishing equipment from top brands</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="card text-center h-100">
                                <div class="card-body">
                                    <i class="fas fa-shipping-fast fa-3x text-success mb-3"></i>
                                    <h5 class="card-title">Fast Shipping</h5>
                                    <p class="card-text">Free shipping on orders over $75</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="card text-center h-100">
                                <div class="card-body">
                                    <i class="fas fa-headset fa-3x text-info mb-3"></i>
                                    <h5 class="card-title">Expert Support</h5>
                                    <p class="card-text">24/7 customer support for all your needs</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <h2 class="text-center mb-4">Featured Products</h2>
                    <div class="row" id="featuredProducts">
            `;
            
            if (data.success && data.products.length > 0) {
                data.products.forEach(product => {
                    html += this.renderProductCard(product);
                });
            } else {
                html += '<div class="col-12 text-center"><p>No featured products available.</p></div>';
            }
            
            html += `
                    </div>
                </div>
            `;
            
            content.innerHTML = html;
            content.classList.add('fade-in');
        } catch (error) {
            console.error('Failed to load home page:', error);
            content.innerHTML = '<div class="container"><div class="alert alert-danger">Failed to load home page.</div></div>';
        }
    }

    async showProducts(categoryId = null, search = null) {
        this.currentPage = 'products';
        const content = document.getElementById('mainContent');
        
        try {
            let url = 'api/products.php?action=list';
            if (categoryId) url += `&category_id=${categoryId}`;
            if (search) url += `&search=${encodeURIComponent(search)}`;
            
            const [productsResponse, categoriesResponse] = await Promise.all([
                fetch(url),
                fetch('api/products.php?action=categories')
            ]);
            
            const productsData = await productsResponse.json();
            const categoriesData = await categoriesResponse.json();
            
            let html = `
                <div class="container">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="filter-section">
                                <h5 class="filter-title">Categories</h5>
                                <div class="list-group">
                                    <a href="#" class="list-group-item list-group-item-action ${!categoryId ? 'active' : ''}" 
                                       onclick="app.showProducts()">All Products</a>
            `;
            
            if (categoriesData.success) {
                categoriesData.categories.forEach(category => {
                    const isActive = categoryId == category.id ? 'active' : '';
                    html += `<a href="#" class="list-group-item list-group-item-action ${isActive}" 
                               onclick="app.showProducts(${category.id})">${category.name}</a>`;
                });
            }
            
            html += `
                                </div>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h2>Products</h2>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-outline-primary" onclick="app.sortProducts('name')">Name</button>
                                    <button class="btn btn-outline-primary" onclick="app.sortProducts('price')">Price</button>
                                    <button class="btn btn-outline-primary" onclick="app.sortProducts('newest')">Newest</button>
                                </div>
                            </div>
                            <div class="row" id="productsList">
            `;
            
            if (productsData.success && productsData.products.length > 0) {
                productsData.products.forEach(product => {
                    html += this.renderProductCard(product);
                });
            } else {
                html += '<div class="col-12 text-center"><p>No products found.</p></div>';
            }
            
            html += `
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            content.innerHTML = html;
            content.classList.add('fade-in');
        } catch (error) {
            console.error('Failed to load products:', error);
            content.innerHTML = '<div class="container"><div class="alert alert-danger">Failed to load products.</div></div>';
        }
    }

    renderProductCard(product) {
        const price = product.sale_price ? product.sale_price : product.price;
        const originalPrice = product.sale_price ? product.price : null;
        
        return `
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card product-card h-100">
                    ${product.is_featured ? '<span class="badge bg-warning product-badge">Featured</span>' : ''}
                    <img src="assets/images/products/${product.id}.jpg" class="card-img-top" 
                         alt="${product.name}" onerror="this.src='assets/images/placeholder.jpg'">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">${product.name}</h5>
                        <p class="card-text flex-grow-1">${product.short_description || product.description}</p>
                        <div class="mt-auto">
                            <div class="price mb-2">
                                $${price.toFixed(2)}
                                ${originalPrice ? `<small class="original-price">$${originalPrice.toFixed(2)}</small>` : ''}
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-primary flex-grow-1" onclick="app.addToCart(${product.id})">
                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                </button>
                                <button class="btn btn-outline-primary" onclick="app.showProductDetails(${product.id})">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    async searchProducts() {
        const searchInput = document.getElementById('searchInput');
        const query = searchInput.value.trim();
        
        if (query) {
            this.showProducts(null, query);
        } else {
            this.showProducts();
        }
    }

    async addToCart(productId, quantity = 1) {
        try {
            const response = await fetch('api/cart.php?action=add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: quantity,
                    csrf_token: this.csrfToken
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification(data.message, 'success');
                this.loadCartCount();
            } else {
                this.showNotification(data.message, 'danger');
            }
        } catch (error) {
            console.error('Failed to add to cart:', error);
            this.showNotification('Failed to add product to cart', 'danger');
        }
    }

    async showCart() {
        this.currentPage = 'cart';
        const content = document.getElementById('mainContent');
        
        try {
            const response = await fetch('api/cart.php?action=get');
            const data = await response.json();
            
            let html = `
                <div class="container">
                    <h2 class="mb-4">Shopping Cart</h2>
            `;
            
            if (data.success && data.items.length > 0) {
                html += `
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-body">
                `;
                
                data.items.forEach(item => {
                    html += `
                        <div class="cart-item d-flex align-items-center">
                            <img src="assets/images/products/${item.product_id}.jpg" 
                                 class="cart-item-image me-3" 
                                 alt="${item.name}" 
                                 onerror="this.src='assets/images/placeholder.jpg'">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">${item.name}</h6>
                                <p class="text-muted mb-0">$${item.price.toFixed(2)} each</p>
                            </div>
                            <div class="d-flex align-items-center">
                                <button class="btn btn-sm btn-outline-secondary" 
                                        onclick="app.updateCartQuantity(${item.id}, ${item.quantity - 1})">-</button>
                                <input type="number" class="quantity-input mx-2" 
                                       value="${item.quantity}" 
                                       onchange="app.updateCartQuantity(${item.id}, this.value)">
                                <button class="btn btn-sm btn-outline-secondary" 
                                        onclick="app.updateCartQuantity(${item.id}, ${item.quantity + 1})">+</button>
                                <span class="ms-3 fw-bold">$${(item.quantity * item.price).toFixed(2)}</span>
                                <button class="btn btn-sm btn-outline-danger ms-2" 
                                        onclick="app.removeFromCart(${item.id})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    `;
                });
                
                html += `
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5>Order Summary</h5>
                                    <div class="d-flex justify-content-between">
                                        <span>Subtotal:</span>
                                        <span>$${data.total.toFixed(2)}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Tax (8%):</span>
                                        <span>$${(data.total * 0.08).toFixed(2)}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Shipping:</span>
                                        <span>${data.total >= 75 ? 'Free' : '$9.99'}</span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between fw-bold">
                                        <span>Total:</span>
                                        <span>$${(data.total * 1.08 + (data.total >= 75 ? 0 : 9.99)).toFixed(2)}</span>
                                    </div>
                                    <button class="btn btn-success w-100 mt-3" onclick="app.showCheckout()">
                                        Proceed to Checkout
                                    </button>
                                    <button class="btn btn-outline-danger w-100 mt-2" onclick="app.clearCart()">
                                        Clear Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                html += `
                    <div class="text-center">
                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                        <h4>Your cart is empty</h4>
                        <p class="text-muted">Add some products to get started!</p>
                        <button class="btn btn-primary" onclick="app.showProducts()">Continue Shopping</button>
                    </div>
                `;
            }
            
            html += '</div>';
            content.innerHTML = html;
            content.classList.add('fade-in');
        } catch (error) {
            console.error('Failed to load cart:', error);
            content.innerHTML = '<div class="container"><div class="alert alert-danger">Failed to load cart.</div></div>';
        }
    }

    async updateCartQuantity(cartId, quantity) {
        try {
            const response = await fetch('api/cart.php?action=update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    cart_id: cartId,
                    quantity: quantity,
                    csrf_token: this.csrfToken
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.loadCartCount();
                this.showCart(); // Refresh cart display
            } else {
                this.showNotification(data.message, 'danger');
            }
        } catch (error) {
            console.error('Failed to update cart:', error);
            this.showNotification('Failed to update cart', 'danger');
        }
    }

    async removeFromCart(cartId) {
        try {
            const response = await fetch('api/cart.php?action=remove', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    cart_id: cartId,
                    csrf_token: this.csrfToken
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification(data.message, 'success');
                this.loadCartCount();
                this.showCart(); // Refresh cart display
            } else {
                this.showNotification(data.message, 'danger');
            }
        } catch (error) {
            console.error('Failed to remove from cart:', error);
            this.showNotification('Failed to remove item', 'danger');
        }
    }

    async clearCart() {
        if (!confirm('Are you sure you want to clear your cart?')) {
            return;
        }
        
        try {
            const response = await fetch('api/cart.php?action=clear', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    csrf_token: this.csrfToken
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification(data.message, 'success');
                this.loadCartCount();
                this.showCart(); // Refresh cart display
            } else {
                this.showNotification(data.message, 'danger');
            }
        } catch (error) {
            console.error('Failed to clear cart:', error);
            this.showNotification('Failed to clear cart', 'danger');
        }
    }

    async handleLogin() {
        const email = document.getElementById('loginEmail').value;
        const password = document.getElementById('loginPassword').value;
        
        try {
            const response = await fetch('api/auth.php?action=login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    email: email,
                    password: password,
                    csrf_token: this.csrfToken
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification(data.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('loginModal')).hide();
                location.reload(); // Reload to update navigation
            } else {
                this.showNotification(data.message, 'danger');
            }
        } catch (error) {
            console.error('Login failed:', error);
            this.showNotification('Login failed', 'danger');
        }
    }

    async handleRegister() {
        const name = document.getElementById('registerName').value;
        const email = document.getElementById('registerEmail').value;
        const password = document.getElementById('registerPassword').value;
        const confirmPassword = document.getElementById('registerConfirmPassword').value;
        
        if (password !== confirmPassword) {
            this.showNotification('Passwords do not match', 'danger');
            return;
        }
        
        try {
            const response = await fetch('api/auth.php?action=register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    name: name,
                    email: email,
                    password: password,
                    confirm_password: confirmPassword,
                    csrf_token: this.csrfToken
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification(data.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('registerModal')).hide();
                // Clear form
                document.getElementById('registerForm').reset();
            } else {
                this.showNotification(data.message, 'danger');
            }
        } catch (error) {
            console.error('Registration failed:', error);
            this.showNotification('Registration failed', 'danger');
        }
    }

    showLoginModal() {
        const modal = new bootstrap.Modal(document.getElementById('loginModal'));
        modal.show();
    }

    showRegisterModal() {
        const modal = new bootstrap.Modal(document.getElementById('registerModal'));
        modal.show();
    }

    async logout() {
        try {
            const response = await fetch('api/auth.php?action=logout', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    csrf_token: this.csrfToken
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification(data.message, 'success');
                location.reload(); // Reload to update navigation
            }
        } catch (error) {
            console.error('Logout failed:', error);
            location.reload(); // Force reload even if logout fails
        }
    }

    showCategories() {
        this.showProducts();
    }

    showContact() {
        this.currentPage = 'contact';
        const content = document.getElementById('mainContent');
        
        const html = `
            <div class="container">
                <div class="row">
                    <div class="col-md-8 mx-auto">
                        <div class="card">
                            <div class="card-body">
                                <h2 class="text-center mb-4">Contact Us</h2>
                                <form id="contactForm">
                                    <div class="mb-3">
                                        <label for="contactName" class="form-label">Name</label>
                                        <input type="text" class="form-control" id="contactName" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="contactEmail" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="contactEmail" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="contactSubject" class="form-label">Subject</label>
                                        <input type="text" class="form-control" id="contactSubject" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="contactMessage" class="form-label">Message</label>
                                        <textarea class="form-control" id="contactMessage" rows="5" required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">Send Message</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        content.innerHTML = html;
        content.classList.add('fade-in');
        
        // Setup contact form handler
        document.getElementById('contactForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleContactForm();
        });
    }

    async handleContactForm() {
        const name = document.getElementById('contactName').value;
        const email = document.getElementById('contactEmail').value;
        const subject = document.getElementById('contactSubject').value;
        const message = document.getElementById('contactMessage').value;
        
        try {
            const response = await fetch('api/contact.php?action=send', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    name: name,
                    email: email,
                    subject: subject,
                    message: message,
                    csrf_token: this.csrfToken
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification(data.message, 'success');
                document.getElementById('contactForm').reset();
            } else {
                this.showNotification(data.message, 'danger');
            }
        } catch (error) {
            console.error('Failed to send message:', error);
            this.showNotification('Failed to send message', 'danger');
        }
    }
}

// Global functions for onclick handlers
function showHome() {
    app.showHome();
}

function showProducts(categoryId = null) {
    app.showProducts(categoryId);
}

function showCategories() {
    app.showCategories();
}

function showCart() {
    app.showCart();
}

function showContact() {
    app.showContact();
}

function searchProducts() {
    app.searchProducts();
}

function showLoginModal() {
    app.showLoginModal();
}

function showRegisterModal() {
    app.showRegisterModal();
}

function logout() {
    app.logout();
}

// Initialize app
const app = new FishingStoreApp();