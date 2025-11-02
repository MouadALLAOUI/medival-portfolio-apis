<?php

class StatsManager
{
  private static $instance = null;
  private $logger;
  private $db;

  private function __construct()
  {
    $this->logger = Logger::getInstance();
    // Create stats table if it doesn't exist
    $this->initStatsTable();
  }

  public static function getInstance()
  {
    if (self::$instance === null) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  private function initStatsTable()
  {
    try {
      db_query("CREATE TABLE IF NOT EXISTS stats (
                id INT AUTO_INCREMENT PRIMARY KEY,
                date DATE NOT NULL,
                page_url VARCHAR(255) NOT NULL,
                visits INT DEFAULT 0,
                unique_visits INT DEFAULT 0,
                referrer_source VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `date_page_referrer` (`date`, `page_url`, `referrer_source`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    } catch (Exception $e) {
      $this->logger->error('Failed to create stats table', ['error' => $e->getMessage()]);
      throw $e;
    }
  }

  public function updateStats(array $visitor)
  {
    try {
      $date = date('Y-m-d', strtotime($visitor['time']));
      $page = $visitor['page'];
      $referrer = $this->categorizeReferrer($visitor['referrer']);
      $isUnique = $this->isUniqueVisit($visitor['ip'], $page, $date);

      // Use INSERT ... ON DUPLICATE KEY UPDATE to handle both insert and update cases
      db_query(
        "INSERT INTO stats (date, page_url, referrer_source, visits, unique_visits) 
                VALUES (?, ?, ?, 1, ?) 
                ON DUPLICATE KEY UPDATE 
                    visits = visits + 1,
                    unique_visits = unique_visits + ?",
        [$date, $page, $referrer, $isUnique ? 1 : 0, $isUnique ? 1 : 0]
      );

      $this->logger->info('Stats updated successfully', [
        'date' => $date,
        'page' => $page,
        'referrer' => $referrer,
        'is_unique' => $isUnique
      ]);

      return true;
    } catch (Exception $e) {
      $this->logger->error('Failed to update stats', ['error' => $e->getMessage()]);
      throw $e;
    }
  }

  private function categorizeReferrer(string $referrer): string
  {
    $referrer = strtolower($referrer);

    if ($referrer === 'unknown' || $referrer === 'direct') {
      return 'Direct';
    }

    $referrerMap = [
      'google' => 'Google',
      'bing' => 'Bing',
      'yahoo' => 'Yahoo',
      'facebook' => 'Facebook',
      'instagram' => 'Instagram',
      'twitter' => 'Twitter',
      'linkedin' => 'LinkedIn'
    ];

    foreach ($referrerMap as $key => $value) {
      if (strpos($referrer, $key) !== false) {
        return $value;
      }
    }

    return 'Other';
  }

  private function isUniqueVisit(string $ip, string $page, string $date): bool
  {
    try {
      // Check if this IP has visited this page today
      $result = db_query(
        "SELECT COUNT(*) as visit_count 
                FROM visitors 
                WHERE ip = ? 
                AND page = ? 
                AND DATE(time) = ?
                AND time < ?",
        [$ip, $page, $date, date('Y-m-d H:i:s')],
        false
      );

      return $result['visit_count'] === 0;
    } catch (Exception $e) {
      $this->logger->error('Failed to check unique visit', ['error' => $e->getMessage()]);
      // If there's an error checking uniqueness, assume it's not unique to avoid overcounting
      return false;
    }
  }
}