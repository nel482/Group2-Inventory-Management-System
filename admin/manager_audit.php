<?php
session_start();
require_once '../auth/auth.php';
$auth = new Auth();
$auth->requireRole('manager');
$user = $auth->currentUser();

require_once '../config/database.php';
$db = (new Database())->conn;

$logs = $db->query("
    SELECT a.id, u.full_name, u.role, a.action, a.created_at
    FROM audit_logs a
    JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC
    LIMIT 100
")->fetchAll(PDO::FETCH_ASSOC);

$baseCSS      = file_get_contents(__DIR__ . '/../assets/css/base.css');
$dashboardCSS = file_get_contents(__DIR__ . '/../assets/css/dashboard.css');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs</title>
    <link rel="stylesheet" href="../assets/css/theme.css">
    <style>
        <?= $baseCSS ?>
    </style>
    <style>
        <?= $dashboardCSS ?>
    </style>
    <style>
        /* Audit Logs Styles */
        .audit-header {
            margin-bottom: 28px;
        }

        .audit-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .audit-header p {
            font-size: 13px;
            color: var(--text-secondary);
        }

        /* Audit Table Wrapper */
        .audit-table-wrapper {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
        }

        .audit-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .audit-table thead {
            background: var(--bg-secondary);
        }

        .audit-table th {
            padding: 14px;
            text-align: left;
            color: var(--text-primary);
            font-weight: 600;
            border-bottom: 1px solid var(--border-color);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .audit-table td {
            padding: 14px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-secondary);
        }

        .audit-table tbody tr:hover {
            background: var(--table-hover);
        }

        .audit-table tr:last-child td {
            border-bottom: none;
        }

        /* Role Badge */
        .role-badge {
            font-size: 11px;
            padding: 6px 12px;
            border-radius: 4px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .role-badge.manager {
            background: var(--warning-bg);
            color: var(--warning-text);
        }

        .role-badge.cashier {
            background: var(--info-bg);
            color: var(--info-text);
        }

        /* Action Badge */
        .action-badge {
            padding: 6px 12px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 12px;
            color: var(--text-primary);
            font-weight: 500;
        }

        /* Timestamp */
        .timestamp {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .empty-logs {
            text-align: center;
            padding: 48px 20px;
            color: var(--text-secondary);
        }

        .empty-logs-icon-title {
            color: #0066cc;
            margin-bottom: 8px;
        }

        html[data-theme="dark"] .empty-logs-icon-title {
            color: #64b5f6;
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
                <a class="nav-item" href="manager_sales.php">Sales Reports</a>
                <a class="nav-item" href="manager_inventory.php">Inventory</a>
                <a class="nav-item" href="manager_staff.php">Staff Accounts</a>
                <a class="nav-item active" href="manager_audit.php">Audit Logs</a>
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
            <div class="audit-header">
                <h1>Audit Logs</h1>
                <p>Activity history from all users (last 100 entries)</p>
            </div>

            <?php if ($logs): ?>
                <div class="audit-table-wrapper">
                    <table class="audit-table">
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
                                    <td><span class="action-badge"><?= htmlspecialchars($log['action']) ?></span></td>
                                    <td class="timestamp"><?= date('M d, Y • h:i A', strtotime($log['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-logs">
                    <div class="empty-logs-icon">📋</div>
                    <h3 class="empty-logs-icon-title">No Audit Logs Yet</h3>
                    <p>Activity history will appear here as users interact with the system.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <script src="../assets/js/theme.js"></script>
</body>

</html>