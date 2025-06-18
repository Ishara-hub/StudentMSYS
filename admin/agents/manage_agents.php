<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

checkRole(['Admin']);

// Get all agents with their student counts
$agents = [];
$query = $conn->query("SELECT a.*, COUNT(s.student_id) as student_count 
                      FROM agents a 
                      LEFT JOIN students s ON a.agent_id = s.agent_id 
                      GROUP BY a.agent_id
                      ORDER BY a.agent_name");
while ($row = $query->fetch_assoc()) {
    $agents[] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Agents</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/<?= isMobile() ? 'mobile' : 'style' ?>.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4">Manage Agents</h2>
            <a href="add_agent.php" class="btn btn-primary">Add New Agent</a>
        </div>
        
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Agent Name</th>
                        <th>Company</th>
                        <th>Contact</th>
                        <th>Students</th>
                        <th>Commission</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($agents as $agent): ?>
                    <tr>
                        <td><?= htmlspecialchars($agent['agent_name']) ?></td>
                        <td><?= htmlspecialchars($agent['company']) ?></td>
                        <td>
                            <?= htmlspecialchars($agent['contact_number']) ?><br>
                            <?= htmlspecialchars($agent['email']) ?>
                        </td>
                        <td><?= $agent['student_count'] ?></td>
                        <td><?= $agent['commission_rate'] ?>%</td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="edit_agent.php?id=<?= $agent['agent_id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="students.php?id=<?= $agent['agent_id'] ?>" class="btn btn-sm btn-info">Students</a>
                                <a href="reset_password.php?id=<?= $agent['agent_id'] ?>" class="btn btn-sm btn-secondary">Reset Password</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>