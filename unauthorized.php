<?php
session_start();
$baseCSS      = file_get_contents(__DIR__ . '/css/base.css');
$dashboardCSS = file_get_contents(__DIR__ . '/css/dashboard.css');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized — POS System</title>
    <!-- base.css -->
    <style><?= $baseCSS ?></style>
    <!-- dashboard.css -->
    <style><?= $dashboardCSS ?></style>
</head>
<body>

<div class="unauth-wrap">
    <div class="unauth-box">
        <div class="code">403</div>
        <h2>Access Denied</h2>
        <p>You don't have permission to view this page. Please contact your manager if you think this is a mistake.</p>
        <a href="login.php" class="btn-back">Back to Login</a>
    </div>
</div>

</body>
</html>
