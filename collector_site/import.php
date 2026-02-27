<?php
// import.php - Read JSONL log and insert into MySQL (CSE135 Part4)
// This does NOT change Part3. It just ingests existing log into DB.

$DB_HOST = "localhost";
$DB_NAME = "cse135";
$DB_USER = "cse135";
$DB_PASS = "LYyousa520233!"; 

$LOG_PATH = "/var/www/collector.mrxijian.site/logs/collector.log"; 
$TOKEN = "xxjsld233"; 
$MAX_LINES = 2000; // optional safety cap per run

header("Content-Type: text/plain; charset=utf-8");

// ---- simple auth gate ----
$token = $_GET["token"] ?? "";
if ($token !== $TOKEN) {
  http_response_code(403);
  echo "Forbidden\n";
  exit();
}

if (!file_exists($LOG_PATH)) {
  http_response_code(500);
  echo "Log not found: $LOG_PATH\n";
  exit();
}
if (!is_readable($LOG_PATH)) {
  http_response_code(500);
  echo "Log not readable: $LOG_PATH\n";
  exit();
}

try {
  $dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4";
  $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
} catch (Exception $e) {
  http_response_code(500);
  echo "DB connect failed\n";
  exit();
}

// ---- ingest ----
$fh = fopen($LOG_PATH, "r");
if ($fh === false) {
  http_response_code(500);
  echo "Failed to open log\n";
  exit();
}

$insert = $pdo->prepare("
  INSERT IGNORE INTO events (ts, ip, ua, referer, origin, payload_json, raw_json, raw_hash)
  VALUES (:ts, :ip, :ua, :referer, :origin, :payload_json, :raw_json, :raw_hash)
");

$total = 0;
$inserted = 0;
$skipped = 0;

while (!feof($fh) && $total < $MAX_LINES) {
  $line = fgets($fh);
  if ($line === false) break;

  $line = trim($line);
  if ($line === "" || $line === "hello") { // ignore your earlier test line
    continue;
  }

  $total++;
  $raw_hash = hash("sha256", $line);

  $obj = json_decode($line, true);
  if (json_last_error() !== JSON_ERROR_NONE || !is_array($obj)) {
    // keep raw anyway; payload_json null
    $obj = [];
  }

  // ts in your log is like "2026-...Z"
  $ts = null;
  if (isset($obj["ts"]) && is_string($obj["ts"])) {
    // Convert ISO8601 Z -> MySQL DATETIME(6)
    $dt = date_create($obj["ts"]);
    if ($dt !== false) {
      $ts = $dt->format("Y-m-d H:i:s.u");
    }
  }

  $ip = $obj["ip"] ?? "";
  $ua = $obj["ua"] ?? "";
  $referer = $obj["referer"] ?? "";
  $origin = $obj["origin"] ?? "";

  $payload_json = null;
  if (array_key_exists("payload", $obj)) {
    $payload_json = json_encode($obj["payload"], JSON_UNESCAPED_UNICODE);
  }

  $insert->execute([
    ":ts" => $ts,
    ":ip" => (string)$ip,
    ":ua" => (string)$ua,
    ":referer" => (string)$referer,
    ":origin" => (string)$origin,
    ":payload_json" => $payload_json,
    ":raw_json" => $line,
    ":raw_hash" => $raw_hash,
  ]);

  if ($insert->rowCount() === 1) $inserted++;
  else $skipped++;
}

fclose($fh);

echo "Read lines (cap): $total\n";
echo "Inserted: $inserted\n";
echo "Skipped (dupe): $skipped\n";