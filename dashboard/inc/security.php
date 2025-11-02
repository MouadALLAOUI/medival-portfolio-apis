<?php

class Security
{
  private static $instance = null;
  private $logger;
  private $csrfToken;

  private function __construct()
  {
    $this->logger = Logger::getInstance();
    $this->initializeCsrf();
  }

  public static function getInstance()
  {
    if (self::$instance === null) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  private function initializeCsrf()
  {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    $this->csrfToken = $_SESSION['csrf_token'];
  }

  public function getCsrfToken()
  {
    return $this->csrfToken;
  }

  public function getCsrfField()
  {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($this->csrfToken) . '">';
  }

  public function validateCsrfToken($token)
  {
    if (empty($token) || !hash_equals($this->csrfToken, $token)) {
      $this->logger->error("CSRF token validation failed", [
        'provided_token' => $token,
        'expected_token' => $this->csrfToken
      ]);
      return false;
    }
    return true;
  }

  public function setSecurityHeaders()
  {
    $headers = [
      'X-Frame-Options' => 'DENY',
      'X-XSS-Protection' => '1; mode=block',
      'X-Content-Type-Options' => 'nosniff',
      'Referrer-Policy' => 'strict-origin-when-cross-origin',
      'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https:; style-src 'self' 'unsafe-inline' https:; img-src 'self' data: https:; font-src 'self' https:;"
    ];

    foreach ($headers as $header => $value) {
      header("$header: $value");
    }
  }

  public function sanitizeInput($data)
  {
    if (is_array($data)) {
      return array_map([$this, 'sanitizeInput'], $data);
    }

    // Remove invisible characters
    $data = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $data);

    // Convert special characters to HTML entities
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
  }

  public function validateEmail($email)
  {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
  }

  public function validateUsername($username)
  {
    return preg_match('/^[a-zA-Z0-9_-]{3,20}$/', $username) === 1;
  }

  public function validatePassword($password)
  {
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
    return strlen($password) >= 8 &&
      preg_match('/[A-Z]/', $password) === 1 &&
      preg_match('/[a-z]/', $password) === 1 &&
      preg_match('/[0-9]/', $password) === 1;
  }

  public function validateUrl($url)
  {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
  }

  public function escapeHtml($string)
  {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
  }
}
