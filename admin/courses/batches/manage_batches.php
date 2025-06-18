<?php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/auth.php';

checkRole(['Admin']);

// Get all active courses for filter dropdown
$courses = $conn->query("SELECT course_id, course_name FROM courses WHERE is_active = 1 ORDER BY course_name")->fetch_all(MYSQLI_ASSOC);

// Get filter parameters
$courseId = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build base query
$query = "SELECT b.*, c.course_name, 
          (SELECT COUNT(*) FROM students WHERE batch_id = b.batch_id) as student_count
          FROM batches b
          LEFT JOIN courses c ON b.course_id = c.course_id
          WHERE 1=1";

$params = [];
$types = '';

// Apply course filter
if ($courseId > 0) {
    $query .= " AND b.course_id = ?";
    $params[] = $courseId;
    $types .= 'i';
}

// Apply status filter
if ($statusFilter === 'active') {
    $query .= " AND b.is_active = 1";
} elseif ($statusFilter === 'inactive') {
    $query .= " AND b.is_active = 0";
}

$query .= " ORDER BY b.start_date DESC";

// Prepare and execute query
$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$batches = $result->fetch_all(MYSQLI_ASSOC);

// Handle batch status toggle
if (isset($_GET['toggle_status'])) {
    $batchId = intval($_GET['id']);
    $conn->query("UPDATE batches SET is_active = NOT is_active WHERE batch_id = $batchId");
    redirect("manage_batches.php?" . http_build_query($_GET));
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Loan Batches</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .status-badge {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 700;
            border-radius: 0.25rem;
        }
        .status-badge.active {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .status-badge.inactive {
            background-color: #f8d7da;
            color: #842029;
        }
        .filter-card {
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <?php include '../../../includes/header.php'; ?>
    
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-users me-2"></i>Manage Loan Batches</h2>
            <a href="add_batch.php" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Add New Batch
            </a>
        </div>
        
        <!-- Filter Card -->
        <div class="card shadow filter-card">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="course_id" class="form-label">Filter by Course</label>
                        <select name="course_id" id="course_id" class="form-select">
                            <option value="0">All Courses</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= $course['course_id'] ?>" <?= $courseId == $course['course_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($course['course_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="status" class="form-label">Filter by Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                            <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active Only</option>
                            <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive Only</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-filter me-1"></i> Apply Filters
                        </button>
                        <a href="manage_batches.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i> Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Batches Table -->
        <div class="card shadow">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Batch Name</th>
                                <th>Course</th>
                                <th>Start Date</th>
                                <th>Students</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($batches)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">No batches found matching your criteria</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($batches as $batch): ?>
                                <tr>
                                    <td><?= htmlspecialchars($batch['batch_name']) ?></td>
                                    <td><?= htmlspecialchars($batch['course_name'] ?? 'N/A') ?></td>
                                    <td><?= date('d M Y', strtotime($batch['start_date'])) ?></td>
                                    <td><?= $batch['student_count'] ?> / <?= $batch['max_students'] ?></td>
                                    <td>
                                        <span class="status-badge <?= $batch['is_active'] ? 'active' : 'inactive' ?>">
                                            <?= $batch['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="edit_batch.php?id=<?= $batch['batch_id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?toggle_status=1&id=<?= $batch['batch_id'] ?>&course_id=<?= $courseId ?>&status=<?= $statusFilter ?>" 
                                               class="btn btn-sm <?= $batch['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?>" 
                                               title="<?= $batch['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                                <?= $batch['is_active'] ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>' ?>
                                            </a>
                                            <a href="../../students/list.php?batch_id=<?= $batch['batch_id'] ?>" class="btn btn-sm btn-outline-info" title="View Students">
                                                <i class="fas fa-user-graduate"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../../../includes/footer.php'; ?>
    
</body>
</html>