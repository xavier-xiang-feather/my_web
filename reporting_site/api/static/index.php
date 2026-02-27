<?php
// index.php - REST API for /api/static and /api/static/{id}
// Uses PDO (MySQL). Returns JSON.

declare(strict_types=1);

function send_json(int $code, $data): void {
  http_response_code($code);
  header("Content-Type: application/json; charset=utf-8");
  // CORS（如果你只用 curl/postman，其实不需要；但加了更省事）
  $origin = $_SERVER["HTTP_ORIGIN"] ?? "";
  $allowed = [
    "https://test.mrxijian.site",
    "https://mrxijian.site",
  ];
  if (in_array($origin, $allowed, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
    header("Vary: Origin");
  }
  header("Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS");
  header("Access-Control-Allow-Headers: Content-Type");
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

if (($_SERVER["REQUEST_METHOD"] ?? "") === "OPTIONS") {
  // Preflight
  send_json(204, (object)[]);
}

$config = require __DIR__ . "/db.php";
$dsn = sprintf(
  "mysql:host=%s;dbname=%s;charset=%s",
  $config["host"], $config["db"], $config["charset"]
);

try {
  $pdo = new PDO($dsn, $config["user"], $config["pass"], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
} catch (Throwable $e) {
  send_json(500, ["error" => "DB connection failed", "detail" => $e->getMessage()]);
}

// ---- Parse /api/static/{id}? ----
// Because of rewrite, this file handles everything under /api/static/
// We can use REQUEST_URI.
$uri = $_SERVER["REQUEST_URI"] ?? "";
$path = parse_url($uri, PHP_URL_PATH) ?? "";
// expected: /api/static or /api/static/123
$prefix = "/api/static";
$rest = substr($path, strlen($prefix)); // "" or "/123" or "/"
$rest = trim($rest, "/");
$id = null;
if ($rest !== "") {
  if (!ctype_digit($rest)) {
    send_json(400, ["error" => "Invalid id"]);
  }
  $id = (int)$rest;
}

// ---- Read JSON body for POST/PUT ----
function read_json_body(): array {
  $raw = file_get_contents("php://input");
  if ($raw === false || trim($raw) === "") return [];
  $data = json_decode($raw, true);
  if (!is_array($data)) {
    send_json(400, ["error" => "Invalid JSON"]);
  }
  return $data;
}



$table = "mrxijian_events"; 

$method = $_SERVER["REQUEST_METHOD"] ?? "GET";

try {
  if ($method === "GET" && $id === null) {
    // GET /api/static
    $stmt = $pdo->query("SELECT * FROM `$table` ORDER BY id DESC LIMIT 200");
    $rows = $stmt->fetchAll();
    send_json(200, $rows);
  }

  if ($method === "GET" && $id !== null) {
    // GET /api/static/{id}
    $stmt = $pdo->prepare("SELECT * FROM `$table` WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) send_json(404, ["error" => "Not found"]);
    send_json(200, $row);
  }

  if ($method === "POST") {
    // POST /api/static  (no id allowed)
    if ($id !== null) send_json(400, ["error" => "Do not include id in POST URL"]);

    $data = read_json_body();

    // 下面字段请按你自己的表改：
    $ts      = $data["ts"] ?? null;
    $ip      = $data["ip"] ?? null;
    $ua      = $data["ua"] ?? null;
    $referer = $data["referer"] ?? null;
    $origin  = $data["origin"] ?? null;
    $payload = isset($data["payload"]) ? json_encode($data["payload"], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;

    $stmt = $pdo->prepare(
      "INSERT INTO `$table` (ts, ip, ua, referer, origin, payload_json)
       VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$ts, $ip, $ua, $referer, $origin, $payload]);

    send_json(201, ["id" => (int)$pdo->lastInsertId()]);
  }

  if ($method === "PUT") {
    // PUT /api/static/{id} required
    if ($id === null) send_json(400, ["error" => "Missing id in URL"]);

    $data = read_json_body();

    // 允许部分更新（给啥改啥）
    $fields = [];
    $vals = [];

    // 下面字段请按你自己的表改：
    foreach (["ts","ip","ua","referer","origin"] as $k) {
      if (array_key_exists($k, $data)) {
        $fields[] = "`$k` = ?";
        $vals[] = $data[$k];
      }
    }
    if (array_key_exists("payload", $data)) {
      $fields[] = "`payload_json` = ?";
      $vals[] = json_encode($data["payload"], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    if (!$fields) send_json(400, ["error" => "No fields to update"]);

    $vals[] = $id;
    $sql = "UPDATE `$table` SET " . implode(", ", $fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($vals);

    send_json(200, ["ok" => true]);
  }

  if ($method === "DELETE") {
    // DELETE /api/static/{id} required
    if ($id === null) send_json(400, ["error" => "Missing id in URL"]);
    $stmt = $pdo->prepare("DELETE FROM `$table` WHERE id = ?");
    $stmt->execute([$id]);
    send_json(200, ["ok" => true]);
  }

  send_json(405, ["error" => "Method not allowed"]);
} catch (Throwable $e) {
  send_json(500, ["error" => "Server error", "detail" => $e->getMessage()]);
}