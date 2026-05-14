<?php
session_start();
require_once '../auth/auth.php';
$auth = new Auth();
$auth->requireRole('manager');
$user = $auth->currentUser();

require_once '../config/database.php';
$db = (new Database())->conn;

$msg = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        if ($_POST['action'] === 'add') {
            $full_name = trim($_POST['full_name'] ?? '');
            $username  = trim($_POST['username'] ?? '');
            $password  = $_POST['password'] ?? '';

            if ($full_name && $username && $password) {
                $check = $db->prepare("SELECT id FROM users WHERE username = ?");
                $check->execute([$username]);
                if ($check->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'Username already exists.']);
                } else {
                    $hashed = password_hash($password, PASSWORD_BCRYPT);
                    $db->prepare("INSERT INTO users (full_name, username, password, role, RoleID) VALUES (?, ?, ?, 'cashier', 2)")
                        ->execute([$full_name, $username, $hashed]);
                    echo json_encode(['success' => true, 'message' => 'Cashier account created successfully!']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
            }
            exit;
        }

        if ($_POST['action'] === 'delete') {
            $id = intval($_POST['user_id'] ?? 0);
            if ($id !== intval($user['id'])) {
                $db->prepare("DELETE FROM users WHERE id = ? AND role = 'cashier'")->execute([$id]);
                echo json_encode(['success' => true, 'message' => 'Cashier account deleted successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'You cannot delete your own account.']);
            }
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Invalid staff action.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
    exit;
}

$staff = $db->query("SELECT id, full_name, username, role, created_at FROM users ORDER BY role, full_name")->fetchAll(PDO::FETCH_ASSOC);

$baseCSS      = file_get_contents(__DIR__ . '/../assets/css/base.css');
$dashboardCSS = file_get_contents(__DIR__ . '/../assets/css/dashboard.css');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Accounts</title>
    <link rel="stylesheet" href="../assets/css/theme.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        <?= $baseCSS ?>
    </style>
    <style>
        <?= $dashboardCSS ?>
    </style>
    <style>
        /* Staff Page Styles */
        .staff-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
        }

        .staff-header .header-left h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .staff-header .header-left p {
            font-size: 13px;
            color: var(--text-secondary);
        }

        .staff-header .btn-add {
            padding: 10px 20px;
            background: var(--sidebar-active-text);
            border: none;
            border-radius: 6px;
            color: white;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px var(--shadow-light);
        }

        .staff-header .btn-add:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px var(--shadow-medium);
        }

        /* Staff Table Styles */
        .staff-table-wrapper {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .staff-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .staff-table thead {
            background: var(--bg-secondary);
        }

        .staff-table th {
            padding: 14px;
            text-align: left;
            color: var(--text-primary);
            font-weight: 600;
            border-bottom: 1px solid var(--border-color);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .staff-table td {
            padding: 14px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .staff-table tbody tr:hover {
            background: var(--table-hover);
        }

        .staff-table tr:last-child td {
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

        /* Staff Actions */
        .staff-actions {
            display: flex;
            gap: 8px;
        }

        .btn-delete-staff {
            padding: 6px 12px;
            background: var(--danger-bg);
            border: 1.5px solid var(--danger-border);
            border-radius: 6px;
            color: var(--danger-text);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-delete-staff:hover {
            background: var(--danger-border);
            border-color: var(--danger-text);
            box-shadow: 0 4px 12px var(--shadow-medium);
        }

        .you {
            font-size: 11px;
            color: var(--text-secondary);
            margin-left: 6px;
            font-style: italic;
        }

        .empty-staff {
            text-align: center;
            padding: 48px 20px;
            color: var(--text-secondary);
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
                <a class="nav-item active" href="manager_staff.php">Staff Accounts</a>
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
            <div class="staff-header">
                <div class="header-left">
                    <h1>Staff Accounts</h1>
                    <p>Manage cashier accounts and permissions</p>
                </div>
                <button class="btn-add" onclick="openAddStaffAlert()">+ Add Cashier</button>
            </div>

            <div class="staff-table-wrapper">
                <table class="staff-table">
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
                                        <div class="staff-actions">
                                            <button class="btn-delete-staff" onclick="deleteStaffAlert(<?= $s['id'] ?>, '<?= htmlspecialchars($s['full_name']) ?>')">🗑️ Delete</button>
                                        </div>
                                    <?php else: ?>
                                        <span style="color:rgba(255,255,255,0.4);font-size:12px">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>

        <script>
            function openAddStaffAlert() {
                const config = SweetAlertTheme.getConfig();
                const html = SweetAlertTheme.getInputHTML([
                    { id: 'add_name', label: 'Full Name *', placeholder: 'Enter full name' },
                    { id: 'add_username', label: 'Username *', placeholder: 'Enter username' },
                    { id: 'add_password', label: 'Password *', placeholder: 'Enter password', type: 'password' }
                ]);

                SweetAlertTheme.fire({
                    title: 'Add New Cashier',
                    html: html,
                    confirmButtonText: 'Add Cashier',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#7c3aed',
                    showCancelButton: true,
                    didOpen: () => {
                        document.getElementById('add_name').focus();
                    }
                }).then(result => {
                    if (result.isConfirmed) {
                        submitAddStaff();
                    }
                });
            }

            function submitAddStaff() {
                const name = document.getElementById('add_name').value.trim();
                const username = document.getElementById('add_username').value.trim();
                const password = document.getElementById('add_password').value;

                if (!name || !username || !password) {
                    SweetAlertTheme.fire({ title: 'Error', text: 'Please fill in all fields.', icon: 'error' });
                    return;
                }

                const formData = new FormData();
                formData.append('action', 'add');
                formData.append('full_name', name);
                formData.append('username', username);
                formData.append('password', password);

                fetch('manager_staff.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            SweetAlertTheme.fire({ title: 'Success', text: data.message, icon: 'success' }).then(() => {
                                location.reload();
                            });
                        } else {
                            SweetAlertTheme.fire({ title: 'Error', text: data.message, icon: 'error' });
                        }
                    })
                    .catch(() => {
                        SweetAlertTheme.fire({ title: 'Error', text: 'An error occurred.', icon: 'error' });
                    });
            }

            function deleteStaffAlert(staffId, staffName) {
                SweetAlertTheme.fire({
                    title: 'Delete Cashier?',
                    text: 'Are you sure you want to delete ' + staffName + '? This action cannot be undone.',
                    icon: 'warning',
                    confirmButtonText: 'Delete',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#ef4444',
                    showCancelButton: true,
                }).then(result => {
                    if (result.isConfirmed) {
                        const formData = new FormData();
                        formData.append('action', 'delete');
                        formData.append('user_id', staffId);

                        fetch('manager_staff.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(r => r.json())
                            .then(data => {
                                if (data.success) {
                                    SweetAlertTheme.fire({ title: 'Deleted!', text: data.message, icon: 'success' }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    SweetAlertTheme.fire({ title: 'Error', text: data.message, icon: 'error' });
                                }
                            })
                            .catch(() => {
                                SweetAlertTheme.fire({ title: 'Error', text: 'An error occurred.', icon: 'error' });
                            });
                    }
                });
            }
        </script>
    </div>
    <script src="../assets/js/theme.js"></script>
</body>

</html>