<?php
session_start();
require_once '../auth/auth.php';
$auth = new Auth();
$auth->requireRole('manager');
$user = $auth->currentUser();

require_once '../config/database.php';
$db = (new Database())->conn;

// Query transactions table instead of sales
$totalSales     = $db->query("SELECT COALESCE(SUM(total), 0) FROM transactions WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$totalTxn       = $db->query("SELECT COUNT(*) FROM transactions WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$totalCashiers  = $db->query("SELECT COUNT(*) FROM users WHERE role = 'cashier'")->fetchColumn();
$lowStock       = $db->query("SELECT COUNT(*) FROM products WHERE stock < 10")->fetchColumn();

$baseCSS      = file_get_contents(__DIR__ . '/../assets/css/base.css');
$dashboardCSS = file_get_contents(__DIR__ . '/../assets/css/dashboard.css');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard</title>
    <link rel="stylesheet" href="../assets/css/theme.css">
    <style>
        <?= $baseCSS ?>
    </style>
    <style>
        <?= $dashboardCSS ?>
    </style>
</head>

<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="sidebar-brand">ASAJ System <span>Manager</span></div>
            <nav class="sidebar-nav">
                <div class="nav-label">Main</div>
                <a class="nav-item active" href="manager_dashboard.php">Dashboard</a>
                <div class="nav-label">Management</div>
                <a class="nav-item" href="manager_sales.php">Sales Reports</a>
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
            <div class="page-title">Dashboard</div>
            <div class="page-subtitle">Welcome back, <?= htmlspecialchars($user['full_name']) ?>!</div>

            <div class="stats-row">
                <div class="stat-card stat-card--revenue">
                    <div class="stat-label">Today's Revenue</div>
                    <div class="stat-value" id="manager-revenue">₱<?= number_format($totalSales, 2) ?></div>
                </div>
                <div class="stat-card stat-card--transactions">
                    <div class="stat-label">Transactions Today</div>
                    <div class="stat-value" id="manager-transactions"><?= $totalTxn ?></div>
                </div>
                <div class="stat-card stat-card--cashiers">
                    <div class="stat-label">Total Cashiers</div>
                    <div class="stat-value"><?= $totalCashiers ?></div>
                </div>
                <div class="stat-card stat-card--lowstock">
                    <div class="stat-label">Low Stock Items</div>
                    <div class="stat-value"><?= $lowStock ?></div>
                </div>
            </div>

            <div class="panels-row">
                <div class="panel">
                    <h2>Quick Links</h2>
                    <div class="actions-grid">
                        <a href="manager_sales.php" class="action-btn"><span class="icon">📊</span>Sales Reports</a>
                        <a href="manager_inventory.php" class="action-btn"><span class="icon">📦</span>Inventory</a>
                        <a href="manager_staff.php" class="action-btn"><span class="icon">👤</span>Staff Accounts</a>
                        <a href="manager_audit.php" class="action-btn"><span class="icon">📋</span>Audit Logs</a>
                        <a href="reset_cashier.php" class="action-btn"><span class="icon">🔄</span>Reset Cashier</a>
                    </div>
                </div>

                <div class="panel">
                    <h2>Low Stock Warning</h2>
                    <?php
                    $lowItems = $db->query("SELECT name, stock FROM products WHERE stock < 10 ORDER BY stock ASC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
                    if ($lowItems): ?>
                        <ul class="access-list">
                            <?php foreach ($lowItems as $item): ?>
                                <li>
                                    <?= htmlspecialchars($item['name']) ?>
                                    <span class="badge" style="background:rgba(239, 68, 68, 0.2);color:#ef4444"><?= $item['stock'] ?> left</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p style="color:rgba(255, 255, 255, 0.5);font-size:13px">All products have enough stock.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Real-time dashboard stats update
        const API_ENDPOINT = '../cashier/cashier-api.php';

        async function updateDashboardStats() {
            try {
                const response = await fetch(API_ENDPOINT, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=get_daily_stats'
                });
                const stats = await response.json();

                if (stats.total_sales !== undefined) {
                    const revenueEl = document.getElementById('manager-revenue');
                    const transactionEl = document.getElementById('manager-transactions');

                    if (revenueEl) {
                        revenueEl.textContent = '₱' + parseFloat(stats.total_sales).toLocaleString('en-PH', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                    }

                    if (transactionEl) {
                        transactionEl.textContent = stats.transaction_count;
                    }
                }
            } catch (error) {
                console.log('Dashboard stats update error:', error);
            }
        }

        // Update stats immediately on page load
        document.addEventListener('DOMContentLoaded', () => {
            updateDashboardStats();
            // Refresh every 1.5 seconds for immediate updates when cashiers process payments
            setInterval(updateDashboardStats, 1500);
        });
    </script>
    <script src="../assets/js/theme.js"></script>
</body>

</html>