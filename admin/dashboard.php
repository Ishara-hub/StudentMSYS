<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Admin authentication
checkRole(['Admin']);

// Get statistics
$studentsCount = $conn->query("SELECT COUNT(*) FROM students")->fetch_row()[0];
$activeStudents = $conn->query("SELECT COUNT(*) FROM students WHERE status = 'Active'")->fetch_row()[0];
$completedStudents = $conn->query("SELECT COUNT(*) FROM students WHERE status = 'Completed'")->fetch_row()[0];
$totalRevenue = $conn->query("SELECT SUM(amount) FROM payments")->fetch_row()[0];
$outstandingBalance = $conn->query("SELECT SUM(balance) FROM registrations WHERE balance > 0")->fetch_row()[0];

// Recent registrations
$recentRegistrations = [];
$regQuery = $conn->query("SELECT s.student_id, s.full_name, s.registration_date, c.course_name 
                         FROM students s
                         JOIN registrations r ON s.student_id = r.student_id
                         JOIN batches b ON r.batch_id = b.batch_id
                         JOIN courses c ON b.course_id = c.course_id
                         ORDER BY s.registration_date DESC LIMIT 5");
while ($row = $regQuery->fetch_assoc()) {
    $recentRegistrations[] = $row;
}

// Recent payments
$recentPayments = [];
$payQuery = $conn->query("SELECT p.amount, p.payment_date, s.full_name 
                         FROM payments p
                         JOIN registrations r ON p.registration_id = r.registration_id
                         JOIN students s ON r.student_id = s.student_id
                         ORDER BY p.payment_date DESC LIMIT 5");
while ($row = $payQuery->fetch_assoc()) {
    $recentPayments[] = $row;
}

$isMobile = isMobile();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="../vendor/twbs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="./assets/css/<?= $isMobile ? 'mobile' : 'style' ?>.css">
    <link rel="stylesheet" href="./assets/css/custom.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <main class="col-md-12 ms-sm-auto col-lg-12 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-tachometer-alt me-2"></i>Dashboard Overview</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#quickStatsModal">
                                <i class="fas fa-chart-pie me-1"></i> Quick Stats
                            </button>
                            <button class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-download me-1"></i> Export
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                        <div class="card border-start border-primary border-4 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-uppercase text-muted small fw-bold">Total Students</h6>
                                        <h3 class="mb-0"><?= $studentsCount ?></h3>
                                    </div>
                                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-users text-primary fa-lg"></i>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <span class="text-success fw-bold"><?= round(($activeStudents/$studentsCount)*100) ?>%</span>
                                    <span class="text-muted small">active</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                        <div class="card border-start border-success border-4 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-uppercase text-muted small fw-bold">Active Students</h6>
                                        <h3 class="mb-0"><?= $activeStudents ?></h3>
                                    </div>
                                    <div class="bg-success bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-user-check text-success fa-lg"></i>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <span class="text-success fw-bold">+<?= rand(5, 15) ?></span>
                                    <span class="text-muted small">this week</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                        <div class="card border-start border-warning border-4 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-uppercase text-muted small fw-bold">Total Revenue</h6>
                                        <h3 class="mb-0"><?= DEFAULT_CURRENCY ?><?= number_format($totalRevenue, 2) ?></h3>
                                    </div>
                                    <div class="bg-warning bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-money-bill-wave text-warning fa-lg"></i>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <span class="text-success fw-bold">+<?= rand(5, 20) ?>%</span>
                                    <span class="text-muted small">vs last month</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                        <div class="card border-start border-danger border-4 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-uppercase text-muted small fw-bold">Outstanding</h6>
                                        <h3 class="mb-0"><?= DEFAULT_CURRENCY ?><?= number_format($outstandingBalance, 2) ?></h3>
                                    </div>
                                    <div class="bg-danger bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-exclamation-triangle text-danger fa-lg"></i>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <span class="text-danger fw-bold"><?= round(($outstandingBalance/$totalRevenue)*100) ?>%</span>
                                    <span class="text-muted small">of total revenue</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-white border-bottom-0">
                                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Monthly Performance</h5>
                            </div>
                            <div class="card-body pt-0">
                                <div id="performanceChart" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-white border-bottom-0">
                                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Course Distribution</h5>
                            </div>
                            <div class="card-body pt-0">
                                <div id="courseDistributionChart" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-white border-bottom-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Recent Registrations</h5>
                                    <a href="../admin/students.php" class="btn btn-sm btn-outline-primary">View All</a>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Student</th>
                                                <th>Course</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentRegistrations as $registration): ?>
                                            <tr>
                                                <td>
                                                    <a href="../student/dashboard.php?id=<?= $registration['student_id'] ?>" class="text-decoration-none">
                                                        <?= htmlspecialchars($registration['full_name']) ?>
                                                    </a>
                                                </td>
                                                <td><?= htmlspecialchars($registration['course_name']) ?></td>
                                                <td><?= date('d/m/Y', strtotime($registration['registration_date'])) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-white border-bottom-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Recent Payments</h5>
                                    <a href="../admin/payments.php" class="btn btn-sm btn-outline-primary">View All</a>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Student</th>
                                                <th>Amount</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentPayments as $payment): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($payment['full_name']) ?></td>
                                                <td class="fw-bold"><?= DEFAULT_CURRENCY ?><?= number_format($payment['amount'], 2) ?></td>
                                                <td><?= date('d/m/Y', strtotime($payment['payment_date'])) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Quick Stats Modal -->
    <div class="modal fade" id="quickStatsModal" tabindex="-1" aria-labelledby="quickStatsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="quickStatsModalLabel">System Statistics</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Total Students
                            <span class="badge bg-primary rounded-pill"><?= $studentsCount ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Active Students
                            <span class="badge bg-success rounded-pill"><?= $activeStudents ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Completed Students
                            <span class="badge bg-info rounded-pill"><?= $completedStudents ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Total Revenue
                            <span class="badge bg-warning text-dark rounded-pill"><?= DEFAULT_CURRENCY ?><?= number_format($totalRevenue, 2) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Outstanding Balance
                            <span class="badge bg-danger rounded-pill"><?= DEFAULT_CURRENCY ?><?= number_format($outstandingBalance, 2) ?></span>
                        </li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <?php include '../includes/footer.php'; ?>
    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Performance Chart (ApexCharts)
        var performanceOptions = {
            series: [{
                name: 'Registrations',
                data: [31, 40, 28, 51, 42, 82, 56, 71, 60, 89, 120, 95]
            }, {
                name: 'Payments',
                data: [11, 32, 45, 32, 34, 52, 41, 45, 62, 72, 92, 85]
            }],
            chart: {
                height: '100%',
                type: 'area',
                toolbar: {
                    show: true
                }
            },
            colors: ['#0d6efd', '#198754'],
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 2
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.7,
                    opacityTo: 0.3,
                }
            },
            xaxis: {
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
            },
            tooltip: {
                shared: true,
                intersect: false
            }
        };
        
        var performanceChart = new ApexCharts(document.querySelector("#performanceChart"), performanceOptions);
        performanceChart.render();
        
        // Course Distribution Chart
        var courseDistributionOptions = {
            series: [44, 55, 41, 17, 15],
            chart: {
                type: 'donut',
                height: '100%'
            },
            labels: ['Hotel', 'Construction', 'House keeping', 'Care give', 'Barbender'],
            colors: ['#0d6efd', '#198754', '#fd7e14', '#6f42c1', '#20c997'],
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        width: 200
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }],
            legend: {
                position: 'right'
            },
            plotOptions: {
                pie: {
                    donut: {
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total Students',
                                color: '#6c757d'
                            }
                        }
                    }
                }
            }
        };
        
        var courseDistributionChart = new ApexCharts(document.querySelector("#courseDistributionChart"), courseDistributionOptions);
        courseDistributionChart.render();
    });
    </script>
    
    
</body>
</html>