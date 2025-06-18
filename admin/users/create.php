<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Admin authentication
checkRole(['Admin']);

$error = '';
$success = '';

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirm_password']);
    $role = $_POST['role'];
    $relatedId = !empty($_POST['related_id']) ? (int)$_POST['related_id'] : null;

    // Validate inputs
    if (empty($username) || empty($password) || empty($role)) {
        $error = 'All fields are required';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters';
    } else {
        // Check if username exists (removed email check)
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = 'Username already exists';
        } else {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert user (removed email from query)
            $insert = $conn->prepare("INSERT INTO users (username, password, role, related_id) VALUES (?, ?, ?, ?)");
            $insert->bind_param("sssi", $username, $hashedPassword, $role, $relatedId);

            if ($insert->execute()) {
                $success = 'User created successfully';
                $_POST = []; // Clear form
            } else {
                $error = 'Error creating user: ' . $conn->error;
            }
        }
    }
}

// Get agents for related_id dropdown (if role is Agent)
$agents = [];
$agentResult = $conn->query("SELECT agent_id, agent_name FROM agents ORDER BY agent_name");
while ($row = $agentResult->fetch_assoc()) {
    $agents[] = $row;
}

$isMobile = isMobile();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create User</title>
    <link rel="stylesheet" href="../../assets/css/<?= $isMobile ? 'mobile' : 'style' ?>.css">
    <style>
        .user-form {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input, 
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .error {
            color: #e74c3c;
            margin-bottom: 15px;
        }
        .success {
            color: #2ecc71;
            margin-bottom: 15px;
        }
        .password-strength {
            margin-top: 5px;
            font-size: 12px;
        }
        .strength-weak { color: red; }
        .strength-medium { color: orange; }
        .strength-strong { color: green; }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="user-form">
        <h2>Create New User</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
            </div>     
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <div id="password-strength" class="password-strength"></div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" required>
                    <option value="">Select Role</option>
                    <option value="Admin" <?= ($_POST['role'] ?? '') === 'Admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="Agent" <?= ($_POST['role'] ?? '') === 'Agent' ? 'selected' : '' ?>>Agent</option>
                    <option value="Staff" <?= ($_POST['role'] ?? '') === 'Staff' ? 'selected' : '' ?>>Staff</option>
                </select>
            </div>
            
            <div class="form-group" id="related-id-group" style="display: none;">
                <label for="related_id">Associated Agent</label>
                <select id="related_id" name="related_id">
                    <option value="">None</option>
                    <?php foreach ($agents as $agent): ?>
                        <option value="<?= $agent['agent_id'] ?>" <?= ($_POST['related_id'] ?? '') == $agent['agent_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($agent['agent_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small>Only required if role is Agent</small>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn">Create User</button>
                <a href="manage_users.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        // Show/hide related_id field based on role selection
        document.getElementById('role').addEventListener('change', function() {
            const relatedIdGroup = document.getElementById('related-id-group');
            relatedIdGroup.style.display = this.value === 'Agent' ? 'block' : 'none';
        });

        // Trigger change event on page load
        document.getElementById('role').dispatchEvent(new Event('change'));

        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const strengthText = document.getElementById('password-strength');
            const password = this.value;
            
            if (password.length === 0) {
                strengthText.textContent = '';
                return;
            }
            
            // Very basic strength check - enhance as needed
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            let strengthClass, strengthLabel;
            if (strength <= 1) {
                strengthClass = 'strength-weak';
                strengthLabel = 'Weak';
            } else if (strength <= 3) {
                strengthClass = 'strength-medium';
                strengthLabel = 'Medium';
            } else {
                strengthClass = 'strength-strong';
                strengthLabel = 'Strong';
            }
            
            strengthText.className = 'password-strength ' + strengthClass;
            strengthText.textContent = 'Strength: ' + strengthLabel;
        });
    </script>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>