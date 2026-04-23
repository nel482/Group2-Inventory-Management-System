<?php
session_start();
require_once 'auth.php';
$auth = new Auth();
$auth->requireRole('manager');
$user = $auth->currentUser();

require_once 'database.php';
$db = (new Database())->conn;

// Get counts for stats
$totalSales     = $db->query("SELECT COALESCE(SUM(total), 0) FROM sales WHERE DATE(sold_at) = CURDATE()")->fetchColumn();
$totalTxn       = $db->query("SELECT COUNT(*) FROM sales WHERE DATE(sold_at) = CURDATE()")->fetchColumn();
$totalCashiers  = $db->query("SELECT COUNT(*) FROM users WHERE role = 'cashier'")->fetchColumn();
$lowStock       = $db->query("SELECT COUNT(*) FROM products WHERE stock < 10")->fetchColumn();

$baseCSS      = file_get_contents(__DIR__ . '/css/base.css');
$dashboardCSS = file_get_contents(__DIR__ . '/css/dashboard.css');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard</title>
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
            </nav>
            <div class="sidebar-footer">
                <div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div>
                <div class="user-role">Manager</div>
                <a href="logout.php" class="btn-logout">Sign Out</a>
            </div>
        </aside>

        <main class="main">
            <div class="page-title">Dashboard</div>
            <div class="page-subtitle">Welcome back, <?= htmlspecialchars($user['full_name']) ?>!</div>

            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-label">Today's Revenue</div>
                    <div class="stat-value">₱<?= number_format($totalSales, 2) ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Transactions Today</div>
                    <div class="stat-value"><?= $totalTxn ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Cashiers</div>
                    <div class="stat-value"><?= $totalCashiers ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Low Stock Items</div>
                    <div class="stat-value" style="color:#e74c3c"><?= $lowStock ?></div>
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
                                    <span class="badge" style="background:#fff0f0;color:#e74c3c"><?= $item['stock'] ?> left</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p style="color:#999;font-size:13px">All products have enough stock.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>

</html>