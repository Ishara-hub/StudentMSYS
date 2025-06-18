<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

checkRole(['CallCenter', 'Admin']);

// Get active courses for dropdown
$courses = $conn->query("SELECT course_id, course_name FROM courses WHERE is_active = 1")->fetch_all(MYSQLI_ASSOC);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $conn->real_escape_string(trim($_POST['name']));
    $phone = $conn->real_escape_string(trim($_POST['phone']));
    $address = $conn->real_escape_string(trim($_POST['address']));
    $courseId = intval($_POST['course_id']);
    $notes = $conn->real_escape_string(trim($_POST['notes'] ?? ''));
    $join_date = $conn->real_escape_string(trim($_POST['join_date'] ?? ''));
    
      // Validate join date
    if (empty($joinDate)) {
        $joinDate = date('Y-m-d'); // Default to today if not provided
    }

    $stmt = $conn->prepare("INSERT INTO call_center_registrations 
                          (student_name, phone_number, interested_course_id, notes, join_date) 
                          VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiss", $name, $phone, $courseId, $notes, $joinDate);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Registration recorded successfully!";
        header("Location: call_center_dashboard.php");
        exit();
    } else {
        $_SESSION['error'] = "Error saving registration: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Call Center Registration</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .required:after {
            content: " *";
            color: red;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container mt-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-phone me-2"></i>New Call Center Registration</h4>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label required">Student Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Phone Number</label>
                            <input type="tel" name="phone" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label required">Interested Course</label>
                            <select name="course_id" class="form-select" required>
                                <option value="">Select Course</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?= $course['course_id'] ?>"><?= htmlspecialchars($course['course_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Address</label>
                            <input type="text" name="address" class="form-control" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Join date</label>
                            <input type="date" name="join_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-1"></i> Save Registration
                        </button>
                        <a href="call_center_dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
</body>
</html>