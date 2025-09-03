<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $isLoggedIn ? $_SESSION['user_role'] : 'guest';
$userName = $isLoggedIn ? $_SESSION['user_name'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fishing Gear Store - Premium Fishing Equipment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#" onclick="showHome()">
                <i class="fas fa-fish"></i> Fishing Gear Store
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="showHome()">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="showProducts()">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="showCategories()">Categories</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="showContact()">Contact</a>
                    </li>
                </ul>
                
                <!-- Search Bar -->
                <div class="d-flex me-3">
                    <input class="form-control me-2" type="search" id="searchInput" placeholder="Search products..." style="width: 300px;">
                    <button class="btn btn-outline-light" type="button" onclick="searchProducts()">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                
                <!-- User Menu -->
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="showCart()">
                            <i class="fas fa-shopping-cart"></i> Cart 
                            <span class="badge bg-danger" id="cartCount">0</span>
                        </a>
                    </li>
                    
                    <?php if ($isLoggedIn): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($userName); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="showOrderHistory()">
                                    <i class="fas fa-history"></i> Order History
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="showProfile()">
                                    <i class="fas fa-user-edit"></i> Profile
                                </a></li>
                                <?php if ($userRole === 'admin'): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="#" onclick="showAdminDashboard()">
                                        <i class="fas fa-tachometer-alt"></i> Admin Dashboard
                                    </a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" onclick="logout()">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#" onclick="showLoginModal()">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" onclick="showRegisterModal()">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container-fluid" style="margin-top: 80px;">
        <div id="mainContent">
            <!-- Home content will be loaded here -->
        </div>
    </main>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Login</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="loginForm">
                        <div class="mb-3">
                            <label for="loginEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="loginEmail" required>
                        </div>
                        <div class="mb-3">
                            <label for="loginPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="loginPassword" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                </div>
                <div class="modal-footer">
                    <p class="text-center w-100">Don't have an account? 
                        <a href="#" onclick="showRegisterModal()">Register here</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Register Modal -->
    <div class="modal fade" id="registerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Register</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="registerForm">
                        <div class="mb-3">
                            <label for="registerName" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="registerName" required>
                        </div>
                        <div class="mb-3">
                            <label for="registerEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="registerEmail" required>
                        </div>
                        <div class="mb-3">
                            <label for="registerPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="registerPassword" required>
                        </div>
                        <div class="mb-3">
                            <label for="registerConfirmPassword" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="registerConfirmPassword" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Register</button>
                    </form>
                </div>
                <div class="modal-footer">
                    <p class="text-center w-100">Already have an account? 
                        <a href="#" onclick="showLoginModal()">Login here</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Toast -->
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="notificationToast" class="toast" role="alert">
            <div class="toast-header">
                <i class="fas fa-bell text-primary me-2"></i>
                <strong class="me-auto">Notification</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body" id="toastMessage">
                <!-- Message will be inserted here -->
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
    <script>
        // Initialize app
        document.addEventListener('DOMContentLoaded', function() {
            loadCartCount();
            showHome();
        });
    </script>
</body>
</html>