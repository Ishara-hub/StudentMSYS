<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

checkRole(['Admin', 'Agent', 'Staff']);

// Initialize filter variables
$courseFilter = isset($_GET['course']) ? intval($_GET['course']) : '';
$batchFilter = isset($_GET['batch']) ? intval($_GET['batch']) : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build the base query
$query = "SELECT s.student_id, s.full_name, s.contact_number, s.email, s.status, 
                 s.registration_date, c.course_name, b.batch_name, a.agent_name
          FROM students s
          LEFT JOIN registrations r ON s.student_id = r.student_id
          LEFT JOIN batches b ON r.batch_id = b.batch_id
          LEFT JOIN courses c ON b.course_id = c.course_id
          LEFT JOIN agents a ON s.agent_id = a.agent_id
          WHERE 1=1";

// Add filters to the query
$params = [];
$types = '';

if (!empty($courseFilter)) {
    $query .= " AND c.course_id = ?";
    $params[] = $courseFilter;
    $types .= 'i';
}

if (!empty($batchFilter)) {
    $query .= " AND b.batch_id = ?";
    $params[] = $batchFilter;
    $types .= 'i';
}

if (!empty($statusFilter)) {
    $query .= " AND s.status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

if (!empty($dateFrom) && !empty($dateTo)) {
    $query .= " AND DATE(s.registration_date) BETWEEN ? AND ?";
    $params[] = $dateFrom;
    $params[] = $dateTo;
    $types .= 'ss';
}

$query .= " ORDER BY s.registration_date DESC";

// Prepare and execute the query
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);

// Get courses for filter dropdown
$courses = $conn->query("SELECT course_id, course_name FROM courses")->fetch_all(MYSQLI_ASSOC);

// Get batches for filter dropdown
$batches = $conn->query("SELECT batch_id, batch_name FROM batches")->fetch_all(MYSQLI_ASSOC);

// Status options
$statusOptions = ['Active', 'Completed', 'Dropped', 'Placed'];
?>

<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="mb-0"><i class="fas fa-users"></i> Student List</h2>
                        <a href="register.php" class="btn btn-light btn-sm">
                            <i class="fas fa-plus"></i> Add New Student
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filter Form -->
                    <form method="get" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="course">Course</label>
                                    <select class="form-control" id="course" name="course">
                                        <option value="">All Courses</option>
                                        <?php foreach ($courses as $course): ?>
                                            <option value="<?= $course['course_id'] ?>" <?= $courseFilter == $course['course_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($course['course_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="batch">Batch</label>
                                    <select class="form-control" id="batch" name="batch">
                                        <option value="">All Batches</option>
                                        <?php foreach ($batches as $batch): ?>
                                            <option value="<?= $batch['batch_id'] ?>" <?= $batchFilter == $batch['batch_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($batch['batch_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="">All Statuses</option>
                                        <?php foreach ($statusOptions as $status): ?>
                                            <option value="<?= $status ?>" <?= $statusFilter == $status ? 'selected' : '' ?>>
                                                <?= $status ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="date_from">From Date</label>
                                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="date_to">To Date</label>
                                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?= htmlspecialchars($dateTo) ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary mr-2">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                                <a href="student_list.php" class="btn btn-secondary">
                                    <i class="fas fa-sync-alt"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- Student List Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Contact</th>
                                    <th>Email</th>
                                    <th>Course</th>
                                    <th>Batch</th>
                                    <th>Status</th>
                                    <th>Registered On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($students) > 0): ?>
                                    <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($student['student_id']) ?></td>
                                            <td><?= htmlspecialchars($student['full_name']) ?></td>
                                            <td><?= htmlspecialchars($student['contact_number']) ?></td>
                                            <td><?= htmlspecialchars($student['email']) ?></td>
                                            <td><?= htmlspecialchars($student['course_name'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($student['batch_name'] ?? 'N/A') ?></td>
                                            <td>
                                                <span class="badge bg-<?= getStatusBadge($student['status']) ?>">
                                                    <?= htmlspecialchars($student['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($student['registration_date'])) ?></td>
                                            <td>
                                                <a href="dashboard.php?id=<?= $student['student_id'] ?>" class="btn btn-sm btn-primary" title="View Dashboard">
                                                    <i class="fas fa-tachometer-alt"></i>
                                                </a>
                                                <a href="edit_student.php?id=<?= $student['student_id'] ?>" class="btn btn-sm btn-info" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center">No students found</td>
                                    </tr>
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