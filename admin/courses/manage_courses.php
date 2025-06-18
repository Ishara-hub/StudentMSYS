<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

checkRole(['Admin']);

// Handle course activation/deactivation
if (isset($_GET['toggle_status'])) {
    $courseId = intval($_GET['id']);
    $conn->query("UPDATE courses SET is_active = NOT is_active WHERE course_id = $courseId");
    redirect("manage_courses.php");
}

// Get all courses
$courses = [];
$query = $conn->query("SELECT * FROM courses ORDER BY course_name");
while ($row = $query->fetch_assoc()) {
    $courses[] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Courses</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="../../vendor/twbs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/<?= isMobile() ? 'mobile' : 'style' ?>.css">
    <style>
        .status-badge {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 700;
            border-radius: 0.25rem;
        }
        .status-badge.active {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .status-badge.inactive {
            background-color: #f8d7da;
            color: #842029;
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-book me-2"></i>Manage Courses</h2>
            <a href="add_course.php" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Add New Course
            </a>
        </div>
        
        <div class="card shadow">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Course Name</th>
                                <th>Fee</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses as $course): ?>
                            <tr>
                                <td><?= htmlspecialchars($course['course_name']) ?></td>
                                <td><?= DEFAULT_CURRENCY ?><?= number_format($course['total_fee'], 2) ?></td>
                                <td><?= $course['duration_weeks'] ?> weeks</td>
                                <td>
                                    <span class="status-badge <?= $course['is_active'] ? 'active' : 'inactive' ?>">
                                        <?= $course['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="edit_course.php?id=<?= $course['course_id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?toggle_status=1&id=<?= $course['course_id'] ?>" class="btn btn-sm <?= $course['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?>">
                                            <?= $course['is_active'] ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>' ?>
                                        </a>
                                        <a href="../courses/batches/manage_batches.php?course_id=<?= $course['course_id'] ?>" class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-users"></i> Batches
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    
    <!-- Bootstrap Bundle with Popper -->
</body>
</html>