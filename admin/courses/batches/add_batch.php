<?php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/auth.php';

checkRole(['Admin']);

$courseId = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

// Get course info
$course = [];
if ($courseId > 0) {
    $stmt = $conn->prepare("SELECT * FROM courses WHERE course_id = ?");
    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    $result = $stmt->get_result();
    $course = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $batchName = $conn->real_escape_string($_POST['batch_name']);
    $startDate = $conn->real_escape_string($_POST['start_date']);
    $maxStudents = intval($_POST['max_students']);
    $courseId = intval($_POST['course_id']);
    
    $stmt = $conn->prepare("INSERT INTO batches (course_id, batch_name, start_date, max_students) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $courseId, $batchName, $startDate, $maxStudents);
    
    if ($stmt->execute()) {
        redirect("manage_batches.php?course_id=$courseId", "Batch added successfully!");
    } else {
        $error = "Error adding batch: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add New Batch</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="../../../vendor/twbs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../../assets/css/<?= isMobile() ? 'mobile' : 'style' ?>.css">
</head>
<body>
    <?php include '../../../includes/header.php'; ?>
    
    <div class="container py-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h2 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Add New Batch</h2>
                <?php if ($course): ?>
                <p class="mb-0">For Course: <strong><?= htmlspecialchars($course['course_name']) ?></strong></p>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <form method="POST" class="needs-validation" novalidate> 
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="course_id" class="form-label">Select Course</label>
                            <select name="course_id" class="form-select" required>
                                <option value="">-- Select Course --</option>
                                <?php
                                    $res = $conn->query("SELECT course_id, course_name FROM courses");
                                    while ($row = $res->fetch_assoc()) {
                                        $selected = ($row['course_id'] == $courseId) ? 'selected' : '';
                                        echo "<option value='{$row['course_id']}' $selected>{$row['course_name']}</option>";
                                    }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="batch_name" class="form-label">Batch Name</label>
                            <input type="text" class="form-control" id="batch_name" name="batch_name" required>
                            <div class="invalid-feedback">Please enter a batch name</div>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                            <div class="invalid-feedback">Please select a start date</div>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="max_students" class="form-label">Maximum Students</label>
                            <input type="number" class="form-control" id="max_students" name="max_students" min="1" required>
                            <div class="invalid-feedback">Please enter maximum students</div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="manage_batches.php?course_id=<?= $courseId ?>" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Add Batch
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include '../../../includes/footer.php'; ?>
    
    <!-- Bootstrap Bundle with Popper -->
    <script>
    // Form validation
    (function () {
        'use strict'
        
        const forms = document.querySelectorAll('.needs-validation')
        
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                
                form.classList.add('was-validated')
            }, false)
        })
    })()
    </script