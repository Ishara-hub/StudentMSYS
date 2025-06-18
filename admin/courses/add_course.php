<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

checkRole(['Admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $fee = floatval($_POST['fee']);
    $duration = intval($_POST['duration']);
    $description = $conn->real_escape_string($_POST['description']);
    
    $stmt = $conn->prepare("INSERT INTO courses (course_name, total_fee, duration_weeks, description) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sdis", $name, $fee, $duration, $description);
    
    if ($stmt->execute()) {
        redirect("manage_courses.php", "Course added successfully!");
    } else {
        $error = "Error adding course: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add New Course</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="../../vendor/twbs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/<?= isMobile() ? 'mobile' : 'style' ?>.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container py-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h2 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Add New Course</h2>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Course Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="invalid-feedback">Please enter a course name</div>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="fee" class="form-label">Total Fee (<?= DEFAULT_CURRENCY ?>)</label>
                            <input type="number" class="form-control" id="fee" name="fee" step="0.01" min="0" required>
                            <div class="invalid-feedback">Please enter a valid fee</div>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="duration" class="form-label">Duration (Days)</label>
                            <input type="number" class="form-control" id="duration" name="duration" min="1" required>
                            <div class="invalid-feedback">Please enter duration in Days</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="manage_courses.php" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Add Course
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    
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
    </script>
</body>
</html>