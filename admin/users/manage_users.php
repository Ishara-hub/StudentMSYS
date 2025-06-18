<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Admin authentication
checkRole(['Admin']);

// Handle user deletion
if (isset($_GET['delete'])) {
    $userId = (int)$_GET['delete'];
    // Prevent deleting own account
    if ($userId !== $_SESSION['user_id']) {
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $_SESSION['message'] = 'User deleted successfully';
        } else {
            $_SESSION['error'] = 'Error deleting user';
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = 'You cannot delete your own account';
    }
    
    header("Location: manage_users.php");
    exit();
}

// Get all users
$users = [];
$query = "SELECT u.*, a.agent_name 
          FROM users u 
          LEFT JOIN agents a ON u.related_id = a.agent_id 
          ORDER BY u.role, u.username";
$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $result->free();
} else {
    $_SESSION['error'] = 'Error fetching users: ' . $conn->error;
}

$isMobile = isMobile();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Users</title>
    <link rel="stylesheet" href="../../assets/css/<?= $isMobile ? 'mobile' : 'style' ?>.css">
    <style>
        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .user-table th, .user-table td {
            padding: 12px 15px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .user-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .user-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .user-table tr:hover {
            background-color: #e9ecef;
        }
        .status-active {
            color: #28a745;
            font-weight: 500;
        }
        .status-inactive {
            color: #dc3545;
            font-weight: 500;
        }
        .action-links a {
            margin-right: 8px;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #007bff;
            color: white;
            border-radius: 4px;
            text-decoration: none;
            margin-bottom: 20px;
        }
        .btn:hover {
            background-color: #0069d9;
        }
        .btn-small {
            padding: 5px 10px;
            font-size: 14px;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .success {
            color: #28a745;
            background-color: #d4edda;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .error {
            color: #dc3545;
            background-color: #f8d7da;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <h2>User Management</h2>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="success"><?= htmlspecialchars($_SESSION['message']) ?></div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <div class="toolbar">
            <a href="create.php" class="btn">Create New User</a>
        </div>
        
        <table class="user-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Associated Agent</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['role']) ?></td>
                    <td><?= htmlspecialchars($user['agent_name'] ?? 'N/A') ?></td>
                    <td class="<?= $user['is_active'] ? 'status-active' : 'status-inactive' ?>">
                        <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                    </td>
                    <td><?= $user['last_login'] ? date('M j, Y g:i a', strtotime($user['last_login'])) : 'Never' ?></td>
                    <td class="action-links">
                        <a href="edit.php?id=<?= $user['user_id'] ?>" class="btn btn-small">Edit</a>
                        <?php if ($user['user_id'] !== $_SESSION['user_id']): ?>
                            <a href="manage_users.php?delete=<?= $user['user_id'] ?>" class="btn btn-small btn-danger" 
                               onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>