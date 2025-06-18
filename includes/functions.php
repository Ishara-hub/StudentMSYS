<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

// Mobile detection
function isMobile() {
    return preg_match("/(android|webos|iphone|ipad|ipod|blackberry|windows phone)/i", $_SERVER['HTTP_USER_AGENT']);
}

// Redirect with message
function redirect($url, $message = null) {
    if ($message) {
        $_SESSION['message'] = $message;
    }
    header("Location: $url");
    exit();
}

// Generate invoice number
function generateInvoiceNumber() {
    return INVOICE_PREFIX . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

// Get student balance
function getStudentBalance($studentId) {
    global $conn;
    $stmt = $conn->prepare("SELECT balance FROM registrations WHERE student_id = ?");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['balance'] ?? 0;
}

// Add student progress
function addStudentProgress($studentId, $batchId, $status, $notes = '') {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO student_progress (student_id, batch_id, status, notes) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $studentId, $batchId, $status, $notes);
    return $stmt->execute();
}

function displayAlerts() {
    if (isset($_SESSION['success'])) {
        echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
        echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
        unset($_SESSION['error']);
    }
    if (isset($_SESSION['message'])) {
        echo '<div class="alert alert-info">' . $_SESSION['message'] . '</div>';
        unset($_SESSION['message']);
    }
}
if (!function_exists('getStatusBadge')) {
    function getStatusBadge($status) {
        switch (strtolower($status)) {
            case 'active': return 'success';
            case 'completed': return 'primary';
            case 'dropped': return 'danger';
            case 'placed': return 'info';
            default: return 'secondary';
        }
    }
}


?>