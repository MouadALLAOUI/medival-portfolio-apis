<?php
require_once __DIR__ . '/../../dashboard/inc/config_loader.php';

class ApiSecurity
{
  private static $instance = null;
  private $logger;
  private $rateLimits = [];
  private $rateLimitWindow = 60; // 1 minute
  private $maxRequests = 60; // 60 requests per minute

  private function __construct()
  {
    $this->logger = Logger::getInstance();
  }

  public static function getInstance()
  {
    if (self::$instance === null) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  public function enforceApiSecurity()
  {
    $this->validateApiKey();
    $this->enforceCors();
    $this->enforceRateLimit();
    $this->logApiRequest();
  }

  private function validateApiKey()
  {
    $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? null;
    $expectedKey = env('API_KEY');

    if (!$apiKey || $apiKey !== $expectedKey) {
      $this->logger->error('Invalid API key attempt', [
        'ip' => $_SERVER['REMOTE_ADDR'],
        'endpoint' => $_SERVER['REQUEST_URI']
      ]);
      $this->sendError('Invalid API key', 401);
    }
  }

  private function enforceCors()
  {
    $allowedOrigins = explode(',', env('ALLOWED_ORIGINS', '*'));
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

    if ($allowedOrigins[0] === '*') {
      header('Access-Control-Allow-Origin: *');
    } elseif (in_array($origin, $allowedOrigins)) {
      header("Access-Control-Allow-Origin: $origin");
    } else {
      $this->logger->warning('CORS violation attempt', [
        'origin' => $origin,
        'ip' => $_SERVER['REMOTE_ADDR']
      ]);
      $this->sendError('Origin not allowed', 403);
    }

    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-API-Key');
    header('Access-Control-Max-Age: 86400'); // 24 hours cache

    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
      header('HTTP/1.1 204 No Content');
      exit();
    }
  }

  private function enforceRateLimit()
  {
    $ip = $_SERVER['REMOTE_ADDR'];
    $currentTime = time();

    // Clean up old rate limit entries
    foreach ($this->rateLimits as $address => $limits) {
      if ($currentTime - $limits['timestamp'] > $this->rateLimitWindow) {
        unset($this->rateLimits[$address]);
      }
    }

    // Initialize or update rate limit for current IP
    if (!isset($this->rateLimits[$ip])) {
      $this->rateLimits[$ip] = [
        'count' => 1,
        'timestamp' => $currentTime
      ];
    } else {
      $this->rateLimits[$ip]['count']++;
    }

    // Check if rate limit exceeded
    if ($this->rateLimits[$ip]['count'] > $this->maxRequests) {
      $this->logger->warning('Rate limit exceeded', [
        'ip' => $ip,
        'requests' => $this->rateLimits[$ip]['count']
      ]);
      $this->sendError('Rate limit exceeded', 429);
    }

    // Set rate limit headers
    header('X-RateLimit-Limit: ' . $this->maxRequests);
    header('X-RateLimit-Remaining: ' . ($this->maxRequests - $this->rateLimits[$ip]['count']));
    header('X-RateLimit-Reset: ' . ($this->rateLimits[$ip]['timestamp'] + $this->rateLimitWindow));
  }

  private function logApiRequest()
  {
    $this->logger->info('API Request', [
      'method' => $_SERVER['REQUEST_METHOD'],
      'endpoint' => $_SERVER['REQUEST_URI'],
      'ip' => $_SERVER['REMOTE_ADDR'],
      'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
      'referer' => $_SERVER['HTTP_REFERER'] ?? 'Direct'
    ]);
  }

  private function sendError($message, $code)
  {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['error' => $message]);
    exit;
  }
}