<?php
require_once __DIR__ . '/../config.php';
require_once INC_PATH . 'db.php';

header('Content-Type: application/json');

// --- Helper Functions ---

/**
 * Fetches daily visit counts for the last N days.
 * @param int $days Number of days to look back.
 * @return array
 */
function get_daily_visits(int $days = 7): array
{
  $sql = "SELECT DATE(date) as visit_date, COUNT(*) as total_visits FROM stats WHERE date >= DATE(NOW() - INTERVAL ? DAY) GROUP BY visit_date ORDER BY date ASC";
  $results = db_query($sql, [$days]);

  // Fill in missing days with 0 visits
  $data = [];
  $today = new DateTime();
  for ($i = $days - 1; $i >= 0; $i--) {
    $date = (clone $today)->modify("-$i days")->format('Y-m-d');
    $data[$date] = 0;
  }

  foreach ($results as $row) {
    $data[$row['visit_date']] = (int)$row['total_visits'];
  }

  return [
    'labels' => array_keys($data),
    'data' => array_values($data)
  ];
}

/**
 * Fetches top pages by visit count.
 * @param int $limit Number of top pages to return.
 * @return array
 */
function get_top_pages(int $limit = 5): array
{
  $sql = "SELECT page_url, COUNT(*) as total_visits FROM stats GROUP BY page_url ORDER BY total_visits DESC LIMIT ?";
  $results = db_query($sql, [$limit]);

  return [
    'labels' => array_column($results, 'page_url'),
    'data' => array_column($results, 'total_visits')
  ];
}

/**
 * Fetches referrer breakdown.
 * @return array
 */
function get_referrers(): array
{
  $sql = "SELECT referrer_source, COUNT(*) as total_visits FROM stats GROUP BY referrer_source ORDER BY total_visits DESC";
  $results = db_query($sql, []);

  return [
    'labels' => array_column($results, 'referrer_source'),
    'data' => array_column($results, 'total_visits')
  ];
}

// --- Main API Logic ---

// Check for login (optional, but good practice for internal API)
if (!is_logged_in()) {
  http_response_code(401);
  echo json_encode(['error' => 'Unauthorized access.']);
  exit;
}

$data = [
  'daily_visits_7_days' => get_daily_visits(7),
  'daily_visits_trend' => get_daily_visits(30), // For the statistics page
  'top_pages_dashboard' => get_top_pages(5),
  'top_pages_stats' => get_top_pages(10),
  'referrers' => get_referrers(),
];

echo json_encode($data);