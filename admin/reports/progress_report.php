<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

checkRole(['Admin', 'Staff']);

// Define steps for reference (same as in add_progress.php)
$steps = [
    1 => '1. Course Started',
    2 => '2. Course Completed',
    3 => '3. Test Scheduled',
    4 => '4. Test Completed',
    5 => '5. Documents Prepared',
    6 => '6. Passport Ready',
    7 => '7. Medical Check',
    8 => '8. Payment Completed',
    9 => '9. Visa Approved',
    10 => '10. Abroad'
];

// Get filter parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$step_number = isset($_GET['step_number']) ? intval($_GET['step_number']) : 0;

// Build query with filters
$query = "SELECT sp.progress_id, sp.student_id, s.full_name, 
                 sp.step_number, sp.status, sp.progress_date,
                 c.course_name, b.batch_name
          FROM student_progress sp
          JOIN students s ON sp.student_id = s.student_id
          JOIN registrations r ON s.student_id = r.student_id
          JOIN batches b ON r.batch_id = b.batch_id
          JOIN courses c ON b.course_id = c.course_id
          WHERE 1=1";

$params = [];
$types = '';

if (!empty($start_date)) {
    $query .= " AND sp.progress_date >= ?";
    $params[] = $start_date;
    $types .= 's';
}

if (!empty($end_date)) {
    $query .= " AND sp.progress_date <= ?";
    $params[] = $end_date;
    $types .= 's';
}

if ($step_number > 0) {
    $query .= " AND sp.step_number = ?";
    $params[] = $step_number;
    $types .= 'i';
}

$query .= " ORDER BY sp.progress_date DESC";

$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$progressData = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Progress Report</title>
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
        .progress-step {
            font-weight: bold;
            color: #2c3e50;
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
        <h2 class="mb-4">Student Progress Report</h2>
        
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
                            <label class="form-label">Progress Step</label>
                            <select name="step_number" class="form-select">
                                <option value="0">All Steps</option>
                                <?php foreach ($steps as $num => $step): ?>
                                    <option value="<?= $num ?>" <?= $step_number == $num ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($step) ?>
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
            <h4>Student Progress Report</h4>
            <p>
                <?php if (!empty($start_date) || !empty($end_date)): ?>
                    Date Range: <?= !empty($start_date) ? htmlspecialchars($start_date) : 'Start' ?> to <?= !empty($end_date) ? htmlspecialchars($end_date) : 'End' ?><br>
                <?php endif; ?>
                <?php if ($step_number > 0): ?>
                    Progress Step: <?= htmlspecialchars($steps[$step_number]) ?><br>
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
                        <th>Course</th>
                        <th>Batch</th>
                        <th>Progress Step</th>
                        <th>Status</th>
                        <th>Updated At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($progressData) > 0): ?>
                        <?php foreach ($progressData as $progress): ?>
                            <tr>
                                <td><?= htmlspecialchars($progress['student_id']) ?></td>
                                <td><?= htmlspecialchars($progress['full_name']) ?></td>
                                <td><?= htmlspecialchars($progress['course_name']) ?></td>
                                <td><?= htmlspecialchars($progress['batch_name']) ?></td>
                                <td class="progress-step"><?= htmlspecialchars($steps[$progress['step_number']] ?? 'Unknown') ?></td>
                                <td><?= htmlspecialchars($progress['status']) ?></td>
                                <td><?= htmlspecialchars($progress['progress_date']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No progress records found with the selected filters</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>