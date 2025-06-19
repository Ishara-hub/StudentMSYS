<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

header('Content-Type: text/html');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Basic validation
if (empty($data) || !isset($data['student_id']) || !isset($data['amount'])) {
    die('<div class="alert alert-danger">Invalid invoice data</div>');
}


// Get student details
$stmt = $conn->prepare("SELECT s.*, c.course_name, b.batch_name , r.registration_id, r.balance
                       FROM students s
                       JOIN registrations r ON s.student_id = r.student_id
                       LEFT JOIN batches b ON r.batch_id = b.batch_id
                       LEFT JOIN courses c ON b.course_id = c.course_id
                       WHERE s.student_id = ?");
$stmt->bind_param("i", $data['student_id']);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    die('<div class="alert alert-danger">Student not found</div>');
}

// Generate invoice HTML
$invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad($data['registration_id'], 5, '0', STR_PAD_LEFT);
$paymentDate = date('d/m/Y');
$balance = $student['balance'] - $data['amount'];

function numberToWords($num) {
    $num = number_format($num, 2, '.', '');
    $parts = explode('.', $num);
    $whole = $parts[0];
    $cents = isset($parts[1]) ? $parts[1] : '00';
    
    $ones = array(
        0 => "Zero", 1 => "One", 2 => "Two", 3 => "Three", 4 => "Four",
        5 => "Five", 6 => "Six", 7 => "Seven", 8 => "Eight", 9 => "Nine",
        10 => "Ten", 11 => "Eleven", 12 => "Twelve", 13 => "Thirteen",
        14 => "Fourteen", 15 => "Fifteen", 16 => "Sixteen", 17 => "Seventeen",
        18 => "Eighteen", 19 => "Nineteen"
    );
    
    $tens = array(
        2 => "Twenty", 3 => "Thirty", 4 => "Forty", 5 => "Fifty",
        6 => "Sixty", 7 => "Seventy", 8 => "Eighty", 9 => "Ninety"
    );
    
    $formatted = "";
    
    if ($whole < 20) {
        $formatted = $ones[$whole];
    } elseif ($whole < 100) {
        $formatted = $tens[substr($whole, 0, 1)];
        if (substr($whole, 1, 1) != "0") {
            $formatted .= " " . $ones[substr($whole, 1, 1)];
        }
    } elseif ($whole < 1000) {
        $formatted = $ones[substr($whole, 0, 1)] . " Hundred";
        if (substr($whole, 1, 2) != "00") {
            $formatted .= " and " . numberToWords(substr($whole, 1, 2));
        }
    } elseif ($whole < 100000) {
        $formatted = numberToWords(substr($whole, 0, strlen($whole)-3)) . " Thousand";
        if (substr($whole, -3) != "000") {
            $formatted .= " " . numberToWords(substr($whole, -3));
        }
    } elseif ($whole < 10000000) {
        $formatted = numberToWords(substr($whole, 0, strlen($whole)-5)) . " Lakh";
        if (substr($whole, -5) != "00000") {
            $formatted .= " " . numberToWords(substr($whole, -5));
        }
    } else {
        $formatted = numberToWords(substr($whole, 0, strlen($whole)-7)) . " Crore";
        if (substr($whole, -7) != "0000000") {
            $formatted .= " " . numberToWords(substr($whole, -7));
        }
    }
    
    if ($cents > 0) {
        $formatted .= " and " . numberToWords($cents) . " Cents";
    }
    
    return $formatted;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt - <?= $invoiceNumber ?></title>
    <style>
        /* Thermal printer friendly styling */
        body {
            font-family: 'Courier New', monospace;
            margin: 0;
            padding: 5px;
            font-size: 12px;
            color: #000;
            background: #fff;
            line-height: 1.3;
        }
        .receipt {
            width: 80mm;
            margin: 0 auto;
            padding: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px dashed #000;
        }
        .company-name {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .receipt-title {
            font-size: 13px;
            margin: 5px 0;
            font-weight: bold;
        }
        .receipt-info {
            margin: 5px 0;
            font-size: 10px;
        }
        .divider {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
        }
        .detail-label {
            font-weight: bold;
        }
        .entries-table {
            width: 100%;
            border-collapse: collapse;
            margin: 5px 0;
        }
        .entries-table th {
            border-bottom: 1px solid #000;
            padding: 3px;
            text-align: left;
        }
        .entries-table td {
            padding: 3px;
            text-align: left;
        }
        .text-right {
            text-align: right;
        }
        .total-row {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        .footer {
            margin-top: 15px;
            text-align: center;
            font-size: 10px;
            border-top: 1px dashed #000;
            padding-top: 10px;
        }
        .signature {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            width: 45%;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 80%;
            margin: 15px auto 5px;
        }
        .amount-in-words {
            margin: 10px 0;
            padding: 5px;
            border: 1px dashed #ccc;
            font-size: 11px;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                padding: 0;
            }
            .receipt {
                width: 100%;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="header">
            <div style="text-align: center;">
                <img src="../assets/images/logo.jpg" alt="Company Logo" style="max-width: 350px; max-height: 250px; ">
            </div>
            <div class="voucher-title">INVOICE</div>
            <div class="company-name">CSTI Bureau (Pvt) Ltd </div>
        </div>

        <div class="divider"></div>

        <div class="detail-row">
            <span class="detail-label">Receipt No:</span>
            <span><?= $invoiceNumber ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Date:</span>
            <span><?= $paymentDate ?></span>
        </div>
        
        <div class="divider"></div>

        <div class="detail-row">
            <span class="detail-label">Student Name:</span>
            <span><?= htmlspecialchars($student['full_name']) ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Student ID:</span>
            <span><?= htmlspecialchars($student['student_id']) ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Course:</span>
            <span><?= htmlspecialchars($student['course_name'] ?? 'N/A') ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Batch:</span>
            <span><?= htmlspecialchars($student['batch_name'] ?? 'N/A') ?></span>
        </div>
        
        <div class="divider"></div>

        <table class="entries-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Course fee payment</td>
                    <td class="text-right"><?= defined('DEFAULT_CURRENCY') ? DEFAULT_CURRENCY : '$' ?><?= number_format($data['amount'], 2) ?></td>
                </tr>
                <tr class="total-row">
                    <td>Total Paid:</td>
                    <td class="text-right"><?= defined('DEFAULT_CURRENCY') ? DEFAULT_CURRENCY : '$' ?><?= number_format($data['amount'], 2) ?></td>
                </tr>
                <tr class="total-row">
                    <td>Remaining Balance:</td>
                    <td class="text-right"><?= defined('DEFAULT_CURRENCY') ? DEFAULT_CURRENCY : '$' ?><?= number_format($balance, 2) ?></td>
                </tr>
            </tbody>
        </table>
        
        <div class="amount-in-words">
            <strong>Amount in words:</strong> <?= numberToWords($data['amount']) ?> Only
        </div>
        
        <div class="divider"></div>

        <div class="detail-row">
            <span class="detail-label">Payment Method:</span>
            <span><?= htmlspecialchars($data['payment_method']) ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Received By:</span>
            <span>SYSTEM USER</span>
        </div>

        <div class="footer">
            <div>** This is a computer generated receipt **</div>
            <div>Thank you for your payment!</div>
        </div>

        <div class="signature">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div>Student Signature</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div>Authorized Signature</div>
            </div>
        </div>
    </div>
    
    <!-- Print Button -->
    <div class="no-print" style="margin-top: 15px;">
        <button onclick="window.print()" style="padding: 5px 10px;">Print Receipt</button>
        <button onclick="window.close()" style="padding: 5px 10px;">Close Window</button>
    </div>

    <script>
        // Auto-print with delay for better rendering
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 300);
            
            // Close window after print (optional)
            window.onafterprint = function() {
                setTimeout(function() {
                    window.close();
                }, 500);
            };
        };
    </script>
</body>
</html>