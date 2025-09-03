// Advanced features for Fishing Gear Store

class AdvancedFeatures {
    constructor(app) {
        this.app = app;
    }

    async showOrderHistory() {
        // Check if user is logged in
        const authResponse = await fetch('api/auth.php?action=check');
        const authData = await authResponse.json();
        
        if (!authData.logged_in) {
            this.app.showNotification('Please login to view order history', 'warning');
            this.app.showLoginModal();
            return;
        }

        const content = document.getElementById('mainContent');
        
        try {
            const response = await fetch('api/orders.php?action=list');
            const data = await response.json();
            
            let html = `
                <div class="container">
                    <h2 class="mb-4">Order History</h2>
            `;
            
            if (data.success && data.orders.length > 0) {
                html += `
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Order #</th>
                                            <th>Date</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Payment</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                `;
                
                data.orders.forEach(order => {
                    html += `
                        <tr>
                            <td>${order.order_number}</td>
                            <td>${new Date(order.created_at).toLocaleDateString()}</td>
                            <td>$${parseFloat(order.total_amount).toFixed(2)}</td>
                            <td>
                                <span class="badge bg-${this.getStatusColor(order.status)}">
                                    ${order.status.charAt(0).toUpperCase() + order.status.slice(1)}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-${this.getPaymentStatusColor(order.payment_status)}">
                                    ${order.payment_status.charAt(0).toUpperCase() + order.payment_status.slice(1)}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick="advancedFeatures.viewOrderDetails(${order.id})">
                                    <i class="fas fa-eye me-1"></i>View
                                </button>
                                <button class="btn btn-sm btn-outline-success" onclick="advancedFeatures.downloadReceipt(${order.id})">
                                    <i class="fas fa-download me-1"></i>Receipt
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
                    </div>
                `;
            } else {
                html += `
                    <div class="text-center">
                        <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                        <h4>No orders found</h4>
                        <p class="text-muted">You haven't placed any orders yet.</p>
                        <button class="btn btn-primary" onclick="app.showProducts()">Start Shopping</button>
                    </div>
                `;
            }
            
            html += '</div>';
            content.innerHTML = html;
            content.classList.add('fade-in');
        } catch (error) {
            console.error('Failed to load order history:', error);
            content.innerHTML = '<div class="container"><div class="alert alert-danger">Failed to load order history.</div></div>';
        }
    }

    async viewOrderDetails(orderId) {
        try {
            const response = await fetch(`api/orders.php?action=get&id=${orderId}`);
            const data = await response.json();
            
            if (data.success) {
                const order = data.order;
                
                const modalHtml = `
                    <div class="modal fade" id="orderDetailsModal" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Order Details - ${order.order_number}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <h6>Order Information</h6>
                                            <p><strong>Order Date:</strong> ${new Date(order.created_at).toLocaleString()}</p>
                                            <p><strong>Status:</strong> 
                                                <span class="badge bg-${this.getStatusColor(order.status)}">
                                                    ${order.status.charAt(0).toUpperCase() + order.status.slice(1)}
                                                </span>
                                            </p>
                                            <p><strong>Payment Status:</strong> 
                                                <span class="badge bg-${this.getPaymentStatusColor(order.payment_status)}">
                                                    ${order.payment_status.charAt(0).toUpperCase() + order.payment_status.slice(1)}
                                                </span>
                                            </p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Shipping Address</h6>
                                            ${order.shipping_address ? `
                                                <p>${JSON.parse(order.shipping_address).first_name} ${JSON.parse(order.shipping_address).last_name}</p>
                                                <p>${JSON.parse(order.shipping_address).address_line_1}</p>
                                                ${JSON.parse(order.shipping_address).address_line_2 ? `<p>${JSON.parse(order.shipping_address).address_line_2}</p>` : ''}
                                                <p>${JSON.parse(order.shipping_address).city}, ${JSON.parse(order.shipping_address).state} ${JSON.parse(order.shipping_address).zip_code}</p>
                                            ` : '<p>No shipping address available</p>'}
                                        </div>
                                    </div>
                                    
                                    <h6>Order Items</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Product</th>
                                                    <th>SKU</th>
                                                    <th>Quantity</th>
                                                    <th>Price</th>
                                                    <th>Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${order.items.map(item => `
                                                    <tr>
                                                        <td>${item.product_name}</td>
                                                        <td>${item.product_sku}</td>
                                                        <td>${item.quantity}</td>
                                                        <td>$${parseFloat(item.price).toFixed(2)}</td>
                                                        <td>$${parseFloat(item.total).toFixed(2)}</td>
                                                    </tr>
                                                `).join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <div class="row mt-3">
                                        <div class="col-md-6"></div>
                                        <div class="col-md-6">
                                            <div class="d-flex justify-content-between">
                                                <span>Subtotal:</span>
                                                <span>$${parseFloat(order.subtotal).toFixed(2)}</span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span>Tax:</span>
                                                <span>$${parseFloat(order.tax_amount).toFixed(2)}</span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span>Shipping:</span>
                                                <span>$${parseFloat(order.shipping_amount).toFixed(2)}</span>
                                            </div>
                                            ${order.discount_amount > 0 ? `
                                                <div class="d-flex justify-content-between text-success">
                                                    <span>Discount:</span>
                                                    <span>-$${parseFloat(order.discount_amount).toFixed(2)}</span>
                                                </div>
                                            ` : ''}
                                            <hr>
                                            <div class="d-flex justify-content-between fw-bold">
                                                <span>Total:</span>
                                                <span>$${parseFloat(order.total_amount).toFixed(2)}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-primary" onclick="advancedFeatures.downloadReceipt(${order.id})">
                                        <i class="fas fa-download me-2"></i>Download Receipt
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Remove existing modal if any
                const existingModal = document.getElementById('orderDetailsModal');
                if (existingModal) {
                    existingModal.remove();
                }
                
                // Add modal to body
                document.body.insertAdjacentHTML('beforeend', modalHtml);
                
                // Show modal
                const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
                modal.show();
            } else {
                this.app.showNotification(data.message, 'danger');
            }
        } catch (error) {
            console.error('Failed to load order details:', error);
            this.app.showNotification('Failed to load order details', 'danger');
        }
    }

    async downloadReceipt(orderId) {
        try {
            const response = await fetch(`api/orders.php?action=receipt&id=${orderId}`);
            const data = await response.json();
            
            if (data.success) {
                // Create a new window with the receipt
                const receiptWindow = window.open('', '_blank');
                receiptWindow.document.write(data.receipt);
                receiptWindow.document.close();
                
                // Trigger print dialog
                receiptWindow.print();
                
                this.app.showNotification('Receipt opened for printing', 'success');
            } else {
                this.app.showNotification(data.message, 'danger');
            }
        } catch (error) {
            console.error('Failed to download receipt:', error);
            this.app.showNotification('Failed to download receipt', 'danger');
        }
    }

    async showProductDetails(productId) {
        try {
            const response = await fetch(`api/products.php?action=get&id=${productId}`);
            const data = await response.json();
            
            if (data.success) {
                const product = data.product;
                
                const modalHtml = `
                    <div class="modal fade" id="productDetailsModal" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">${product.name}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <img src="assets/images/products/${product.id}.jpg" 
                                                 class="img-fluid rounded" 
                                                 alt="${product.name}"
                                                 onerror="this.src='assets/images/placeholder.jpg'">
                                        </div>
                                        <div class="col-md-6">
                                            <h4>${product.name}</h4>
                                            <p class="text-muted">${product.brand} - ${product.sku}</p>
                                            
                                            <div class="mb-3">
                                                <span class="h3 text-success">$${parseFloat(product.sale_price || product.price).toFixed(2)}</span>
                                                ${product.sale_price ? `<span class="text-muted text-decoration-line-through ms-2">$${parseFloat(product.price).toFixed(2)}</span>` : ''}
                                            </div>
                                            
                                            <div class="mb-3">
                                                <span class="badge bg-${product.stock_quantity > 0 ? 'success' : 'danger'}">
                                                    ${product.stock_quantity > 0 ? 'In Stock' : 'Out of Stock'}
                                                </span>
                                                <span class="ms-2">${product.stock_quantity} available</span>
                                            </div>
                                            
                                            <p>${product.description}</p>
                                            
                                            <div class="mb-3">
                                                <label for="productQuantity" class="form-label">Quantity:</label>
                                                <input type="number" class="form-control" id="productQuantity" 
                                                       value="1" min="1" max="${product.stock_quantity}" style="width: 100px;">
                                            </div>
                                            
                                            <button class="btn btn-primary btn-lg" 
                                                    onclick="advancedFeatures.addToCartFromModal(${product.id})"
                                                    ${product.stock_quantity <= 0 ? 'disabled' : ''}>
                                                <i class="fas fa-cart-plus me-2"></i>Add to Cart
                                            </button>
                                        </div>
                                    </div>
                                    
                                    ${product.reviews && product.reviews.length > 0 ? `
                                        <hr class="my-4">
                                        <h5>Customer Reviews</h5>
                                        <div class="mb-3">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3">
                                                    <div class="h4 mb-0">${product.avg_rating}</div>
                                                    <div class="text-warning">
                                                        ${'★'.repeat(Math.floor(product.avg_rating))}${'☆'.repeat(5 - Math.floor(product.avg_rating))}
                                                    </div>
                                                    <small class="text-muted">${product.review_count} reviews</small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="reviews">
                                            ${product.reviews.slice(0, 3).map(review => `
                                                <div class="review-item">
                                                    <div class="review-header">
                                                        <div>
                                                            <strong>${review.user_name}</strong>
                                                            <div class="review-rating">
                                                                ${'★'.repeat(review.rating)}${'☆'.repeat(5 - review.rating)}
                                                            </div>
                                                        </div>
                                                        <small class="review-date">${new Date(review.created_at).toLocaleDateString()}</small>
                                                    </div>
                                                    ${review.title ? `<h6>${review.title}</h6>` : ''}
                                                    <p>${review.comment}</p>
                                                </div>
                                            `).join('')}
                                        </div>
                                    ` : ''}
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Remove existing modal if any
                const existingModal = document.getElementById('productDetailsModal');
                if (existingModal) {
                    existingModal.remove();
                }
                
                // Add modal to body
                document.body.insertAdjacentHTML('beforeend', modalHtml);
                
                // Show modal
                const modal = new bootstrap.Modal(document.getElementById('productDetailsModal'));
                modal.show();
            } else {
                this.app.showNotification(data.message, 'danger');
            }
        } catch (error) {
            console.error('Failed to load product details:', error);
            this.app.showNotification('Failed to load product details', 'danger');
        }
    }

    async addToCartFromModal(productId) {
        const quantity = parseInt(document.getElementById('productQuantity').value) || 1;
        await this.app.addToCart(productId, quantity);
        
        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('productDetailsModal'));
        if (modal) {
            modal.hide();
        }
    }

    showProfile() {
        // Check if user is logged in
        const authResponse = await fetch('api/auth.php?action=check');
        const authData = await authResponse.json();
        
        if (!authData.logged_in) {
            this.app.showNotification('Please login to view profile', 'warning');
            this.app.showLoginModal();
            return;
        }

        const content = document.getElementById('mainContent');
        
        const html = `
            <div class="container">
                <div class="row">
                    <div class="col-md-8 mx-auto">
                        <div class="card">
                            <div class="card-header">
                                <h4><i class="fas fa-user me-2"></i>User Profile</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Personal Information</h6>
                                        <p><strong>Name:</strong> ${authData.user.name}</p>
                                        <p><strong>Email:</strong> ${authData.user.email}</p>
                                        <p><strong>Role:</strong> 
                                            <span class="badge bg-${authData.user.role === 'admin' ? 'danger' : 'primary'}">
                                                ${authData.user.role}
                                            </span>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Account Actions</h6>
                                        <button class="btn btn-outline-primary w-100 mb-2" onclick="advancedFeatures.showOrderHistory()">
                                            <i class="fas fa-history me-2"></i>Order History
                                        </button>
                                        <button class="btn btn-outline-secondary w-100 mb-2" onclick="advancedFeatures.showNotifications()">
                                            <i class="fas fa-bell me-2"></i>Notifications
                                        </button>
                                        <button class="btn btn-outline-info w-100" onclick="advancedFeatures.showAddresses()">
                                            <i class="fas fa-map-marker-alt me-2"></i>Addresses
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        content.innerHTML = html;
        content.classList.add('fade-in');
    }

    async showNotifications() {
        const content = document.getElementById('mainContent');
        
        const html = `
            <div class="container">
                <h2 class="mb-4">Notifications</h2>
                <div class="card">
                    <div class="card-body">
                        <div class="text-center">
                            <i class="fas fa-bell fa-3x text-muted mb-3"></i>
                            <h4>No notifications</h4>
                            <p class="text-muted">You don't have any notifications at the moment.</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        content.innerHTML = html;
        content.classList.add('fade-in');
    }

    showAddresses() {
        const content = document.getElementById('mainContent');
        
        const html = `
            <div class="container">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Addresses</h2>
                    <button class="btn btn-primary" onclick="advancedFeatures.addAddress()">
                        <i class="fas fa-plus me-2"></i>Add Address
                    </button>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <div class="text-center">
                            <i class="fas fa-map-marker-alt fa-3x text-muted mb-3"></i>
                            <h4>No addresses saved</h4>
                            <p class="text-muted">Add an address to make checkout faster.</p>
                            <button class="btn btn-primary" onclick="advancedFeatures.addAddress()">
                                Add Your First Address
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        content.innerHTML = html;
        content.classList.add('fade-in');
    }

    addAddress() {
        this.app.showNotification('Add address functionality coming soon', 'info');
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
}

// Initialize advanced features
const advancedFeatures = new AdvancedFeatures(app);

// Add global functions
function showOrderHistory() {
    advancedFeatures.showOrderHistory();
}

function showProfile() {
    advancedFeatures.showProfile();
}

function showProductDetails(productId) {
    advancedFeatures.showProductDetails(productId);
}

function showAdminDashboard() {
    adminManager.showAdminDashboard();
}