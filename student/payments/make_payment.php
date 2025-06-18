<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

checkRole(['Admin', 'Staff']);

$studentId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get student and registration info
$student = [];
$stmt = $conn->prepare("SELECT s.student_id, s.full_name, r.registration_id, r.balance 
                       FROM students s 
                       JOIN registrations r ON s.student_id = r.student_id 
                       WHERE s.student_id = ?");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    $_SESSION['error'] = 'Student not found';
    header("Location: ../dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Payment | <?= SITE_NAME ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="<?= BASE_URL ?>/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link href="<?= BASE_URL ?>/assets/css/custom.css" rel="stylesheet">
    
    <!-- Print CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/print.css" media="print">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-money-bill-wave me-2"></i>
                            Make Payment for <?= htmlspecialchars($student['full_name']) ?>
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-4">
                            <h5 class="alert-heading">
                                <i class="fas fa-wallet me-2"></i>
                                Current Balance
                            </h5>
                            <p class="mb-0 fs-4 fw-bold">
                                <?= DEFAULT_CURRENCY ?><?= number_format($student['balance'], 2) ?>
                            </p>
                        </div>
                        
                        <form action="../../processes/payment_processing.php" method="POST" class="needs-validation" novalidate id="paymentForm">
                            <input type="hidden" name="registration_id" value="<?= $student['registration_id'] ?>">
                            <input type="hidden" name="student_id" value="<?= $student['student_id'] ?>">
                            
                            <div class="mb-3">
                                <label for="amount" class="form-label">Payment Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text"><?= DEFAULT_CURRENCY ?></span>
                                    <input type="number" class="form-control" id="amount" name="amount" 
                                           min="0.01" max="<?= $student['balance'] ?>" step="0.01" 
                                           value="<?= min(10000, $student['balance']) ?>" required>
                                    <span class="input-group-text">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setMaxAmount()">
                                            Max
                                        </button>
                                    </span>
                                </div>
                                <div class="form-text">Enter amount between <?= DEFAULT_CURRENCY ?>0.01 and <?= DEFAULT_CURRENCY ?><?= number_format($student['balance'], 2) ?></div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="payment_method" class="form-label">Payment Method</label>
                                <select class="form-select" id="payment_method" name="payment_method" required>
                                    <option value="" selected disabled>-- Select Payment Method --</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Card">Credit/Debit Card</option>
                                    <option value="Check">Check</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="../dashboard.php?id=<?= $studentId ?>" class="btn btn-outline-secondary me-md-2">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Record Payment & Print
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Set maximum amount when clicking Max button
        function setMaxAmount() {
            const maxAmount = parseFloat(document.getElementById('amount').max);
            document.getElementById('amount').value = maxAmount.toFixed(2);
        }
        
        // Form validation
        (function() {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');
            
            Array.from(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    
                    form.classList.add('was-validated');
                }, false);
            });
        })();
        
        // Amount validation
        document.getElementById('amount').addEventListener('input', function() {
            const max = parseFloat(this.max);
            const value = parseFloat(this.value) || 0;
            
            if (value > max) {
                this.value = max.toFixed(2);
            } else if (value < 0.01) {
                this.value = '0.01';
            }
            
            // Update validation message
            if (this.value > max) {
                this.setCustomValidity(`Amount cannot exceed ${max.toFixed(2)}`);
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Handle form submission to show print dialog after successful submission
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            if (this.checkValidity()) {
                e.preventDefault();
                
                // Submit form via AJAX
                fetch(this.action, {
                    method: 'POST',
                    body: new FormData(this)
                })
                .then(response => response.text())
                .then(response => {
                    // On successful submission, generate and print invoice
                    const formData = new FormData(this);
                    const data = {};
                    formData.forEach((value, key) => {
                        data[key] = value;
                    });
                    
                    // Add student info
                    data.student_name = '<?= addslashes($student["full_name"]) ?>';
                    data.student_id = '<?= $student["student_id"] ?>';
                    data.balance = '<?= $student["balance"] ?>';
                    
                    return fetch('generate_invoice.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(data)
                    });
                })
                .then(response => response.text())
                .then(html => {
                    // Open invoice in new window and print
                    const printWindow = window.open('', '_blank');
                    printWindow.document.write(html);
                    printWindow.document.close();
                    
                    // Auto-print with delay for better rendering
                    printWindow.onload = function() {
                        setTimeout(function() {
                            printWindow.print();
                        }, 300);
                        
                        // Close window after print (optional)
                        printWindow.onafterprint = function() {
                            setTimeout(function() {
                                printWindow.close();
                                window.location.href = '../dashboard.php?id=<?= $studentId ?>';
                            }, 500);
                        };
                    };
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Payment recorded but failed to generate invoice');
                    window.location.href = '../dashboard.php?id=<?= $studentId ?>';
                });
            }
        });
    </script>

    <?php include '../../includes/footer.php'; ?>
</body>
</html>