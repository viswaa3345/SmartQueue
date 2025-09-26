<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Queue Restaurant - Welcome</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .welcome-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            padding: 3rem;
            text-align: center;
            max-width: 600px;
            margin: 2rem;
        }
        
        .logo {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
        
        .btn-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            margin: 0.5rem;
            min-width: 150px;
        }
        
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            color: white;
        }
        
        .btn-secondary-custom {
            background: linear-gradient(135deg, #ff9a56 0%, #ff6b35 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            margin: 0.5rem;
            min-width: 150px;
        }
        
        .btn-secondary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            color: white;
        }
        
        .feature-list {
            text-align: left;
            margin: 2rem 0;
        }
        
        .feature-item {
            margin: 0.5rem 0;
            color: #555;
        }
        
        .feature-item i {
            color: #667eea;
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="hero-section">
        <div class="welcome-card">
            <div class="logo">
                <i class="fas fa-utensils"></i>
            </div>
            <h1 class="display-4 mb-3">Smart Queue Restaurant</h1>
            <p class="lead mb-4">Welcome to our intelligent queue management system for restaurants</p>
            
            <div class="feature-list">
                <div class="feature-item">
                    <i class="fas fa-clock"></i> Skip the waiting line
                </div>
                <div class="feature-item">
                    <i class="fas fa-mobile-alt"></i> Book your table digitally
                </div>
                <div class="feature-item">
                    <i class="fas fa-bell"></i> Get notified when your table is ready
                </div>
                <div class="feature-item">
                    <i class="fas fa-chart-line"></i> Track queue status in real-time
                </div>
            </div>
            
            <div class="mt-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- User is logged in -->
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="admin_dashboard.html" class="btn btn-custom">
                            <i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard
                        </a>
                    <?php else: ?>
                        <a href="user_dashboard.html" class="btn btn-custom">
                            <i class="fas fa-user me-2"></i>My Dashboard
                        </a>
                    <?php endif; ?>
                    <a href="api/logout.php" class="btn btn-secondary-custom">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                <?php else: ?>
                    <!-- User is not logged in -->
                    <a href="register.html" class="btn btn-custom">
                        <i class="fas fa-user-plus me-2"></i>Register
                    </a>
                    <a href="test_login.html" class="btn btn-secondary-custom">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </a>
                <?php endif; ?>
            </div>
            
            <div class="mt-4">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    New to Smart Queue? Register as a customer or contact admin for restaurant management access.
                </small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>