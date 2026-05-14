<?php
session_start();
require_once '../auth/auth.php';
$auth = new Auth();
$auth->requireRole('manager');
$user = $auth->currentUser();

require_once '../config/database.php';
$db = (new Database())->conn;

// Filter by date
$date = $_GET['date'] ?? date('Y-m-d');

$stmt = $db->prepare("
    SELECT ti.id, u.full_name AS cashier, p.name AS product, ti.quantity, (ti.quantity * ti.price) AS total, t.created_at as sold_at
    FROM transaction_items ti
    JOIN transactions t ON ti.transaction_id = t.id
    JOIN users u ON t.cashier_id = u.id
    JOIN products p ON ti.product_id = p.id
    WHERE DATE(t.created_at) = :date
    ORDER BY t.created_at DESC
");
$stmt->execute([':date' => $date]);
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate report metrics
$totalRevenue = array_sum(array_column($sales, 'total'));
$totalQuantity = array_sum(array_column($sales, 'quantity'));
$transactionCount = count($sales);
$avgTransaction = $transactionCount > 0 ? $totalRevenue / $transactionCount : 0;

// Group by cashier for summary
$byCashier = [];
foreach ($sales as $sale) {
    if (!isset($byCashier[$sale['cashier']])) {
        $byCashier[$sale['cashier']] = ['count' => 0, 'total' => 0];
    }
    $byCashier[$sale['cashier']]['count']++;
    $byCashier[$sale['cashier']]['total'] += $sale['total'];
}

// Group by product for summary
$byProduct = [];
foreach ($sales as $sale) {
    if (!isset($byProduct[$sale['product']])) {
        $byProduct[$sale['product']] = ['qty' => 0, 'total' => 0];
    }
    $byProduct[$sale['product']]['qty'] += $sale['quantity'];
    $byProduct[$sale['product']]['total'] += $sale['total'];
}

$baseCSS      = file_get_contents(__DIR__ . '/../assets/css/base.css');
$dashboardCSS = file_get_contents(__DIR__ . '/../assets/css/dashboard.css');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Reports</title>
    <link rel="stylesheet" href="../assets/css/theme.css">
    <style>
        <?= $baseCSS ?>
    </style>
    <style>
        <?= $dashboardCSS ?>
    </style>
    <style>
        /* Report Sections */
        .report-header {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }

        .report-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 18px;
            backdrop-filter: blur(10px);
        }

        .report-card .label {
            font-size: 11px;
            color: var(--text-secondary);
            text-transform: uppercase;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        .report-card .value {
            font-size: 24px;
            font-weight: 700;
            color: var(--sidebar-active-text);
        }

        /* Breakdown Sections */
        .breakdown-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 32px;
        }

        .breakdown-panel {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            backdrop-filter: blur(10px);
        }

        .breakdown-panel h3 {
            font-size: 14px;
            color: var(--text-primary);
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }

        .breakdown-list {
            list-style: none;
        }

        .breakdown-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-color);
            font-size: 12px;
            color: var(--text-primary);
        }

        .breakdown-list li:last-child {
            border-bottom: none;
        }

        .breakdown-list .amount {
            color: var(--sidebar-active-text);
            font-weight: 600;
        }

        /* Toolbar */
        .toolbar {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 28px;
        }

        .toolbar label {
            font-size: 13px;
            color: var(--text-primary);
            font-weight: 500;
        }

        .toolbar input[type="date"] {
            padding: 8px 12px;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 6px;
            font-size: 13px;
            color: var(--input-text);
        }

        .toolbar input[type="date"]::placeholder {
            color: var(--text-secondary);
        }

        .toolbar button {
            padding: 8px 20px;
            background: var(--sidebar-active-text);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .toolbar button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px var(--shadow-medium);
        }

        /* Sales Table */
        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
            font-size: 13px;
            backdrop-filter: blur(10px);
            display: none;
        }

        /* Sales Cards */
        .sales-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .sales-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 16px;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .sales-card:hover {
            border-color: var(--sidebar-active-text);
            box-shadow: 0 8px 20px var(--shadow-medium);
            transform: translateY(-2px);
        }

        .sales-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }

        .sales-card-txn {
            font-size: 11px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sales-card-time {
            font-size: 11px;
            color: var(--sidebar-active-text);
            font-weight: 600;
        }

        .sales-card-cashier {
            font-size: 12px;
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 8px;
        }

        .sales-card-item {
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }

        .sales-card-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .sales-card-product {
            font-size: 12px;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .sales-card-qty {
            font-size: 11px;
            color: var(--text-secondary);
            margin-bottom: 4px;
        }

        .sales-card-price {
            font-size: 13px;
            color: var(--sidebar-active-text);
            font-weight: 600;
        }

        .sales-card-total {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sales-card-total-label {
            font-size: 11px;
            color: var(--text-secondary);
            text-transform: uppercase;
        }

        .sales-card-total-amount {
            font-size: 16px;
            font-weight: bold;
            color: var(--success-text);
        }

        .total-row {
            font-size: 13px;
            color: var(--text-secondary);
            margin-top: 16px;
            text-align: right;
            padding: 14px;
            background: var(--bg-secondary);
            border-radius: 6px;
        }

        .total-row strong {
            color: var(--sidebar-active-text);
            font-size: 14px;
        }

        .empty {
            text-align: center;
            padding: 48px 20px;
            color: var(--text-secondary);
            font-size: 13px;
        }
    </style>
</head>

<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="sidebar-brand">ASAJ System <span>Manager</span></div>
            <nav class="sidebar-nav">
                <div class="nav-label">Main</div>
                <a class="nav-item" href="manager_dashboard.php">Dashboard</a>
                <div class="nav-label">Management</div>
                <a class="nav-item active" href="manager_sales.php">Sales Reports</a>
                <a class="nav-item" href="manager_inventory.php">Inventory</a>
                <a class="nav-item" href="manager_staff.php">Staff Accounts</a>
                <a class="nav-item" href="manager_audit.php">Audit Logs</a>
                <div class="nav-label">System</div>
                <a class="nav-item" href="manager_backups.php">Database Backups</a>
            </nav>
            <div class="sidebar-footer">
                <div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div>
                <div class="user-role">Manager</div>
                <a href="../auth/logout.php" class="btn-logout">Sign Out</a>
            </div>
        </aside>

        <main class="main">
            <div class="page-title">Sales Reports</div>
            <div class="page-subtitle">View all transactions by date</div>

            <form method="GET" class="toolbar">
                <label for="date">Date:</label>
                <input type="date" name="date" id="date" value="<?= htmlspecialchars($date) ?>">
                <button type="submit">Generate Report</button>
            </form>

            <?php if ($sales): ?>
                <div class="report-header">
                    <div class="report-card">
                        <div class="label">Total Revenue</div>
                        <div class="value">₱<?= number_format($totalRevenue, 2) ?></div>
                    </div>
                    <div class="report-card">
                        <div class="label">Transactions</div>
                        <div class="value"><?= $transactionCount ?></div>
                    </div>
                    <div class="report-card">
                        <div class="label">Items Sold</div>
                        <div class="value"><?= $totalQuantity ?></div>
                    </div>
                    <div class="report-card">
                        <div class="label">Avg Transaction</div>
                        <div class="value">₱<?= number_format($avgTransaction, 2) ?></div>
                    </div>
                </div>

                <div class="breakdown-row">
                    <div class="breakdown-panel">
                        <h3>Sales by Cashier</h3>
                        <ul class="breakdown-list">
                            <?php foreach ($byCashier as $cashier => $data): ?>
                                <li>
                                    <span><?= htmlspecialchars($cashier) ?></span>
                                    <span><span class="amount">₱<?= number_format($data['total'], 2) ?></span> (<?= $data['count'] ?> trans)</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="breakdown-panel">
                        <h3>Top Products</h3>
                        <ul class="breakdown-list">
                            <?php
                            // Sort products by total revenue
                            uasort($byProduct, function ($a, $b) {
                                return $b['total'] <=> $a['total'];
                            });
                            foreach (array_slice($byProduct, 0, 10) as $product => $data):
                            ?>
                                <li>
                                    <span><?= htmlspecialchars($product) ?></span>
                                    <span><span class="amount">₱<?= number_format($data['total'], 2) ?></span> (<?= $data['qty'] ?> units)</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($sales): ?>
                <div class="sales-grid">
                    <?php
                    // Group sales by transaction
                    $transactions = [];
                    foreach ($sales as $item) {
                        $txnKey = $item['sold_at'] . '_' . $item['cashier']; // Create unique key
                        if (!isset($transactions[$txnKey])) {
                            $transactions[$txnKey] = [
                                'cashier' => $item['cashier'],
                                'sold_at' => $item['sold_at'],
                                'items' => [],
                                'total' => 0
                            ];
                        }
                        $transactions[$txnKey]['items'][] = [
                            'product' => $item['product'],
                            'quantity' => $item['quantity'],
                            'price' => $item['total']
                        ];
                        $transactions[$txnKey]['total'] += $item['total'];
                    }

                    foreach ($transactions as $txn):
                        $txnTime = date('h:i A', strtotime($txn['sold_at']));
                    ?>
                        <div class="sales-card">
                            <div class="sales-card-header">
                                <div class="sales-card-txn">Transaction</div>
                                <div class="sales-card-time"><?= $txnTime ?></div>
                            </div>

                            <div class="sales-card-cashier">👤 <?= htmlspecialchars($txn['cashier']) ?></div>

                            <?php foreach ($txn['items'] as $item): ?>
                                <div class="sales-card-item">
                                    <div class="sales-card-product"><?= htmlspecialchars($item['product']) ?></div>
                                    <div class="sales-card-qty">Qty: <?= $item['quantity'] ?></div>
                                    <div class="sales-card-price">₱<?= number_format($item['price'], 2) ?></div>
                                </div>
                            <?php endforeach; ?>

                            <div class="sales-card-total">
                                <span class="sales-card-total-label">Total</span>
                                <span class="sales-card-total-amount">₱<?= number_format($txn['total'], 2) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="total-row">
                    Total Revenue for <?= htmlspecialchars($date) ?>: <strong>₱<?= number_format($totalRevenue, 2) ?></strong>
                </div>
            <?php else: ?>
                <div class="empty">No sales found for <?= htmlspecialchars($date) ?>.</div>
            <?php endif; ?>
        </main>
    </div>
    <script src="../assets/js/theme.js"></script>
</body>

</html>