<?php
require_once 'dashboard/config.php';
require_once INC_PATH . 'db.php';
require_once INC_PATH . 'security.php';
require_once INC_PATH . 'session_manager.php';

$security = Security::getInstance();
$logger = Logger::getInstance();

// Set security headers
$security->setSecurityHeaders();

// Check if already logged in
if (is_logged_in()) {
  header('Location: dashboard/index.php');
  exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Validate CSRF token
  if (!$security->validateCsrfToken($_POST['csrf_token'] ?? '')) {
    $error = 'Invalid request token. Please try again.';
    $logger->error('CSRF validation failed during login attempt');
  } else {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
      $error = 'Please enter both username and password.';
    } else if (!$security->validateUsername($username)) {
      $error = 'Invalid username format.';
      $logger->warning('Invalid username format attempt', ['username' => $username]);
    } else {
      try {
        $user = db_query("SELECT id, username, password_hash FROM users WHERE username = ?", [$username], false);

        if ($user && password_verify($password, $user['password_hash'])) {
          // Initialize session manager and handle login
          $sessionManager = SessionManager::getInstance();
          $rememberMe = isset($_POST['remember_me']) && $_POST['remember_me'] === '1';
          $sessionManager->login($user['id'], $user['username'], $rememberMe);

          $logger->info('Successful login', [
            'username' => $username,
            'remember_me' => $rememberMe
          ]);
          header('Location: dashboard/index.php');
          exit;
        } else {
          $error = 'Invalid username or password.';
          $logger->warning('Failed login attempt', ['username' => $username]);
        }
      } catch (Exception $e) {
        $logger->error('Login error', ['error' => $e->getMessage()]);
        $error = 'An error occurred during login. Please try again.';
      }
    }
  }
}

// Minimal HTML for login page, no full template needed
$site_name = SITE_NAME;
$css_path = 'dashboard/assets/css/style.css';

echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | {$site_name}</title>
    <link rel="stylesheet" href="./dashboard/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-box">
            <h2>{$site_name}</h2>
            <h3>Admin Login</h3>
            <form method="POST" action="index.php">
                {$security->getCsrfField()}
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required pattern="[a-zA-Z0-9_-]{3,20}" title="Username must be 3-20 characters long and may include letters, numbers, underscores, and hyphens">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required minlength="8">
HTML;

if ($error) {
  echo '<p class="error-message">' . htmlspecialchars($error) . '</p>';
}

echo <<<HTML
                <button type="submit" class="btn-primary">Login</button>
            </form>
            <p class="default-creds">Default: admin/password123 (Change immediately)</p>
        </div>
    </div>
</body>
</html>
HTML;