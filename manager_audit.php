<?php
session_start();
require_once 'auth.php';
$auth = new Auth();
$auth->requireRole('manager');
$user = $auth->currentUser();

require_once 'database.php';
$db = (new Database())->conn;

$logs = $db->query("
    SELECT a.id, u.full_name, u.role, a.action, a.created_at
    FROM audit_logs a
    JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC
    LIMIT 100
")->fetchAll(PDO::FETCH_ASSOC);

$baseCSS      = file_get_contents(__DIR__ . '/css/base.css');
$dashboardCSS = file_get_contents(__DIR__ . '/css/dashboard.css');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs</title>
    <style><?= $baseCSS ?></style>
    <style><?= $dashboardCSS ?></style>
    <style>
        table { width:100%; border-collapse:collapse; background:#fff; border:1px solid #e0e0e0; border-radius:6px; font-size:13px; }
        th { background:#f5f7fa; padding:10px 14px; text-align:left; font-size:12px; color:#777; border-bottom:1px solid #e0e0e0; }
        td { padding:10px 14px; border-bottom:1px solid #f5f5f5; color:#444; }
        tr:last-child td { border-bottom:none; }
        .role-badge { font-size:11px; padding:2px 8px; border-radius:10px; }
        .role-badge.manager { background:#fff8e1; color:#f39c12; }
        .role-badge.cashier { background:#e8f4fd; color:#2980b9; }
        .empty { text-align:center; padding:40px; color:#aaa; font-size:13px; }
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
            <a class="nav-item" href="manager_sales.php">Sales Reports</a>
            <a class="nav-item" href="manager_inventory.php">Inventory</a>
            <a class="nav-item" href="manager_staff.php">Staff Accounts</a>
            <a class="nav-item active" href="manager_audit.php">Audit Logs</a>
        </nav>
        <div class="sidebar-footer">
            <div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div>
            <div class="user-role">Manager</div>
            <a href="logout.php" class="btn-logout">Sign Out</a>
        </div>
    </aside>

    <main class="main">
        <div class="page-title">Audit Logs</div>
        <div class="page-subtitle">Recent activity from all users (last 100 entries)</div>

        <?php if ($logs): ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>User</th>
                    <th>Role</th>
                    <th>Action</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $i => $log): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($log['full_name']) ?></td>
                    <td><span class="role-badge <?= $log['role'] ?>"><?= ucfirst($log['role']) ?></span></td>
                    <td><?= htmlspecialchars($log['action']) ?></td>
                    <td><?= date('M d, Y h:i A', strtotime($log['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty">No audit logs yet.</div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
