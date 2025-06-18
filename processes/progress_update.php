<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../student/dashboard.php");
    exit();
}

// Validate inputs
$studentId = intval($_POST['student_id'] ?? 0);
$stepNumber = intval($_POST['step_number'] ?? 0);
$currentStatus = trim($_POST['current_status'] ?? '');

// Get batch_id from registration table
$batchId = 0;
$batchQuery = $conn->prepare("SELECT batch_id FROM registrations WHERE student_id = ? ORDER BY registration_date DESC LIMIT 1");
$batchQuery->bind_param("i", $studentId);
$batchQuery->execute();
$batchResult = $batchQuery->get_result();
if ($batchResult->num_rows > 0) {
    $batchRow = $batchResult->fetch_assoc();
    $batchId = intval($batchRow['batch_id']);
}

// Define all steps with their fields
$steps = [
    1 => ['status' => 'Course Started', 'fields' => []],
    2 => ['status' => 'Course Completed', 'fields' => []],
    3 => ['status' => 'Test Scheduled', 'fields' => ['test_date']],
    4 => ['status' => 'Test Completed', 'fields' => ['test_result']],
    5 => ['status' => 'Documents Prepared', 'fields' => ['video_sent', 'documents_sent', 'documents_details']],
    6 => ['status' => 'Passport Ready', 'fields' => ['passport_status', 'passport_number']],
    7 => ['status' => 'Medical Check', 'fields' => ['medical_status']],
    8 => ['status' => 'Payment Completed', 'fields' => ['payment_status']],
    9 => ['status' => 'Visa Approved', 'fields' => ['agency_name']],
    10 => ['status' => 'Abroad', 'fields' => ['abroad_status']]
];

// Validate inputs
if ($studentId <= 0 || $batchId <= 0) {
    $_SESSION['error'] = 'Invalid student or batch information';
    header("Location: ../student/dashboard.php?id=$studentId");
    exit();
}

if ($stepNumber <= 0 || $stepNumber > 10 || !isset($steps[$stepNumber])) {
    $_SESSION['error'] = 'Invalid progress step number';
    header("Location: ../student/add_progress.php?id=$studentId");
    exit();
}

$submittedStatus = trim($_POST['current_status'] ?? '');
$expectedStatus = $steps[$stepNumber]['status'];

if (empty($submittedStatus) || $expectedStatus !== $submittedStatus) {
    $_SESSION['error'] = 'Invalid progress status for this step. Expected: ' . $expectedStatus . ' | Received: ' . $submittedStatus;
    header("Location: ../student/progress.php?id=$studentId");
    exit();
}

// Check if previous steps are completed (except for step 1)
if ($stepNumber > 1) {
    $prevStep = $stepNumber - 1;
    $checkStmt = $conn->prepare("SELECT 1 FROM student_progress WHERE student_id = ? AND step_number = ?");
    $checkStmt->bind_param("ii", $studentId, $prevStep);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        $_SESSION['error'] = "Please complete step $prevStep first";
        header("Location: ../student/add_progress.php?id=$studentId");
        exit();
    }
}

// Prepare data for current step
$fieldsToUpdate = ['status', 'progress_date'];
$values = [$currentStatus, date('Y-m-d H:i:s')];
$types = 'ss';

// Process step-specific fields
foreach ($steps[$stepNumber]['fields'] as $field) {
    $fieldsToUpdate[] = $field;
    
    switch ($field) {
        case 'test_date':
            $testDate = !empty($_POST['test_date']) ? $_POST['test_date'] : null;
            $values[] = $testDate;
            $types .= 's';
            break;
            
        case 'test_result':
            $testResult = in_array($_POST['test_result'] ?? '', ['Pass', 'Fail']) ? $_POST['test_result'] : '';
            $values[] = $testResult;
            $types .= 's';
            break;
            
        case 'video_sent':
        case 'documents_sent':
            $values[] = isset($_POST[$field]) ? 1 : 0;
            $types .= 'i';
            break;
            
        case 'documents_details':
        case 'passport_number':
        case 'agency_name':
            $values[] = trim($_POST[$field] ?? '');
            $types .= 's';
            break;
            
        case 'passport_status':
            $values[] = in_array($_POST['passport_status'] ?? '', ['OK', 'Not OK']) ? $_POST['passport_status'] : '';
            $types .= 's';
            break;
            
        case 'medical_status':
        case 'payment_status':
        case 'abroad_status':
            $values[] = in_array($_POST[$field] ?? '', ['Pass', 'Fail', 'Paid', 'Not Paid', 'Yes', 'No']) ? $_POST[$field] : '';
            $types .= 's';
            break;
    }
}

// Add notes if provided
$notes = trim($_POST['notes'] ?? '');
if (!empty($notes)) {
    $fieldsToUpdate[] = "notes";
    $values[] = $notes;
    $types .= 's';
}

try {
    // Begin transaction
    $conn->begin_transaction();
    
    // Use ON DUPLICATE KEY UPDATE to handle insert/update in one query
    $allFields = array_merge(['student_id', 'batch_id', 'step_number'], $fieldsToUpdate);
    $allValues = array_merge([$studentId, $batchId, $stepNumber], $values);
    $allTypes = 'iis' . $types;
    
    $placeholders = implode(',', array_fill(0, count($allValues), '?'));
    $columns = implode(',', $allFields);
    
    // Build the ON DUPLICATE KEY UPDATE part
    $updateParts = [];
    foreach ($fieldsToUpdate as $field) {
        $updateParts[] = "$field = VALUES($field)";
    }
    
    $query = "INSERT INTO student_progress ($columns) VALUES ($placeholders)
              ON DUPLICATE KEY UPDATE " . implode(', ', $updateParts);
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $bindResult = $stmt->bind_param($allTypes, ...$allValues);
    if (!$bindResult) {
        throw new Exception("Bind failed: " . $stmt->error);
    }
    
    $executeResult = $stmt->execute();
    if (!$executeResult) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    // Update student status if final step
    if ($stepNumber === 10) {
        $updateStmt = $conn->prepare("UPDATE students SET status = 'Abroad' WHERE student_id = ?");
        $updateStmt->bind_param("i", $studentId);
        $updateStmt->execute();
    }

    // Commit transaction
    $conn->commit();

    $_SESSION['success'] = 'Progress updated successfully!';
    header("Location: ../student/add_progress.php?id=$studentId");
    exit();

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    // Log detailed error
    error_log("Progress update failed: " . $e->getMessage());
    
    $_SESSION['error'] = 'Failed to update progress: ' . $e->getMessage();
    header("Location: ../student/add_progress.php?id=$studentId");
    exit();
}
?>