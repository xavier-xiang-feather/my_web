<?php

header("Content-Type: text/html; charset=utf-8");
?>

<!DOCTYPE html>
<html>
<head>
  <title>Environment from PHP</title>
</head>
<body>
  <h1>Environment Variables</h1>
  <ul>
    <?php
      $env = $_SERVER;           // CGI-like variables + headers
      ksort($env);
      foreach ($env as $key => $value) {
        if (is_array($value)) $value = json_encode($value);
        echo "<li><strong>" . htmlspecialchars($key) . "</strong>: " . htmlspecialchars((string)$value) . "</li>";
      }
    ?>
  </ul>
</body>
</html>
