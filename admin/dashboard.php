<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-4"><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h2>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <i class="fas fa-box fa-3x mb-3"></i>
                    <h4 id="total-products">-</h4>
                    <p class="mb-0">Total Products</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                    <h4 id="total-orders">-</h4>
                    <p class="mb-0">Total Orders</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <i class="fas fa-users fa-3x mb-3"></i>
                    <h4 id="total-users">-</h4>
                    <p class="mb-0">Total Users</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <i class="fas fa-dollar-sign fa-3x mb-3"></i>
                    <h4 id="total-revenue">-</h4>
                    <p class="mb-0">Total Revenue</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-line"></i> Recent Orders</h5>
                </div>
                <div class="card-body">
                    <div id="recent-orders">
                        <div class="spinner"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-exclamation-triangle"></i> Low Stock Alert</h5>
                </div>
                <div class="card-body">
                    <div id="low-stock">
                        <div class="spinner"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
loadDashboardData();

async function loadDashboardData() {
    try {
        const response = await fetch('api/admin/dashboard.php');
        const data = await response.json();
        
        document.getElementById('total-products').textContent = data.totalProducts;
        document.getElementById('total-orders').textContent = data.totalOrders;
        document.getElementById('total-users').textContent = data.totalUsers;
        document.getElementById('total-revenue').textContent = '$' + data.totalRevenue;
        
        loadRecentOrders(data.recentOrders);
        loadLowStock(data.lowStock);
    } catch (error) {
        console.error('Error loading dashboard data:', error);
    }
}

function loadRecentOrders(orders) {
    const container = document.getElementById('recent-orders');
    if (!orders || orders.length === 0) {
        container.innerHTML = '<p class="text-muted">No recent orders</p>';
        return;
    }
    
    container.innerHTML = orders.map(order => `
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <strong>Order #${order.id}</strong><br>
                <small class="text-muted">${order.customer_name}</small>
            </div>
            <div class="text-end">
                <span class="badge bg-${getStatusColor(order.status)}">${order.status}</span><br>
                <small class="text-muted">$${order.total}</small>
            </div>
        </div>
    `).join('');
}

function loadLowStock(products) {
    const container = document.getElementById('low-stock');
    if (!products || products.length === 0) {
        container.innerHTML = '<p class="text-muted">All products have sufficient stock</p>';
        return;
    }
    
    container.innerHTML = products.map(product => `
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <strong>${product.name}</strong><br>
                <small class="text-muted">Current: ${product.stock}</small>
            </div>
            <div>
                <button class="btn btn-sm btn-warning" onclick="loadPage('admin/products', {edit: ${product.id}})">
                    <i class="fas fa-edit"></i> Update
                </button>
            </div>
        </div>
    `).join('');
}

function getStatusColor(status) {
    const colors = {
        'pending': 'warning',
        'paid': 'info',
        'shipped': 'primary',
        'delivered': 'success',
        'cancelled': 'danger'
    };
    return colors[status] || 'secondary';
}
</script>
