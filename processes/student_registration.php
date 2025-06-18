<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../student/register.php");
    exit();
}

// Function to sanitize and validate input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to validate date format
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// Initialize error array
$errors = [];

// Sanitize and validate all inputs
$title = sanitizeInput($_POST['title'] ?? '');
$fullName = sanitizeInput($_POST['full_name'] ?? '');
$initials = sanitizeInput($_POST['initials'] ?? '');
$nic = sanitizeInput($_POST['nic'] ?? '');
$batchId = isset($_POST['batch_id']) ? (int)$_POST['batch_id'] : 0;
$dob = sanitizeInput($_POST['dob'] ?? '');
$gender = sanitizeInput($_POST['gender'] ?? '');
$phone = sanitizeInput($_POST['contact_number'] ?? '');
$address = sanitizeInput($_POST['address'] ?? '');
$civilStatus = sanitizeInput($_POST['civil_status'] ?? 'Single');
$initialPayment = isset($_POST['initial_payment']) ? (float)$_POST['initial_payment'] : 0;
$motherName = sanitizeInput($_POST['mother_name'] ?? '');
$fatherName = sanitizeInput($_POST['father_name'] ?? '');
$spouseNic = sanitizeInput($_POST['spouse_nic'] ?? '');
$spouseName = sanitizeInput($_POST['spouse_name'] ?? '');

// Validate required fields
if (empty($title)) $errors[] = "Title is required";
if (empty($fullName)) $errors[] = "Full name is required";
if (empty($nic) || $nic === 'N/A') $errors[] = "NIC is required";
if (empty($batchId)) $errors[] = "Batch selection is required";
if (empty($phone)) $errors[] = "Phone number is required";
if (empty($address)) $errors[] = "Address is required";

// Validate phone format
if (!preg_match('/^(0|94|\+94)?[1-9][0-9]{8}$/', $phone)) {
    $errors[] = "Invalid phone number format";
}

// Validate NIC format (if not N/A)
if ($nic !== 'N/A' && !preg_match('/^([0-9]{9}[VXvx]|[0-9]{12})$/', $nic)) {
    $errors[] = "Invalid NIC format (use 123456789V or 199012345678)";
}

// Validate date of birth
if (!empty($dob) && !validateDate($dob)) {
    $errors[] = "Invalid date of birth format";
}

// Validate spouse details if married
if ($civilStatus === 'Married') {
    if (empty($spouseNic)) $errors[] = "Spouse NIC is required for married status";
    if (empty($spouseName)) $errors[] = "Spouse name is required for married status";
}

// If there are errors, redirect back with error messages
if (!empty($errors)) {
    $_SESSION['error'] = implode("<br>", $errors);
    header("Location: ../student/register.php");
    exit();
}

// Get course fee for selected batch
$feeQuery = $conn->prepare("SELECT c.total_fee FROM batches b JOIN courses c ON b.course_id = c.course_id WHERE b.batch_id = ?");
$feeQuery->bind_param("i", $batchId);
$feeQuery->execute();
$feeResult = $feeQuery->get_result();

if ($feeResult->num_rows === 0) {
    $_SESSION['error'] = 'Invalid batch selected';
    header("Location: ../student/register.php");
    exit();
}

$courseFee = $feeResult->fetch_assoc()['total_fee'];
$balance = $courseFee - $initialPayment;

// Start transaction
$conn->begin_transaction();

try {
    // Insert student record
    $stmt = $conn->prepare("INSERT INTO students (
        title, 
        full_name, 
        initials, 
        nic, 
        dob, 
        gender, 
        contact_number, 
        address, 
        civil_status, 
        mother_name, 
        father_name,
        status,
        registration_date
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Registered', NOW())");
    
    $stmt->bind_param(
        "sssssssssss", 
        $title, 
        $fullName, 
        $initials, 
        $nic, 
        $dob, 
        $gender, 
        $phone, 
        $address, 
        $civilStatus, 
        $motherName, 
        $fatherName
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to create student record: " . $stmt->error);
    }
    
    $studentId = $stmt->insert_id;

    // Insert spouse details if married
    if ($civilStatus === 'Married' && !empty($spouseNic) && !empty($spouseName)) {
        $spouseStmt = $conn->prepare("INSERT INTO student_spouses (
            student_id, 
            nic, 
            name,
            created_at
        ) VALUES (?, ?, ?, NOW())");
        
        $spouseStmt->bind_param("iss", $studentId, $spouseNic, $spouseName);
        
        if (!$spouseStmt->execute()) {
            throw new Exception("Failed to add spouse details: " . $spouseStmt->error);
        }
    }

    // Create registration
    $regStmt = $conn->prepare("INSERT INTO registrations (
        student_id, 
        batch_id, 
        initial_payment, 
        balance,
        registration_date
    ) VALUES (?, ?, ?, ?, NOW())");
    
    $regStmt->bind_param("iidd", $studentId, $batchId, $initialPayment, $balance);
    
    if (!$regStmt->execute()) {
        throw new Exception("Failed to create registration: " . $regStmt->error);
    }
    
    $registrationId = $regStmt->insert_id;

    // Record payment if any
    if ($initialPayment > 0) {
        $paymentStmt = $conn->prepare("INSERT INTO payments (
            registration_id, 
            amount, 
            payment_method,
            payment_date
        ) VALUES (?, ?, 'Cash', NOW())");
        
        $paymentStmt->bind_param("id", $registrationId, $initialPayment);
        
        if (!$paymentStmt->execute()) {
            throw new Exception("Failed to record payment: " . $paymentStmt->error);
        }
    }

    // Update batch student count
    if (!$conn->query("UPDATE batches SET current_students = current_students + 1 WHERE batch_id = $batchId")) {
        throw new Exception("Failed to update batch student count: " . $conn->error);
    }

    // Add initial progress
    if (!addStudentProgress($studentId, $batchId, 'Course Started', 'Initial registration')) {
        throw new Exception("Failed to add initial progress record");
    }

    // Commit transaction
    $conn->commit();

    $_SESSION['success'] = 'Student registered successfully!';
    header("Location: ../student/dashboard.php?id=$studentId");
    exit();

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $_SESSION['error'] = 'Registration failed: ' . $e->getMessage();
    header("Location: ../student/register.php");
    exit();
}
?>