<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-lg">
                <div class="card-body p-5">
                    <h3 class="text-center mb-4">Login</h3>
                    
                    <form id="login-form">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-3">
                        <p>Don't have an account? <a href="#" onclick="loadPage('register')">Register here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('login-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch('api/login.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            app.showToast('Login successful!', 'success');
            app.loadUserStatus();
            loadPage('home');
        } else {
            app.showToast(result.error || 'Login failed', 'error');
        }
    } catch (error) {
        app.showToast('Login failed', 'error');
    }
});
</script>
