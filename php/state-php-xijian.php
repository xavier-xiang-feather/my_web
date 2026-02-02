<?php
// state-php-xijian.php
session_start();
header("Content-Type: text/html; charset=utf-8");

$action = $_GET["action"] ?? "";
$sid = session_id();

function h($s) {
  return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}

function clear_session() {
  $_SESSION = [];

  if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), "", time() - 42000,
      $params["path"], $params["domain"],
      $params["secure"], $params["httponly"]
    );
  }

  session_destroy();
}

$saved = $_SESSION["name"] ?? "";

// ---- handle POST for set ----
if ($action === "set" && $_SERVER["REQUEST_METHOD"] === "POST") {
  $name = $_POST["name"] ?? "";
  $_SESSION["name"] = $name;
  $saved = $name;
}

// ---- handle clear ----
if ($action === "clear") {
  clear_session();
  // After destroy, session_id() becomes empty unless session_start again.
  // For display clarity, restart a new session automatically.
  session_start();
  $sid = session_id();
  $saved = $_SESSION["name"] ?? "";
}

?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>State Demo (PHP)</title>
</head>
<body>

<?php if ($action === "set"): ?>

  <h1>Set State (PHP)</h1>
  <form method="POST" action="?action=set">
    <label>Name:</label>
    <input type="text" name="name" placeholder="Type a name">
    <button type="submit">Save</button>
  </form>

  <p><strong>Current saved name:</strong> <?= h($saved === "" ? "(empty)" : $saved) ?></p>

  <p><a href="?action=view">View State</a></p>
  <p><a href="?action=clear">Clear State</a></p>
  <p><a href="?">Home</a></p>

  <p><strong>Session ID:</strong> <?= h($sid) ?></p>

<?php elseif ($action === "view"): ?>

  <h1>View State (PHP)</h1>
  <p><strong>Saved State:</strong></p>
  <pre><?= h($saved === "" ? "(no state saved)" : $saved) ?></pre>

  <p><a href="?action=set">Set State</a></p>
  <p><a href="?action=clear">Clear State</a></p>
  <p><a href="?">Home</a></p>

  <p><strong>Session ID:</strong> <?= h($sid) ?></p>

<?php elseif ($action === "clear"): ?>

  <h1>State Cleared (PHP)</h1>
  <p>The server-side session state has been cleared.</p>

  <p><a href="?action=set">Set State</a></p>
  <p><a href="?action=view">View State</a></p>
  <p><a href="?">Home</a></p>

  <p><strong>Session ID:</strong> <?= h($sid) ?></p>

<?php else: ?>

  <h1>PHP State Demo</h1>
  <ul>
    <li><a href="?action=set">Set State</a></li>
    <li><a href="?action=view">View State</a></li>
    <li><a href="?action=clear">Clear State</a></li>
  </ul>

  <p><strong>Session ID:</strong> <?= h($sid) ?></p>
  <p><strong>Current saved name:</strong> <?= h($saved === "" ? "(empty)" : $saved) ?></p>

<?php endif; ?>

</body>
</html>
