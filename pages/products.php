<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-4"><i class="fas fa-box"></i> All Products</h2>
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" id="product-search" class="form-control" placeholder="Search products...">
                        <button class="btn btn-outline-primary" onclick="searchProducts()">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <select id="sort-select" class="form-select d-inline-block w-auto" onchange="sortProducts()">
                        <option value="name">Sort by Name</option>
                        <option value="price_low">Price: Low to High</option>
                        <option value="price_high">Price: High to Low</option>
                        <option value="newest">Newest First</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div id="products-container">
        <div class="spinner"></div>
    </div>
</div>

<script>
function searchProducts() {
    const query = document.getElementById('product-search').value;
    if (query.trim()) {
        app.loadProducts({ search: query });
    } else {
        app.loadProducts();
    }
}

function sortProducts() {
    const sortBy = document.getElementById('sort-select').value;
    app.loadProducts({ sort: sortBy });
}
</script>
