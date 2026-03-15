<?php
require __DIR__ . '/includes/auth.php';
require_login();



$username = $_SESSION['username'];
$role = $_SESSION['role'];

//test purpose:
// $username = 'test_user';
// $role = 'superadmin';

?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reporting Home</title>

<style>

body{
    font-family: Arial, sans-serif;
    background:#f5f7fb;
    margin:0;
}

/* white background */
.container{
    margin:20px;
    background:white;
    padding:40px;
    border-radius:10px;
    box-shadow:0 4px 10px rgba(0,0,0,0.1);
    min-height:90vh;
}

.topbar{
    display:flex;
    justify-content:flex-end;
    align-items:center;
    gap:15px;
}

.topbar a{
    color:#2563eb;
    text-decoration:none;
}

.header{
    text-align:center;
    margin-top:40px;
}

/* button */
.nav{
    margin-top:60px;
    display:flex;
    justify-content:center;
    gap:40px;
}

.nav button{
    padding:18px 40px;
    background:#2563eb;
    color:white;
    border:none;
    border-radius:8px;
    font-size:18px;
    cursor:pointer;
}

.nav button:hover{
    background:#1e4ed8;
}

.message{
    text-align:center;
    color:red;
    margin-top:30px;
    font-size:18px;
}

</style>

</head>
<body>

<div class="container">

<div class="topbar">
    <span><?= htmlspecialchars($username) ?></span>
    <a href="/logout.php">Logout</a>
</div>
<div class="topbar">
    <p>(Test)Role: <?= htmlspecialchars($role) ?></p>
</div>

<div class="header">
<h1>Welcome to Reporting Home Page</h1>

</div>

<div class="nav">

<button onclick="goUserManager()">User Manager</button>

<button onclick="goDataManager()">Data Manager</button>

<button onclick="goReportManager()">Report Manager</button>

</div>

<div id="msg" class="message"></div>

</div>

<script>

const role = "<?= htmlspecialchars($role) ?>";

function goUserManager(){

    if(role === "superadmin"){
        window.location.href = "/manager_pages/user_manager.php";
    }else{
        document.getElementById("msg").innerText = "Access Denied";
    }

}

function goDataManager(){

    if(role !== "viewer"){
        window.location.href = "/manager_pages/data_dashboard.php";
    }else{
        document.getElementById("msg").innerText = "Access Denied";
    }

}

function goReportManager(){

    window.location.href = "/manager_pages/report_dashboard.php";

}

</script>

</body>
</html>