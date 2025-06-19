<?php
$currentPage = basename($_SERVER['PHP_SELF']);
// Detect user's preferred color scheme
$isDarkMode = isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'true';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?= $isDarkMode ? 'dark' : 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> | <?= ucfirst(str_replace('.php', '', $currentPage)) ?></title>

    <!-- Bootstrap CSS -->
    <link href="<?= BASE_URL ?>/vendor/twbs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="./assets/css/custom.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"> 

    <!-- Optional: Add custom styles directly in head -->
    <style>
        /* Theme toggle button */
        .theme-toggle {
            border: none;
            background: transparent;
            padding: 0.25rem 0.5rem;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        /* Improved navbar */
        .navbar {
            font-size: 0.85rem;
            padding: 0.3rem 0.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        [data-bs-theme="dark"] .navbar {
            background-color: #212529 !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .dropdown-menu {
            font-size: 0.8rem;
            min-width: 120px;
        }

        .dropdown-item {
            padding: 0.25rem 0.75rem;
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.25em 0.5em;
        }
        
        /* Improved toggle button */
        .navbar-toggler {
            height: 2rem;
            width: 2rem;
            margin: 0.25rem;
            padding: 0.25rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        [data-bs-theme="dark"] .navbar-toggler {
            border-color: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.8);
        }

        .navbar-toggler:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }
        
        [data-bs-theme="dark"] .navbar-toggler:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .btn-sm,
        .btn-group-sm > .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            border-radius: 0.2rem;
        }

        /* Sidebar animation */
        .sidebar {
            transition: all 0.3s ease-in-out;
        }

        main.col-md-9.col-lg-10 {
            transition: margin-left 0.3s ease-in-out;
        }
        
        footer a:hover {
            color: #f8f9fa !important;
        }

        .footer h5 {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Top Header -->
    <header class="navbar navbar-expand-lg sticky-top bg-light navbar-light flex-md-nowrap p-0 shadow">
        <div class="container-fluid px-3">
            <div class="d-flex align-items-center">
                <button id="sidebarToggle" class="navbar-toggler me-2" type="button" data-bs-toggle="collapse"
                        data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false"
                        aria-label="Toggle navigation">
                    <i class="fas fa-bars"></i>
                </button>
                
                <!-- Logo - Replace with your actual logo path -->
                <a class="navbar-brand me-3" href="<?= BASE_URL ?>/admin/dashboard.php">
                    <img src="<?= BASE_URL ?>/assets/images/logo.jpg" alt="Company Logo" height="40" width="200" class="d-inline-block align-top">
                    <!-- Optional text beside logo -->
                    <span class="ms-2 d-none d-lg-inline"><?= SITE_NAME ?></span>
                </a>
            </div>
            
            <div class="d-flex align-items-center ms-auto">
                <!-- Theme Toggle Button -->
                <button class="theme-toggle me-2" id="themeToggle">
                    <i class="fas <?= $isDarkMode ? 'fa-sun' : 'fa-moon' ?>"></i>
                </button>
                
                <div class="collapse navbar-collapse justify-content-end" id="navbarSupportedContent">
                    <ul class="navbar-nav align-items-center">
                        <li class="nav-item dropdown">
                            <a class="dropdown-toggle text-light" href="#" id="userDropdown" role="button"
                               data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1"></i> <?= $_SESSION['username'] ?>
                                <span class="badge bg-<?= ($_SESSION['role'] === 'Admin') ? 'danger' : (($_SESSION['role'] === 'Staff') ? 'info' : 'secondary') ?> ms-1">
                                    <?= $_SESSION['role'] ?>
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><h6 class="dropdown-header">Signed in as <?= $_SESSION['username'] ?></h6></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-user me-1"></i> Profile</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-1"></i> Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar Navigation -->
            <nav id="sidebarMenu" class="col-md-4 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>"
                               href="<?= BASE_URL ?>/admin/dashboard.php">
                                <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                            </a>
                        </li>
                        <?php if ($_SESSION['role'] === 'Admin'): ?>
                        <!-- Students Dropdown -->
                        <li class="nav-item">
                            <a class="nav-link <?= in_array($currentPage, ['students_list.php', 'register.php', 'progress.php', 'make_payment.php', 'history.php']) ? 'active' : '' ?>"
                               data-bs-toggle="collapse" href="#studentsCollapse" role="button">
                                <i class="fas fa-users me-1"></i> Students
                                <i class="fas fa-angle-down float-end mt-1"></i>
                            </a>
                            <div class="collapse <?= in_array($currentPage, ['students_list.php', 'register.php', 'progress.php', 'make_payment.php', 'history.php']) ? 'show' : '' ?>" id="studentsCollapse">
                                <ul class="nav flex-column ms-3">
                                    <li class="nav-item">
                                        <a class="nav-link <?= $currentPage === 'students_list.php' ? 'active' : '' ?>"
                                           href="<?= BASE_URL ?>/student/student_list.php">
                                            <i class="fas fa-list me-1"></i> All Students
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $currentPage === 'register.php' ? 'active' : '' ?>"
                                           href="<?= BASE_URL ?>/student/register.php">
                                            <i class="fas fa-plus-circle me-1"></i> Add New
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $currentPage === 'progress.php' ? 'active' : '' ?>"
                                           href="<?= BASE_URL ?>/student/progress.php">
                                            <i class="fas fa-chart-line me-1"></i> Progress
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $currentPage === 'make_payment.php' ? 'active' : '' ?>"
                                           href="<?= BASE_URL ?>/student/payments/make_payment.php">
                                            <i class="fas fa-money-bill-wave me-1"></i> Payments
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $currentPage === 'history.php' ? 'active' : '' ?>"
                                           href="<?= BASE_URL ?>/student/payments/history.php">
                                            <i class="fas fa-history me-1"></i> Payment History
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>

                        <!-- Users Dropdown -->
                        <li class="nav-item">
                            <a class="nav-link <?= in_array($currentPage, ['manage_users.php', 'create.php', 'edit.php']) ? 'active' : '' ?>"
                               data-bs-toggle="collapse" href="#usersCollapse" role="button">
                                <i class="fas fa-user-cog me-1"></i> Users
                                <i class="fas fa-angle-down float-end mt-1"></i>
                            </a>
                            <div class="collapse <?= in_array($currentPage, ['manage_users.php', 'create.php', 'edit.php']) ? 'show' : '' ?>" id="usersCollapse">
                                <ul class="nav flex-column ms-3">
                                    <li class="nav-item">
                                        <a class="nav-link <?= $currentPage === 'manage_users.php' ? 'active' : '' ?>"
                                           href="<?= BASE_URL ?>/admin/users/manage_users.php">
                                            <i class="fas fa-list me-1"></i> All Users
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $currentPage === 'create.php' ? 'active' : '' ?>"
                                           href="<?= BASE_URL ?>/admin/users/create.php">
                                            <i class="fas fa-user-plus me-1"></i> Add New
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>

                        <!-- Call Center Dropdown -->
                        <li class="nav-item">
                            <a class="nav-link <?= in_array($currentPage, ['call_center_register.php', 'call_center_dashboard.php', 'call_center_confirm.php']) ? 'active' : '' ?>"
                               data-bs-toggle="collapse" href="#callCenterCollapse" role="button">
                                <i class="fas fa-phone me-1"></i> Call Center
                                <i class="fas fa-angle-down float-end mt-1"></i>
                            </a>
                            <div class="collapse <?= in_array($currentPage, ['call_center_register.php', 'call_center_dashboard.php', 'call_center_confirm.php']) ? 'show' : '' ?>" id="callCenterCollapse">
                                <ul class="nav flex-column ms-3">
                                    <li class="nav-item">
                                        <a class="nav-link <?= $currentPage === 'call_center_register.php' ? 'active' : '' ?>"
                                           href="<?= BASE_URL ?>/call/call_center_register.php">
                                            <i class="fas fa-clipboard-list me-1"></i> Register
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $currentPage === 'call_center_dashboard.php' ? 'active' : '' ?>"
                                           href="<?= BASE_URL ?>/call/call_center_dashboard.php">
                                            <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>

                        <!-- Courses Dropdown -->
                        <li class="nav-item">
                            <a class="nav-link <?= in_array($currentPage, ['add_course.php', 'manage_courses.php', 'add_batch.php', 'manage_batches.php']) ? 'active' : '' ?>"
                               data-bs-toggle="collapse" href="#coursesCollapse" role="button">
                                <i class="fas fa-book me-1"></i> Courses
                                <i class="fas fa-angle-down float-end mt-1"></i>
                            </a>
                            <div class="collapse <?= in_array($currentPage, ['add_course.php', 'manage_courses.php', 'add_batch.php', 'manage_batches.php']) ? 'show' : '' ?>" id="coursesCollapse">
                                <ul class="nav flex-column ms-3">
                                    <li class="nav-item">
                                        <a class="nav-link <?= $currentPage === 'add_course.php' ? 'active' : '' ?>"
                                           href="<?= BASE_URL ?>/admin/courses/add_course.php">
                                            <i class="fas fa-plus-circle me-1"></i> Add Course
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $currentPage === 'manage_courses.php' ? 'active' : '' ?>"
                                           href="<?= BASE_URL ?>/admin/courses/manage_courses.php">
                                            <i class="fas fa-list me-1"></i> Manage Courses
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $currentPage === 'add_batch.php' ? 'active' : '' ?>"
                                           href="<?= BASE_URL ?>/admin/courses/batches/add_batch.php">
                                            <i class="fas fa-plus-circle me-1"></i> Add Batch
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $currentPage === 'manage_batches.php' ? 'active' : '' ?>"
                                           href="<?= BASE_URL ?>/admin/courses/batches/manage_batches.php">
                                            <i class="fas fa-list me-1"></i> Manage Batches
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        <!-- accounts Dropdown -->
                         <li class="nav-item">
                            <a class="nav-link <?= in_array($currentPage, ['chart_of_accounts_data.php', 'chart_of_accounts.php', 'balance_sheet_pdf.php', 'balance_sheet.php', 'general_ledger.php', 'income_statement.php', 'journal_entry.php', 'trial_balance.php', 'account_payment.php', 'add_branch.php']) ? 'active' : '' ?>"
                               data-bs-toggle="collapse" href="#accountsCollapse" role="button">
                                <i class="fas fa-book me-1"></i> Accounts
                                <i class="fas fa-angle-down float-end mt-1"></i>
                            </a>
                            <div class="collapse <?= in_array($currentPage, ['chart_of_accounts_data.php', 'chart_of_accounts.php', 'balance_sheet_pdf.php', 'balance_sheet.php', 'general_ledger.php', 'income_statement.php', 'journal_entry.php', 'trial_balance.php', 'account_payment.php', 'add_branch.php']) ? 'show' : '' ?>" id="accountsCollapse">
                                <ul class="nav flex-column ms-1">
                                    <li class="nav-item">
                                        <a class="nav-link <?= $currentPage === 'add_branch.php' ? 'active' : '' ?>"
                                           href="<?= BASE_URL ?>/accounts/add_branch.php">
                                            <i class="fas fa-plus-circle me-1"></i> Add Branch
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $currentPage === 'chart_of_accounts.php' ? 'active' : '' ?>"
                                           href="<?= BASE_URL ?>/accounts/chart_of_accounts.php">
                                            <i class="fas fa-list me-1"></i> Chart of Accounts
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $currentPage === 'chart_of_accounts.php' ? 'active' : '' ?>"
                                           href="<?= BASE_URL ?>/accounts/chart_of_accounts.php">
                                            <i class="fas fa-list me-1"></i> Sub Accounts
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $currentPage === 'account_payment.php' ? 'active' : '' ?>"
                                           href="<?= BASE_URL ?>/accounts/account_payment.php">
                                            <i class="fas fa-money-bill-wave me-1"></i> Account Payment
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $currentPage === 'journal_entry.php' ? 'active' : '' ?>"
                                           href="<?= BASE_URL ?>/accounts/journal_entry.php">
                                            <i class="fas fa-journal-whills me-1"></i> Journal Entry
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $currentPage === 'chart_of_accounts_data.php' ? 'active' : '' ?>"
                                           href="<?= BASE_URL ?>/accounts/chart_of_accounts_data.php">
                                            <i class="fas fa-database me-1"></i> Chart of Accounts
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $currentPage === 'balance_sheet.php' ? 'active' : '' ?>"
                                           href="<?= BASE_URL ?>/accounts/balance_sheet.php">
                                            <i class="fas fa-balance-scale me-1"></i> Balance Sheet
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $currentPage === 'income_statement.php' ? 'active' : '' ?>"
                                           href="<?= BASE_URL ?>/accounts/income_statement.php">
                                            <i class="fas fa-file-invoice-dollar me-1"></i> Income Statement
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $currentPage === 'trial_balance.php' ? 'active' : '' ?>"
                                           href="<?= BASE_URL ?>/accounts/trial_balance.php">
                                            <i class="fas fa-balance-scale-left me-1"></i> Trial Balance
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= in_array($currentPage, ['financial.php', 'progress_report.php', 'student_list_report.php']) ? 'active' : '' ?>"
                               data-bs-toggle="collapse" href="#reportsCollapse" role="button">
                                <i class="fas fa-file-alt me-1"></i> Reports
                                <i class="fas fa-angle-down float-end mt-1"></i>
                            </a>
                            <div class="collapse <?= in_array($currentPage, ['financial.php', 'progress_report.php', 'student_list_report.php']) ? 'show' : '' ?>" id="reportsCollapse">
                                <ul class="nav flex-column ms-3">
                                    <li class="nav-item">
                                        <a class="nav-link <?= $currentPage === 'financial.php' ? 'active' : '' ?>"
                                           href="<?= BASE_URL ?>/admin/reports/financial.php">                                           
                                            <i class="fas fa-file-invoice-dollar me-1"></i> Financial Report                                      
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $currentPage === 'progress_report.php' ? 'active' : '' ?>"
                                           href="<?= BASE_URL ?>/admin/reports/progress_report.php">
                                            <i class="fas fa-chart-bar me-1"></i> Progress Report
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $currentPage === 'student_list_report.php' ? 'active' : '' ?>"
                                           href="<?= BASE_URL ?>/admin/reports/student_list_report.php">
                                            <i class="fas fa-users me-1"></i> Student List Report
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Flash Messages -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center mt-3">
                        <i class="fas fa-check-circle me-2"></i>
                        <div><?= $_SESSION['success'] ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mt-3">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <div><?= $_SESSION['error'] ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

<script>
// Theme toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('themeToggle');
    const html = document.documentElement;
    
    // Check for saved theme preference
    const currentTheme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    
    // Apply the saved theme
    if (currentTheme === 'dark') {
        html.setAttribute('data-bs-theme', 'dark');
        themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
        document.cookie = "darkMode=true; path=/; max-age=31536000"; // 1 year
    } else {
        html.setAttribute('data-bs-theme', 'light');
        themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
        document.cookie = "darkMode=false; path=/; max-age=31536000"; // 1 year
    }
    
    // Toggle theme on button click
    themeToggle.addEventListener('click', function() {
        if (html.getAttribute('data-bs-theme') === 'dark') {
            html.setAttribute('data-bs-theme', 'light');
            themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
            localStorage.setItem('theme', 'light');
            document.cookie = "darkMode=false; path=/; max-age=31536000";
        } else {
            html.setAttribute('data-bs-theme', 'dark');
            themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
            localStorage.setItem('theme', 'dark');
            document.cookie = "darkMode=true; path=/; max-age=31536000";
        }
    });
    
    // Improved toggle button visibility
    const sidebarToggle = document.getElementById('sidebarToggle');
    sidebarToggle.addEventListener('mouseenter', function() {
        this.style.backgroundColor = 'rgba(0, 0, 0, 0.1)';
    });
    sidebarToggle.addEventListener('mouseleave', function() {
        this.style.backgroundColor = '';
    });
});
</script>