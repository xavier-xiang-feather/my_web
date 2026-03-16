<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$dbConfig = require __DIR__ . '/../includes/db.php';

try {

    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['db']};charset={$dbConfig['charset']}";

    $pdo = new PDO($dsn,$dbConfig['user'],$dbConfig['pass']);

    $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT raw_json FROM events WHERE raw_json LIKE '%performance%' ORDER BY id DESC LIMIT 200";

    $stmt = $pdo->query($sql);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

}catch(PDOException $e){

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

    if(!isset($data['payload']['timing'])) continue;

    $timing = $data['payload']['timing'];

    foreach($metrics as $key=>$arr){

        if(isset($timing[$key])){
            $metrics[$key][] = $timing[$key];
        }

    }

}

$avg = [];

foreach($metrics as $key=>$values){

    if(count($values)>0){
        $avg[$key] = array_sum($values)/count($values);
    }else{
        $avg[$key] = 0;
    }

}

?>

<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">

<title>Performance Report</title>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>

body{
font-family:Arial;
margin:30px;
background:#f5f7fb;
}

.container{
max-width:1100px;
margin:auto;
background:white;
padding:40px;
border-radius:10px;
box-shadow:0 4px 12px rgba(0,0,0,0.08);
}

.topbar{
display:flex;
justify-content:space-between;
margin-bottom:20px;
}

.topbar a{
text-decoration:none;
color:#2563eb;
font-weight:bold;
}

.logout{
padding:8px 14px;
background:#dc2626;
color:white;
border-radius:6px;
text-decoration:none;
}

canvas{
margin-top:30px;
}

.comment-box{
margin-top:40px;
padding-top:20px;
border-top:1px solid #ddd;
}

.comment{
background:#f1f5f9;
padding:12px;
margin-bottom:10px;
border-radius:6px;
}

</style>

</head>

<body>

<div class="container">

<div class="topbar">

<a href="/../manager_pages/data_dashboard.php">Dashboard</a>

<a class="logout" href="/logout.php">Log Out</a>

</div>

<h1>Performance Report</h1>

<p>This report shows the average value of page performance metrics.</p>

<canvas id="performanceChart"></canvas>

<div class="comment-box">

<h2>Recent Analyst Comments</h2>

<?php

try{

$sql = "SELECT comment FROM comments WHERE category='performance' ORDER BY id DESC LIMIT 10";

$stmt = $pdo->query($sql);

$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

if(count($comments)==0){

echo "<div class='comment'>No comment available yet.</div>";

}else{

foreach($comments as $c){
echo "<div class='comment'>".htmlspecialchars($c['comment'])."</div>";
}

}

}catch(PDOException $e){

echo "<div class='comment'>Error loading comments</div>";

}

?>

</div>

</div>

<script>

const labels = [
"DNS Lookup",
"TCP Connect",
"TLS Handshake",
"TTFB",
"Download",
"DOM Interactive",
"DOM Complete",
"Load Event"
];

const values = [
<?= $avg['dnsLookup'] ?>,
<?= $avg['tcpConnect'] ?>,
<?= $avg['tlsHandshake'] ?>,
<?= $avg['ttfb'] ?>,
<?= $avg['download'] ?>,
<?= $avg['domInteractive'] ?>,
<?= $avg['domComplete'] ?>,
<?= $avg['loadEvent'] ?>
];

const ctx = document.getElementById('performanceChart');

new Chart(ctx,{

type:'bar',

data:{
labels:labels,
datasets:[{
label:'Average ms',
data:values,
backgroundColor:[
'#3b82f6',
'#22c55e',
'#eab308',
'#ef4444',
'#8b5cf6',
'#14b8a6',
'#f97316',
'#6366f1'
]
}]
},

options:{
responsive:true,
plugins:{
legend:{
display:false
}
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

return document.getElementById("performanceChart").toDataURL("image/png");

}

</script>

</body>
</html>