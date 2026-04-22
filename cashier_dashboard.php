<?php
session_start();
require_once 'auth.php';
$auth = new Auth();
$auth->requireRole('cashier');
$user = $auth->currentUser();

$baseCSS      = file_get_contents(__DIR__ . '/css/base.css');
$dashboardCSS = file_get_contents(__DIR__ . '/css/dashboard.css');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier Terminal — POS System</title>
    <!-- base.css -->
    <style>
        <?= $baseCSS ?>
    </style>
    <!-- dashboard.css -->
    <style>
        <?= $dashboardCSS ?>
    </style>
</head>

<body>

    <div class="layout">

        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                ASAJ System
                <span>Cashier Terminal</span>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-label">Cashier</div>
                <a class="nav-item active" href="#">Terminal</a>
                <a class="nav-item" href="#">New Transaction</a>
                <a class="nav-item" href="#">My Sales Today</a>

                <div class="nav-label">Restricted</div>
                <span class="nav-item disabled">Sales Reports <span class="lock">🔒</span></span>
                <span class="nav-item disabled">Inventory <span class="lock">🔒</span></span>
                <span class="nav-item disabled">Staff Accounts <span class="lock">🔒</span></span>
            </nav>

            <div class="sidebar-footer">
                <div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div>
                <div class="user-role">Cashier</div>
                <a href="logout.php" class="btn-logout">Sign Out</a>
            </div>
        </aside>

        <!-- Main -->
        <main class="main">
            <div class="page-title">Cashier Terminal</div>
            <div class="page-subtitle">Welcome, <?= htmlspecialchars($user['full_name']) ?></div>

            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-label">My Sales Today</div>
                    <div class="stat-value">₱0.00</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Transactions</div>
                    <div class="stat-value">0</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Shift Started</div>
                    <div class="stat-value" id="shift-time">--:--</div>
                </div>
            </div>

            <!-- Panels -->
            <div class="panels-row">
                <div class="panel">
                    <h2>My Access Level</h2>
                    <ul class="access-list">
                        <li>Process Transactions <span class="badge granted">Granted</span></li>
                        <li>View My Daily Sales <span class="badge granted">Granted</span></li>
                        <li>View Product List <span class="badge granted">Granted</span></li>
                        <li class="denied">All Sales Reports <span class="badge denied">Manager Only</span></li>
                        <li class="denied">Manage Inventory <span class="badge denied">Manager Only</span></li>
                        <li class="denied">Staff Management <span class="badge denied">Manager Only</span></li>
                    </ul>
                </div>

                <div class="panel">
                    <h2>Quick Actions</h2>
                    <div class="actions-grid">
                        <button class="action-btn full">
                            <span class="icon">🛒</span>New Transaction
                        </button>
                        <button class="action-btn">
                            <span class="icon">🧾</span>Reprint Receipt
                        </button>
                        <button class="action-btn">
                            <span class="icon">📋</span>My Sales Log
                        </button>
                    </div>
                </div>
            </div>
        </main>

    </div>

    <script>
        const now = new Date();
        document.getElementById('shift-time').textContent = now.toLocaleTimeString('en-PH', {
            hour: '2-digit',
            minute: '2-digit'
        });
    </script>

</body>

</html>