<?php

class BackupManager
{
    private $db;
    private $backupDir;
    private $dbHost;
    private $dbName;
    private $dbUser;
    private $dbPass;
    private $maxBackups = 10;

    public function __construct($database = null)
    {
        if ($database) {
            $this->db = $database;
        }

        // Database credentials
        $this->dbHost = "localhost";
        $this->dbName = "inventorysystem";
        $this->dbUser = "root";
        $this->dbPass = "root";

        //backup directory
        $this->backupDir = __DIR__ . '/../assets/backups';

        // create backup directory if wala pa
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    /**
     * himo database back up
     * @return array ['success' => bool, 'message' => string, 'filename' => string]
     */
    public function createBackup()
    {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "backup_{$this->dbName}_{$timestamp}.sql";
            $filepath = $this->backupDir . DIRECTORY_SEPARATOR . $filename;

            // Use mysqldump command
            $command = sprintf(
                '"C:\\laragon\\bin\\mysql\\mysql-8.4.3-winx64\\bin\\mysqldump" --user=%s --password=%s --host=%s %s > %s 2>&1',
                escapeshellarg($this->dbUser),
                escapeshellarg($this->dbPass),
                escapeshellarg($this->dbHost),
                escapeshellarg($this->dbName),
                escapeshellarg($filepath)
            );

            exec($command, $output, $returnCode);
            $outputText = implode("\n", $output);

            // verify if naay sud ang backup
            if (!file_exists($filepath) || filesize($filepath) === 0) {
                return [
                    'success' => false,
                    'message' => 'Backup file is empty or could not be created. ' . trim($outputText)
                ];
            }

            $isWarning = ($returnCode !== 0);
            $message = $isWarning
                ? "Backup created successfully: $filename (completed with warnings)"
                : "Backup created successfully: $filename";

            // Log backup creation
            $this->logBackupActivity('CREATE', $filename, true);

            // Clean up old backups
            $this->cleanOldBackups();

            $result = [
                'success' => true,
                'message' => $message,
                'filename' => $filename,
                'filesize' => $this->formatBytes(filesize($filepath)),
                'timestamp' => $timestamp
            ];

            if ($isWarning) {
                $result['warnings'] = trim($outputText);
            }

            return $result;
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 
     * @return array List of backup files with metadata
     */
    public function listBackups()
    {
        $backups = [];

        if (!is_dir($this->backupDir)) {
            return $backups;
        }

        $files = scandir($this->backupDir, SCANDIR_SORT_DESCENDING);

        foreach ($files as $file) {
            if (strpos($file, 'backup_') === 0 && strpos($file, '.sql') !== false) {
                $filepath = $this->backupDir . DIRECTORY_SEPARATOR . $file;
                $backups[] = [
                    'filename' => $file,
                    'filesize' => filesize($filepath),
                    'filesize_formatted' => $this->formatBytes(filesize($filepath)),
                    'created' => filemtime($filepath),
                    'created_formatted' => date('Y-m-d H:i:s', filemtime($filepath))
                ];
            }
        }

        return $backups;
    }

    /**
     * Restore database from backup
     * @param string $filename - Backup filename
     * @return array ['success' => bool, 'message' => string]
     */
    public function restoreBackup($filename)
    {
        try {
            // Validate filename to prevent directory traversal
            if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
                return [
                    'success' => false,
                    'message' => 'Invalid backup filename.'
                ];
            }

            $filepath = $this->backupDir . DIRECTORY_SEPARATOR . $filename;

            if (!file_exists($filepath)) {
                return [
                    'success' => false,
                    'message' => 'Backup file not found.'
                ];
            }

            // Create a pre-restore backup first (safety measure)
            $preRestoreBackup = $this->createBackup();
            if (!$preRestoreBackup['success']) {
                return [
                    'success' => false,
                    'message' => 'Could not create safety backup before restore.'
                ];
            }

            // Restore from backup
            $command = sprintf(
                '"C:\\laragon\\bin\\mysql\\mysql-8.4.3-winx64\\bin\\mysql" --user=%s --password=%s --host=%s %s < %s',
                escapeshellarg($this->dbUser),
                escapeshellarg($this->dbPass),
                escapeshellarg($this->dbHost),
                escapeshellarg($this->dbName),
                escapeshellarg($filepath)
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                return [
                    'success' => false,
                    'message' => 'Failed to restore backup. Check backup file integrity.'
                ];
            }

            // Log restore activity
            $this->logBackupActivity('RESTORE', $filename, true);

            return [
                'success' => true,
                'message' => 'Database restored successfully from: ' . $filename,
                'safety_backup' => $preRestoreBackup['filename']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete a backup file
     * @param string $filename - Backup filename
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteBackup($filename)
    {
        try {
            // Validate filename
            if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
                return [
                    'success' => false,
                    'message' => 'Invalid backup filename.'
                ];
            }

            $filepath = $this->backupDir . DIRECTORY_SEPARATOR . $filename;

            if (!file_exists($filepath)) {
                return [
                    'success' => false,
                    'message' => 'Backup file not found.'
                ];
            }

            if (unlink($filepath)) {
                $this->logBackupActivity('DELETE', $filename, true);
                return [
                    'success' => true,
                    'message' => 'Backup deleted successfully.'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to delete backup file.'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Download a backup file
     * @param string $filename - Backup filename
     * @return bool
     */
    public function downloadBackup($filename)
    {
        // Validate filename
        if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
            return false;
        }

        $filepath = $this->backupDir . DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($filepath)) {
            return false;
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));

        readfile($filepath);
        return true;
    }

    /**
     * Get backup statistics
     * @return array Statistics about backups
     */
    public function getBackupStats()
    {
        $backups = $this->listBackups();
        $totalSize = 0;

        foreach ($backups as $backup) {
            $totalSize += $backup['filesize'];
        }

        return [
            'total_backups' => count($backups),
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize),
            'latest_backup' => !empty($backups) ? $backups[0] : null,
            'oldest_backup' => !empty($backups) ? end($backups) : null
        ];
    }

    /**
     * Clean up old backups (keep only maxBackups number)
     * @return int Number of deleted backups
     */
    private function cleanOldBackups()
    {
        $backups = $this->listBackups();
        $deleted = 0;

        if (count($backups) > $this->maxBackups) {
            // Sort by creation date (oldest first)
            usort($backups, function ($a, $b) {
                return $a['created'] - $b['created'];
            });

            // Delete excess backups
            $toDelete = count($backups) - $this->maxBackups;
            for ($i = 0; $i < $toDelete; $i++) {
                $filepath = $this->backupDir . DIRECTORY_SEPARATOR . $backups[$i]['filename'];
                if (unlink($filepath)) {
                    $deleted++;
                    $this->logBackupActivity('AUTO-DELETE', $backups[$i]['filename'], true);
                }
            }
        }

        return $deleted;
    }

    /**
     * Log backup activity
     * @param string $action - Action type (CREATE, RESTORE, DELETE, AUTO-DELETE)
     * @param string $filename - Backup filename
     * @param bool $success - Whether action was successful
     */
    private function logBackupActivity($action, $filename, $success = true)
    {
        if (!$this->db) {
            return;
        }

        try {
            $userId = $_SESSION['user_id'] ?? null;
            $username = $_SESSION['username'] ?? 'system';

            $stmt = $this->db->prepare("
                INSERT INTO audit_logs (user_id, username, action, details, status, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $userId,
                $username,
                'BACKUP_' . $action,
                json_encode(['filename' => $filename]),
                $success ? 'SUCCESS' : 'FAILED'
            ]);
        } catch (Exception $e) {
            // Silent fail - don't block backup if logging fails
        }
    }

    /**
     * Format bytes to human readable format
     * @param int $bytes
     * @return string
     */
    private function formatBytes($bytes)
    {
        $sizes = ['B', 'KB', 'MB', 'GB'];
        if ($bytes == 0) return 0 . ' ' . $sizes[0];
        $i = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . $sizes[$i];
    }
}