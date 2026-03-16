<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$dbConfig = require __DIR__ . '/../includes/db.php';

try {
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['db']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $sql = "SELECT ua FROM events";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll();

    $browserCounts = [
        'Chrome' => 0, 'Edge' => 0, 'Firefox' => 0,
        'Safari' => 0, 'Opera' => 0, 'curl' => 0, 'Other' => 0
    ];

    foreach ($rows as $row) {
        $ua = strtolower($row['ua'] ?? '');
        if (strpos($ua, 'edg') !== false) $browserCounts['Edge']++;
        elseif (strpos($ua, 'chrome') !== false && strpos($ua, 'edg') === false) $browserCounts['Chrome']++;
        elseif (strpos($ua, 'firefox') !== false) $browserCounts['Firefox']++;
        elseif (strpos($ua, 'safari') !== false && strpos($ua, 'chrome') === false) $browserCounts['Safari']++;
        elseif (strpos($ua, 'opr') !== false || strpos($ua, 'opera') !== false) $browserCounts['Opera']++;
        elseif (strpos($ua, 'curl') !== false) $browserCounts['curl']++;
        else $browserCounts['Other']++;
    }

    $labels = array_keys($browserCounts);
    $counts = array_values($browserCounts);

} catch (PDOException $e) {
    die("Database error: " . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Accessed Browser Report</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body { font-family: Arial, sans-serif; margin: 30px; background: #f8fafc; }
    .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
    .nav a { margin-right: 14px; text-decoration: none; color: #2563eb; font-weight: bold; }
    .btn-export { padding: 8px 16px; background: #059669; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; }
    .logout { padding: 8px 14px; background: #dc2626; color: white; text-decoration: none; border-radius: 6px; }
    .card { background: white; padding: 24px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); max-width: 1000px; }
  </style>
</head>
<body>

<div class="topbar">
  <div class="nav">
    <a href="/../manager_pages/report_dashboard.php">Dashboard</a>
    <button class="btn-export" onclick="exportToPDF()">Export PDF</button>
  </div>
  <a class="logout" href="/../logout.php">Log Out</a>
</div>

<div class="card">
  <h1>Accessed Browser Report</h1>
  <p>This is a distribution of how many events are recorded through each browser</p>
  <canvas id="browserChart"></canvas>
</div>

<script>
const labels = <?= json_encode($labels) ?>;
const dataCounts = <?= json_encode($counts) ?>;

const ctx = document.getElementById('browserChart').getContext('2d');
const chart = new Chart(ctx, {
  type: 'bar',
  data: {
    labels: labels,
    datasets: [{
      label: 'Counts',
      data: dataCounts,
      backgroundColor: 'rgba(37, 99, 235, 0.5)',
      borderColor: 'rgba(37, 99, 235, 1)',
      borderWidth: 1
    }]
  },
  options: {
    responsive: true,
    animation: {
        onComplete: function() {
            console.log('Chart rendered');
        }
    }
  }
});

async function exportToPDF() {
    const btn = document.querySelector('.btn-export');
    btn.innerText = 'Exporting...';
    btn.disabled = true;

    // 关键点：从 canvas 获取图片数据
    const chartImage = chart.toBase64Image();

    try {
        const response = await fetch('../export_report.php', { // 请根据实际路径调整
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: 1,
                chart: chartImage
            })
        });

        const result = await response.json();
        if (result.status === 'success') {
            window.open(result.url, '_blank');
        } else {
            alert('Export failed: ' + result.message);
        }
    } catch (error) {
        console.error(error);
        alert('An error occurred during export.');
    } finally {
        btn.innerText = 'Export PDF';
        btn.disabled = false;
    }
}
</script>
</body>
</html>