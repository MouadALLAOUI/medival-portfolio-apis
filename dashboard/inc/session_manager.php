<?php
require_once __DIR__ . '/logger.php';

class SessionManager
{
  private static $instance = null;
  private $logger;
  private $config;
  private $sessionTimeout;
  private $rememberMeTimeout;

  private function __construct()
  {
    $this->logger = Logger::getInstance();
    $this->config = ConfigLoader::getInstance();
    $this->sessionTimeout = (int)env('SESSION_LIFETIME', 3600);
    $this->rememberMeTimeout = 30 * 24 * 60 * 60; // 30 days
  }

  public static function getInstance()
  {
    if (self::$instance === null) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  public function startSession()
  {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }

    if ($this->isSessionExpired()) {
      $this->logout();
      return false;
    }

    if (isset($_SESSION['user_id'])) {
      $_SESSION['last_activity'] = time();
    }

    return true;
  }

  public function login($userId, $username, $rememberMe = false)
  {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['last_activity'] = time();

    if ($rememberMe) {
      $this->setRememberMeCookie($userId);
    }

    $this->logger->info('User logged in', ['username' => $username]);
  }

  public function logout()
  {
    $username = $_SESSION['username'] ?? 'Unknown';

    // Clear session
    $_SESSION = array();

    // Clear remember-me cookie if exists
    if (isset($_COOKIE['remember_me'])) {
      $this->clearRememberMeCookie();
    }

    // Destroy session
    session_destroy();

    $this->logger->info('User logged out', ['username' => $username]);
  }

  private function isSessionExpired()
  {
    if (!isset($_SESSION['last_activity'])) {
      return true;
    }

    $inactive = time() - $_SESSION['last_activity'];
    return $inactive >= $this->sessionTimeout;
  }

  private function setRememberMeCookie($userId)
  {
    $token = bin2hex(random_bytes(32));
    $hash = password_hash($token, PASSWORD_DEFAULT);
    $expiry = time() + $this->rememberMeTimeout;

    // Store in database
    try {
      db_query(
        "INSERT INTO remember_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)",
        [$userId, $hash, date('Y-m-d H:i:s', $expiry)]
      );

      // Set cookie
      setcookie(
        'remember_me',
        $userId . ':' . $token,
        [
          'expires' => $expiry,
          'path' => '/',
          'domain' => '',
          'secure' => $this->config->isProduction(),
          'httponly' => true,
          'samesite' => 'Lax'
        ]
      );
    } catch (Exception $e) {
      $this->logger->error('Failed to set remember-me cookie', ['error' => $e->getMessage()]);
    }
  }

  private function clearRememberMeCookie()
  {
    if (isset($_COOKIE['remember_me'])) {
      list($userId, $token) = explode(':', $_COOKIE['remember_me']);

      // Remove from database
      try {
        db_query(
          "DELETE FROM remember_tokens WHERE user_id = ?",
          [$userId]
        );
      } catch (Exception $e) {
        $this->logger->error('Failed to clear remember token from database', ['error' => $e->getMessage()]);
      }

      // Clear cookie
      setcookie('remember_me', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'domain' => '',
        'secure' => $this->config->isProduction(),
        'httponly' => true,
        'samesite' => 'Lax'
      ]);
    }
  }

  public function handleRememberMe()
  {
    if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
      list($userId, $token) = explode(':', $_COOKIE['remember_me']);

      try {
        $tokenData = db_query(
          "SELECT token_hash FROM remember_tokens WHERE user_id = ? AND expires_at > NOW()",
          [$userId],
          false
        );

        if ($tokenData && password_verify($token, $tokenData['token_hash'])) {
          $user = db_query(
            "SELECT id, username FROM users WHERE id = ?",
            [$userId],
            false
          );

          if ($user) {
            $this->login($user['id'], $user['username'], true);
            return true;
          }
        }
      } catch (Exception $e) {
        $this->logger->error('Remember-me authentication failed', ['error' => $e->getMessage()]);
      }

      $this->clearRememberMeCookie();
    }

    return false;
  }
}
