<?php

require_once 'database.php';

class Auth
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->conn;
    }

    public function login(string $username, string $password): array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $this->log($user['id'], 'Logged in');
            return ['success' => true, 'user' => $user];
        }

        return ['success' => false, 'message' => 'Invalid username or password.'];
    }

    public function requireLogin(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['user'])) {
            header('Location: login.php');
            exit;
        }
    }

    public function requireRole(string $role): void
    {
        $this->requireLogin();
        if ($_SESSION['user']['role'] !== $role) {
            header('Location: unauthorized.php');
            exit;
        }
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!empty($_SESSION['user'])) {
            $this->log($_SESSION['user']['id'], 'Logged out');
        }
        session_destroy();
        header('Location: login.php');
        exit;
    }

    public function currentUser(): ?array
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return $_SESSION['user'] ?? null;
    }

    public function log(int $userId, string $action): void
    {
        try {
            $this->db->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, ?)")
                     ->execute([$userId, $action]);
        } catch (Exception $e) {
            // Silently fail if table not yet created
        }
    }
}
