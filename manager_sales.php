<?php
session_start();
require_once 'auth.php';
$auth = new Auth();
$auth->requireRole('manager');
$user = $auth->currentUser();

require_once 'database.php';
$db = (new Database())->conn;

// Filter by date
$date = $_GET['date'] ?? date('Y-m-d');

$stmt = $db->prepare("
    SELECT s.id, u.full_name AS cashier, p.name AS product, s.quantity, s.total, s.sold_at
    FROM sales s
    JOIN users u ON s.cashier_id = u.id
    JOIN products p ON s.product_id = p.id
    WHERE DATE(s.sold_at) = :date
    ORDER BY s.sold_at DESC
");
$stmt->execute([':date' => $date]);
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalRevenue = array_sum(array_column($sales, 'total'));

$baseCSS      = file_get_contents(__DIR__ . '/css/base.css');
$dashboardCSS = file_get_contents(__DIR__ . '/css/dashboard.css');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Reports</title>
    <style><?= $baseCSS ?></style>
    <style><?= $dashboardCSS ?></style>
    <style>
        .toolbar { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; }
        .toolbar input[type="date"] { padding: 7px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px; }
        .toolbar button { padding: 7px 16px; background: #4a90e2; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #e0e0e0; border-radius: 6px; overflow: hidden; font-size: 13px; }
        th { background: #f5f7fa; padding: 10px 14px; text-align: left; font-size: 12px; color: #777; border-bottom: 1px solid #e0e0e0; }
        td { padding: 10px 14px; border-bottom: 1px solid #f5f5f5; color: #444; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fafbff; }
        .total-row { font-size: 13px; color: #555; margin-top: 12px; text-align: right; }
        .total-row strong { color: #222; }
        .empty { text-align: center; padding: 40px; color: #aaa; font-size: 13px; }
    </style>
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="sidebar-brand">POS System <span>Manager</span></div>
        <nav class="sidebar-nav">
            <div class="nav-label">Main</div>
            <a class="nav-item" href="manager_dashboard.php">Dashboard</a>
            <div class="nav-label">Management</div>
            <a class="nav-item active" href="manager_sales.php">Sales Reports</a>
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
        <div class="page-title">Sales Reports</div>
        <div class="page-subtitle">View all transactions by date</div>

        <form method="GET" class="toolbar">
            <label for="date" style="font-size:13px;color:#555">Date:</label>
            <input type="date" name="date" id="date" value="<?= htmlspecialchars($date) ?>">
            <button type="submit">Filter</button>
        </form>

        <?php if ($sales): ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cashier</th>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Total</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sales as $i => $row): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($row['cashier']) ?></td>
                    <td><?= htmlspecialchars($row['product']) ?></td>
                    <td><?= $row['quantity'] ?></td>
                    <td>₱<?= number_format($row['total'], 2) ?></td>
                    <td><?= date('h:i A', strtotime($row['sold_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="total-row">
            Total Revenue for <?= htmlspecialchars($date) ?>: <strong>₱<?= number_format($totalRevenue, 2) ?></strong>
        </div>
        <?php else: ?>
        <div class="empty">No sales found for <?= htmlspecialchars($date) ?>.</div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
