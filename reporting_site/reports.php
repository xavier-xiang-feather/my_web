<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reports</title>
</head>
<body>
  <h1>Reports Page</h1>
  <p>Welcome, you are logged in.</p>
  <p><a href="/logout.php">Logout</a></p>
</body>
</html>