<?php
require_once 'config.php';
require_once __DIR__ . '/middleware/api_security.php';
require_once __DIR__ . '/../dashboard/inc/db.php';
require_once __DIR__ . '/../dashboard/inc/logger.php';

// Apply API security middleware
$apiSecurity = ApiSecurity::getInstance();
$apiSecurity->enforceApiSecurity();

$logger = Logger::getInstance();

// Create visitors table if it doesn't exist
try {
  db_query("CREATE TABLE IF NOT EXISTS visitors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip VARCHAR(45) NOT NULL,
        agent VARCHAR(255) NOT NULL,
        page VARCHAR(255) NOT NULL,
        referrer VARCHAR(255) NOT NULL,
        time DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
} catch (Exception $e) {
  $logger->error('Failed to create visitors table', ['error' => $e->getMessage()]);
  respond(['status' => 'error', 'message' => 'Database initialization failed'], 500);
}

try {
  $input = json_decode(file_get_contents('php://input'), true);

  if (json_last_error() !== JSON_ERROR_NONE) {
    $logger->error('Invalid JSON input', ['error' => json_last_error_msg()]);
    respond(['status' => 'error', 'message' => 'Invalid JSON input'], 400);
  }

  if (empty($input['page'])) {
    $logger->warning('Missing page parameter in tracking request');
    respond(['status' => 'error', 'message' => 'Page parameter is required'], 400);
  }

  $visitor = [
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'page' => $input['page'],
    'referrer' => $_SERVER['HTTP_REFERER'] ?? 'unknown',
    'time' => date('Y-m-d H:i:s')
  ];

  try {
    // Start a transaction to ensure both operations succeed or fail together
    db_query("START TRANSACTION");

    // Insert visitor record
    db_query(
      "INSERT INTO visitors (ip, agent, page, referrer, time) VALUES (?, ?, ?, ?, ?)",
      [$visitor['ip'], $visitor['agent'], $visitor['page'], $visitor['referrer'], $visitor['time']]
    );

    // Update stats
    require_once __DIR__ . '/stats_manager.php';
    $statsManager = StatsManager::getInstance();
    $statsManager->updateStats($visitor);

    // Commit the transaction
    db_query("COMMIT");

    $logger->info('Visitor tracked and stats updated successfully', $visitor);
    respond([
      'status' => 'success',
      'message' => 'Visitor tracked and stats updated',
      'logged' => $visitor
    ]);
  } catch (Exception $e) {
    $logger->error('Failed to track visitor', ['error' => $e->getMessage()]);
    respond([
      'status' => 'error',
      'message' => 'Failed to track visitor'
    ], 500);
  }
} catch (Throwable $e) {

  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}