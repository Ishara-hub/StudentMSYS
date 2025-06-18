<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // Get registration data for the last 6 months
    $query = "SELECT 
                DATE_FORMAT(registration_date, '%Y-%m') AS month,
                COUNT(*) AS count
              FROM students
              WHERE registration_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
              GROUP BY DATE_FORMAT(registration_date, '%Y-%m')
              ORDER BY month ASC";
    
    $result = $conn->query($query);
    $data = [
        'labels' => [],
        'values' => []
    ];

    while ($row = $result->fetch_assoc()) {
        $data['labels'][] = date('M Y', strtotime($row['month']));
        $data['values'][] = (int)$row['count'];
    }

    // Fill in missing months with zero values
    $completeData = fillMissingMonths($data);
    
    echo json_encode($completeData);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function fillMissingMonths($data) {
    $months = [];
    $current = new DateTime();
    $current->modify('-5 months'); // Get last 6 months including current
    
    for ($i = 0; $i < 6; $i++) {
        $monthKey = $current->format('Y-m');
        $monthLabel = $current->format('M Y');
        
        $months[$monthKey] = [
            'label' => $monthLabel,
            'value' => 0
        ];
        
        $current->modify('+1 month');
    }
    
    // Merge with actual data
    foreach ($data['labels'] as $index => $label) {
        $monthKey = date('Y-m', strtotime($label));
        if (isset($months[$monthKey])) {
            $months[$monthKey]['value'] = $data['values'][$index];
        }
    }
    
    // Prepare final output
    $result = [
        'labels' => array_column($months, 'label'),
        'values' => array_column($months, 'value')
    ];
    
    return $result;
}