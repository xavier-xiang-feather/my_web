<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$dbConfig = require __DIR__ . '/../includes/db.php';

$chartType = $_GET['chart'] ?? 'bar';

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

    $stmt = $pdo->prepare(
        "SELECT comment, author, created_at
         FROM comments
         WHERE report_id = 1
         ORDER BY created_at DESC
         LIMIT 10"
    );

    $stmt->execute();
    $comments = $stmt->fetchAll();

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

.topbar {
display:flex;
justify-content:space-between;
align-items:center;
margin-bottom:40px;
}

.nav a {
margin-right:14px;
text-decoration:none;
color:#2563eb;
font-weight:bold;
}

.logout {
padding:8px 14px;
background:#dc2626;
color:white;
text-decoration:none;
border-radius:6px;
}

.card {
background:white;
padding:24px;
border-radius:10px;
box-shadow:0 4px 12px rgba(0,0,0,0.08);
max-width:1000px;
}

.chart-container{
width:100%;
max-width:900px;
}

.chart-container.pie{
max-width:630px;
}

.comment-section{
margin-top:40px;
padding-top:20px;
border-top:1px solid #ddd;
}

.comment-box{
background:#f1f5f9;
padding:16px;
border-radius:8px;
margin-top:12px;
}

.comment-meta{
margin-top:6px;
font-size:13px;
color:#666;
}

</style>
</head>

<body>

<div class="topbar">

<div class="nav">
<a href="/../manager_pages/report_dashboard.php">Dashboard</a>
</div>

<a class="logout" href="/../logout.php">Log Out</a>

</div>

<div class="card">

<h1>Accessed Browser Report</h1>

<p>This is a distribution of how many events are recorded through each browser</p>

<div class="chart-container">
<canvas id="browserChart"></canvas>
</div>

<div class="comment-section">

<h2>Recent Analyst Comments</h2>

<?php if(!empty($comments)): ?>

<?php foreach($comments as $c): ?>

<div class="comment-box">

<?= htmlspecialchars($c['comment']) ?>

<div class="comment-meta">
Comment by <?= htmlspecialchars($c['author']) ?>
|
<?= htmlspecialchars($c['created_at']) ?>
</div>

</div>

<?php endforeach; ?>

<?php else: ?>

<div class="comment-box">
No comment available yet.
</div>

<?php endif; ?>

</div>

</div>

<script>

const chartType = "<?= $chartType ?>";

if(chartType === "pie"){
document.querySelector(".chart-container").classList.add("pie");
}

const labels = <?= json_encode($labels) ?>;
const dataCounts = <?= json_encode($counts) ?>;

const ctx = document.getElementById('browserChart').getContext('2d');

const chart = new Chart(ctx, {

type: chartType,

data: {

labels: labels,

datasets: [{

label:'Counts',

data:dataCounts,

backgroundColor:[
'rgba(37,99,235,0.6)',
'rgba(16,185,129,0.6)',
'rgba(245,158,11,0.6)',
'rgba(239,68,68,0.6)',
'rgba(139,92,246,0.6)',
'rgba(14,165,233,0.6)',
'rgba(156,163,175,0.6)'
],

borderWidth:1

}]

},

options:{
responsive:true,
animation:false,
scales: chartType==='bar'
? {y:{beginAtZero:true,ticks:{precision:0}}}
: {}
}

});

function getChartImage(){
return document.getElementById('browserChart').toDataURL('image/png');
}

</script>

</body>
</html>