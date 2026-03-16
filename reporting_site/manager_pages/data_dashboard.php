<?php
require __DIR__ . '/../includes/auth.php';
require_login();

if ($_SESSION['role'] === 'viewer') {
    echo "Access denied";
    exit();
}

$role = $_SESSION['role'];



$datasets = [
    [
        "id" => 1,
        "name" => "Event Logs",
        "category" => "browser/behavior/performance",
        "path" => "/../data_pages/event_data.php"
    ]
    // [
    //     "id" => 2,
    //     "name" => "Performance Data",
    //     "category" => "performance",
    //     "path" => ""
    // ]
];

//test purpose
// $role = 'superadmin';
// $role = 'analytics_browser';
// $role = 'analytics_performance';
// $role = 'analytics_behavior';
//helper
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
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Data Dashboard</title>

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

</style>

</head>

<body>

<div class="home">
<a href="/index.php">Reporting Home Page</a>
<span> | </span>
<a href="/../manager_pages/report_dashboard.php">Report Dashboard</a>
</div>


<div class="container">

<h1>Data Dashboard</h1>

<table>

<thead>
<tr>
<th>ID</th>
<th>Data Name</th>
<th>Category</th>
<th>Update Data</th>
</tr>
</thead>

<tbody>

<?php foreach ($datasets as $data): ?>

<tr>

<td><?= $data['id'] ?></td>

<td>

<?php if(canAccess($data['category'], $role)): ?>

<a href="<?= $data['path'] ?>">
<?= htmlspecialchars($data['name']) ?>
</a>

<?php else: ?>

<span style="color:gray">Access Denied</span>

<?php endif; ?>

</td>

<td><?= htmlspecialchars($data['category']) ?></td>

<td>
<?php if(canAccess($data['category'], $role)): ?>
<button onclick="updateData(<?= $data['id'] ?>)">Update</button>

<?php else: ?>
<button disabled style="background:gray; cursor:not-allowed">Update</button>

<?php endif;?>
</td>
</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>
<script>
    function updateData(dataId){

        fetch("/api/static/update_data.php",{

        method:"POST",

        headers:{
        "Content-Type":"application/json"
        },

        body:JSON.stringify({
        id:dataId
        })

        })
        .then(res=>res.text())
        .then(data=>{
        if(data==="success"){
        alert("Data updated");
        }else{
        alert(data);
        }
        });

}
</script>

</body>

</html>