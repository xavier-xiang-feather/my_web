<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$dbConfig = require __DIR__ . '/../includes/db.php';

try {

$dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['db']};charset={$dbConfig['charset']}";
$pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$sql = "
SELECT raw_json
FROM events
WHERE raw_json LIKE '%\"kind\": \"performance\"%'
ORDER BY id DESC
LIMIT 200
";

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll();

$dns = [];
$tcp = [];
$ttfb = [];
$dom = [];
$load = [];

foreach ($rows as $row) {

$data = json_decode($row['raw_json'], true);

if(!isset($data['payload']['timing'])) continue;

$t = $data['payload']['timing'];

if(isset($t['dnsLookup'])) $dns[] = (float)$t['dnsLookup'];
if(isset($t['tcpConnect'])) $tcp[] = (float)$t['tcpConnect'];
if(isset($t['ttfb'])) $ttfb[] = (float)$t['ttfb'];
if(isset($t['domInteractive'])) $dom[] = (float)$t['domInteractive'];
if(isset($t['loadEvent'])) $load[] = (float)$t['loadEvent'];

}

/* comments */

$stmt = $pdo->prepare("
SELECT comment, author, created_at
FROM comments
WHERE report_id = 3
ORDER BY created_at DESC
LIMIT 10
");

$stmt->execute();
$comments = $stmt->fetchAll();

} catch (PDOException $e) {

die("Database error: " . htmlspecialchars($e->getMessage()));

}
?>

<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">
<title>Performance Report</title>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-chart-box-and-violin-plot"></script>

<style>

body{
font-family: Arial;
margin:30px;
background:#f8fafc;
}

.topbar{
display:flex;
justify-content:space-between;
align-items:center;
margin-bottom:40px;
}

.nav a{
margin-right:14px;
text-decoration:none;
color:#2563eb;
font-weight:bold;
}

.logout{
padding:8px 14px;
background:#dc2626;
color:white;
text-decoration:none;
border-radius:6px;
}

.card{
background:white;
padding:24px;
border-radius:10px;
box-shadow:0 4px 12px rgba(0,0,0,0.08);
max-width:1000px;
}

canvas{
margin-top:20px;
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

<h1>Performance Report</h1>

<p>This report shows the distribution of page performance metrics.</p>

<canvas id="performanceChart"></canvas>

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
    const ctx = document.getElementById('performanceChart').getContext('2d');

new Chart(ctx,{

type:'boxplot',

data:{
labels:['DNS','TCP','TTFB','DOM','LOAD'],

datasets:[{
label:'Performance Distribution',

backgroundColor:'rgba(37,99,235,0.5)',
borderColor:'rgba(37,99,235,1)',

data:[
{data: <?= json_encode($dns) ?>},
{data: <?= json_encode($tcp) ?>},
{data: <?= json_encode($ttfb) ?>},
{data: <?= json_encode($dom) ?>},
{data: <?= json_encode($load) ?>}
]

}]

},

options:{
responsive:true,
plugins:{
legend:{display:false}
}
}

});
</script>

</body>
</html>