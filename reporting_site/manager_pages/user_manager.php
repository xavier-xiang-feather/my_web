<?php
require __DIR__ . '/../includes/auth.php';
require_login();

//check role
if ($_SESSION['role'] !== 'superadmin') {
    echo "Access denied";
    exit();
}

//connect db
require __DIR__ . '/../includes/connect_db.php';

// //read users
$stmt = $pdo->query("SELECT id, username, role FROM users ORDER BY id");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);


//test
// $users = [
//     ['id' => 1, 'username' => 'admin', 'role' => 'superadmin'],
//     ['id' => 2, 'username' => 'sam', 'role' => 'analytics'],
//     ['id' => 3, 'username' => 'bob', 'role' => 'viewer'],
//     ['id' => 4, 'username' => 'alice', 'role' => 'analytics']
// ];

?>


<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>User Manager</title>

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
    table-layout:fixed;
}

th, td{
    padding:16px;
    border-bottom:1px solid #ddd;
    text-align:left;
}

th:nth-child(1),
td:nth-child(1){
    width:80px;
}

th:nth-child(2),
td:nth-child(2){
    width:50%;
}

th:nth-child(3),
td:nth-child(3){
    text-align:right;
}

/* dropdown统一大小 */

select{
    width:160px;
    padding:6px;
}

</style>
</head>

<body>

<div class="home">
<a href="/index.php">Reporting Home Page</a>
</div>

<div class="container">

<h1>User Manager Dashboard</h1>

<table>

<thead>
<tr>
<th>ID</th>
<th>Username</th>
<th>Role</th>
</tr>
</thead>

<tbody>

<?php foreach ($users as $user): ?>

<tr>

<td><?= htmlspecialchars($user['id']) ?></td>

<td><?= htmlspecialchars($user['username']) ?></td>

<td>

<?php if ($user['id'] == 1): ?>

<select disabled>
<option selected>superadmin</option>
</select>

<?php else: ?>

<select onchange="updateRole(<?= $user['id'] ?>, this.value)">

<option value="superadmin" <?= $user['role']=='superadmin'?'selected':'' ?>>superadmin</option>

<option value="analytics" <?= $user['role']=='analytics'?'selected':'' ?>>analytics</option>

<option value="viewer" <?= $user['role']=='viewer'?'selected':'' ?>>viewer</option>

</select>

<?php endif; ?>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

<script>

function updateRole(userId, newRole){

fetch("/api/static/update_role.php",{

method:"POST",

headers:{
"Content-Type":"application/json"
},

body:JSON.stringify({
id:userId,
role:newRole
})

})
.then(res=>res.text())
.then(data => {
    alert("Role updated");
});
}

</script>

</body>
</html>