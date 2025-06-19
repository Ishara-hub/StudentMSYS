<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$isDarkMode = isset($_COOKIE['darkMode']) ? $_COOKIE['darkMode'] === 'true' : false;
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?= $isDarkMode ? 'dark' : 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> | <?= ucfirst(str_replace('.php', '', $currentPage)) ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="<?= BASE_URL ?>/vendor/twbs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body>
    <!-- Top Header -->
    <header class="main-header navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid px-3">
            <button id="sidebarToggle" class="navbar-toggler me-2" type="button">
                <i class="fas fa-bars"></i>
            </button>
            
            <a class="navbar-brand me-auto" href="<?= BASE_URL ?>/admin/dashboard.php">
                <img src="<?= BASE_URL ?>/assets/images/logo.jpg" alt="Company Logo" class="logo-img d-inline-block align-top">
                <span class="ms-2 d-none d-lg-inline"><?= SITE_NAME ?></span>
            </a>
            
            <div class="d-flex align-items-center">
                <button class="theme-toggle me-3" id="themeToggle" title="Toggle Theme">
                    <i class="fas <?= $isDarkMode ? 'fa-sun' : 'fa-moon' ?>"></i>
                </button>
                
                <div class="dropdown">
                    <button class="btn btn-link text-white p-0 d-flex align-items-center" type="button" id="userDropdown" data-bs-toggle="dropdown">
                        <div class="me-2 d-none d-lg-block text-end">
                            <div class="fw-bold"><?= $_SESSION['username'] ?></div>
                            <div class="small text-white-50">
                                <span class="badge bg-<?= ($_SESSION['role'] === 'Admin') ? 'danger' : (($_SESSION['role'] === 'Staff') ? 'info' : 'secondary') ?>">
                                    <?= $_SESSION['role'] ?>
                                </span>
                            </div>
                        </div>
                        <div class="avatar avatar-sm">
                            <i class="fas fa-user-circle fa-2x"></i>
                        </div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><h6 class="dropdown-header">Welcome, <?= $_SESSION['username'] ?></h6></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i> Profile</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i> Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <!-- Sidebar Navigation -->
    <nav id="sidebar" class="sidebar">
        <div class="position-sticky pt-2">
            <div class="sidebar-heading px-3 py-4 d-flex justify-content-between align-items-center">
                <span>MAIN NAVIGATION</span>
                <button class="btn btn-sm btn-link text-muted" id="sidebarCollapse">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>/admin/dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                
                <?php if ($_SESSION['role'] === 'Admin'): ?>
                <!-- Students Section -->
                <li class="nav-item">
                    <a class="nav-link <?= in_array($currentPage, ['students_list.php', 'register.php', 'progress.php', 'make_payment.php', 'history.php']) ? 'active' : '' ?>"
                       data-bs-toggle="collapse" href="#studentsCollapse">
                        <i class="fas fa-users"></i> Students
                        <i class="fas fa-angle-down float-end mt-1"></i>
                    </a>
                    <div class="collapse <?= in_array($currentPage, ['students_list.php', 'register.php', 'progress.php', 'make_payment.php', 'history.php']) ? 'show' : '' ?>" id="studentsCollapse">
                        <ul class="nav flex-column ms-3">
                            <li class="nav-item">
                                <a class="nav-link <?= $currentPage === 'students_list.php' ? 'active' : '' ?>"
                                   href="<?= BASE_URL ?>/student/student_list.php">
                                    <i class="fas fa-list"></i> All Students
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $currentPage === 'register.php' ? 'active' : '' ?>"
                                   href="<?= BASE_URL ?>/student/register.php">
                                    <i class="fas fa-plus-circle"></i> Add New
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $currentPage === 'progress.php' ? 'active' : '' ?>"
                                   href="<?= BASE_URL ?>/student/progress.php">
                                    <i class="fas fa-chart-line"></i> Progress
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $currentPage === 'make_payment.php' ? 'active' : '' ?>"
                                   href="<?= BASE_URL ?>/student/payments/make_payment.php">
                                    <i class="fas fa-money-bill-wave"></i> Payments
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $currentPage === 'history.php' ? 'active' : '' ?>"
                                   href="<?= BASE_URL ?>/student/payments/history.php">
                                    <i class="fas fa-history"></i> Payment History
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Users Section -->
                <li class="nav-item">
                    <a class="nav-link <?= in_array($currentPage, ['manage_users.php', 'create.php', 'edit.php']) ? 'active' : '' ?>"
                       data-bs-toggle="collapse" href="#usersCollapse">
                        <i class="fas fa-user-cog"></i> Users
                        <i class="fas fa-angle-down float-end mt-1"></i>
                    </a>
                    <div class="collapse <?= in_array($currentPage, ['manage_users.php', 'create.php', 'edit.php']) ? 'show' : '' ?>" id="usersCollapse">
                        <ul class="nav flex-column ms-3">
                            <li class="nav-item">
                                <a class="nav-link <?= $currentPage === 'manage_users.php' ? 'active' : '' ?>"
                                   href="<?= BASE_URL ?>/admin/users/manage_users.php">
                                    <i class="fas fa-list"></i> All Users
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $currentPage === 'create.php' ? 'active' : '' ?>"
                                   href="<?= BASE_URL ?>/admin/users/create.php">
                                    <i class="fas fa-user-plus"></i> Add New
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Call Center Section -->
                <li class="nav-item">
                    <a class="nav-link <?= in_array($currentPage, ['call_center_register.php', 'call_center_dashboard.php', 'call_center_confirm.php']) ? 'active' : '' ?>"
                       data-bs-toggle="collapse" href="#callCenterCollapse">
                        <i class="fas fa-phone"></i> Call Center
                        <i class="fas fa-angle-down float-end mt-1"></i>
                    </a>
                    <div class="collapse <?= in_array($currentPage, ['call_center_register.php', 'call_center_dashboard.php', 'call_center_confirm.php']) ? 'show' : '' ?>" id="callCenterCollapse">
                        <ul class="nav flex-column ms-3">
                            <li class="nav-item">
                                <a class="nav-link <?= $currentPage === 'call_center_register.php' ? 'active' : '' ?>"
                                   href="<?= BASE_URL ?>/call/call_center_register.php">
                                    <i class="fas fa-clipboard-list"></i> Register
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $currentPage === 'call_center_dashboard.php' ? 'active' : '' ?>"
                                   href="<?= BASE_URL ?>/call/call_center_dashboard.php">
                                    <i class="fas fa-tachometer-alt"></i> Dashboard
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Courses Section -->
                <li class="nav-item">
                    <a class="nav-link <?= in_array($currentPage, ['add_course.php', 'manage_courses.php', 'add_batch.php', 'manage_batches.php']) ? 'active' : '' ?>"
                       data-bs-toggle="collapse" href="#coursesCollapse">
                        <i class="fas fa-book"></i> Courses
                        <i class="fas fa-angle-down float-end mt-1"></i>
                    </a>
                    <div class="collapse <?= in_array($currentPage, ['add_course.php', 'manage_courses.php', 'add_batch.php', 'manage_batches.php']) ? 'show' : '' ?>" id="coursesCollapse">
                        <ul class="nav flex-column ms-3">
                            <li class="nav-item">
                                <a class="nav-link <?= $currentPage === 'add_course.php' ? 'active' : '' ?>"
                                   href="<?= BASE_URL ?>/admin/courses/add_course.php">
                                    <i class="fas fa-plus-circle"></i> Add Course
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $currentPage === 'manage_courses.php' ? 'active' : '' ?>"
                                   href="<?= BASE_URL ?>/admin/courses/manage_courses.php">
                                    <i class="fas fa-list"></i> Manage Courses
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $currentPage === 'add_batch.php' ? 'active' : '' ?>"
                                   href="<?= BASE_URL ?>/admin/courses/batches/add_batch.php">
                                    <i class="fas fa-plus-circle"></i> Add Batch
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $currentPage === 'manage_batches.php' ? 'active' : '' ?>"
                                   href="<?= BASE_URL ?>/admin/courses/batches/manage_batches.php">
                                    <i class="fas fa-list"></i> Manage Batches
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Accounts Section -->
                <li class="nav-item">
                    <a class="nav-link <?= in_array($currentPage, ['chart_of_accounts_data.php', 'chart_of_accounts.php', 'balance_sheet_pdf.php', 'balance_sheet.php', 'general_ledger.php', 'income_statement.php', 'journal_entry.php', 'trial_balance.php', 'account_payment.php', 'add_branch.php']) ? 'active' : '' ?>"
                       data-bs-toggle="collapse" href="#accountsCollapse">
                        <i class="fas fa-calculator"></i> Accounts
                        <i class="fas fa-angle-down float-end mt-1"></i>
                    </a>
                    <div class="collapse <?= in_array($currentPage, ['chart_of_accounts_data.php', 'chart_of_accounts.php', 'balance_sheet_pdf.php', 'balance_sheet.php', 'general_ledger.php', 'income_statement.php', 'journal_entry.php', 'trial_balance.php', 'account_payment.php', 'add_branch.php']) ? 'show' : '' ?>" id="accountsCollapse">
                        <ul class="nav flex-column ms-3">
                            <li class="nav-item">
                                <a class="nav-link <?= $currentPage === 'add_branch.php' ? 'active' : '' ?>"
                                   href="<?= BASE_URL ?>/accounts/add_branch.php">
                                    <i class="fas fa-plus-circle"></i> Add Branch
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $currentPage === 'chart_of_accounts.php' ? 'active' : '' ?>"
                                   href="<?= BASE_URL ?>/accounts/chart_of_accounts.php">
                                    <i class="fas fa-list"></i> Chart of Accounts
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $currentPage === 'account_payment.php' ? 'active' : '' ?>"
                                   href="<?= BASE_URL ?>/accounts/account_payment.php">
                                    <i class="fas fa-money-bill-wave"></i> Account Payment
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $currentPage === 'journal_entry.php' ? 'active' : '' ?>"
                                   href="<?= BASE_URL ?>/accounts/journal_entry.php">
                                    <i class="fas fa-file-invoice"></i> Journal Entry
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $currentPage === 'balance_sheet.php' ? 'active' : '' ?>"
                                   href="<?= BASE_URL ?>/accounts/balance_sheet.php">
                                    <i class="fas fa-balance-scale"></i> Balance Sheet
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $currentPage === 'income_statement.php' ? 'active' : '' ?>"
                                   href="<?= BASE_URL ?>/accounts/income_statement.php">
                                    <i class="fas fa-file-invoice-dollar"></i> Income Statement
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $currentPage === 'trial_balance.php' ? 'active' : '' ?>"
                                   href="<?= BASE_URL ?>/accounts/trial_balance.php">
                                    <i class="fas fa-balance-scale-left"></i> Trial Balance
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Reports Section -->
                <li class="nav-item">
                    <a class="nav-link <?= in_array($currentPage, ['financial.php', 'progress_report.php', 'student_list_report.php']) ? 'active' : '' ?>"
                       data-bs-toggle="collapse" href="#reportsCollapse">
                        <i class="fas fa-chart-bar"></i> Reports
                        <i class="fas fa-angle-down float-end mt-1"></i>
                    </a>
                    <div class="collapse <?= in_array($currentPage, ['financial.php', 'progress_report.php', 'student_list_report.php']) ? 'show' : '' ?>" id="reportsCollapse">
                        <ul class="nav flex-column ms-3">
                            <li class="nav-item">
                                <a class="nav-link <?= $currentPage === 'financial.php' ? 'active' : '' ?>"
                                   href="<?= BASE_URL ?>/admin/reports/financial.php">
                                    <i class="fas fa-file-invoice-dollar"></i> Financial Report
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $currentPage === 'progress_report.php' ? 'active' : '' ?>"
                                   href="<?= BASE_URL ?>/admin/reports/progress_report.php">
                                    <i class="fas fa-chart-line"></i> Progress Report
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $currentPage === 'student_list_report.php' ? 'active' : '' ?>"
                                   href="<?= BASE_URL ?>/admin/reports/student_list_report.php">
                                    <i class="fas fa-users"></i> Student List Report
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
            </ul>
        </div>
    </nav>
    <?php endif; // Close the Admin role check ?>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Flash Messages -->
        <?php include 'flash_messages.php'; ?>