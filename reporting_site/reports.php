<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reports</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 40px;
      background: #f8fafc;
    }
    .topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    a.button {
      display: inline-block;
      padding: 8px 14px;
      background: #dc2626;
      color: white;
      text-decoration: none;
      border-radius: 6px;
    }
    .card {
      margin-top: 24px;
      background: white;
      padding: 24px;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
  </style>
</head>
<body>
  <nav>
    <a href="/reportts.php">Reports</a>
    <a href="/dashboard.php">Dashboard</a>
    <a href="/logout.php">Logout</a>
  </nav>
  <div class="topbar">
    <h1>Reports Dashboard</h1>
    <a class="button" href="/logout.php">Log Out</a>
  </div>

  <div class="card">
    <p>Welcome! You are successfully logged in.</p>
    <p>This page is protected and cannot be accessed without authentication.</p>
    <p>Later, this page can contain your data table and charts for Parts 2 and 3.</p>
  </div>
</body>
</html>