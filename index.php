<?php
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern E-Commerce Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="public/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#" onclick="loadPage('home')">
                <i class="fas fa-shopping-bag"></i> Modern Store
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="loadPage('home')">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="loadPage('products')">Products</a>
                    </li>
                </ul>
                
                <div class="navbar-nav me-3">
                    <div class="search-container">
                        <input type="text" id="search-input" class="form-control search-input" placeholder="Search products...">
                        <div id="search-results" class="search-results"></div>
                    </div>
                </div>
                
                <ul class="navbar-nav me-3">
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="#" onclick="showCart()">
                            <i class="fas fa-shopping-cart"></i>
                            <span id="cart-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">0</span>
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav" id="auth-nav">
                    <!-- Authentication menu will be loaded here -->
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid main-content">
        <div class="row">
            <div class="col-md-3 bg-light p-3 sidebar">
                <div class="mb-4">
                    <h5><i class="fas fa-tags"></i> Categories</h5>
                    <div id="categories-list">
                        <!-- Categories will be loaded here -->
                    </div>
                </div>

                <div id="admin-panel" class="mb-4" style="display: none;">
                    <h5 class="text-danger"><i class="fas fa-cog"></i> Admin Panel</h5>
                    <div class="list-group">
                        <button class="list-group-item list-group-item-action" onclick="showAdminTab('dashboard')">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </button>
                        <button class="list-group-item list-group-item-action" onclick="showAdminTab('products')">
                            <i class="fas fa-box"></i> Manage Products
                        </button>
                        <button class="list-group-item list-group-item-action" onclick="showAdminTab('orders')">
                            <i class="fas fa-shopping-bag"></i> Manage Orders
                        </button>
                        <button class="list-group-item list-group-item-action" onclick="showAdminTab('users')">
                            <i class="fas fa-users"></i> Manage Users
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-md-9 p-4">
                <div id="main-content">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="cartModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-shopping-cart"></i> Shopping Cart</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="cart-modal-body">
                    <!-- Cart items will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="clearCart()">
                        <i class="fas fa-trash"></i> Clear Cart
                    </button>
                    <button type="button" class="btn btn-primary" onclick="proceedToCheckout()">
                        <i class="fas fa-credit-card"></i> Checkout
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="public/js/app.js"></script>
</body>
</html>
