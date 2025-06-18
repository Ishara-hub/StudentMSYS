<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Admin authentication
checkRole(['Admin']);

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get user data
$user = [];
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    $_SESSION['error'] = 'User not found';
    header("Location: manage_users.php");
    exit();
}

$error = '';
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $role = $_POST['role'];
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $relatedId = !empty($_POST['related_id']) ? (int)$_POST['related_id'] : null;
    $changePassword = isset($_POST['change_password']);
    $password = $changePassword ? $_POST['password'] : '';
    $confirmPassword = $changePassword ? $_POST['confirm_password'] : '';

    // Validate inputs
    if (empty($username) || empty($email) || empty($role)) {
        $error = 'Required fields are missing';
    } elseif ($changePassword && ($password !== $confirmPassword || strlen($password) < 8)) {
        $error = $password !== $confirmPassword ? 'Passwords do not match' : 'Password must be at least 8 characters';
    } else {
        // Check if username or email exists for other users
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
        $stmt->bind_param("ssi", $username, $email, $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = 'Username or email already exists';
        } else {
            // Update user
            if ($changePassword) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ?, related_id = ?, is_active = ?, password = ? WHERE user_id = ?");
                $update->bind_param("ssssisi", $username, $email, $role, $relatedId, $isActive, $hashedPassword, $userId);
            } else {
                $update = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ?, related_id = ?, is_active = ? WHERE user_id = ?");
                $update->bind_param("ssssii", $username, $email, $role, $relatedId, $isActive, $userId);
            }

            if ($update->execute()) {
                $success = 'User updated successfully';
                // Refresh user data
                $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
            } else {
                $error = 'Error updating user: ' . $conn->error;
            }
        }
    }
}

// Get agents for related_id dropdown
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
    <title>Edit User</title>
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
        .checkbox-group {
            display: flex;
            align-items: center;
        }
        .checkbox-group input {
            width: auto;
            margin-right: 10px;
        }
        .password-fields {
            display: none;
            margin-top: 15px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="user-form">
        <h2>Edit User: <?= htmlspecialchars($user['username']) ?></h2>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
            </div>

            
            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" required>
                    <option value="Admin" <?= $user['role'] === 'Admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="Agent" <?= $user['role'] === 'Agent' ? 'selected' : '' ?>>Agent</option>
                    <option value="Staff" <?= $user['role'] === 'Staff' ? 'selected' : '' ?>>Staff</option>
                </select>
            </div>
            
            <div class="form-group" id="related-id-group" style="<?= $user['role'] !== 'Agent' ? 'display: none;' : '' ?>">
                <label for="related_id">Associated Agent</label>
                <select id="related_id" name="related_id">
                    <option value="">None</option>
                    <?php foreach ($agents as $agent): ?>
                        <option value="<?= $agent['agent_id'] ?>" <?= $user['related_id'] == $agent['agent_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($agent['agent_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small>Only required if role is Agent</small>
            </div>
            
            <div class="form-group checkbox-group">
                <input type="checkbox" id="is_active" name="is_active" value="1" <?= $user['is_active'] ? 'checked' : '' ?>>
                <label for="is_active">Active User</label>
            </div>
            
            <div class="form-group checkbox-group">
                <input type="checkbox" id="change_password" name="change_password" value="1">
                <label for="change_password">Change Password</label>
            </div>
            
            <div id="password-fields" class="password-fields">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password">
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn">Update User</button>
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

        // Show/hide password fields
        document.getElementById('change_password').addEventListener('change', function() {
            document.getElementById('password-fields').style.display = this.checked ? 'block' : 'none';
            document.getElementById('password').required = this.checked;
            document.getElementById('confirm_password').required = this.checked;
        });
    </script>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>