<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$dbConfig = require __DIR__ . '/../includes/db.php';

$exportMode = isset($_GET['export']);

try {

    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['db']};charset={$dbConfig['charset']}";

    $pdo = new PDO($dsn,$dbConfig['user'],$dbConfig['pass']);

    $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);

    $sql = "SELECT raw_json
            FROM events
            WHERE raw_json LIKE '%performance%'
            ORDER BY id DESC
            LIMIT 200";

    $stmt = $pdo->query($sql);

    $rows = $stmt->fetchAll();

} catch(PDOException $e){

    die("Database error: ".htmlspecialchars($e->getMessage()));

}

$metrics = [
"dnsLookup"=>[],
"tcpConnect"=>[],
"tlsHandshake"=>[],
"ttfb"=>[],
"download"=>[],
"domInteractive"=>[],
"domComplete"=>[],
"loadEvent"=>[]
];

foreach($rows as $row){

$data = json_decode($row['raw_json'],true);

if(!$data || !isset($data['payload']['timing'])) continue;

$timing = $data['payload']['timing'];

foreach($metrics as $key=>$arr){

if(isset($timing[$key])){
$metrics[$key][] = $timing[$key];
}

}

}

$avg=[];

foreach($metrics as $key=>$values){

if(count($values)>0){
$avg[$key]=array_sum($values)/count($values);
}else{
$avg[$key]=0;
}

}

/* Load comments */

$stmt=$pdo->prepare(
"SELECT comment, author, created_at
 FROM comments
 WHERE report_id = 3
 ORDER BY created_at DESC
 LIMIT 10"
);

$stmt->execute();

$comments=$stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<title>Performance Report</title>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<style>

body{
font-family: Arial, sans-serif;
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

<div class="card" id="reportContent">

<h1>Performance Report</h1>

<p>This report shows the average value of page performance metrics.</p>

<div class="chart-container">
<canvas id="performanceChart"></canvas>
</div>

<div class="comment-section">

<h2> Analyst Comments</h2>

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

const labels=[
"DNS Lookup",
"TCP Connect",
"TLS Handshake",
"TTFB",
"Download",
"DOM Interactive",
"DOM Complete",
"Load Event"
];

const values=[
<?= $avg['dnsLookup'] ?>,
<?= $avg['tcpConnect'] ?>,
<?= $avg['tlsHandshake'] ?>,
<?= $avg['ttfb'] ?>,
<?= $avg['download'] ?>,
<?= $avg['domInteractive'] ?>,
<?= $avg['domComplete'] ?>,
<?= $avg['loadEvent'] ?>
];

const ctx=document.getElementById('performanceChart').getContext('2d');

const chart=new Chart(ctx,{

type:'bar',

data:{
labels:labels,
datasets:[{
label:'Average ms',
data:values,
backgroundColor:[
'rgba(37,99,235,0.6)',
'rgba(16,185,129,0.6)',
'rgba(245,158,11,0.6)',
'rgba(239,68,68,0.6)',
'rgba(139,92,246,0.6)',
'rgba(14,165,233,0.6)',
'rgba(249,115,22,0.6)',
'rgba(99,102,241,0.6)'
]
}]
},

options:{
responsive:true,
animation:false,
plugins:{
legend:{display:false}
},
scales:{
y:{
beginAtZero:true,
title:{
display:true,
text:'Milliseconds'
}
}
}
}

});

function getChartImage(){
return document.getElementById('performanceChart').toDataURL('image/png');
}

function exportPDF(){

    const element = document.getElementById('reportContent');

    const opt = {
        margin: 0.5,
        filename: 'performance_report.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
    };

    html2pdf().set(opt).from(element).save();
}

<?php if($exportMode): ?>
window.onload = function(){
    setTimeout(()=>{
        exportPDF();
    },1000);
};
<?php endif; ?>

</script>

</body>
</html>