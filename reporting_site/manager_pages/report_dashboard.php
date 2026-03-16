<?php
require __DIR__ . '/../includes/auth.php';
require_login();

$role = $_SESSION['role'];

function canAccess($category, $role){

    if($role === 'superadmin'){
        return true;
    }

    if(str_contains($category,'browser') && $role === 'analytics_browser'){
        return true;
    }

    if(str_contains($category,'behavior') && $role === 'analytics_behavior'){
        return true;
    }

    if(str_contains($category,'performance') && $role === 'analytics_performance'){
        return true;
    }

    return false;
}

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

select{
    padding:6px;
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
<th>Chart Type</th>
<th>Comment</th>
</tr>
</thead>

<tbody>

<?php foreach ($reports as $report): ?>

<tr>

<td><?= $report['id'] ?></td>

<td>
<a href="#" onclick="openReport(<?= $report['id'] ?>,'<?= $report['path'] ?>')">
<?= htmlspecialchars($report['name']) ?>
</a>
</td>

<td><?= htmlspecialchars($report['category']) ?></td>

<td>
<button onclick="exportReport(<?= $report['id'] ?>)">
Export
</button>
</td>

<td>

<select
id="chart<?= $report['id'] ?>"
<?= canAccess($report['category'],$role) ? '' : 'disabled' ?>
>

<option value="bar" selected>Histogram</option>
<option value="pie">Pie Chart</option>

</select>

</td>

<td>

<?php if(canAccess($report['category'], $role)): ?>

<button onclick="addComment(<?= $report['id'] ?>,'<?= $report['category'] ?>')">
Add Comment
</button>

<?php else: ?>

<button disabled style="background:gray; cursor:not-allowed">
No Permission
</button>

<?php endif; ?>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

<script>

function openReport(reportId, path){

    const select = document.getElementById("chart"+reportId);

    const chartType = select.value;

    window.open(path + "?chart=" + chartType, "_blank");

}

function addComment(reportId, category){

    const comment = prompt("Enter your comment:");

    if(!comment){
        return;
    }

    fetch("/api/static/add_comment.php",{

        method:"POST",

        headers:{
            "Content-Type":"application/json"
        },

        body:JSON.stringify({
            report_id:reportId,
            category:category,
            comment:comment
        })

    })
    .then(res=>res.text())
    .then(data=>{

        if(data==="success"){
            alert("Comment saved");
        }else{
            alert(data);
        }

    });

}

function exportReport(reportId){
    const reports = [
        {id: 1, path: "/../report_pages/browser_report.php"},
        {id: 2, path: "/../report_pages/mouse_event_report.php"},
        {id: 3, path: "/../report_pages/performance_report.php"}
    ];
    const report = reports.find(r => r.id === reportId);
    if(!report) return;

    let iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    iframe.src = report.path;
    document.body.appendChild(iframe);

    iframe.onload = function() {
        setTimeout(() => {
            let chartImage = null;
            try {
                const iframeWin = iframe.contentWindow;
                if(iframeWin.getChartImage){
                    chartImage = iframeWin.getChartImage();
                }
            } catch(e) {
                console.error("Iframe access error:", e);
            }

            if(!chartImage || chartImage === "data:,"){
                alert("Failed to capture chart image. Please try again.");
                document.body.removeChild(iframe);
                return;
            }

            fetch("/api/static/export_report.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    id: reportId,
                    chart: chartImage
                })
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === "success") {
                    window.open(data.url);
                } else {
                    alert("Error: " + data.message);
                }
                document.body.removeChild(iframe);
            })
            .catch(err => {
                console.error(err);
                document.body.removeChild(iframe);
            });
        }, 1500);
    };
}

</script>

</body>
</html>