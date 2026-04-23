<?php
session_start();
require_once 'auth.php';
$auth = new Auth();
$auth->requireRole('manager');
$user = $auth->currentUser();

require_once 'database.php';
$db = (new Database())->conn;

$msg = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'add') {
        $full_name = trim($_POST['full_name']);
        $username  = trim($_POST['username']);
        $password  = $_POST['password'];

        if ($full_name && $username && $password) {
            // Check if username already exists
            $check = $db->prepare("SELECT id FROM users WHERE username = ?");
            $check->execute([$username]);
            if ($check->fetch()) {
                $msg = 'Username already exists.';
                $msgType = 'error';
            } else {
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $db->prepare("INSERT INTO users (full_name, username, password, role) VALUES (?, ?, ?, 'cashier')")
                   ->execute([$full_name, $username, $hashed]);
                $msg = 'Cashier account created.';
            }
        } else {
            $msg = 'Please fill in all fields.';
            $msgType = 'error';
        }
    }

    if ($_POST['action'] === 'delete') {
        $id = intval($_POST['user_id']);
        // Don't delete yourself
        if ($id !== intval($user['id'])) {
            $db->prepare("DELETE FROM users WHERE id = ? AND role = 'cashier'")->execute([$id]);
            $msg = 'Cashier account deleted.';
        } else {
            $msg = 'You cannot delete your own account.';
            $msgType = 'error';
        }
    }
}

$staff = $db->query("SELECT id, full_name, username, role, created_at FROM users ORDER BY role, full_name")->fetchAll(PDO::FETCH_ASSOC);

$baseCSS      = file_get_contents(__DIR__ . '/css/base.css');
$dashboardCSS = file_get_contents(__DIR__ . '/css/dashboard.css');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Accounts</title>
    <style><?= $baseCSS ?></style>
    <style><?= $dashboardCSS ?></style>
    <style>
        .msg { padding:10px 14px; border-radius:4px; margin-bottom:16px; font-size:13px; }
        .msg.success { background:#eaf7ef; border:1px solid #b2dfcc; color:#27ae60; }
        .msg.error   { background:#fff0f0; border:1px solid #f5c2c2; color:#c0392b; }
        .add-form { background:#fff; border:1px solid #e0e0e0; border-radius:6px; padding:20px; margin-bottom:24px; }
        .add-form h3 { font-size:14px; font-weight:600; margin-bottom:14px; color:#333; }
        .form-row { display:flex; gap:10px; flex-wrap:wrap; }
        .form-row input { flex:1; min-width:140px; padding:8px 10px; border:1px solid #ccc; border-radius:4px; font-size:13px; }
        .form-row button { padding:8px 18px; background:#4a90e2; color:#fff; border:none; border-radius:4px; cursor:pointer; font-size:13px; }
        table { width:100%; border-collapse:collapse; background:#fff; border:1px solid #e0e0e0; border-radius:6px; font-size:13px; }
        th { background:#f5f7fa; padding:10px 14px; text-align:left; font-size:12px; color:#777; border-bottom:1px solid #e0e0e0; }
        td { padding:10px 14px; border-bottom:1px solid #f5f5f5; color:#444; }
        tr:last-child td { border-bottom:none; }
        .role-badge { font-size:11px; padding:2px 8px; border-radius:10px; }
        .role-badge.manager { background:#fff8e1; color:#f39c12; }
        .role-badge.cashier { background:#e8f4fd; color:#2980b9; }
        .btn-del { background:none; border:none; color:#e74c3c; cursor:pointer; font-size:12px; }
        .you { font-size:11px; color:#aaa; margin-left:4px; }
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
            <a class="nav-item active" href="manager_staff.php">Staff Accounts</a>
            <a class="nav-item" href="manager_audit.php">Audit Logs</a>
        </nav>
        <div class="sidebar-footer">
            <div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div>
            <div class="user-role">Manager</div>
            <a href="logout.php" class="btn-logout">Sign Out</a>
        </div>
    </aside>

    <main class="main">
        <div class="page-title">Staff Accounts</div>
        <div class="page-subtitle">Add or remove cashier accounts</div>

        <?php if ($msg): ?>
        <div class="msg <?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <div class="add-form">
            <h3>Add New Cashier</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-row">
                    <input type="text" name="full_name" placeholder="Full name" required>
                    <input type="text" name="username" placeholder="Username" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <button type="submit">Add Cashier</button>
                </div>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($staff as $i => $s): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td>
                        <?= htmlspecialchars($s['full_name']) ?>
                        <?php if ($s['id'] == $user['id']): ?>
                        <span class="you">(you)</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($s['username']) ?></td>
                    <td><span class="role-badge <?= $s['role'] ?>"><?= ucfirst($s['role']) ?></span></td>
                    <td><?= date('M d, Y', strtotime($s['created_at'])) ?></td>
                    <td>
                        <?php if ($s['role'] === 'cashier'): ?>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Delete this account?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="user_id" value="<?= $s['id'] ?>">
                            <button type="submit" class="btn-del">Delete</button>
                        </form>
                        <?php else: ?>
                        <span style="color:#ccc;font-size:12px">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
</div>
</body>
</html>
