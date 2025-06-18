<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

checkRole(['Admin', 'Agent', 'Staff']);

// Get all students for the dropdown
$studentsList = [];
$allStudentsStmt = $conn->prepare("SELECT student_id, full_name FROM students ORDER BY full_name");
$allStudentsStmt->execute();
$allStudentsResult = $allStudentsStmt->get_result();
while ($row = $allStudentsResult->fetch_assoc()) {
    $studentsList[] = $row;
}

// Set studentId with proper ternary logic
$studentId = isset($_GET['id']) ? intval($_GET['id']) : (count($studentsList) > 0 ? $studentsList[0]['student_id'] : 0);
// Get student info
$student = [
    'full_name' => 'Select a student',
    'student_id' => 'N/A',
    'contact_number' => 'N/A',
    'status' => 'Unknown',
    'agent_name' => 'N/A'
];

$registration = [
    'course_name' => 'N/A',
    'batch_name' => 'N/A',
    'total_fee' => 0,
    'balance' => 0,
    'registration_id' => null
];

if ($studentId > 0) {
    $stmt = $conn->prepare("SELECT s.*, a.agent_name, s.student_id, s.contact_number, s.status
                            FROM students s 
                            LEFT JOIN agents a ON s.agent_id = a.agent_id 
                            WHERE s.student_id = ?");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $student = array_merge($student, $result->fetch_assoc());
    }

    // Get registration info
    $regStmt = $conn->prepare("SELECT r.*, b.batch_name, c.course_name, c.total_fee 
                              FROM registrations r 
                              JOIN batches b ON r.batch_id = b.batch_id 
                              JOIN courses c ON b.course_id = c.course_id 
                              WHERE r.student_id = ?");
    $regStmt->bind_param("i", $studentId);
    $regStmt->execute();
    $regResult = $regStmt->get_result();
    if ($regResult->num_rows > 0) {
        $registration = array_merge($registration, $regResult->fetch_assoc());
    }

    // Get payments
    $payments = [];
    if (!empty($registration['registration_id'])) {
        $payStmt = $conn->prepare("SELECT * FROM payments WHERE registration_id = ? ORDER BY payment_date DESC LIMIT 3");
        $payStmt->bind_param("i", $registration['registration_id']);
        $payStmt->execute();
        $payResult = $payStmt->get_result();
        while ($row = $payResult->fetch_assoc()) {
            $payments[] = $row;
        }
    }

    // Get progress
    $progress = [];
    $progressStmt = $conn->prepare("SELECT * FROM student_progress WHERE student_id = ? ORDER BY progress_date DESC LIMIT 5");
    $progressStmt->bind_param("i", $studentId);
    $progressStmt->execute();
    $progressResult = $progressStmt->get_result();
    while ($row = $progressResult->fetch_assoc()) {
        $progress[] = $row;
    }
}
?>
<?php include '../includes/header.php'; ?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card shadow">
            <div class="card-body">
                <!-- Student Selection Dropdown -->
                <form method="get" class="mb-4">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="studentSelect"><strong>Select Student:</strong></label>
                                <select class="form-control" id="studentSelect" name="id" onchange="this.form.submit()">
                                    <?php foreach ($studentsList as $s): ?>
                                        <option value="<?= $s['student_id'] ?>" <?= $s['student_id'] == $studentId ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($s['full_name']) ?> (ID: <?= $s['student_id'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <a href="student_list.php" class="btn btn-secondary">
                                <i class="fas fa-list"></i> View Full Student List
                            </a>
                        </div>
                    </div>
                </form>

                <!-- Student Info -->
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="mb-0">
                        <i class="fas fa-user-graduate"></i> <?= htmlspecialchars($student['full_name']) ?>
                    </h2>
                    <span class="badge bg-<?= getStatusBadge($student['status']) ?>">
                        <?= htmlspecialchars($student['status']) ?>
                    </span>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <p><i class="fas fa-id-card"></i> <strong>Student ID:</strong> <?= htmlspecialchars($student['student_id']) ?></p>
                        <p><i class="fas fa-phone"></i> <strong>Contact:</strong> <?= htmlspecialchars($student['contact_number']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><i class="fas fa-book"></i> <strong>Course:</strong> <?= htmlspecialchars($registration['course_name']) ?></p>
                        <p><i class="fas fa-calendar-alt"></i> <strong>Batch:</strong> <?= htmlspecialchars($registration['batch_name']) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($studentId > 0): ?>
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-money-bill-wave"></i> Payment Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-6">
                            <div class="p-3 bg-light rounded">
                                <h6 class="text-muted">Total Fee</h6>
                                <h4><?= DEFAULT_CURRENCY ?><?= number_format($registration['total_fee'], 2) ?></h4>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded">
                                <h6 class="text-muted">Balance</h6>
                                <h4 class="<?= $registration['balance'] > 0 ? 'text-danger' : 'text-success' ?>">
                                    <?= DEFAULT_CURRENCY ?><?= number_format($registration['balance'], 2) ?>
                                </h4>
                            </div>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="payments/make_payment.php?id=<?= $studentId ?>" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> Make Payment
                        </a>
                        <a href="payments/history.php?id=<?= $studentId ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-history"></i> View Payment History
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> Recent Progress</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($progress)): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($progress as $update): ?>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <strong><?= htmlspecialchars($update['status']) ?></strong>
                                        <small class="text-muted"><?= date('M j, Y', strtotime($update['progress_date'])) ?></small>
                                    </div>
                                    <?php if (!empty($update['notes'])): ?>
                                        <p class="mb-0 text-muted"><?= htmlspecialchars($update['notes']) ?></p>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="mt-3">
                            <a href="add_progress.php?id=<?= $studentId ?>" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-list"></i> View All Progress
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info mb-0">No progress updates available</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-receipt"></i> Recent Payments</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($payments)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Receipt</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td><?= date('M j, Y', strtotime($payment['payment_date'])) ?></td>
                                            <td><?= DEFAULT_CURRENCY ?><?= number_format($payment['amount'], 2) ?></td>
                                            <td><?= htmlspecialchars($payment['payment_method']) ?></td>
                                            <td><?= $payment['payment_id'] ?? 'N/A' ?></td>
                                            <td class="action-buttons">
                                                <a href="payments/generate_invoice.php?id=<?= $payment['payment_id'] ?>" class="btn btn-sm btn-outline-primary" title="View Receipt">
                                                    <i class="fas fa-file-invoice"></i>
                                                </a>
                                                <a href="payments/edit_payment.php?id=<?= $payment['payment_id'] ?>" class="btn btn-sm btn-outline-secondary" title="Edit Payment">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info mb-0">No payment records found</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-warning">
        No students found in the system. Please add students first.
    </div>
<?php endif; ?>

<?php
function getStatusBadge($status) {
    switch ($status) {
        case 'Active': return 'success';
        case 'Completed': return 'primary';
        case 'Placed': return 'info';
        case 'Dropped': return 'danger';
        default: return 'secondary';
    }
}
?>

<?php include '../includes/footer.php'; ?>