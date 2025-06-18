<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

checkRole(['Admin', 'Staff']);

// Get filter parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$batch_id = isset($_GET['batch_id']) ? intval($_GET['batch_id']) : 0;

// Get courses for filter dropdown
$courses = [];
$courseQuery = $conn->query("SELECT course_id, course_name FROM courses");
while ($row = $courseQuery->fetch_assoc()) {
    $courses[] = $row;
}

// Get batches for filter dropdown
$batches = [];
$batchQuery = $conn->query("SELECT batch_id, batch_name FROM batches");
while ($row = $batchQuery->fetch_assoc()) {
    $batches[] = $row;
}

// Build query with filters
$query = "SELECT s.student_id, s.full_name, s.nic, s.contact_number, 
                 c.course_name, b.batch_name, r.registration_date
          FROM students s
          JOIN registrations r ON s.student_id = r.student_id
          JOIN batches b ON r.batch_id = b.batch_id
          JOIN courses c ON b.course_id = c.course_id
          WHERE 1=1";

$params = [];
$types = '';

if (!empty($start_date)) {
    $query .= " AND r.registration_date >= ?";
    $params[] = $start_date;
    $types .= 's';
}

if (!empty($end_date)) {
    $query .= " AND r.registration_date <= ?";
    $params[] = $end_date;
    $types .= 's';
}

if ($course_id > 0) {
    $query .= " AND c.course_id = ?";
    $params[] = $course_id;
    $types .= 'i';
}

if ($batch_id > 0) {
    $query .= " AND b.batch_id = ?";
    $params[] = $batch_id;
    $types .= 'i';
}

$query .= " ORDER BY r.registration_date DESC";

$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student List Report</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .report-container {
            max-width: 1200px;
            margin: 20px auto;
        }
        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .table-responsive {
            margin-top: 20px;
        }
        .print-only {
            display: none;
        }
        @media print {
            .no-print {
                display: none;
            }
            .print-only {
                display: block;
            }
            body {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="report-container">
        <h2 class="mb-4">Student List Report</h2>
        
        <div class="filter-section no-print">
            <form method="GET" action="">
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Course</label>
                            <select name="course_id" class="form-select">
                                <option value="0">All Courses</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?= $course['course_id'] ?>" <?= $course_id == $course['course_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($course['course_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Batch</label>
                            <select name="batch_id" class="form-select">
                                <option value="0">All Batches</option>
                                <?php foreach ($batches as $batch): ?>
                                    <option value="<?= $batch['batch_id'] ?>" <?= $batch_id == $batch['batch_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($batch['batch_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <button type="button" class="btn btn-success" onclick="window.print()">Print Report</button>
                </div>
            </form>
        </div>
        
        <div class="print-only">
            <h4>Student List Report</h4>
            <p>
                <?php if (!empty($start_date) || !empty($end_date)): ?>
                    Date Range: <?= !empty($start_date) ? htmlspecialchars($start_date) : 'Start' ?> to <?= !empty($end_date) ? htmlspecialchars($end_date) : 'End' ?><br>
                <?php endif; ?>
                <?php if ($course_id > 0): ?>
                    Course: <?= htmlspecialchars(array_column($courses, 'course_name', 'course_id')[$course_id]) ?><br>
                <?php endif; ?>
                <?php if ($batch_id > 0): ?>
                    Batch: <?= htmlspecialchars(array_column($batches, 'batch_name', 'batch_id')[$batch_id]) ?><br>
                <?php endif; ?>
                Generated on: <?= date('Y-m-d H:i:s') ?>
            </p>
        </div>
        
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Full Name</th>
                        <th>NIC</th>
                        <th>Contact Number</th>
                        <th>Course</th>
                        <th>Batch</th>
                        <th>Registration Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($students) > 0): ?>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?= htmlspecialchars($student['student_id']) ?></td>
                                <td><?= htmlspecialchars($student['full_name']) ?></td>
                                <td><?= htmlspecialchars($student['nic']) ?></td>
                                <td><?= htmlspecialchars($student['contact_number']) ?></td>
                                <td><?= htmlspecialchars($student['course_name']) ?></td>
                                <td><?= htmlspecialchars($student['batch_name']) ?></td>
                                <td><?= htmlspecialchars($student['registration_date']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No students found with the selected filters</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>