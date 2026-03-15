<?php
require __DIR__ . '/../includes/auth.php';
require_login();

if ($_SESSION['role'] === 'viewer') {
    echo "Access denied";
    exit();
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h1> This is data dashboard page. </h1>
</body>
</html>