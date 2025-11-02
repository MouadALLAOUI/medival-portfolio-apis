<?php
require_once __DIR__ . '/../dashboard/inc/config_loader.php';

define('ALLOWED_ORIGINS', 'http://127.0.0.1:5501');

define('API_KEY', 'your-secure-api-key-123');


header("Access-Control-Allow-Origin: " . ALLOWED_ORIGINS);
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-KEY");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  // Handle preflight request
  http_response_code(200);
  exit();
}

define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_NAME', env('DB_NAME', 'portfolio_db'));

try {
  $pdo = new PDO(
    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
  );
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]);
  exit();
}

function respond($data, $code = 200)
{
  if (ob_get_length()) ob_clean();
  http_response_code($code);
  echo json_encode($data, JSON_PRETTY_PRINT);
  exit();
}