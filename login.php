<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Check if user is already logged in
if (isLoggedIn()) {
    redirectBasedOnRole();
}

// Initialize variables
$error = '';
$username = '';

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Validate inputs
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        // Attempt login
        if (login($username, $password)) {
            // Login successful - redirect based on role
            redirectBasedOnRole();
        } else {
            $error = 'Invalid username or password';
        }
    }
}

// Function to redirect based on user role
function redirectBasedOnRole() {
    if ($_SESSION['role'] === 'Admin') {
        redirect('admin/dashboard.php');
    } elseif ($_SESSION['role'] === 'Agent') {
        redirect('agent/dashboard.php');
    } else {
        redirect('student/dashboard.php');
    }
}

$isMobile = isMobile();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Skill Training School</title>
    <link rel="stylesheet" href="assets/css/<?= $isMobile ? 'mobile' : 'style' ?>.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .login-container h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn-login {
            width: 100%;
            padding: 10px;
            background-color: #2c3e50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-login:hover {
            background-color: #1a252f;
        }
        .error {
            color: #e74c3c;
            margin-bottom: 15px;
            text-align: center;
        }
        .forgot-password {
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Skill Training School</h2>
        <h3>Login to Your Account</h3>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-login">Login</button>
        </form>
        
        <div class="forgot-password">
            <a href="forgot_password.php">Forgot Password?</a>
        </div>
    </div>

    <script>
        // Focus on username field when page loads
        document.getElementById('username').focus();
        
        // Show/hide password toggle (would need additional HTML element)
        // This is just a placeholder for potential enhancement
        function setupPasswordToggle() {
            const passwordInput = document.getElementById('password');
            const toggle = document.createElement('span');
            // Implementation would go here
        }
    </script>
</body>