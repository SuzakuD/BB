// Admin functionality for Fishing Gear Store

class AdminManager {
    constructor(app) {
        this.app = app;
        this.currentSection = 'dashboard';
    }

    async showAdminDashboard() {
        // Check if user is admin
        const authResponse = await fetch('api/auth.php?action=check');
        const authData = await authResponse.json();
        
        if (!authData.logged_in || authData.user.role !== 'admin') {
            this.app.showNotification('Access denied. Admin privileges required.', 'danger');
            return;
        }

        this.currentSection = 'dashboard';
        const content = document.getElementById('mainContent');
        
        const html = `
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <h2 class="mb-4">Admin Dashboard</h2>
                        
                        <!-- Admin Navigation -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="btn-group w-100" role="group">
                                            <button class="btn btn-outline-primary" onclick="adminManager.showSection('dashboard')">
                                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                            </button>
                                            <button class="btn btn-outline-primary" onclick="adminManager.showSection('products')">
                                                <i class="fas fa-box me-2"></i>Products
                                            </button>
                                            <button class="btn btn-outline-primary" onclick="adminManager.showSection('categories')">
                                                <i class="fas fa-tags me-2"></i>Categories
                                            </button>
                                            <button class="btn btn-outline-primary" onclick="adminManager.showSection('orders')">
                                                <i class="fas fa-shopping-cart me-2"></i>Orders
                                            </button>
                                            <button class="btn btn-outline-primary" onclick="adminManager.showSection('users')">
                                                <i class="fas fa-users me-2"></i>Users
                                            </button>
                                            <button class="btn btn-outline-primary" onclick="adminManager.showSection('promotions')">
                                                <i class="fas fa-percent me-2"></i>Promotions
                                            </button>
                                            <button class="btn btn-outline-primary" onclick="adminManager.showSection('messages')">
                                                <i class="fas fa-envelope me-2"></i>Messages
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div id="adminContent">
                            ${await this.renderDashboard()}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        content.innerHTML = html;
        content.classList.add('fade-in');
    }

    async showSection(section) {
        this.currentSection = section;
        const content = document.getElementById('adminContent');
        
        switch (section) {
            case 'dashboard':
                content.innerHTML = await this.renderDashboard();
                break;
            case 'products':
                content.innerHTML = await this.renderProducts();
                break;
            case 'categories':
                content.innerHTML = await this.renderCategories();
                break;
            case 'orders':
                content.innerHTML = await this.renderOrders();
                break;
            case 'users':
                content.innerHTML = await this.renderUsers();
                break;
            case 'promotions':
                content.innerHTML = await this.renderPromotions();
                break;
            case 'messages':
                content.innerHTML = await this.renderMessages();
                break;
        }
        
        content.classList.add('fade-in');
    }

    async renderDashboard() {
        try {
            const response = await fetch('api/admin.php?action=dashboard');
            const data = await response.json();
            
            if (data.success) {
                const stats = data.stats;
                
                return `
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card admin-card">
                                <div class="card-body">
                                    <div class="card-title">${stats.total_users}</div>
                                    <div class="card-text">Total Users</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card admin-card">
                                <div class="card-body">
                                    <div class="card-title">${stats.total_products}</div>
                                    <div class="card-text">Total Products</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card admin-card">
                                <div class="card-body">
                                    <div class="card-title">${stats.total_orders}</div>
                                    <div class="card-text">Total Orders</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card admin-card">
                                <div class="card-body">
                                    <div class="card-title">$${parseFloat(stats.total_revenue || 0).toFixed(2)}</div>
                                    <div class="card-text">Total Revenue</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Recent Orders</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Order #</th>
                                                    <th>Customer</th>
                                                    <th>Total</th>
                                                    <th>Status</th>
                                                    <th>Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${stats.recent_orders.map(order => `
                                                    <tr>
                                                        <td>${order.order_number}</td>
                                                        <td>${order.user_name || 'Guest'}</td>
                                                        <td>$${parseFloat(order.total_amount).toFixed(2)}</td>
                                                        <td><span class="badge bg-${this.getStatusColor(order.status)}">${order.status}</span></td>
                                                        <td>${new Date(order.created_at).toLocaleDateString()}</td>
                                                    </tr>
                                                `).join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Low Stock Alert</h5>
                                </div>
                                <div class="card-body">
                                    ${stats.low_stock_products.length > 0 ? `
                                        <div class="list-group">
                                            ${stats.low_stock_products.map(product => `
                                                <div class="list-group-item">
                                                    <div class="d-flex justify-content-between">
                                                        <span>${product.name}</span>
                                                        <span class="badge bg-warning">${product.stock_quantity} left</span>
                                                    </div>
                                                </div>
                                            `).join('')}
                                        </div>
                                    ` : '<p class="text-muted">No low stock products</p>'}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Failed to load dashboard:', error);
            return '<div class="alert alert-danger">Failed to load dashboard data.</div>';
        }
    }

    async renderProducts() {
        try {
            const response = await fetch('api/admin.php?action=products');
            const data = await response.json();
            
            if (data.success) {
                return `
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4>Products Management</h4>
                        <button class="btn btn-primary" onclick="adminManager.showProductModal()">
                            <i class="fas fa-plus me-2"></i>Add Product
                        </button>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>SKU</th>
                                            <th>Price</th>
                                            <th>Stock</th>
                                            <th>Category</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.products.map(product => `
                                            <tr>
                                                <td>${product.id}</td>
                                                <td>${product.name}</td>
                                                <td>${product.sku}</td>
                                                <td>$${parseFloat(product.price).toFixed(2)}</td>
                                                <td>${product.stock_quantity}</td>
                                                <td>${product.category_name || 'N/A'}</td>
                                                <td>
                                                    <span class="badge bg-${product.is_active ? 'success' : 'danger'}">
                                                        ${product.is_active ? 'Active' : 'Inactive'}
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="adminManager.editProduct(${product.id})">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="adminManager.deleteProduct(${product.id})">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Failed to load products:', error);
            return '<div class="alert alert-danger">Failed to load products.</div>';
        }
    }

    async renderCategories() {
        try {
            const response = await fetch('api/admin.php?action=categories');
            const data = await response.json();
            
            if (data.success) {
                return `
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4>Categories Management</h4>
                        <button class="btn btn-primary" onclick="adminManager.showCategoryModal()">
                            <i class="fas fa-plus me-2"></i>Add Category
                        </button>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.categories.map(category => `
                                            <tr>
                                                <td>${category.id}</td>
                                                <td>${category.name}</td>
                                                <td>${category.description || 'N/A'}</td>
                                                <td>
                                                    <span class="badge bg-${category.is_active ? 'success' : 'danger'}">
                                                        ${category.is_active ? 'Active' : 'Inactive'}
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="adminManager.editCategory(${category.id})">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Failed to load categories:', error);
            return '<div class="alert alert-danger">Failed to load categories.</div>';
        }
    }

    async renderOrders() {
        try {
            const response = await fetch('api/admin.php?action=orders');
            const data = await response.json();
            
            if (data.success) {
                return `
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4>Orders Management</h4>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Order #</th>
                                            <th>Customer</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Payment</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.orders.map(order => `
                                            <tr>
                                                <td>${order.order_number}</td>
                                                <td>${order.user_name || 'Guest'}</td>
                                                <td>$${parseFloat(order.total_amount).toFixed(2)}</td>
                                                <td>
                                                    <select class="form-select form-select-sm" onchange="adminManager.updateOrderStatus(${order.id}, this.value)">
                                                        <option value="pending" ${order.status === 'pending' ? 'selected' : ''}>Pending</option>
                                                        <option value="processing" ${order.status === 'processing' ? 'selected' : ''}>Processing</option>
                                                        <option value="shipped" ${order.status === 'shipped' ? 'selected' : ''}>Shipped</option>
                                                        <option value="delivered" ${order.status === 'delivered' ? 'selected' : ''}>Delivered</option>
                                                        <option value="cancelled" ${order.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <span class="badge bg-${this.getPaymentStatusColor(order.payment_status)}">
                                                        ${order.payment_status}
                                                    </span>
                                                </td>
                                                <td>${new Date(order.created_at).toLocaleDateString()}</td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="adminManager.viewOrder(${order.id})">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Failed to load orders:', error);
            return '<div class="alert alert-danger">Failed to load orders.</div>';
        }
    }

    async renderUsers() {
        try {
            const response = await fetch('api/admin.php?action=users');
            const data = await response.json();
            
            if (data.success) {
                return `
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4>Users Management</h4>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Joined</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.users.map(user => `
                                            <tr>
                                                <td>${user.id}</td>
                                                <td>${user.name}</td>
                                                <td>${user.email}</td>
                                                <td>
                                                    <span class="badge bg-${user.role === 'admin' ? 'danger' : 'primary'}">
                                                        ${user.role}
                                                    </span>
                                                </td>
                                                <td>${new Date(user.created_at).toLocaleDateString()}</td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="adminManager.viewUser(${user.id})">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Failed to load users:', error);
            return '<div class="alert alert-danger">Failed to load users.</div>';
        }
    }

    async renderPromotions() {
        try {
            const response = await fetch('api/admin.php?action=promotions');
            const data = await response.json();
            
            if (data.success) {
                return `
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4>Promotions Management</h4>
                        <button class="btn btn-primary" onclick="adminManager.showPromotionModal()">
                            <i class="fas fa-plus me-2"></i>Add Promotion
                        </button>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Code</th>
                                            <th>Name</th>
                                            <th>Type</th>
                                            <th>Value</th>
                                            <th>Min Order</th>
                                            <th>Usage</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.promotions.map(promotion => `
                                            <tr>
                                                <td><code>${promotion.code}</code></td>
                                                <td>${promotion.name}</td>
                                                <td>${promotion.type}</td>
                                                <td>${promotion.type === 'percentage' ? promotion.value + '%' : '$' + promotion.value}</td>
                                                <td>$${parseFloat(promotion.min_order_amount).toFixed(2)}</td>
                                                <td>${promotion.used_count}/${promotion.usage_limit || '∞'}</td>
                                                <td>
                                                    <span class="badge bg-${promotion.is_active ? 'success' : 'danger'}">
                                                        ${promotion.is_active ? 'Active' : 'Inactive'}
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="adminManager.editPromotion(${promotion.id})">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Failed to load promotions:', error);
            return '<div class="alert alert-danger">Failed to load promotions.</div>';
        }
    }

    async renderMessages() {
        try {
            const response = await fetch('api/admin.php?action=contact_messages');
            const data = await response.json();
            
            if (data.success) {
                return `
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4>Contact Messages</h4>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Subject</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.messages.map(message => `
                                            <tr>
                                                <td>${message.id}</td>
                                                <td>${message.name}</td>
                                                <td>${message.email}</td>
                                                <td>${message.subject}</td>
                                                <td>
                                                    <span class="badge bg-${this.getMessageStatusColor(message.status)}">
                                                        ${message.status}
                                                    </span>
                                                </td>
                                                <td>${new Date(message.created_at).toLocaleDateString()}</td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="adminManager.viewMessage(${message.id})">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Failed to load messages:', error);
            return '<div class="alert alert-danger">Failed to load messages.</div>';
        }
    }

    async updateOrderStatus(orderId, status) {
        try {
            const response = await fetch('api/admin.php?action=orders', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: orderId,
                    status: status,
                    csrf_token: this.app.csrfToken
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.app.showNotification('Order status updated successfully', 'success');
            } else {
                this.app.showNotification(data.message, 'danger');
            }
        } catch (error) {
            console.error('Failed to update order status:', error);
            this.app.showNotification('Failed to update order status', 'danger');
        }
    }

    showProductModal() {
        // Implementation for product modal
        this.app.showNotification('Product modal functionality coming soon', 'info');
    }

    showCategoryModal() {
        // Implementation for category modal
        this.app.showNotification('Category modal functionality coming soon', 'info');
    }

    showPromotionModal() {
        // Implementation for promotion modal
        this.app.showNotification('Promotion modal functionality coming soon', 'info');
    }

    editProduct(productId) {
        // Implementation for editing product
        this.app.showNotification('Edit product functionality coming soon', 'info');
    }

    deleteProduct(productId) {
        if (confirm('Are you sure you want to delete this product?')) {
            // Implementation for deleting product
            this.app.showNotification('Delete product functionality coming soon', 'info');
        }
    }

    editCategory(categoryId) {
        // Implementation for editing category
        this.app.showNotification('Edit category functionality coming soon', 'info');
    }

    viewOrder(orderId) {
        // Implementation for viewing order details
        this.app.showNotification('View order functionality coming soon', 'info');
    }

    viewUser(userId) {
        // Implementation for viewing user details
        this.app.showNotification('View user functionality coming soon', 'info');
    }

    editPromotion(promotionId) {
        // Implementation for editing promotion
        this.app.showNotification('Edit promotion functionality coming soon', 'info');
    }

    viewMessage(messageId) {
        // Implementation for viewing message details
        this.app.showNotification('View message functionality coming soon', 'info');
    }

    getStatusColor(status) {
        const colors = {
            'pending': 'warning',
            'processing': 'info',
            'shipped': 'primary',
            'delivered': 'success',
            'cancelled': 'danger'
        };
        return colors[status] || 'secondary';
    }

    getPaymentStatusColor(status) {
        const colors = {
            'pending': 'warning',
            'paid': 'success',
            'failed': 'danger',
            'refunded': 'info'
        };
        return colors[status] || 'secondary';
    }

    getMessageStatusColor(status) {
        const colors = {
            'new': 'primary',
            'read': 'info',
            'replied': 'success'
        };
        return colors[status] || 'secondary';
    }
}

// Initialize admin manager
const adminManager = new AdminManager(app);

// Add admin styles
const adminStyles = `
    <style>
        .admin-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .admin-card:hover {
            transform: translateY(-5px);
        }
        
        .admin-card .card-title {
            color: white;
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .admin-card .card-text {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
        }
        
        .btn-group .btn {
            border-radius: 0;
        }
        
        .btn-group .btn:first-child {
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
        }
        
        .btn-group .btn:last-child {
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
        }
        
        .table-responsive {
            border-radius: 10px;
        }
        
        .form-select-sm {
            font-size: 0.875rem;
        }
    </style>
`;

// Add styles to head
document.head.insertAdjacentHTML('beforeend', adminStyles);