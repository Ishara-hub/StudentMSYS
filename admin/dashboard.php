<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Admin authentication
checkRole(['Admin']);

// Get statistics
$stats = $conn->query("
    SELECT 
        (SELECT COUNT(*) FROM students) AS total_students,
        (SELECT COUNT(*) FROM students WHERE status = 'Active') AS active_students,
        (SELECT COUNT(*) FROM students WHERE status = 'Completed') AS completed_students,
        (SELECT SUM(amount) FROM payments) AS total_revenue,
        (SELECT SUM(balance) FROM registrations WHERE balance > 0) AS outstanding_balance,
        (SELECT COUNT(*) FROM agents) AS total_agents,
        (SELECT COUNT(*) FROM batches WHERE is_active = 1) AS active_batches,
        (SELECT COUNT(*) FROM courses) AS total_courses
")->fetch_assoc();

// Recent registrations with agent info
$recentRegistrations = $conn->query("
    SELECT s.student_id, s.full_name, s.registration_date, c.course_name, 
           IFNULL(a.agent_name, 'Direct') AS registered_by
    FROM students s
    JOIN registrations r ON s.student_id = r.student_id
    JOIN batches b ON r.batch_id = b.batch_id
    JOIN courses c ON b.course_id = c.course_id
    LEFT JOIN agents a ON s.agent_id = a.agent_id
    ORDER BY s.registration_date DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Recent payments with payment method
$recentPayments = $conn->query("
    SELECT p.amount, p.payment_date, p.payment_method, s.full_name 
    FROM payments p
    JOIN registrations r ON p.registration_id = r.registration_id
    JOIN students s ON r.student_id = s.student_id
    ORDER BY p.payment_date DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Top performing agents
$topAgents = $conn->query("
    SELECT a.agent_name, COUNT(s.student_id) AS student_count
    FROM agents a
    LEFT JOIN students s ON a.agent_id = s.agent_id
    GROUP BY a.agent_id
    ORDER BY student_count DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Course enrollment data for chart
$courseEnrollment = $conn->query("
    SELECT c.course_name, COUNT(r.registration_id) AS enrollment_count
    FROM courses c
    LEFT JOIN batches b ON c.course_id = b.course_id
    LEFT JOIN registrations r ON b.batch_id = r.batch_id
    GROUP BY c.course_id
    ORDER BY enrollment_count DESC
")->fetch_all(MYSQLI_ASSOC);

// Monthly revenue data for chart
$monthlyRevenue = $conn->query("
    SELECT 
        DATE_FORMAT(payment_date, '%Y-%m') AS month,
        SUM(amount) AS revenue
    FROM payments
    WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
    ORDER BY month
")->fetch_all(MYSQLI_ASSOC);


$isMobile = isMobile();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- ApexCharts CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .stat-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 4px solid;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .card-primary { border-left-color: #4e73df; }
        .card-success { border-left-color: #1cc88a; }
        .card-warning { border-left-color: #f6c23e; }
        .card-danger { border-left-color: #e74a3b; }
        .card-info { border-left-color: #36b9cc; }
        .card-secondary { border-left-color: #858796; }
        .recent-activity {
            max-height: 350px;
            overflow-y: auto;
        }
        .table-responsive {
            overflow-x: auto;
        }
        @media (max-width: 768px) {
            .stat-card {
                margin-bottom: 5px;
            }
        }
    </style>
    <?php include '../includes/header.php'; ?>
</head>
<body class="bg-light">
    
    
    <div class="container-fluid">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2"><i class="fas fa-tachometer-alt me-2"></i>Dashboard Overview</h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <div class="btn-group me-2">
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#quickStatsModal">
                        <i class="fas fa-chart-pie me-1"></i> Quick Stats
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                        <i class="fas fa-print me-1"></i> Print
                    </button>
                </div>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown">
                        <i class="fas fa-calendar me-1"></i> 
                        <?= date('F Y') ?>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                        <li><a class="dropdown-item" href="#">Today</a></li>
                        <li><a class="dropdown-item" href="#">This Week</a></li>
                        <li><a class="dropdown-item" href="#">This Month</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#">Custom Range</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                <div class="card stat-card card-primary h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-uppercase text-muted small fw-bold">Total Students</h6>
                                <h3 class="mb-0"><?= number_format($stats['total_students']) ?></h3>
                            </div>
                            <div class="icon-circle bg-primary text-white">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <div class="mt-2 small">
                            <span class="text-success fw-bold">
                                <i class="fas fa-arrow-up"></i> <?= round(($stats['active_students']/$stats['total_students'])*100) ?>%
                            </span>
                            <span class="text-muted">active</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                <div class="card stat-card card-warning h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-uppercase text-muted small fw-bold">Total Revenue</h6>
                                <h3 class="mb-0"><?= DEFAULT_CURRENCY ?><?= number_format($stats['total_revenue'], 2) ?></h3>
                            </div>
                            <div class="icon-circle bg-warning text-white">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                        <div class="mt-2 small">
                            <span class="text-success fw-bold">
                                <i class="fas fa-arrow-up"></i> <?= rand(5, 20) ?>%
                            </span>
                            <span class="text-muted">vs last month</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                <div class="card stat-card card-danger h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-uppercase text-muted small fw-bold">Outstanding</h6>
                                <h3 class="mb-0"><?= DEFAULT_CURRENCY ?><?= number_format($stats['outstanding_balance'], 2) ?></h3>
                            </div>
                            <div class="icon-circle bg-danger text-white">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                        </div>
                        <div class="mt-2 small">
                            <span class="text-danger fw-bold">
                                <?= round(($stats['outstanding_balance']/$stats['total_revenue'])*100) ?>%
                            </span>
                            <span class="text-muted">of revenue</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                <div class="card stat-card card-secondary h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-uppercase text-muted small fw-bold">Active Batches</h6>
                                <h3 class="mb-0"><?= number_format($stats['active_batches']) ?></h3>
                            </div>
                            <div class="icon-circle bg-secondary text-white">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                        </div>
                        <div class="mt-2 small">
                            <span class="text-success fw-bold">
                                <i class="fas fa-book"></i> <?= $stats['total_courses'] ?>
                            </span>
                            <span class="text-muted">courses</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content Row -->
        <div class="row">
            <!-- Charts Column -->
            <div class="col-lg-8 mb-4">
                <!-- Revenue Chart -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Monthly Revenue</h5>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="revenueDropdown" data-bs-toggle="dropdown">
                                    This Year
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="revenueDropdown">
                                    <li><a class="dropdown-item" href="#">This Week</a></li>
                                    <li><a class="dropdown-item" href="#">This Month</a></li>
                                    <li><a class="dropdown-item" href="#">This Year</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="#">Custom Range</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="revenueChart" style="height: 300px;"></div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Course Distribution -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Course Enrollment</h5>
                            </div>
                            <div class="card-body">
                                <div id="courseChart" style="height: 250px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Top Agents -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="fas fa-medal me-2"></i>Top Agents</h5>
                                    <a href="../admin/agents.php" class="btn btn-sm btn-outline-primary">View All</a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <?php foreach ($topAgents as $index => $agent): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                        <div>
                                            <span class="badge bg-primary me-2"><?= $index + 1 ?></span>
                                            <?= htmlspecialchars($agent['agent_name']) ?>
                                        </div>
                                        <span class="badge bg-success rounded-pill"><?= $agent['student_count'] ?> students</span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity Column -->
            <div class="col-lg-4 mb-4">
                <!-- Recent Registrations -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Recent Registrations</h5>
                            <a href="../student/student_list.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                    </div>
                    <div class="card-body recent-activity p-0">
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentRegistrations as $reg): ?>
                            <a href="../student/dashboard.php?id=<?= $reg['student_id'] ?>" class="list-group-item list-group-item-action px-3 py-2">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?= htmlspecialchars($reg['full_name']) ?></h6>
                                    <small><?= date('M d', strtotime($reg['registration_date'])) ?></small>
                                </div>
                                <p class="mb-1"><?= htmlspecialchars($reg['course_name']) ?></p>
                                <small class="text-muted">Registered by: <?= htmlspecialchars($reg['registered_by']) ?></small>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Payments -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Recent Payments</h5>
                            <a href="../admin/payments.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                    </div>
                    <div class="card-body recent-activity p-0">
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentPayments as $payment): ?>
                            <div class="list-group-item px-3 py-2">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?= htmlspecialchars($payment['full_name']) ?></h6>
                                    <span class="text-success fw-bold"><?= DEFAULT_CURRENCY ?><?= number_format($payment['amount'], 2) ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted"><?= date('M d, Y', strtotime($payment['payment_date'])) ?></small>
                                    <small class="text-muted"><?= $payment['payment_method'] ?></small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
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
                            <span class="badge bg-primary rounded-pill"><?= number_format($stats['total_students']) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Active Students
                            <span class="badge bg-success rounded-pill"><?= number_format($stats['active_students']) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Completed Students
                            <span class="badge bg-info rounded-pill"><?= number_format($stats['completed_students']) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Total Revenue
                            <span class="badge bg-warning text-dark rounded-pill"><?= DEFAULT_CURRENCY ?><?= number_format($stats['total_revenue'], 2) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Outstanding Balance
                            <span class="badge bg-danger rounded-pill"><?= DEFAULT_CURRENCY ?><?= number_format($stats['outstanding_balance'], 2) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Active Agents
                            <span class="badge bg-secondary rounded-pill"><?= number_format($stats['total_agents']) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Active Batches
                            <span class="badge bg-dark rounded-pill"><?= number_format($stats['active_batches']) ?></span>
                        </li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="window.print()">
                        <i class="fas fa-print me-1"></i> Print
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
                                
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Revenue Chart
        var revenueOptions = {
            series: [{
                name: 'Revenue',
                data: [
                    <?php 
                    $months = [];
                    $revenues = [];
                    foreach ($monthlyRevenue as $month) {
                        $months[] = "'" . date('M Y', strtotime($month['month'] . '-01')) . "'";
                        $revenues[] = $month['revenue'];
                    }
                    echo implode(', ', $revenues);
                    ?>
                ]
            }],
            chart: {
                height: '100%',
                type: 'area',
                toolbar: {
                    show: true,
                    tools: {
                        download: true,
                        selection: true,
                        zoom: true,
                        zoomin: true,
                        zoomout: true,
                        pan: true,
                        reset: true
                    }
                }
            },
            colors: ['#4e73df'],
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
                    stops: [0, 100]
                }
            },
            xaxis: {
                categories: [<?= implode(', ', $months) ?>],
                labels: {
                    rotate: -45,
                    style: {
                        fontSize: '12px'
                    }
                }
            },
            yaxis: {
                title: {
                    text: '<?= DEFAULT_CURRENCY ?>',
                    style: {
                        fontSize: '12px',
                        fontWeight: 'bold'
                    }
                },
                labels: {
                    formatter: function(value) {
                        return '<?= DEFAULT_CURRENCY ?>' + value.toLocaleString();
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function(value) {
                        return '<?= DEFAULT_CURRENCY ?>' + value.toLocaleString();
                    }
                }
            }
        };
        
        var revenueChart = new ApexCharts(document.querySelector("#revenueChart"), revenueOptions);
        revenueChart.render();
        
        // Course Enrollment Chart
        var courseOptions = {
            series: [
                <?php 
                $courseNames = [];
                $enrollments = [];
                foreach ($courseEnrollment as $course) {
                    $courseNames[] = "'" . $course['course_name'] . "'";
                    $enrollments[] = $course['enrollment_count'];
                }
                echo json_encode($enrollments);
                ?>
            ],
            chart: {
                type: 'donut',
                height: '100%'
            },
            labels: [<?= implode(', ', $courseNames) ?>],
            colors: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'],
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
                position: 'right',
                fontSize: '14px'
            },
            plotOptions: {
                pie: {
                    donut: {
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total Students',
                                color: '#6c757d',
                                formatter: function(w) {
                                    return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                }
                            }
                        }
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function(value) {
                        return value + ' students';
                    }
                }
            }
        };
        
        var courseChart = new ApexCharts(document.querySelector("#courseChart"), courseOptions);
        courseChart.render();
    });
    </script>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>