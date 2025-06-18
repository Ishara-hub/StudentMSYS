<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

checkRole(['Admin', 'Agent', 'Staff']);

$studentId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get student info
$student = [];
$stmt = $conn->prepare("SELECT student_id, full_name FROM students WHERE student_id = ?");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    $_SESSION['error'] = 'Student not found';
    header("Location: ../../dashboard.php");
    exit();
}

// Get registration and payments
$payments = [];
$stmt = $conn->prepare("SELECT p.* FROM payments p 
                       JOIN registrations r ON p.registration_id = r.registration_id 
                       WHERE r.student_id = ? 
                       ORDER BY p.payment_date DESC");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $payments[] = $row;
}

$isMobile = isMobile();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment History</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../../../assets/css/<?= $isMobile ? 'mobile' : 'style' ?>.css">
    <style>
        .payment-history {
            max-width: 800px;
            margin: 20px auto;
        }
        .payment-table {
            width: 100%;
            border-collapse: collapse;
        }
        .payment-table th, 
        .payment-table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .payment-table th {
            background-color: #f2f2f2;
        }
        .total-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="payment-history">
        <h2>Payment History for <?= htmlspecialchars($student['full_name']) ?></h2>
        
        <table class="payment-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Receipt</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total = 0;
                foreach ($payments as $payment): 
                    $total += $payment['amount'];
                ?>
                    <tr>
                        <td><?= date('d M Y', strtotime($payment['payment_date'])) ?></td>
                        <td><?= DEFAULT_CURRENCY ?><?= number_format($payment['amount'], 2) ?></td>
                        <td><?= htmlspecialchars($payment['payment_method']) ?></td>
                        <td><?= $payment['receipt_number'] ?? 'N/A' ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="1">Total</td>
                    <td><?= DEFAULT_CURRENCY ?><?= number_format($total, 2) ?></td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>
        
        <div style="margin-top: 20px;">
            <a href="../dashboard.php?id=<?= $studentId ?>" class="btn">Back to Dashboard</a>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>