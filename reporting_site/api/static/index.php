<?php
// index.php - REST API for /api/static and /api/static/{id}
// Uses PDO (MySQL). Returns JSON.

declare(strict_types=1);

/**
 * Send JSON response and exit.
 */
function send_json(int $code, $data): void {
  http_response_code($code);
  header("Content-Type: application/json; charset=utf-8");

  // CORS (safe + simple)
  $origin = $_SERVER["HTTP_ORIGIN"] ?? "";
  $allowed = [
    "https://test.mrxijian.site",
    "https://mrxijian.site",
  ];
  if ($origin !== "" && in_array($origin, $allowed, true)) {
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

/**
 * Read JSON body into associative array.
 */
function read_json_body(): array {
  $raw = file_get_contents("php://input");
  if ($raw === false || trim($raw) === "") return [];
  $data = json_decode($raw, true);
  if (!is_array($data)) {
    send_json(400, ["error" => "Invalid JSON"]);
  }
  return $data;
}

/**
 * Convert incoming ISO8601 timestamps to MySQL DATETIME format.
 * Examples:
 *  - 2026-02-27T00:00:00Z      -> 2026-02-27 00:00:00
 *  - 2026-02-27T00:00:00.123Z  -> 2026-02-27 00:00:00.123
 *  - 2026-02-27 00:00:00       -> unchanged
 * Returns null if empty.
 */
function normalize_ts($ts): ?string {
  if ($ts === null) return null;
  if (!is_string($ts)) return null;
  $ts = trim($ts);
  if ($ts === "") return null;

  // Replace 'T' with space
  $ts = str_replace("T", " ", $ts);
  // Drop trailing 'Z'
  if (str_ends_with($ts, "Z")) {
    $ts = substr($ts, 0, -1);
  }
  // If there is timezone offset like +00:00, remove it
  // (simple handling; MySQL DATETIME doesn't store timezone)
  $ts = preg_replace('/([+-]\d{2}:\d{2})$/', '', $ts);

  // Keep up to microseconds if present (MySQL DATETIME(6) supports it)
  // Ensure there is a space between date and time already.
  return trim($ts);
}

/**
 * Parse /api/static/{id}? from REQUEST_URI
 * Handle both /api/static and /api/static/ paths cleanly.
 */
$uri  = $_SERVER["REQUEST_URI"] ?? "";
$path = parse_url($uri, PHP_URL_PATH) ?? "";

// Normalize: remove trailing slash only for routing parsing
$path_no_trailing = rtrim($path, "/");

// It might be /api/static or /api/static/123 (or with trailing slash).
$prefix = "/api/static";

if (strncmp($path_no_trailing, $prefix, strlen($prefix)) !== 0) {
  // If rewrite misrouted or called directly in unexpected way:
  send_json(404, ["error" => "Not found"]);
}

$rest = substr($path_no_trailing, strlen($prefix)); // "" or "/123"
$rest = trim($rest, "/");
$id = null;

if ($rest !== "") {
  if (!ctype_digit($rest)) {
    send_json(400, ["error" => "Invalid id"]);
  }
  $id = (int)$rest;
}

// ---- DB connect ----
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

// ---- Config ----
$table = "mrxijian_events";   // table name in DB
$method = $_SERVER["REQUEST_METHOD"] ?? "GET";

try {
  // GET /api/static
  if ($method === "GET" && $id === null) {
    $stmt = $pdo->query("SELECT * FROM `$table` ORDER BY id DESC LIMIT 200");
    $rows = $stmt->fetchAll();
    send_json(200, $rows);
  }

  // GET /api/static/{id}
  if ($method === "GET" && $id !== null) {
    $stmt = $pdo->prepare("SELECT * FROM `$table` WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) send_json(404, ["error" => "Not found"]);
    send_json(200, $row);
  }

  // POST /api/static
  if ($method === "POST") {
    if ($id !== null) send_json(400, ["error" => "Do not include id in POST URL"]);

    $data = read_json_body();

    $ts      = normalize_ts($data["ts"] ?? null);
    $ip      = $data["ip"] ?? null;
    $ua      = $data["ua"] ?? null;
    $referer = $data["referer"] ?? null;
    $origin  = $data["origin"] ?? null;

    // Keep as JSON string in payload_json (your current schema)
    $payload_json = array_key_exists("payload", $data)
      ? json_encode($data["payload"], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
      : null;

    // Insert (schema expects payload_json)
    $stmt = $pdo->prepare(
      "INSERT INTO `$table` (ts, ip, ua, referer, origin, payload_json)
       VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$ts, $ip, $ua, $referer, $origin, $payload_json]);

    send_json(201, ["id" => (int)$pdo->lastInsertId()]);
  }

  // PUT /api/static/{id}
  if ($method === "PUT") {
    if ($id === null) send_json(400, ["error" => "Missing id in URL"]);

    $data = read_json_body();

    $fields = [];
    $vals = [];

    if (array_key_exists("ts", $data)) {
      $fields[] = "`ts` = ?";
      $vals[] = normalize_ts($data["ts"]);
    }

    foreach (["ip","ua","referer","origin"] as $k) {
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

  // DELETE /api/static/{id}
  if ($method === "DELETE") {
    if ($id === null) send_json(400, ["error" => "Missing id in URL"]);
    $stmt = $pdo->prepare("DELETE FROM `$table` WHERE id = ?");
    $stmt->execute([$id]);
    send_json(200, ["ok" => true]);
  }

  send_json(405, ["error" => "Method not allowed"]);
} catch (Throwable $e) {
  send_json(500, ["error" => "Server error", "detail" => $e->getMessage()]);
}