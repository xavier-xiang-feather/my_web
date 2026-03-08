<?php
require_once __DIR__ . '/includes/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    //debug purpose
    echo 'username input: [' . htmlspecialchars($username) . ']<br>';
    echo 'password input: [' . htmlspecialchars($password) . ']<br>';
    echo 'APP_USERNAME: [' . htmlspecialchars(APP_USERNAME) . ']<br>';
    echo 'APP_PASSWORD: [' . htmlspecialchars(APP_PASSWORD) . ']<br>';

    if (
        $username === APP_USERNAME &&
        password_verify($password, APP_PASSWORD_HASH)
    ) {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;

        header('Location: /reports.php');
        exit();
    } else {
        $error = 'Invalid username or password.';
        echo 'login failed';
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - Analytics Backend</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f5f7fb;
      margin: 0;
      padding: 0;
    }
    .container {
      max-width: 400px;
      margin: 100px auto;
      background: white;
      padding: 24px;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    h1 {
      margin-top: 0;
      text-align: center;
    }
    label {
      display: block;
      margin-top: 12px;
      margin-bottom: 6px;
    }
    input {
      width: 100%;
      padding: 10px;
      box-sizing: border-box;
    }
    button {
      width: 100%;
      margin-top: 18px;
      padding: 10px;
      background: #2563eb;
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
    }
    .error {
      color: #b91c1c;
      margin-top: 12px;
      text-align: center;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Analytics Login</h1>
    <form method="POST" action="/login.php">
      <label for="username">Username</label>
      <input id="username" name="username" type="text" required>

      <label for="password">Password</label>
      <input id="password" name="password" type="password" required>

      <button type="submit">Log In</button>
    </form>

    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
  </div>
</body>
</html>