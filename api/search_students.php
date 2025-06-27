<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

checkRole(['Admin']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $searchTerm = '%' . $_POST['search'] . '%';
    $searchType = $_POST['type'] ?? 'name';
    
    $query = "
        SELECT s.student_id, s.full_name, s.nic, s.status, c.course_name
        FROM students s
        LEFT JOIN registrations r ON s.student_id = r.student_id
        LEFT JOIN batches b ON r.batch_id = b.batch_id
        LEFT JOIN courses c ON b.course_id = c.course_id
    ";
    
    switch ($searchType) {
        case 'id':
            $query .= " WHERE s.student_id LIKE ?";
            break;
        case 'nic':
            $query .= " WHERE s.nic LIKE ?";
            break;
        case 'name':
        default:
            $query .= " WHERE s.full_name LIKE ?";
            break;
    }
    
    $query .= " GROUP BY s.student_id LIMIT 20";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $students = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode($students);
    exit;
}

echo json_encode([]);