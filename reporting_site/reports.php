<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$dbConfig = require __DIR__ . '/db.php';

try {
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['db']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $sql = "SELECT * FROM mrxijian_events ORDER BY id DESC LIMIT 50";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reports</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 30px;
      background: #f8fafc;
    }

    .topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 24px;
    }

    .nav a {
      margin-right: 14px;
      text-decoration: none;
      color: #2563eb;
      font-weight: bold;
    }

    .logout {
      padding: 8px 14px;
      background: #dc2626;
      color: white;
      text-decoration: none;
      border-radius: 6px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      border-radius: 10px;
      overflow: hidden;
      font-size: 13px;
    }

    th, td {
      border: 1px solid #ddd;
      padding: 8px;
      text-align: left;
      vertical-align: top;
      max-width: 300px;
      word-break: break-word;
    }

    th {
      background: #e2e8f0;
    }

    tr:nth-child(even) {
      background: #f8fafc;
    }
  </style>
</head>
<body>

<div class="topbar">
  <div class="nav">
    <a href="/reports.php">Reports</a>
    <a href="/dashboard.php">Dashboard</a>
  </div>
  <a class="logout" href="/logout.php">Log Out</a>
</div>

<h1>Analytics Event Logs</h1>

<?php if (count($rows) > 0): ?>
  <table>
    <thead>
      <tr>
        <?php foreach (array_keys($rows[0]) as $column): ?>
          <th><?= htmlspecialchars($column) ?></th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $row): ?>
        <tr>
          <?php foreach ($row as $value): ?>
            <td><?= htmlspecialchars((string)$value) ?></td>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php else: ?>
  <p>No data found.</p>
<?php endif; ?>

</body>
</html>