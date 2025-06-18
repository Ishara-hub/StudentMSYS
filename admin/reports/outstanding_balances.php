<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

checkRole(['Admin']);

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$batch_id = isset($_GET['batch_id']) ? intval($_GET['batch_id']) : 0;
$min_balance = isset($_GET['min_balance']) ? floatval($_GET['min_balance']) : 0;

// Get courses for filter dropdown
$courses = [];
$courseQuery = $conn->query("SELECT course_id, course_name, total_fee FROM courses ORDER BY course_name");
while ($row = $courseQuery->fetch_assoc()) {
    $courses[] = $row;
}

// Get batches for filter dropdown
$batches = [];
$batchQuery = $conn->query("SELECT batch_id, batch_name FROM batches ORDER BY batch_name");
while ($row = $batchQuery->fetch_assoc()) {
    $batches[] = $row;
}

// Build query with filters
$query = "SELECT s.student_id, s.full_name, s.contact_number, s.nic,
                 c.course_name, c.total_fee, 
                 b.batch_name, 
                 r.registration_date, r.balance,
                 (c.total_fee - r.balance) as paid_amount
          FROM registrations r
          JOIN students s ON r.student_id = s.student_id
          JOIN batches b ON r.batch_id = b.batch_id
          JOIN courses c ON b.course_id = c.course_id
          WHERE r.balance > 0";

$params = [];
$types = '';
$conditions = [];

if (!empty($search)) {
    $conditions[] = "(s.full_name LIKE ? OR s.contact_number LIKE ? OR s.nic LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'sss';
}

if ($course_id > 0) {
    $conditions[] = "c.course_id = ?";
    $params[] = $course_id;
    $types .= 'i';
}

if ($batch_id > 0) {
    $conditions[] = "b.batch_id = ?";
    $params[] = $batch_id;
    $types .= 'i';
}

if ($min_balance > 0) {
    $conditions[] = "r.balance >= ?";
    $params[] = $min_balance;
    $types .= 'd';
}

if (!empty($conditions)) {
    $query .= " AND " . implode(" AND ", $conditions);
}

$query .= " ORDER BY r.balance DESC, s.full_name ASC";

// Prepare and execute query
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);

// Calculate totals
$total_balance = 0;
$total_fee = 0;
$total_paid = 0;

foreach ($students as $student) {
    $total_balance += $student['balance'];
    $total_fee += $student['total_fee'];
    $total_paid += $student['paid_amount'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Outstanding Balances Report</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .report-container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 20px;
        }
        .filter-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .table-responsive {
            margin-top: 20px;
        }
        .highlight-row {
            background-color: #fff3cd !important;
        }
        .total-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .progress {
            height: 20px;
        }
        .progress-bar {
            transition: width 0.6s ease;
        }
        @media print {
            .no-print {
                display: none;
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
        <h2 class="mb-4">Outstanding Balances Report</h2>
        
        <div class="filter-card no-print">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search (Name, Phone, NIC)</label>
                    <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2">
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
                <div class="col-md-2">
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
                <div class="col-md-2">
                    <label class="form-label">Min. Balance</label>
                    <input type="number" name="min_balance" class="form-control" min="0" step="0.01" value="<?= htmlspecialchars($min_balance) ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <button type="button" class="btn btn-success" onclick="window.print()">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </form>
        </div>
        
        <div class="alert alert-warning">
            <strong>Total Outstanding Balance: <?= DEFAULT_CURRENCY ?><?= number_format($total_balance, 2) ?></strong> 
            across <?= count($students) ?> students (Total Fees: <?= DEFAULT_CURRENCY ?><?= number_format($total_fee, 2) ?>, 
            Paid: <?= DEFAULT_CURRENCY ?><?= number_format($total_paid, 2) ?>)
        </div>
        
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <th>Contact</th>
                        <th>NIC</th>
                        <th>Course</th>
                        <th>Batch</th>
                        <th class="text-end">Total Fee</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Balance</th>
                        <th>Progress</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($students) > 0): ?>
                        <?php foreach ($students as $student): ?>
                            <tr class="<?= $student['balance'] >= 1000 ? 'highlight-row' : '' ?>">
                                <td><?= htmlspecialchars($student['student_id']) ?></td>
                                <td><?= htmlspecialchars($student['full_name']) ?></td>
                                <td><?= htmlspecialchars($student['contact_number']) ?></td>
                                <td><?= htmlspecialchars($student['nic']) ?></td>
                                <td><?= htmlspecialchars($student['course_name']) ?></td>
                                <td><?= htmlspecialchars($student['batch_name']) ?></td>
                                <td class="text-end"><?= DEFAULT_CURRENCY ?><?= number_format($student['total_fee'], 2) ?></td>
                                <td class="text-end"><?= DEFAULT_CURRENCY ?><?= number_format($student['paid_amount'], 2) ?></td>
                                <td class="text-end fw-bold"><?= DEFAULT_CURRENCY ?><?= number_format($student['balance'], 2) ?></td>
                                <td>
                                    <div class="progress">
                                        <?php 
                                        $paid_percent = $student['total_fee'] > 0 ? 
                                            round(($student['paid_amount'] / $student['total_fee']) * 100) : 0;
                                        $progress_class = $paid_percent >= 80 ? 'bg-success' : 
                                                         ($paid_percent >= 50 ? 'bg-info' : 'bg-warning');
                                        ?>
                                        <div class="progress-bar <?= $progress_class ?>" 
                                             role="progressbar" 
                                             style="width: <?= $paid_percent ?>%" 
                                             aria-valuenow="<?= $paid_percent ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            <?= $paid_percent ?>%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="6" class="text-end">Totals:</td>
                            <td class="text-end"><?= DEFAULT_CURRENCY ?><?= number_format($total_fee, 2) ?></td>
                            <td class="text-end"><?= DEFAULT_CURRENCY ?><?= number_format($total_paid, 2) ?></td>
                            <td class="text-end"><?= DEFAULT_CURRENCY ?><?= number_format($total_balance, 2) ?></td>
                            <td></td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center">No students found with outstanding balances matching your criteria</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="no-print mt-4">
            <div class="alert alert-info">
                <h5><i class="fas fa-info-circle"></i> Report Summary</h5>
                <ul>
                    <li>Total Students with Outstanding Balances: <?= count($students) ?></li>
                    <li>Total Outstanding Amount: <?= DEFAULT_CURRENCY ?><?= number_format($total_balance, 2) ?></li>
                    <li>Average Outstanding per Student: <?= DEFAULT_CURRENCY ?><?= count($students) > 0 ? number_format($total_balance / count($students), 2) : '0.00' ?></li>
                    <li>Students with balances ≥ <?= DEFAULT_CURRENCY ?>1,000: <?= count(array_filter($students, function($s) { return $s['balance'] >= 1000; })) ?></li>
                </ul>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    
    <script>
    // Highlight rows with significant balances
    document.addEventListener('DOMContentLoaded', function() {
        const rows = document.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const balanceCell = row.querySelector('td:nth-child(9)');
            if (balanceCell) {
                const balance = parseFloat(balanceCell.textContent.replace(/[^\d.-]/g, ''));
                if (balance >= 1000) {
                    row.classList.add('highlight-row');
                }
            }
        });
    });
    </script>
</body>
</html>