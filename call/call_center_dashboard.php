<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

checkRole(['CallCenter', 'Admin']);

// Get filter parameters
$courseId = isset($_GET['course_id']) ? intval($_GET['course_id']) : null;
$joinStartDate = isset($_GET['join_start_date']) ? $_GET['join_start_date'] : null;
$joinEndDate = isset($_GET['join_end_date']) ? $_GET['join_end_date'] : null;

// Build query with filters
$query = "SELECT ccr.*, c.course_name 
          FROM call_center_registrations ccr
          LEFT JOIN courses c ON ccr.interested_course_id = c.course_id
          WHERE 1=1";

$params = [];
$types = '';

if ($courseId) {
    $query .= " AND ccr.interested_course_id = ?";
    $params[] = $courseId;
    $types .= 'i';
}

if ($joinStartDate) {
    $query .= " AND ccr.join_date >= ?";
    $params[] = $joinStartDate;
    $types .= 's';
}

if ($joinEndDate) {
    $query .= " AND ccr.join_date <= ?";
    $params[] = $joinEndDate;
    $types .= 's';
}

$query .= " ORDER BY ccr.registration_date DESC";

// Prepare and execute query
$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$registrations = $result->fetch_all(MYSQLI_ASSOC);

// Get courses for filter dropdown
$courses = $conn->query("SELECT course_id, course_name FROM courses WHERE is_active = 1")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Call Center Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="../vendor/twbs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/<?= isMobile() ? 'mobile' : 'style' ?>.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-list me-2"></i>Call Center Registrations</h4>
                    <a href="call_center_register.php" class="btn btn-light">
                        <i class="fas fa-plus me-1"></i> New Registration
                    </a>
                </div>
            </div>
            
            <div class="card-body">
                <!-- Filter Form -->
                <form method="GET" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Course</label>
                            <select name="course_id" class="form-select">
                                <option value="">All Courses</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?= $course['course_id'] ?>" <?= $courseId == $course['course_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($course['course_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Join From Date</label>
                            <input type="date" name="join_start_date" class="form-control" value="<?= htmlspecialchars($joinStartDate) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Join To Date</label>
                            <input type="date" name="join_end_date" class="form-control" value="<?= htmlspecialchars($joinEndDate) ?>">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-filter me-1"></i> Filter
                            </button>
                            <a href="call_center_dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i> Clear
                            </a>
                        </div>
                    </div>
                </form>
                
                <!-- Registrations Table -->
                <div class="card shadow">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Name</th>
                                        <th>Phone</th>
                                        <th>Course</th>
                                        <th>Notes</th>
                                        <th>Join Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($registrations)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No registrations found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($registrations as $reg): ?>
                                            <tr>
                                                <td><?= date('d M Y', strtotime($reg['registration_date'])) ?></td>
                                                <td><?= htmlspecialchars($reg['student_name']) ?></td>
                                                <td><?= htmlspecialchars($reg['phone_number']) ?></td>
                                                <td><?= htmlspecialchars($reg['course_name'] ?? 'N/A') ?></td>
                                                <td><?= htmlspecialchars($reg['notes']) ?></td>
                                                <td><?= htmlspecialchars($reg['join_date']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
</body>
</html>