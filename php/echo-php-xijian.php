<?php

date_default_timezone_set('America/Los_Angeles');

function html_escape($s) {
  return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
$method = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
$query_string = $_SERVER['QUERY_STRING'] ?? '';
$content_type = $_SERVER['CONTENT_TYPE'] ?? '';
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
$current = date('Y-m-d H:i:s');

$body = '';
$parsed = [];
$body_mode = 'n/a';

if ($method === 'GET') {
  $parsed = $_GET;
  $body = '';
  $body_mode = 'n/a';
} elseif (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
  $body = file_get_contents('php://input') ?: '';

  if (stripos($content_type, 'application/json') !== false) {
    $decoded = json_decode($body, true);
    if (json_last_error() === JSON_ERROR_NONE) {
      $parsed = $decoded;
      $body_mode = 'json';
    } else {
      $parsed = ['error' => 'Invalid JSON', 'detail' => json_last_error_msg()];
      $body_mode = 'json_error';
    }
  } else {
    // treat as x-www-form-urlencoded by default
    parse_str($body, $parsed);
    $body_mode = ($body === '') ? 'empty' : 'form';
  }
} else {
  $body = '';
  $parsed = [];
  $body_mode = 'unsupported';
}

header("Content-Type: text/html; charset=utf-8");
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>General Request Echo (PHP)</title>
</head>
<body>
  <h1 style="text-align:center;">General Request Echo</h1>
  <hr>

  <p><strong>HTTP Protocol:</strong> <?= html_escape($protocol) ?></p>
  <p><strong>HTTP Method:</strong> <?= html_escape($method) ?></p>

  <p><strong>Query String:</strong></p>
  <pre><?= html_escape($query_string) ?></pre>

  <p><strong>Message Body:</strong></p>
  <pre><?= html_escape($body) ?></pre>

  <p><strong>Parsed Data:</strong> (mode: <?= html_escape($body_mode) ?>)</p>
  <pre><?= html_escape(json_encode($parsed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre>

  <hr>
  <p><strong>Time:</strong> <?= html_escape($current) ?></p>
  <p><strong>IP Address:</strong> <?= html_escape($ip) ?></p>
  <p><strong>User-Agent:</strong> <?= html_escape($user_agent) ?></p>
</body>
</html>
