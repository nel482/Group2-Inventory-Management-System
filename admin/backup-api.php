<?php
header('Content-Type: application/json');
session_start();

require_once '../auth/auth.php';
require_once '../config/database.php';
require_once '../config/backup.php';

$auth = new Auth();
$auth->requireRole('manager');

$db = (new Database())->conn;
$backupManager = new BackupManager($db);

$action = $_GET['action'] ?? $_POST['action'] ?? null;
$response = ['success' => false, 'message' => 'Invalid action'];

try {
    switch ($action) {
        case 'create':
            $response = $backupManager->createBackup();
            break;

        case 'list':
            $response = [
                'success' => true,
                'backups' => $backupManager->listBackups(),
                'stats' => $backupManager->getBackupStats()
            ];
            break;

        case 'restore':
            $filename = $_POST['filename'] ?? null;
            if (!$filename) {
                $response = ['success' => false, 'message' => 'No backup filename provided'];
                break;
            }
            // Require confirmation
            $confirm = $_POST['confirm'] ?? false;
            if (!$confirm) {
                $response = ['success' => false, 'message' => 'Restore action requires confirmation'];
                break;
            }
            $response = $backupManager->restoreBackup($filename);
            break;

        case 'delete':
            $filename = $_POST['filename'] ?? null;
            if (!$filename) {
                $response = ['success' => false, 'message' => 'No backup filename provided'];
                break;
            }
            $response = $backupManager->deleteBackup($filename);
            break;

        case 'download':
            $filename = $_GET['filename'] ?? null;
            if (!$filename) {
                $response = ['success' => false, 'message' => 'No backup filename provided'];
                break;
            }
            if ($backupManager->downloadBackup($filename)) {
                exit;
            } else {
                $response = ['success' => false, 'message' => 'Failed to download backup'];
            }
            break;

        default:
            $response = ['success' => false, 'message' => 'Unknown action'];
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($response);
