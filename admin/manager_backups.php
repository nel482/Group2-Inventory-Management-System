<?php
session_start();
require_once '../auth/auth.php';
$auth = new Auth();
$auth->requireRole('manager');
$user = $auth->currentUser();

require_once '../config/database.php';
require_once '../config/backup.php';
$db = (new Database())->conn;
$backupManager = new BackupManager($db);

$stats = $backupManager->getBackupStats();
$backups = $backupManager->listBackups();

$baseCSS      = file_get_contents(__DIR__ . '/../assets/css/base.css');
$dashboardCSS = file_get_contents(__DIR__ . '/../assets/css/dashboard.css');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Backups</title>
    <link rel="stylesheet" href="../assets/css/theme.css">
    <style>
        <?= $baseCSS ?>
    </style>
    <style>
        <?= $dashboardCSS ?>
    </style>
    <style>
        .content-main {
            padding: 2rem;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            gap: 1rem;
        }

        .page-header h1 {
            margin: 0;
            font-size: 2rem;
            color: var(--text-primary);
        }

        .btn-primary {
            background: #007bff;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background 0.3s;
        }

        .btn-primary:hover {
            background: #0056b3;
        }

        .btn-primary:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 2px 4px var(--shadow-light);
        }

        .stat-card .label {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        .stat-card .value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--text-primary);
        }

        .stat-card .details {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 0.5rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .alert-info {
            background: var(--info-bg);
            border: 1px solid var(--info-border);
            color: var(--info-text);
        }

        .alert-success {
            background: var(--success-bg);
            border: 1px solid var(--success-border);
            color: var(--success-text);
        }

        .alert-danger {
            background: var(--danger-bg);
            border: 1px solid var(--danger-border);
            color: var(--danger-text);
        }

        .table-container {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 2px 4px var(--shadow-light);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: var(--bg-secondary);
            border-bottom: 2px solid var(--border-color);
        }

        th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--text-primary);
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        tbody tr:hover {
            background: var(--table-hover);
        }

        .btn-small {
            padding: 0.5rem 0.75rem;
            font-size: 0.85rem;
            border: none;
            border-radius: 0.25rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-restore {
            background: #28a745;
            color: white;
        }

        .btn-restore:hover {
            background: #218838;
        }

        .btn-download {
            background: #17a2b8;
            color: white;
        }

        .btn-download:hover {
            background: #138496;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: var(--modal-bg);
            color: var(--text-primary);
            border-radius: 0.5rem;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            border: 1px solid var(--border-color);
            box-shadow: 0 10px 40px var(--shadow-medium);
        }

        .modal-header {
            margin-bottom: 1.5rem;
        }

        .modal-header h2 {
            margin: 0;
            color: var(--text-primary);
        }

        .modal-body {
            margin-bottom: 1.5rem;
            color: var(--text-primary);
        }

        .modal-body p {
            margin: 0.75rem 0;
            color: var(--text-primary);
        }

        .modal-body strong {
            color: var(--text-primary);
        }

        .modal-footer {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
        }

        .btn-cancel:hover {
            background: #5a6268;
        }

        .btn-confirm {
            background: #dc3545;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
        }

        .btn-confirm:hover {
            background: #c82333;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 1rem;
            color: #666;
        }

        .loading.active {
            display: block;
        }

        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid var(--bg-secondary);
            border-top: 3px solid #0066cc;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .no-backups {
            text-align: center;
            padding: 2rem;
            color: var(--text-muted);
        }

        .no-backups-message {
            text-align: center !important;
            padding: 2rem !important;
            color: var(--text-muted) !important;
        }

        td[colspan="4"] {
            color: var(--text-muted) !important;
        }

        .description {
            background: var(--bg-secondary);
            border-left: 4px solid #0066cc;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 0.25rem;
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
                <a class="nav-item" href="manager_staff.php">Staff Accounts</a>
                <a class="nav-item" href="manager_audit.php">Audit Logs</a>
                <div class="nav-label">System</div>
                <a class="nav-item active" href="manager_backups.php">Database Backups</a>
            </nav>
            <div class="sidebar-footer">
                <div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div>
                <div class="user-role">Manager</div>
                <a class="btn-logout" href="../auth/logout.php">Sign Out</a>
            </div>
        </aside>

        <main class="content-main">
            <div class="page-header">
                <h1>Database Backups</h1>
                <button class="btn-primary" id="createBackupBtn" onclick="createBackup()">Create Backup Now</button>
            </div>

            <div class="description">
                <strong>Data Protection & Business Continuity:</strong> Regular backups ensure rapid recovery from data loss, hardware failures, or cyberattacks. Backups enable point-in-time recovery and protect against human error. Maintain system resilience by creating regular backups and testing recovery procedures.
            </div>

            <div id="alertContainer"></div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="label">Total Backups</div>
                    <div class="value"><?= $stats['total_backups'] ?></div>
                    <div class="details">Max 10 retained</div>
                </div>
                <div class="stat-card">
                    <div class="label">Total Size</div>
                    <div class="value"><?= $stats['total_size_formatted'] ?></div>
                    <div class="details">All backups combined</div>
                </div>
                <?php if ($stats['latest_backup']): ?>
                    <div class="stat-card">
                        <div class="label">Latest Backup</div>
                        <div class="value" style="font-size: 1rem;"><?= $stats['latest_backup']['created_formatted'] ?></div>
                        <div class="details"><?= $stats['latest_backup']['filesize_formatted'] ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="table-container">
                <table id="backupTable">
                    <thead>
                        <tr>
                            <th>Backup File</th>
                            <th>Created</th>
                            <th>Size</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="backupList">
                        <?php if (empty($backups)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 2rem; color: #999;">
                                    No backups yet. Create your first backup to get started.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($backups as $backup): ?>
                                <tr>
                                    <td><?= htmlspecialchars($backup['filename']) ?></td>
                                    <td><?= $backup['created_formatted'] ?></td>
                                    <td><?= $backup['filesize_formatted'] ?></td>
                                    <td>
                                        <button class="btn-small btn-restore" onclick="confirmRestore('<?= htmlspecialchars($backup['filename']) ?>')">Restore</button>
                                        <button class="btn-small btn-download" onclick="downloadBackup('<?= htmlspecialchars($backup['filename']) ?>')">Download</button>
                                        <button class="btn-small btn-delete" onclick="confirmDelete('<?= htmlspecialchars($backup['filename']) ?>')">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Restore Confirmation Modal -->
    <div id="restoreModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Restore Database?</h2>
            </div>
            <div class="modal-body">
                <p><strong>⚠️ Warning:</strong> This will replace your current database with data from the backup file.</p>
                <p>A safety backup of your current database will be created automatically before restoration.</p>
                <p style="margin-top: 1rem;"><strong>Backup to restore:</strong> <span id="restoreFilename"></span></p>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeModal('restoreModal')">Cancel</button>
                <button class="btn-confirm" onclick="executeRestore()">Yes, Restore Database</button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Delete Backup?</h2>
            </div>
            <div class="modal-body">
                <p>This action cannot be undone. The backup file will be permanently deleted.</p>
                <p style="margin-top: 1rem;"><strong>Backup to delete:</strong> <span id="deleteFilename"></span></p>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeModal('deleteModal')">Cancel</button>
                <button class="btn-confirm" onclick="executeDelete()">Delete Backup</button>
            </div>
        </div>
    </div>

    <script>
        let pendingAction = null;
        const apiUrl = 'backup-api.php';

        function showAlert(message, type = 'info') {
            const container = document.getElementById('alertContainer');
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.innerHTML = message;
            container.appendChild(alertDiv);

            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }

        function createBackup() {
            const btn = document.getElementById('createBackupBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> Creating...';

            fetch(`${apiUrl}?action=create`, {
                    method: 'POST'
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showAlert(`✓ ${data.message}`, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showAlert(`✗ ${data.message}`, 'danger');
                    }
                })
                .catch(err => {
                    showAlert(`✗ Error: ${err.message}`, 'danger');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = 'Create Backup Now';
                });
        }

        function confirmRestore(filename) {
            pendingAction = {
                type: 'restore',
                filename
            };
            document.getElementById('restoreFilename').textContent = filename;
            document.getElementById('restoreModal').classList.add('active');
        }

        function executeRestore() {
            if (!pendingAction || pendingAction.type !== 'restore') return;

            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> Restoring...';

            const formData = new FormData();
            formData.append('action', 'restore');
            formData.append('filename', pendingAction.filename);
            formData.append('confirm', '1');

            fetch(apiUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    closeModal('restoreModal');
                    if (data.success) {
                        showAlert(`✓ ${data.message}`, 'success');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showAlert(`✗ ${data.message}`, 'danger');
                    }
                })
                .catch(err => {
                    showAlert(`✗ Error: ${err.message}`, 'danger');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = 'Yes, Restore Database';
                    pendingAction = null;
                });
        }

        function confirmDelete(filename) {
            pendingAction = {
                type: 'delete',
                filename
            };
            document.getElementById('deleteFilename').textContent = filename;
            document.getElementById('deleteModal').classList.add('active');
        }

        function executeDelete() {
            if (!pendingAction || pendingAction.type !== 'delete') return;

            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> Deleting...';

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('filename', pendingAction.filename);

            fetch(apiUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    closeModal('deleteModal');
                    if (data.success) {
                        if (window.Swal) {
                            Swal.fire('Deleted!', data.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            showAlert(`✓ ${data.message}`, 'success');
                            setTimeout(() => location.reload(), 1000);
                        }
                    } else {
                        showAlert(`✗ ${data.message}`, 'danger');
                    }
                })
                .catch(err => {
                    showAlert(`✗ Error: ${err.message}`, 'danger');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = 'Delete Backup';
                    pendingAction = null;
                });
        }

        function downloadBackup(filename) {
            window.location.href = `${apiUrl}?action=download&filename=${encodeURIComponent(filename)}`;
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
            pendingAction = null;
        }

        // Close modal when clicking outside
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('active');
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="../assets/js/theme.js"></script>
</body>

</html>