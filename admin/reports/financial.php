<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

checkRole(['Admin']);

// Secure date filters with validation
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

$startDate = isset($_GET['start_date']) && validateDate($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) && validateDate($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$endDateWithTime = $endDate . ' 23:59:59'; // Create this variable separately

// Get financial summary with prepared statement
$summary = [];
$stmt = $conn->prepare("SELECT 
                      SUM(amount) as total_payments,
                      COUNT(DISTINCT registration_id) as payment_count,
                      AVG(amount) as avg_payment
                      FROM payments 
                      WHERE payment_date BETWEEN ? AND ?");
$stmt->bind_param("ss", $startDate, $endDateWithTime);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get payments by method with prepared statement
$methods = [];
$stmt = $conn->prepare("SELECT 
                       payment_method, 
                       SUM(amount) as total 
                       FROM payments 
                       WHERE payment_date BETWEEN ? AND ?
                       GROUP BY payment_method");
$stmt->bind_param("ss", $startDate, $endDateWithTime);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $methods[] = $row;
}
$stmt->close();

// Get outstanding balances
$outstanding = $conn->query("SELECT 
                            SUM(balance) as total_outstanding,
                            COUNT(*) as student_count
                            FROM registrations 
                            WHERE balance > 0")->fetch_assoc();

// Get daily payment trends for chart
$dailyTrends = [];
$stmt = $conn->prepare("SELECT 
                        DATE(payment_date) as day,
                        SUM(amount) as daily_total
                        FROM payments
                        WHERE payment_date BETWEEN ? AND ?
                        GROUP BY DATE(payment_date)
                        ORDER BY day");
$stmt->bind_param("ss", $startDate, $endDateWithTime);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $dailyTrends[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Financial Reports Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 20px;
        }
        .filter-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .stat-card {
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card h3 {
            font-size: 1.1rem;
            color: #6c757d;
        }
        .stat-card p {
            font-size: 1.8rem;
            font-weight: bold;
            margin: 10px 0;
        }
        .stat-card small {
            color: #6c757d;
        }
        .chart-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .report-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .table-responsive {
            overflow-x: auto;
        }
        .total-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .bg-light-primary {
            background-color: #e3f2fd;
        }
        .bg-light-info {
            background-color: #e1f5fe;
        }
        .bg-light-warning {
            background-color: #fff8e1;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                font-size: 12px;
            }
            .stat-card {
                box-shadow: none;
                border: 1px solid #dee2e6;
            }
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="dashboard-container">
        <h2 class="mb-4">Financial Reports Dashboard</h2>
        
        <div class="filter-card no-print">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                </div>
                <div class="col-md-3 d-flex align-items-end justify-content-end">
                    <button type="button" class="btn btn-success" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                </div>
            </form>
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <div class="stat-card bg-light-primary">
                    <h3>Total Payments</h3>
                    <p><?= DEFAULT_CURRENCY ?><?= number_format($summary['total_payments'] ?? 0, 2) ?></p>
                    <small><?= $summary['payment_count'] ?? 0 ?> transactions</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card bg-light-info">
                    <h3>Average Payment</h3>
                    <p><?= DEFAULT_CURRENCY ?><?= number_format($summary['avg_payment'] ?? 0, 2) ?></p>
                    <small>Per transaction</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card bg-light-warning">
                    <h3>Outstanding Balances</h3>
                    <p><?= DEFAULT_CURRENCY ?><?= number_format($outstanding['total_outstanding'] ?? 0, 2) ?></p>
                    <small><?= $outstanding['student_count'] ?? 0 ?> students</small>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <div class="chart-container">
                    <h4 class="mb-4">Daily Payment Trends</h4>
                    <canvas id="dailyTrendsChart" height="300"></canvas>
                </div>
            </div>
            <div class="col-md-4">
                <div class="chart-container">
                    <h4 class="mb-4">Payment Methods</h4>
                    <canvas id="methodsChart" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <div class="report-section">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>Payment Methods Breakdown</h4>
                <span class="text-muted"><?= htmlspecialchars($startDate) ?> to <?= htmlspecialchars($endDate) ?></span>
            </div>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Payment Method</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($methods as $method): ?>
                        <tr>
                            <td><?= htmlspecialchars($method['payment_method']) ?></td>
                            <td class="text-end"><?= DEFAULT_CURRENCY ?><?= number_format($method['total'], 2) ?></td>
                            <td class="text-end"><?= $summary['total_payments'] > 0 ? number_format(($method['total'] / $summary['total_payments']) * 100, 1) : 0 ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td>Total</td>
                            <td class="text-end"><?= DEFAULT_CURRENCY ?><?= number_format($summary['total_payments'] ?? 0, 2) ?></td>
                            <td class="text-end">100%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="report-section">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>Outstanding Balances</h4>
                <a href="outstanding_balances.php" class="btn btn-primary">
                    <i class="fas fa-file-alt"></i> View Detailed Report
                </a>
            </div>
            <div class="alert alert-info">
                Total outstanding balance: <strong><?= DEFAULT_CURRENCY ?><?= number_format($outstanding['total_outstanding'] ?? 0, 2) ?></strong>
                across <strong><?= $outstanding['student_count'] ?? 0 ?></strong> students.
            </div>
        </div>
    </div>
    
    <script>
    // Daily Trends Chart
    const dailyCtx = document.getElementById('dailyTrendsChart').getContext('2d');
    new Chart(dailyCtx, {
        type: 'line',
        data: {
            labels: [<?= implode(',', array_map(function($d) { return "'" . $d['day'] . "'"; }, $dailyTrends)) ?>],
            datasets: [{
                label: 'Daily Payments',
                data: [<?= implode(',', array_column($dailyTrends, 'daily_total')) ?>],
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '<?= DEFAULT_CURRENCY ?>' + context.raw.toFixed(2);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '<?= DEFAULT_CURRENCY ?>' + value.toFixed(2);
                        }
                    }
                }
            }
        }
    });

    // Payment Methods Chart
    const methodCtx = document.getElementById('methodsChart').getContext('2d');
    new Chart(methodCtx, {
        type: 'doughnut',
        data: {
            labels: [<?= implode(',', array_map(function($m) { return "'" . $m['payment_method'] . "'"; }, $methods)) ?>],
            datasets: [{
                data: [<?= implode(',', array_column($methods, 'total')) ?>],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const value = context.raw;
                            const percentage = Math.round((value / total) * 100);
                            return `${context.label}: <?= DEFAULT_CURRENCY ?>${value.toFixed(2)} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
    </script>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>