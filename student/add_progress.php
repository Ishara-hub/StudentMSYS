<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

checkRole(['Admin', 'Staff']);

$studentId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get student info including batch_id
$student = [];
$stmt = $conn->prepare("SELECT s.student_id, s.full_name, r.batch_id 
                       FROM students s
                       LEFT JOIN registrations r ON s.student_id = r.student_id
                       WHERE s.student_id = ?
                       ORDER BY r.registration_date DESC LIMIT 1");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    $_SESSION['error'] = 'Student not found';
    header("Location: ../admin/students.php");
    exit();
}

// Get current progress to determine next step
$currentStep = 1;
$stmt = $conn->prepare("SELECT MAX(step_number) as max_step FROM student_progress WHERE student_id = ?");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $currentStep = $row['max_step'] ? $row['max_step'] + 1 : 1;
    if ($currentStep > 10) $currentStep = 10;
}

// Define steps for reference
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
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Progress Update</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .form-container {
            max-width: 800px;
            margin: 20px auto;
        }
        .form-section {
            margin-bottom: 25px;
            padding: 20px;
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
        }
        .section-title {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            color: #2c3e50;
        }
        .current-step {
            background-color: #e7f5ff;
            border-left: 4px solid #228be6;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="form-container">
        <h2 class="mb-4">Add Progress Update for <?= htmlspecialchars($student['full_name']) ?></h2>
        <div class="alert alert-info mb-4">
            Current Step: <strong><?= $steps[$currentStep] ?? 'Unknown' ?></strong>
        </div>
        
        <form method="POST" action="../processes/progress_update.php">
            <input type="hidden" name="student_id" value="<?= $student['student_id'] ?>">
            <input type="hidden" name="batch_id" value="<?= $student['batch_id'] ?>">
            <input type="hidden" name="step_number" value="<?= $currentStep ?>">
            <input type="hidden" name="current_status" value="<?= explode('. ', $steps[$currentStep])[1] ?>">
            
            <div class="form-section current-step">
                <h4 class="section-title"><?= $steps[$currentStep] ?></h4>
                
                <?php if ($currentStep == 1): ?>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                <?php elseif ($currentStep == 2): ?>
                     <div class="mb-3">
                        <label class="form-label">Agency Name</label>
                        <textarea name="agency_name" class="form-control" rows="3"></textarea>
                    </div>
                <?php elseif ($currentStep == 3): ?>
                    <div class="mb-3">
                        <label class="form-label">Test Date <span class="text-danger">*</span></label>
                        <input type="date" name="test_date" class="form-control" required>
                    </div>
                <?php elseif ($currentStep == 4): ?>
                    <div class="mb-3">
                        <label class="form-label">Test Result <span class="text-danger">*</span></label>
                        <select name="test_result" class="form-select" required>
                            <option value="">-- Select Result --</option>
                            <option value="Pass">Pass</option>
                            <option value="Fail">Fail</option>
                        </select>
                    </div>
                <?php elseif ($currentStep == 5): ?>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="video_sent" class="form-check-input" id="videoSent">
                        <label class="form-check-label" for="videoSent">Video Sent</label>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="documents_sent" class="form-check-input" id="documentsSent">
                        <label class="form-check-label" for="documentsSent">Documents Sent</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Document Details</label>
                        <textarea name="documents_details" class="form-control" rows="3"></textarea>
                    </div>
                <?php elseif ($currentStep == 6): ?>
                    <div class="mb-3">
                        <label class="form-label">Passport Ok <span class="text-danger">*</span></label>
                            <select name="passport_status" class="form-select" required>
                                <option value="">-- Select Result --</option>
                                <option value="OK">OK</option>
                                <option value="Not OK">Not OK</option>
                            </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Passport Number</label>
                        <input type="text" name="passport_number" class="form-control" placeholder="Enter Passport Number">
                    </div>
                <?php elseif ($currentStep == 7): ?>
                    <div class="mb-3">
                        <label class="form-label">Medical status <span class="text-danger">*</span></label>
                            <select name="medical_status" class="form-select" required>
                                <option value="">-- Select Result --</option>
                                <option value="Pass">medical Pass</option>
                                <option value="Fail">Medical Fail</option>
                            </select>
                    </div>
                <?php elseif ($currentStep == 8): ?>
                    <div class="mb-3">
                        <label class="form-label">Payment Status <span class="text-danger">*</span></label>
                            <select name="payment_status" class="form-select" required>
                                <option value="">-- Select Result --</option>
                                <option value="Paid">Paid</option>
                                <option value="Not Paid">Not Paid</option>
                            </select>
                    </div>
                <?php elseif ($currentStep == 9): ?>
                    <div class="mb-3">
                        <label class="form-label">Agency Name <span class="text-danger">*</span></label>
                        <input type="text" name="agency_name" class="form-control" placeholder="Enter Agency Name" required>
                    </div>
                <?php else: ?>
                    <div class="mb-3">
                        <label class="form-label">Abroad Status <span class="text-danger">*</span></label>
                        <select name="abroad_status" class="form-select" required>
                            <option value="">-- Select Status --</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="d-flex justify-content-between mt-4">
                <a href="add_progress.php?id=<?= $studentId ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Progress</button>
            </div>
        </form>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>