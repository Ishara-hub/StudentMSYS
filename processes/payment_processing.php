<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../student/dashboard.php");
    exit();
}

// Validate inputs
$registrationId = intval($_POST['registration_id'] ?? 0);
$amount = floatval($_POST['amount'] ?? 0);
$paymentMethod = trim($_POST['payment_method'] ?? '');
$studentId = intval($_POST['student_id'] ?? 0);

if ($registrationId <= 0 || $amount <= 0 || empty($paymentMethod) || $studentId <= 0) {
    $_SESSION['error'] = 'Invalid payment details';
    header("Location: ../student/dashboard.php?id=$studentId");
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Record payment
    $stmt = $conn->prepare("INSERT INTO payments (registration_id, amount, payment_method) VALUES (?, ?, ?)");
    $stmt->bind_param("ids", $registrationId, $amount, $paymentMethod);
    $stmt->execute();

    // Update balance
    $update = $conn->prepare("UPDATE registrations SET balance = balance - ? WHERE registration_id = ?");
    $update->bind_param("di", $amount, $registrationId);
    $update->execute();

    // Check if fully paid
    $balanceCheck = $conn->prepare("SELECT balance FROM registrations WHERE registration_id = ?");
    $balanceCheck->bind_param("i", $registrationId);
    $balanceCheck->execute();
    $result = $balanceCheck->get_result();
    $balance = $result->fetch_assoc()['balance'];

    if ($balance <= 0) {
        $conn->query("UPDATE registrations SET payment_status = 'Full' WHERE registration_id = $registrationId");
    }

    // Commit transaction
    $conn->commit();

    $_SESSION['success'] = 'Payment recorded successfully!';
    header("Location: ../student/payments/history.php?id=$studentId");
    exit();

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $_SESSION['error'] = 'Payment failed: ' . $e->getMessage();
    header("Location: ../student/payments/make_payment.php?id=$studentId");
    exit();
}
?>