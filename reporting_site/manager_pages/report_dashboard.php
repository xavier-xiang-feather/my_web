<?php
require __DIR__ . '/../includes/auth.php';
require_login();

$reports = [

[
"id" => 1,
"name" => "Accessed Browser Report",
"category" => "browser",
"path" => "/../report_pages/browser_report.php"
],

[
"id" => 2,
"name" => "Mouse Event Report",
"category" => "behavior",
"path" => "/../report_pages/mouse_event_report.php"
],

[
"id" => 3,
"name" => "Performance Report",
"category" => "performance",
"path" => "/../report_pages/performance_report.php"
]

];

?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Report Dashboard</title>

<style>

body{
    font-family: Arial, sans-serif;
    background:#f5f7fb;
    margin:0;
}

.home{
    margin:20px;
}

.container{
    max-width:900px;
    margin:80px auto;
    background:white;
    padding:40px;
    border-radius:10px;
    box-shadow:0 4px 10px rgba(0,0,0,0.1);
}

h1{
    text-align:center;
    margin-bottom:40px;
}

table{
    width:100%;
    border-collapse:collapse;
}

th, td{
    padding:16px;
    border-bottom:1px solid #ddd;
    text-align:left;
}

th:nth-child(1){
    width:80px;
}

th:nth-child(2){
    width:50%;
}

a{
    color:#2563eb;
    text-decoration:none;
    font-weight:bold;
}

a:hover{
    text-decoration:underline;
}

button{
    padding:8px 14px;
    background:#2563eb;
    color:white;
    border:none;
    border-radius:6px;
    cursor:pointer;
}

button:hover{
    background:#1e4ed8;
}

</style>

</head>

<body>

<div class="home">
<a href="/index.php">Reporting Home Page</a>
</div>

<div class="container">

<h1>Report Dashboard</h1>

<table>

<thead>
<tr>
<th>ID</th>
<th>Report Name</th>
<th>Category</th>
<th>Export</th>
</tr>
</thead>

<tbody>

<?php foreach ($reports as $report): ?>

<tr>

<td><?= $report['id'] ?></td>

<td>
<a href="<?= $report['path'] ?>">
<?= htmlspecialchars($report['name']) ?>
</a>
</td>

<td><?= htmlspecialchars($report['category']) ?></td>

<td>
<button onclick="exportReport(<?= $report['id'] ?>)">
Export
</button>
</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>
<script>
function exportReport(reportId){
    // 1. 查找对应的报告路径
    const reports = [
        {id: 1, path: "/../report_pages/browser_report.php"},
        {id: 2, path: "/../report_pages/mouse_event_report.php"},
        {id: 3, path: "/../report_pages/performance_report.php"}
    ];
    const report = reports.find(r => r.id === reportId);
    if(!report) return;

    // 2. 创建隐藏 iframe 渲染图表
    let iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    iframe.src = report.path;
    document.body.appendChild(iframe);

    iframe.onload = function() {
        // 给一点点时间让脚本在 iframe 内执行
        setTimeout(() => {
            let chartImage = null;
            try {
                // 调用 iframe 内部刚才定义的 getChartImage 函数
                if(iframe.contentWindow.getChartImage){
                    chartImage = iframe.contentWindow.getChartImage();
                }
            } catch(e) {
                console.error("Cannot access iframe content", e);
            }

            // 3. 执行 Fetch
            fetch("/api/static/export_report.php",{
                method:"POST",
                headers:{ "Content-Type":"application/json" },
                body:JSON.stringify({
                    id:reportId,
                    chart:chartImage
                })
            })
            .then(res=>res.json())
            .then(data=>{
                if(data.status==="success"){
                    window.open(data.url);
                }else{
                    alert(data.message);
                }
                document.body.removeChild(iframe);
            })
            .catch(err => {
                alert("Export failed");
                document.body.removeChild(iframe);
            });
        }, 500);
    };
}
</script>
</body>
</html>