<?php
session_start();

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

// Get parameters from URL
$sub_account_id = $_GET['id'] ?? 0;
$date_from = $_GET['date_from'] ?? date('Y-01-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$branch_id = $_GET['branch_id'] ?? null;

// Fetch all branches for the dropdown
$branches = $conn->query("SELECT id, name FROM branches ORDER BY name");
$branches_data = $branches->fetch_all(MYSQLI_ASSOC);

// Fetch sub-account details
$subAccountQuery = $conn->prepare("
    SELECT 
        sa.sub_account_code,
        sa.sub_account_name,
        ca.account_code AS parent_account_code,
        ca.account_name AS parent_account_name,
        ac.name AS category_name
    FROM 
        sub_accounts sa
    JOIN 
        chart_of_accounts ca ON sa.parent_account_id = ca.id
    JOIN 
        account_categories ac ON ca.category_id = ac.id
    WHERE 
        sa.id = ?
");
$subAccountQuery->bind_param("i", $sub_account_id);
$subAccountQuery->execute();
$subAccountResult = $subAccountQuery->get_result();
$subAccount = $subAccountResult->fetch_assoc();

// Build transactions query with filters
$transactionsSql = "
    SELECT 
        je.id AS entry_id,
        gj.transaction_date,
        gj.description,
        gj.reference,
        je.debit,
        je.credit,
        (je.debit - je.credit) AS balance,
        b.name AS branch_name
    FROM 
        journal_entries je
    JOIN 
        general_journal gj ON je.journal_id = gj.id
    LEFT JOIN
        branches b ON gj.branch_id = b.id
    WHERE 
        je.account_id = ?
        AND gj.transaction_date BETWEEN ? AND ?
";

// Add branch filter if selected
if ($branch_id && is_numeric($branch_id)) {
    $transactionsSql .= " AND gj.branch_id = ?";
}

$transactionsSql .= " ORDER BY gj.transaction_date DESC, je.id DESC";

// Prepare and execute the query
$transactionsQuery = $conn->prepare($transactionsSql);

if ($branch_id && is_numeric($branch_id)) {
    $transactionsQuery->bind_param("issi", $sub_account_id, $date_from, $date_to, $branch_id);
} else {
    $transactionsQuery->bind_param("iss", $sub_account_id, $date_from, $date_to);
}

$transactionsQuery->execute();
$transactionsResult = $transactionsQuery->get_result();
$transactions = $transactionsResult->fetch_all(MYSQLI_ASSOC);

// Calculate totals
$totalDebit = 0;
$totalCredit = 0;
foreach ($transactions as $transaction) {
    $totalDebit += $transaction['debit'];
    $totalCredit += $transaction['credit'];
}

// Get branch name for header if filtered
$branch_name = "All Branches";
if ($branch_id && is_numeric($branch_id)) {
    foreach ($branches_data as $branch) {
        if ($branch['id'] == $branch_id) {
            $branch_name = $branch['name'];
            break;
        }
    }
}

// Include header

?>

<style>
    .sub-account-details th, .sub-account-details td {
        padding: 8px;
        vertical-align: middle;
    }
    .info-card {
        background-color: #f8f9fa;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 20px;
    }
    .info-label {
        font-weight: bold;
        color: #6c757d;
    }
    .text-end {
        text-align: right;
    }
    .debit-amount {
        color: #0d6efd;
    }
    .credit-amount {
        color: #dc3545;
    }
    .total-row {
        font-weight: bold;
        background-color: #f8f9fa;
    }
    .filter-card {
        margin-bottom: 20px;
    }
    .sub-account-header {
        background-color: #e9ecef;
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
</style>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-file-invoice me-2"></i>Sub-Account Transaction Details</h2>
                <a href="chart_of_accounts_data.php?date_from=<?= $date_from ?>&date_to=<?= $date_to ?>&branch_id=<?= $branch_id ?>" 
                   class="btn btn-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Back to Chart
                </a>
            </div>
        </div>
        
        <!-- Filter Section -->
        <div class="card-body filter-card">
            <form method="GET" class="row g-3">
                <input type="hidden" name="id" value="<?= $sub_account_id ?>">
                <div class="col-md-3">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" name="date_from" class="form-control" 
                           value="<?= htmlspecialchars($date_from) ?>">
                </div>
                <div class="col-md-3">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" name="date_to" class="form-control" 
                           value="<?= htmlspecialchars($date_to) ?>">
                </div>
                <div class="col-md-4">
                    <label for="branch_id" class="form-label">Branch</label>
                    <select name="branch_id" class="form-select">
                        <option value="">All Branches</option>
                        <?php foreach ($branches_data as $branch): ?>
                            <option value="<?= $branch['id'] ?>" 
                                <?= ($branch_id == $branch['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($branch['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>
        
        <div class="card-body">
            <?php if (!$subAccount): ?>
                <div class="alert alert-danger">Sub-account not found</div>
            <?php else: ?>
                <!-- Sub-Account Information -->
                <div class="sub-account-header mb-4">
                    <div class="row">
                        <div class="col-md-4">
                            <span class="info-label">Sub-Account:</span>
                            <span><?= htmlspecialchars($subAccount['sub_account_code']) ?> - <?= htmlspecialchars($subAccount['sub_account_name']) ?></span>
                        </div>
                        <div class="col-md-4">
                            <span class="info-label">Parent Account:</span>
                            <span><?= htmlspecialchars($subAccount['parent_account_code']) ?> - <?= htmlspecialchars($subAccount['parent_account_name']) ?></span>
                        </div>
                        <div class="col-md-4">
                            <span class="info-label">Category:</span>
                            <span><?= htmlspecialchars($subAccount['category_name']) ?></span>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-4">
                            <span class="info-label">Branch:</span>
                            <span><?= htmlspecialchars($branch_name) ?></span>
                        </div>
                        <div class="col-md-4">
                            <span class="info-label">Period:</span>
                            <span><?= date('m/d/Y', strtotime($date_from)) ?> to <?= date('m/d/Y', strtotime($date_to)) ?></span>
                        </div>
                        <div class="col-md-4">
                            <span class="info-label">Net Balance:</span>
                            <span class="<?= ($totalDebit - $totalCredit) >= 0 ? 'debit-amount' : 'credit-amount' ?>">
                                <?= number_format(abs($totalDebit - $totalCredit), 2) ?>
                                (<?= ($totalDebit - $totalCredit) >= 0 ? 'Debit' : 'Credit' ?>)
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Transactions Table -->
                <table class="table table-bordered sub-account-details">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Reference</th>
                            <th>Branch</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Credit</th>
                            <th class="text-end">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No transactions found for the selected filters</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td><?= htmlspecialchars($transaction['transaction_date']) ?></td>
                                    <td><?= htmlspecialchars($transaction['description']) ?></td>
                                    <td><?= htmlspecialchars($transaction['reference']) ?></td>
                                    <td><?= htmlspecialchars($transaction['branch_name'] ?? 'N/A') ?></td>
                                    <td class="text-end debit-amount"><?= number_format($transaction['debit'], 2) ?></td>
                                    <td class="text-end credit-amount"><?= number_format($transaction['credit'], 2) ?></td>
                                    <td class="text-end"><?= number_format($transaction['balance'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="total-row">
                                <td colspan="4" class="text-end">Totals:</td>
                                <td class="text-end debit-amount"><?= number_format($totalDebit, 2) ?></td>
                                <td class="text-end credit-amount"><?= number_format($totalCredit, 2) ?></td>
                                <td class="text-end"><?= number_format($totalDebit - $totalCredit, 2) ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
