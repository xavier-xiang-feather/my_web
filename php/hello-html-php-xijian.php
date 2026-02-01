<?php
date_default_timezone_set('America/Los_Angeles');

$current = date('Y-m-d H:i:s');
$ip_addr = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

header("Content-Type: text/html; charset=utf-8");
?>

<!DOCTYPE html>
<html>
<head>
  <title>hello html php</title>
</head>
<body>
  <h1>Hello! Welcome to my web!</h1>
  <p>Greeting from Xijian Xiang</p>
  <p>Language: PHP</p>
  <p>Generated at <?= htmlspecialchars($current) ?></p>
  <p>IP address: <?= htmlspecialchars($ip_addr) ?></p>
</body>
</html>
