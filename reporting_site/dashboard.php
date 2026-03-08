<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
</head>
<body>
  <nav>
    <a href="/reports.php">Reports</a> |
    <a href="/dashboard.php">Dashboard</a> |
    <a href="/logout.php">Logout</a>
  </nav>

  <h1>Dashboard</h1>
  <p>This is another protected page behind login.</p>
</body>
</html>