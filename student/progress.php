<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

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
    header("Location: ../admin/students.php");
    exit();
}

// Get all progress updates ordered by step
$progress = [];
$stmt = $conn->prepare("SELECT * FROM student_progress WHERE student_id = ? ORDER BY step_number ASC");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $progress[$row['step_number']] = $row;
}

// Define all steps with titles
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

$isMobile = isMobile();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Student Progress</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .progress-container {
            max-width: 1000px;
            margin: 20px auto;
        }
        .step-card {
            margin-bottom: 20px;
            border-left: 4px solid #dee2e6;
        }
        .step-card.completed {
            border-left-color: #28a745;
        }
        .step-card.current {
            border-left-color: #007bff;
        }
        .step-header {
            display: flex;
            justify-content: space-between;
            padding: 15px;
            background-color: #f8f9fa;
            cursor: pointer;
        }
        .step-title {
            font-weight: bold;
            margin: 0;
        }
        .step-status {
            font-weight: bold;
        }
        .step-details {
            padding: 15px;
            background-color: #fff;
            display: none;
        }
        .detail-item {
            margin-bottom: 10px;
        }
        .detail-label {
            font-weight: bold;
            color: #6c757d;
        }
        .badge {
            font-size: 0.9em;
        }
        .form-container {
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="progress-container">
        <h2 class="mb-4">Student Progress: <?= htmlspecialchars($student['full_name']) ?></h2>
        
        <div class="progress-steps">
            <?php 
            $currentStep = 0;
            $lastCompleted = 0;
            
            // Find current step
            foreach ($steps as $stepNum => $stepTitle) {
                if (isset($progress[$stepNum])) {
                    $lastCompleted = $stepNum;
                }
            }
            $currentStep = $lastCompleted < 10 ? $lastCompleted + 1 : 10;
            
            // Display all steps
            foreach ($steps as $stepNum => $stepTitle): 
                $stepData = $progress[$stepNum] ?? null;
                $isCompleted = $stepData !== null;
                $isCurrent = $stepNum === $currentStep;
            ?>
                <div class="step-card <?= $isCompleted ? 'completed' : '' ?> <?= $isCurrent ? 'current' : '' ?>">
                    <div class="step-header" onclick="toggleStepDetails(<?= $stepNum ?>)">
                        <h5 class="step-title"><?= $stepTitle ?></h5>
                        <span class="step-status">
                            <?php if ($isCompleted): ?>
                                <span class="badge badge-success">Completed</span>
                                <small class="text-muted"><?= date('d M Y', strtotime($stepData['progress_date'])) ?></small>
                            <?php elseif ($stepNum < $currentStep): ?>
                                <span class="badge badge-secondary">Pending</span>
                            <?php else: ?>
                                <span class="badge badge-primary">Current Step</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <div class="step-details" id="stepDetails<?= $stepNum ?>">
                        <?php if ($isCompleted): ?>
                            <?php if (!empty($stepData['test_date'])): ?>
                                <div class="detail-item">
                                    <span class="detail-label">Test Date:</span>
                                    <?= date('d M Y', strtotime($stepData['test_date'])) ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($stepData['test_result'])): ?>
                                <div class="detail-item">
                                    <span class="detail-label">Test Result:</span>
                                    <span class="badge <?= $stepData['test_result'] === 'Pass' ? 'badge-success' : 'badge-danger' ?>">
                                        <?= $stepData['test_result'] ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($stepData['video_sent'])): ?>
                                <div class="detail-item">
                                    <span class="detail-label">Video Sent:</span>
                                    <span class="badge <?= $stepData['video_sent'] ? 'badge-success' : 'badge-danger' ?>">
                                        <?= $stepData['video_sent'] ? 'Yes' : 'No' ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Display other step-specific details similarly -->
                            
                            <?php if (!empty($stepData['notes'])): ?>
                                <div class="detail-item">
                                    <span class="detail-label">Notes:</span>
                                    <?= htmlspecialchars($stepData['notes']) ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if ($isCurrent && in_array($_SESSION['role'], ['Admin', 'Staff'])): ?>
                            <div class="form-container mt-3">
                                <form method="POST" action="../processes/progress_update.php">
                                    <input type="hidden" name="student_id" value="<?= $studentId ?>">
                                    <input type="hidden" name="step_number" value="<?= $stepNum ?>">
                                    <input type="hidden" name="current_status" value="<?= $steps[$stepNum] ?>">
                                    
                                    <?php if ($stepNum === 3): ?>
                                        <div class="mb-3">
                                            <label>Test Date</label>
                                            <input type="date" name="test_date" class="form-control" required>
                                        </div>
                                    <?php elseif ($stepNum === 4): ?>
                                        <div class="mb-3">
                                            <label>Test Result</label>
                                            <select name="test_result" class="form-control" required>
                                                <option value="Pass">Pass</option>
                                                <option value="Fail">Fail</option>
                                            </select>
                                        </div>
                                    <?php elseif ($stepNum === 5): ?>
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" name="video_sent" class="form-check-input" id="videoSent">
                                            <label class="form-check-label" for="videoSent">Video Sent</label>
                                        </div>
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" name="documents_sent" class="form-check-input" id="documentsSent">
                                            <label class="form-check-label" for="documentsSent">Documents Sent</label>
                                        </div>
                                        <div class="mb-3">
                                            <label>Document Details</label>
                                            <textarea name="documents_details" class="form-control"></textarea>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="mb-3">
                                        <label>Notes</label>
                                        <textarea name="notes" class="form-control"></textarea>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">Update Progress</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="mt-4">
            <a href="dashboard.php?id=<?= $studentId ?>" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
    
    <script>
        function toggleStepDetails(stepNum) {
            const details = document.getElementById('stepDetails' + stepNum);
            details.style.display = details.style.display === 'none' ? 'block' : 'none';
        }
        
        // Open current step by default
        document.addEventListener('DOMContentLoaded', function() {
            const currentStep = <?= $currentStep ?>;
            console.log("Current step:", currentStep);
            const details = document.getElementById('stepDetails' + currentStep);
            if (details) {
                details.style.display = 'block';
            } else {
                console.error("Step details not found for step", currentStep);
            }
        });
    </script>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>