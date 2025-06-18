<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

checkRole(['Admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $company = $conn->real_escape_string($_POST['company']);
    $contact = $conn->real_escape_string($_POST['contact']);
    $email = $conn->real_escape_string($_POST['email']);
    $commission = floatval($_POST['commission']);
    
    $stmt = $conn->prepare("INSERT INTO agents (agent_name, company, contact_number, email, commission_rate) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssd", $name, $company, $contact, $email, $commission);
    
    if ($stmt->execute()) {
        // Create user account for agent
        $agentId = $stmt->insert_id;
        $username = strtolower(str_replace(' ', '', $name)) . $agentId;
        $password = password_hash('agent123', PASSWORD_DEFAULT);
        
        $userStmt = $conn->prepare("INSERT INTO users (username, password, role, related_id) VALUES (?, ?, 'Agent', ?)");
        $userStmt->bind_param("ssi", $username, $password, $agentId);
        $userStmt->execute();
        
        redirect("manage_agents.php", "Agent added successfully! Default username: $username");
    } else {
        $error = "Error adding agent: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add New Agent</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/<?= isMobile() ? 'mobile' : 'style' ?>.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container mt-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h2 class="h4">Add New Agent</h2>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Agent Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Company</label>
                        <input type="text" name="company" class="form-control">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Contact Number</label>
                        <input type="text" name="contact" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Commission Rate (%)</label>
                        <input type="number" name="commission" class="form-control" step="0.01" min="0" max="100" required>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex">
                        <button type="submit" class="btn btn-primary me-md-2">Add Agent</button>
                        <a href="manage_agents.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>