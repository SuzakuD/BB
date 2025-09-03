// Modern E-Commerce Store JavaScript Application

class ECommerceApp {
    constructor() {
        this.cart = JSON.parse(localStorage.getItem('cart')) || {};
        this.currentUser = null;
        this.isAdmin = false;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadUserStatus();
        this.updateCartCount();
        this.loadCategories();
        this.loadPage('home');
    }

    setupEventListeners() {
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            searchInput.addEventListener('input', this.debounce(this.handleSearch.bind(this), 300));
            searchInput.addEventListener('focus', () => this.showSearchResults());
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.search-container')) {
                    this.hideSearchResults();
                }
            });
        }
    }

    async handleSearch(e) {
        const query = e.target.value.trim();
        if (query.length < 2) {
            this.hideSearchResults();
            return;
        }

        try {
            const response = await fetch(`api/search.php?q=${encodeURIComponent(query)}`);
            const results = await response.json();
            this.displaySearchResults(results);
        } catch (error) {
            console.error('Search error:', error);
        }
    }

    displaySearchResults(results) {
        const searchResults = document.getElementById('search-results');
        if (!searchResults) return;

        if (results.length === 0) {
            searchResults.innerHTML = '<div class="search-result-item">No products found</div>';
        } else {
            searchResults.innerHTML = results.map(product => `
                <div class="search-result-item" onclick="app.showProductQuickView(${product.id})">
                    <div class="d-flex align-items-center">
                        <img src="${product.image}" alt="${product.name}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 6px; margin-right: 12px;">
                        <div>
                            <div class="fw-bold">${product.name}</div>
                            <div class="text-muted">$${product.price}</div>
                        </div>
                    </div>
                </div>
            `).join('');
        }
        searchResults.style.display = 'block';
    }

    showSearchResults() {
        const searchResults = document.getElementById('search-results');
        if (searchResults) {
            searchResults.style.display = 'block';
        }
    }

    hideSearchResults() {
        const searchResults = document.getElementById('search-results');
        if (searchResults) {
            searchResults.style.display = 'none';
        }
    }

    addToCart(productId, quantity = 1) {
        if (this.cart[productId]) {
            this.cart[productId] += quantity;
        } else {
            this.cart[productId] = quantity;
        }
        this.saveCart();
        this.updateCartCount();
        this.showToast('Product added to cart!', 'success');
    }

    updateCartItem(productId, quantity) {
        if (quantity <= 0) {
            delete this.cart[productId];
        } else {
            this.cart[productId] = quantity;
        }
        this.saveCart();
        this.updateCartCount();
        this.showToast('Cart updated!', 'success');
    }

    removeFromCart(productId) {
        delete this.cart[productId];
        this.saveCart();
        this.updateCartCount();
        this.showToast('Item removed from cart!', 'success');
    }

    clearCart() {
        this.cart = {};
        this.saveCart();
        this.updateCartCount();
        this.hideCart();
        this.showToast('Cart cleared!', 'success');
    }

    saveCart() {
        localStorage.setItem('cart', JSON.stringify(this.cart));
    }

    updateCartCount() {
        const cartCount = document.getElementById('cart-count');
        if (cartCount) {
            const totalItems = Object.values(this.cart).reduce((sum, qty) => sum + qty, 0);
            cartCount.textContent = totalItems;
        }
    }

    async showCart() {
        if (Object.keys(this.cart).length === 0) {
            this.showToast('Your cart is empty!', 'info');
            return;
        }

        try {
            const response = await fetch('api/cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cart: this.cart })
            });
            const cartData = await response.json();
            
            const modalBody = document.getElementById('cart-modal-body');
            if (modalBody) {
                modalBody.innerHTML = this.renderCart(cartData);
            }
            
            const cartModal = new bootstrap.Modal(document.getElementById('cartModal'));
            cartModal.show();
        } catch (error) {
            console.error('Error loading cart:', error);
            this.showToast('Error loading cart!', 'error');
        }
    }

    renderCart(cartData) {
        if (!cartData.items || cartData.items.length === 0) {
            return '<div class="text-center p-4"><i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i><p>Your cart is empty</p></div>';
        }

        return `
            <div class="cart-items">
                ${cartData.items.map(item => `
                    <div class="cart-item">
                        <div class="row align-items-center">
                            <div class="col-md-2">
                                <img src="${item.image}" alt="${item.name}" class="cart-item-image">
                            </div>
                            <div class="col-md-4">
                                <h6 class="mb-1">${item.name}</h6>
                                <small class="text-muted">$${item.price}</small>
                            </div>
                            <div class="col-md-3">
                                <div class="quantity-controls">
                                    <button class="quantity-btn" onclick="app.updateCartItem(${item.id}, ${item.quantity - 1})">-</button>
                                    <input type="number" class="quantity-input" value="${item.quantity}" 
                                           onchange="app.updateCartItem(${item.id}, parseInt(this.value))" min="1">
                                    <button class="quantity-btn" onclick="app.updateCartItem(${item.id}, ${item.quantity + 1})">+</button>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <strong>$${(item.price * item.quantity).toFixed(2)}</strong>
                            </div>
                            <div class="col-md-1">
                                <button class="btn btn-sm btn-outline-danger" onclick="app.removeFromCart(${item.id})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
            <div class="cart-summary mt-4 p-3 bg-light rounded">
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal:</span>
                    <strong>$${cartData.subtotal.toFixed(2)}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Tax:</span>
                    <strong>$${cartData.tax.toFixed(2)}</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="h5">Total:</span>
                    <strong class="h5 text-primary">$${cartData.total.toFixed(2)}</strong>
                </div>
            </div>
        `;
    }

    hideCart() {
        const cartModal = bootstrap.Modal.getInstance(document.getElementById('cartModal'));
        if (cartModal) {
            cartModal.hide();
        }
    }

    async loadPage(page, params = {}) {
        const mainContent = document.getElementById('main-content');
        if (!mainContent) return;

        mainContent.innerHTML = '<div class="spinner"></div>';

        try {
            let url = `pages/${page}.php`;
            if (Object.keys(params).length > 0) {
                const searchParams = new URLSearchParams(params);
                url += `?${searchParams.toString()}`;
            }

            const response = await fetch(url);
            const content = await response.text();
            
            mainContent.innerHTML = content;
            mainContent.classList.add('fade-in');
            
            if (page === 'home') {
                this.loadFeaturedProducts();
            } else if (page === 'products') {
                this.loadProducts();
            }
        } catch (error) {
            console.error('Error loading page:', error);
            mainContent.innerHTML = '<div class="alert alert-danger">Error loading page</div>';
        }
    }

    async loadProducts(filters = {}) {
        try {
            const queryString = new URLSearchParams(filters).toString();
            const response = await fetch(`api/products.php?${queryString}`);
            const products = await response.json();
            
            const productsContainer = document.getElementById('products-container');
            if (productsContainer) {
                productsContainer.innerHTML = this.renderProducts(products);
            }
        } catch (error) {
            console.error('Error loading products:', error);
        }
    }

    renderProducts(products) {
        if (!products || products.length === 0) {
            return '<div class="text-center p-5"><i class="fas fa-box-open fa-3x text-muted mb-3"></i><p>No products found</p></div>';
        }

        return `
            <div class="row">
                ${products.map(product => `
                    <div class="col-md-4 col-lg-3 mb-4">
                        <div class="card product-card h-100">
                            <img src="${product.image}" class="card-img-top product-image" alt="${product.name}">
                            <div class="card-body product-info">
                                <h5 class="card-title product-title">${product.name}</h5>
                                <p class="card-text product-description">${product.description.substring(0, 100)}...</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="product-price">$${product.price}</span>
                                    <div class="product-actions">
                                        <button class="btn btn-outline-primary btn-sm" onclick="app.showProductQuickView(${product.id})">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-primary btn-sm" onclick="app.addToCart(${product.id}, 1)">
                                            <i class="fas fa-cart-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    async loadCategories() {
        try {
            const response = await fetch('api/categories.php');
            const categories = await response.json();
            
            const categoriesList = document.getElementById('categories-list');
            if (categoriesList) {
                categoriesList.innerHTML = categories.map(category => `
                    <div class="category-item" onclick="app.filterByCategory(${category.id})">
                        ${category.name}
                    </div>
                `).join('');
            }
        } catch (error) {
            console.error('Error loading categories:', error);
        }
    }

    filterByCategory(categoryId) {
        document.querySelectorAll('.category-item').forEach(item => item.classList.remove('active'));
        event.target.classList.add('active');
        this.loadProducts({ category_id: categoryId });
    }

    async loadUserStatus() {
        try {
            const response = await fetch('api/user-status.php');
            const userData = await response.json();
            
            this.currentUser = userData.user;
            this.isAdmin = userData.isAdmin;
            
            this.updateAuthNav();
            this.updateAdminPanel();
        } catch (error) {
            console.error('Error loading user status:', error);
        }
    }

    updateAuthNav() {
        const authNav = document.getElementById('auth-nav');
        if (!authNav) return;

        if (this.currentUser) {
            authNav.innerHTML = `
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> ${this.currentUser.username}
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" onclick="app.loadPage('account')">
                            <i class="fas fa-user-cog"></i> My Account
                        </a></li>
                        <li><a class="dropdown-item" href="#" onclick="app.loadPage('orders')">
                            <i class="fas fa-shopping-bag"></i> My Orders
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" onclick="app.logout()">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a></li>
                    </ul>
                </li>
            `;
        } else {
            authNav.innerHTML = `
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="app.loadPage('login')">Login</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="app.loadPage('register')">Register</a>
                </li>
            `;
        }
    }

    updateAdminPanel() {
        const adminPanel = document.getElementById('admin-panel');
        if (adminPanel) {
            adminPanel.style.display = this.isAdmin ? 'block' : 'none';
        }
    }

    async logout() {
        try {
            await fetch('api/logout.php', { method: 'POST' });
            this.currentUser = null;
            this.isAdmin = false;
            this.updateAuthNav();
            this.updateAdminPanel();
            this.loadPage('home');
            this.showToast('Logged out successfully!', 'success');
        } catch (error) {
            console.error('Logout error:', error);
        }
    }

    proceedToCheckout() {
        if (!this.currentUser) {
            this.showToast('Please login to checkout!', 'warning');
            this.loadPage('login');
            this.hideCart();
            return;
        }
        
        this.loadPage('checkout');
        this.hideCart();
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    showToast(message, type = 'info') {
        const toastContainer = document.querySelector('.toast-container') || this.createToastContainer();
        
        const toast = document.createElement('div');
        toast.className = `toast show bg-${type} text-white`;
        toast.innerHTML = `
            <div class="toast-body">
                <i class="fas fa-${this.getToastIcon(type)} me-2"></i>
                ${message}
            </div>
        `;
        
        toastContainer.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }

    createToastContainer() {
        const container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
        container.style.display = 'none';
        return container;
    }

    getToastIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    }
}

const app = new ECommerceApp();

window.loadPage = (page, params) => app.loadPage(page, params);
window.showCart = () => app.showCart();
window.showAdminTab = (tab) => app.loadPage(`admin/${tab}`);
window.addToCart = (productId, quantity) => app.addToCart(productId, quantity);
window.updateCartItem = (productId, quantity) => app.updateCartItem(productId, quantity);
window.removeFromCart = (productId) => app.removeFromCart(productId);
window.clearCart = () => app.clearCart();
window.proceedToCheckout = () => app.proceedToCheckout();
window.filterByCategory = (categoryId) => app.filterByCategory(categoryId);
