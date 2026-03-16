<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$dbConfig = require __DIR__ . '/../includes/db.php';

$chartType = $_GET['chart'] ?? 'bar';

try{

$dsn="mysql:host={$dbConfig['host']};dbname={$dbConfig['db']};charset={$dbConfig['charset']}";
$pdo=new PDO($dsn,$dbConfig['user'],$dbConfig['pass']);

$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);

/* -------- mouse behavior statistics -------- */

$sql = "
SELECT
payload_json->>'$.activity.type' AS event_type,
COUNT(*) AS count
FROM events
WHERE payload_json->>'$.activity.type' IS NOT NULL
GROUP BY event_type
";

$stmt=$pdo->query($sql);
$rows=$stmt->fetchAll();

$labels=[];
$counts=[];

foreach($rows as $r){

$labels[]=$r['event_type'];
$counts[]=$r['count'];

}

/* -------- comments -------- */

$stmt=$pdo->prepare("
SELECT comment,author,created_at
FROM comments
WHERE report_id=2
ORDER BY created_at DESC
LIMIT 10
");

$stmt->execute();
$comments=$stmt->fetchAll();

}catch(PDOException $e){

die("Database error: ".htmlspecialchars($e->getMessage()));

}

?>

<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">
<title>Mouse Behavior Report</title>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>

body{
font-family:Arial,sans-serif;
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

<h1>Mouse Behavior Report</h1>

<p>This report summarizes mouse and interaction events recorded on the site.</p>

<div class="chart-container">
<canvas id="mouseChart"></canvas>
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

const chartType="<?= $chartType ?>";

if(chartType==="pie"){
document.querySelector(".chart-container").classList.add("pie");
}

const labels=<?= json_encode($labels) ?>;
const counts=<?= json_encode($counts) ?>;

const ctx=document.getElementById("mouseChart").getContext("2d");

new Chart(ctx,{

type:chartType,

data:{

labels:labels,

datasets:[{

label:"Mouse Events",

data:counts,

backgroundColor:[
"rgba(37,99,235,0.6)",
"rgba(16,185,129,0.6)",
"rgba(245,158,11,0.6)",
"rgba(239,68,68,0.6)",
"rgba(139,92,246,0.6)"
]

}]

},

options:{
responsive:true,
animation:false,
scales: chartType==="bar"
? {y:{beginAtZero:true,ticks:{precision:0}}}
: {}
}

});

</script>

</body>
</html>