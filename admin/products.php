<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-box"></i> Manage Products</h2>
            <button class="btn btn-primary" onclick="showAddProductModal()">
                <i class="fas fa-plus"></i> Add New Product
            </button>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <input type="text" id="product-search" class="form-control" placeholder="Search products...">
        </div>
        <div class="col-md-3">
            <select id="category-filter" class="form-select">
                <option value="">All Categories</option>
            </select>
        </div>
        <div class="col-md-3">
            <button class="btn btn-outline-secondary w-100" onclick="filterProducts()">
                <i class="fas fa-filter"></i> Filter
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="products-table-body">
                <tr>
                    <td colspan="6" class="text-center">
                        <div class="spinner"></div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Product Modal -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="product-modal-title">Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="product-form">
                    <input type="hidden" id="product-id" name="id">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="product-name" class="form-label">Product Name</label>
                                <input type="text" class="form-control" id="product-name" name="name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="product-description" class="form-label">Description</label>
                                <textarea class="form-control" id="product-description" name="description" rows="4" required></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="product-price" class="form-label">Price</label>
                                        <input type="number" class="form-control" id="product-price" name="price" step="0.01" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="product-stock" class="form-label">Stock</label>
                                        <input type="number" class="form-control" id="product-stock" name="stock" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="product-category" class="form-label">Category</label>
                                <select class="form-select" id="product-category" name="category_id" required>
                                    <option value="">Select Category</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="product-image" class="form-label">Product Image</label>
                                <input type="file" class="form-control" id="product-image" name="image" accept="image/*">
                                <div id="current-image" class="mt-2"></div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveProduct()">Save Product</button>
            </div>
        </div>
    </div>
</div>

<script>
let products = [];
let categories = [];

loadProducts();
loadCategories();

async function loadProducts() {
    try {
        const response = await fetch('api/admin/products.php');
        products = await response.json();
        renderProductsTable();
    } catch (error) {
        console.error('Error loading products:', error);
    }
}

async function loadCategories() {
    try {
        const response = await fetch('api/categories.php');
        categories = await response.json();
        
        const categoryFilter = document.getElementById('category-filter');
        const productCategory = document.getElementById('product-category');
        
        const options = categories.map(cat => `<option value="${cat.id}">${cat.name}</option>`).join('');
        
        categoryFilter.innerHTML = '<option value="">All Categories</option>' + options;
        productCategory.innerHTML = '<option value="">Select Category</option>' + options;
    } catch (error) {
        console.error('Error loading categories:', error);
    }
}

function renderProductsTable() {
    const tbody = document.getElementById('products-table-body');
    
    if (!products || products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">No products found</td></tr>';
        return;
    }
    
    tbody.innerHTML = products.map(product => `
        <tr>
            <td>
                <img src="${product.image || 'public/images/placeholder.jpg'}" alt="${product.name}" 
                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
            </td>
            <td>${product.name}</td>
            <td>${product.category_name || 'Uncategorized'}</td>
            <td>$${product.price}</td>
            <td>
                <span class="badge bg-${product.stock > 10 ? 'success' : product.stock > 0 ? 'warning' : 'danger'}">
                    ${product.stock}
                </span>
            </td>
            <td>
                <button class="btn btn-sm btn-outline-primary me-1" onclick="editProduct(${product.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteProduct(${product.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function showAddProductModal() {
    document.getElementById('product-modal-title').textContent = 'Add New Product';
    document.getElementById('product-form').reset();
    document.getElementById('product-id').value = '';
    document.getElementById('current-image').innerHTML = '';
    
    const modal = new bootstrap.Modal(document.getElementById('productModal'));
    modal.show();
}

function editProduct(productId) {
    const product = products.find(p => p.id === productId);
    if (!product) return;
    
    document.getElementById('product-modal-title').textContent = 'Edit Product';
    document.getElementById('product-id').value = product.id;
    document.getElementById('product-name').value = product.name;
    document.getElementById('product-description').value = product.description;
    document.getElementById('product-price').value = product.price;
    document.getElementById('product-stock').value = product.stock;
    document.getElementById('product-category').value = product.category_id || '';
    
    if (product.image) {
        document.getElementById('current-image').innerHTML = `
            <img src="${product.image}" alt="Current image" style="width: 100px; height: 100px; object-fit: cover; border-radius: 4px;">
        `;
    } else {
        document.getElementById('current-image').innerHTML = '';
    }
    
    const modal = new bootstrap.Modal(document.getElementById('productModal'));
    modal.show();
}

async function saveProduct() {
    const form = document.getElementById('product-form');
    const formData = new FormData(form);
    
    try {
        const response = await fetch('api/admin/save-product.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            app.showToast('Product saved successfully!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('productModal')).hide();
            loadProducts();
        } else {
            app.showToast(result.error || 'Failed to save product', 'error');
        }
    } catch (error) {
        app.showToast('Failed to save product', 'error');
    }
}

async function deleteProduct(productId) {
    if (!confirm('Are you sure you want to delete this product?')) return;
    
    try {
        const response = await fetch('api/admin/delete-product.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: productId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            app.showToast('Product deleted successfully!', 'success');
            loadProducts();
        } else {
            app.showToast(result.error || 'Failed to delete product', 'error');
        }
    } catch (error) {
        app.showToast('Failed to delete product', 'error');
    }
}

function filterProducts() {
    const search = document.getElementById('product-search').value.toLowerCase();
    const category = document.getElementById('category-filter').value;
    
    const filtered = products.filter(product => {
        const matchesSearch = product.name.toLowerCase().includes(search) || 
                             product.description.toLowerCase().includes(search);
        const matchesCategory = !category || product.category_id == category;
        
        return matchesSearch && matchesCategory;
    });
    
    renderFilteredProducts(filtered);
}

function renderFilteredProducts(filteredProducts) {
    const tbody = document.getElementById('products-table-body');
    
    if (filteredProducts.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">No products found</td></tr>';
        return;
    }
    
    tbody.innerHTML = filteredProducts.map(product => `
        <tr>
            <td>
                <img src="${product.image || 'public/images/placeholder.jpg'}" alt="${product.name}" 
                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
            </td>
            <td>${product.name}</td>
            <td>${product.category_name || 'Uncategorized'}</td>
            <td>$${product.price}</td>
            <td>
                <span class="badge bg-${product.stock > 10 ? 'success' : product.stock > 0 ? 'warning' : 'danger'}">
                    ${product.stock}
                </span>
            </td>
            <td>
                <button class="btn btn-sm btn-outline-primary me-1" onclick="editProduct(${product.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteProduct(${product.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}
</script>
