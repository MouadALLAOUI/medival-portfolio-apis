<?php
require_once 'config.php';

$key = $_SERVER['HTTP_X_API_KEY'] ?? '';

if ($key !== API_KEY) {
  respond(['status' => 'error', 'message' => 'Unauthorized'], 403);
}

try {
  $stmt = $pdo->query("SELECT * FROM visitors ORDER BY time DESC LIMIT 10");
  $latest = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $totalVisits = $pdo->query("SELECT COUNT(*) FROM visitors")->fetchColumn();
  $uniqueIPs = $pdo->query("SELECT COUNT(DISTINCT ip) FROM visitors")->fetchColumn();

  $topPagesStmt = $pdo->query("SELECT page, COUNT(*) as visits FROM visitors GROUP BY page ORDER BY visits DESC LIMIT 5");
  $topPages = $topPagesStmt->fetchAll(PDO::FETCH_ASSOC);

  respond([
    'status' => 'success',
    'total_visits' => (int)$totalVisits,
    'unique_ips' => (int)$uniqueIPs,
    'latest' => $latest,
    'top_pages' => $topPages
  ]);
} catch (\Throwable $e) {
  respond([
    'status' => 'error',
    'message' => 'Failed to fetch stats: ' . $e->getMessage()
  ], 500);
}

// if (!file_exists(LOG_FILE)) {
//   respond(['status' => 'error', 'count' => 0, 'message' => 'Log file not found', 'visits' => []], 404);
// }

// $data = json_decode(file_get_contents(LOG_FILE), true);

// $totalVisits = count($data);
// $uniqueIPs = count(array_unique(array_column($data, 'ip')));

// respond([
//   'status' => 'success',
//   'total_visits' => $totalVisits,
//   'unique_ips' => $uniqueIPs,
//   'latest' => array_slice(array_reverse($data), 0, 10),
//   'visits' => $data
// ]);