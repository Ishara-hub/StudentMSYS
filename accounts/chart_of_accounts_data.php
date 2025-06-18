<?php
session_start();
require_once '../includes/auth.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

// Get filter parameters
$date_from = $_GET['date_from'] ?? date('Y-01-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$branch_id = $_GET['branch_id'] ?? null;

// Fetch all branches for the dropdown
$branches = $conn->query("SELECT id, name FROM branches ORDER BY name");
$branches_data = $branches->fetch_all(MYSQLI_ASSOC);

// Fetch all account categories with their accounts, sub-accounts, and filtered balances
$sql = "
    SELECT 
        ac.id AS category_id,
        ac.name AS category_name,
        ca.id AS account_id,
        ca.account_code,
        ca.account_name,
        sa.id AS sub_account_id,
        sa.sub_account_code,
        sa.sub_account_name,
        SUM(IFNULL(je.debit, 0)) AS total_debit,
        SUM(IFNULL(je.credit, 0)) AS total_credit,
        (SUM(IFNULL(je.debit, 0)) - SUM(IFNULL(je.credit, 0))) AS balance
    FROM 
        account_categories ac
    LEFT JOIN 
        chart_of_accounts ca ON ca.category_id = ac.id
    LEFT JOIN 
        sub_accounts sa ON sa.parent_account_id = ca.id
    LEFT JOIN
        journal_entries je ON (
            (sa.id IS NULL AND je.account_id = ca.id) OR 
            (sa.id IS NOT NULL AND je.account_id = sa.id)
        )
    LEFT JOIN
        general_journal gj ON je.journal_id = gj.id
    WHERE 
        ca.is_active = 1
        AND gj.transaction_date BETWEEN ? AND ?
";

// Add branch filter if selected
if ($branch_id && is_numeric($branch_id)) {
    $sql .= " AND gj.branch_id = ?";
}

$sql .= "
    GROUP BY
        ac.id, ac.name, ca.id, ca.account_code, ca.account_name, 
        sa.id, sa.sub_account_code, sa.sub_account_name
    ORDER BY 
        ac.name ASC,
        ca.account_code ASC,
        sa.sub_account_code ASC
";

// Prepare and execute the query
$stmt = $conn->prepare($sql);

if ($branch_id && is_numeric($branch_id)) {
    $stmt->bind_param("ssi", $date_from, $date_to, $branch_id);
} else {
    $stmt->bind_param("ss", $date_from, $date_to);
}

$stmt->execute();
$result = $stmt->get_result();
$accounts = $result->fetch_all(MYSQLI_ASSOC);

// Organize data hierarchically and calculate totals
$groupedData = [];

foreach ($accounts as $account) {
    $categoryId = $account['category_id'];
    $accountId = $account['account_id'];
    
    if (!isset($groupedData[$categoryId])) {
        $groupedData[$categoryId] = [
            'category_name' => $account['category_name'],
            'accounts' => [],
            'total_debit' => 0,
            'total_credit' => 0,
            'total_balance' => 0
        ];
    }
    
    if ($accountId && !isset($groupedData[$categoryId]['accounts'][$accountId])) {
        $groupedData[$categoryId]['accounts'][$accountId] = [
            'account_code' => $account['account_code'],
            'account_name' => $account['account_name'],
            'sub_accounts' => [],
            'total_debit' => 0,
            'total_credit' => 0,
            'total_balance' => 0,
            'has_sub_accounts' => false
        ];
    }
    
    if ($account['sub_account_id']) {
        $groupedData[$categoryId]['accounts'][$accountId]['sub_accounts'][] = [
            'sub_account_id' => $account['sub_account_id'],
            'sub_account_code' => $account['sub_account_code'],
            'sub_account_name' => $account['sub_account_name'],
            'debit' => $account['total_debit'] ?? 0,
            'credit' => $account['total_credit'] ?? 0,
            'balance' => $account['balance'] ?? 0
        ];
        
        $groupedData[$categoryId]['accounts'][$accountId]['has_sub_accounts'] = true;
        
        $groupedData[$categoryId]['accounts'][$accountId]['total_debit'] += $account['total_debit'] ?? 0;
        $groupedData[$categoryId]['accounts'][$accountId]['total_credit'] += $account['total_credit'] ?? 0;
        $groupedData[$categoryId]['accounts'][$accountId]['total_balance'] += $account['balance'] ?? 0;
        
        $groupedData[$categoryId]['total_debit'] += $account['total_debit'] ?? 0;
        $groupedData[$categoryId]['total_credit'] += $account['total_credit'] ?? 0;
        $groupedData[$categoryId]['total_balance'] += $account['balance'] ?? 0;
    } elseif ($accountId && $account['account_code']) {
        if (!isset($groupedData[$categoryId]['accounts'][$accountId]['has_sub_accounts'])) {
            $groupedData[$categoryId]['accounts'][$accountId]['debit'] = $account['total_debit'] ?? 0;
            $groupedData[$categoryId]['accounts'][$accountId]['credit'] = $account['total_credit'] ?? 0;
            $groupedData[$categoryId]['accounts'][$accountId]['balance'] = $account['balance'] ?? 0;
            
            $groupedData[$categoryId]['total_debit'] += $account['total_debit'] ?? 0;
            $groupedData[$categoryId]['total_credit'] += $account['total_credit'] ?? 0;
            $groupedData[$categoryId]['total_balance'] += $account['balance'] ?? 0;
        }
    }
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

?>

<style>
    .chart-of-accounts-table th, .chart-of-accounts-table td {
        padding: 8px;
        vertical-align: middle;
    }
    .category-header {
        font-weight: bold;
        background-color: #eee;
    }
    .account-row {
        font-weight: bold;
        background-color: #f8f9fa;
    }
    .sub-account-row {
        padding-left: 30px;
    }
    .sub-account-link {
        color: #0d6efd;
        text-decoration: none;
    }
    .sub-account-link:hover {
        text-decoration: underline;
    }
    .no-records {
        text-align: center;
        font-style: italic;
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
        background-color: #e9ecef;
    }
    .category-total-row {
        font-weight: bold;
        background-color: #dee2e6;
    }
    .amount-cell {
        min-width: 100px;
    }
    .filter-card {
        margin-bottom: 20px;
    }
</style>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-list me-2"></i>Chart of Accounts</h2>
            <button class="btn btn-light btn-sm" onclick="window.print()">
                <i class="fas fa-print me-1"></i> Print
            </button>
        </div>
        
        <!-- Filter Section -->
        <div class="card-body filter-card">
            <form method="GET" class="row g-3">
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
        
        <!-- Report Section -->
        <div class="card-body">
            <div class="mb-4">
                <h4 class="text-center">FAF Solution (pvt)Ltd</h4>
                <h5 class="text-center">Branch: <?= htmlspecialchars($branch_name) ?></h5>
                <h6 class="text-center">
                    Period: <?= date('m/d/Y', strtotime($date_from)) ?> to <?= date('m/d/Y', strtotime($date_to)) ?>
                </h6>
            </div>
            
            <table class="table table-bordered chart-of-accounts-table">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Account Name</th>
                        <th>Type</th>
                        <th class="text-end amount-cell">Debit</th>
                        <th class="text-end amount-cell">Credit</th>
                        <th class="text-end amount-cell">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($groupedData)): ?>
                        <tr>
                            <td colspan="6" class="no-records">No accounts found for the selected filters</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($groupedData as $categoryId => $category): ?>
                            <tr class="category-header">
                                <td colspan="6"><?= htmlspecialchars($category['category_name']) ?></td>
                            </tr>
                            
                            <?php foreach ($category['accounts'] as $accountId => $account): ?>
                                <?php if (empty($account['sub_accounts']) || !$account['has_sub_accounts']): ?>
                                    <tr class="account-row">
                                        <td><?= htmlspecialchars($account['account_code']) ?></td>
                                        <td><?= htmlspecialchars($account['account_name']) ?></td>
                                        <td>Main Account</td>
                                        <td class="text-end debit-amount"><?= number_format($account['debit'] ?? 0, 2) ?></td>
                                        <td class="text-end credit-amount"><?= number_format($account['credit'] ?? 0, 2) ?></td>
                                        <td class="text-end"><?= number_format($account['balance'] ?? 0, 2) ?></td>
                                    </tr>
                                <?php else: ?>
                                    <tr class="account-row">
                                        <td><?= htmlspecialchars($account['account_code']) ?></td>
                                        <td><?= htmlspecialchars($account['account_name']) ?></td>
                                        <td>Main Account</td>
                                        <td colspan="3"></td>
                                    </tr>
                                    
                                    <?php foreach ($account['sub_accounts'] as $subAccount): ?>
                                        <tr class="sub-account-row">
                                            <td><?= htmlspecialchars($subAccount['sub_account_code']) ?></td>
                                            <td>
                                                <a href="sub_account_details.php?id=<?= $subAccount['sub_account_id'] ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>&branch_id=<?= $branch_id ?>" 
                                                   class="sub-account-link">
                                                    <?= htmlspecialchars($subAccount['sub_account_name']) ?>
                                                </a>
                                            </td>
                                            <td>Sub Account</td>
                                            <td class="text-end debit-amount"><?= number_format($subAccount['debit'], 2) ?></td>
                                            <td class="text-end credit-amount"><?= number_format($subAccount['credit'], 2) ?></td>
                                            <td class="text-end"><?= number_format($subAccount['balance'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    
                                    <tr class="total-row">
                                        <td colspan="3" class="text-end"><?= htmlspecialchars($account['account_name']) ?> Total:</td>
                                        <td class="text-end debit-amount"><?= number_format($account['total_debit'], 2) ?></td>
                                        <td class="text-end credit-amount"><?= number_format($account['total_credit'], 2) ?></td>
                                        <td class="text-end"><?= number_format($account['total_balance'], 2) ?></td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            
                            <tr class="category-total-row">
                                <td colspan="3" class="text-end"><?= htmlspecialchars($category['category_name']) ?> Total:</td>
                                <td class="text-end debit-amount"><?= number_format($category['total_debit'], 2) ?></td>
                                <td class="text-end credit-amount"><?= number_format($category['total_credit'], 2) ?></td>
                                <td class="text-end"><?= number_format($category['total_balance'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>