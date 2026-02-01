<?php

date_default_timezone_set('America/Los_Angeles');

$current = date('Y-m-d H:i:s');
$ip_addr = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

$response = [
  "greeting" => "Hello, World!",
  "from" => "greeting from Xijian Xiang",
  "Generated at" => $current,
  "IP Address" => $ip_addr
];

header("Content-Type: application/json; charset=utf-8");
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
