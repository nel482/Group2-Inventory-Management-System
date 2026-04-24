<?php
session_start();
if (!empty($_SESSION['user'])) {
    $role = $_SESSION['user']['role'];
    header('Location: ' . ($role === 'manager' ? 'manager_dashboard.php' : 'cashier_dashboard.php'));
    exit;
}

require_once 'auth.php';
$auth  = new Auth();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $result = $auth->login($username, $password);
        if ($result['success']) {
            $_SESSION['user'] = $result['user'];
            $role = $result['user']['role'];
            header('Location: ' . ($role === 'manager' ? 'manager_dashboard.php' : 'cashier_dashboard.php'));
            exit;
        } else {
            $error = $result['message'];
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}

// Inline CSS from separate files
$baseCSS  = file_get_contents(__DIR__ . '/css/base.css');
$loginCSS = file_get_contents(__DIR__ . '/css/login.css');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — ASAJ System</title>
    <!-- base.css -->
    <style>
        <?= $baseCSS ?>
    </style>
    <!-- login.css -->
    <style>
        <?= $loginCSS ?>
    </style>
</head>

<body>

    <div class="login-card">
        <h1>ASAJ System</h1>
        <p class="subtitle">Sign in to your account</p>

        <?php if ($error): ?>
            <div class="error-msg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="field">
                <label for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Enter username"
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                    autocomplete="username"
                    required>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter password"
                    autocomplete="current-password"
                    required>
            </div>
            <button type="submit" class="btn-primary">Sign In</button>
        </form>
    </div>

</body>

</html>