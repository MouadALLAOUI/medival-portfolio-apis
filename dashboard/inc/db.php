<?php

if (!defined('DB_HOST')) {
  require_once __DIR__ . '/../config.php';
}

require_once __DIR__ . '/logger.php';

class Database
{
  private static $instance = null;
  private $pdo = null;
  private $logger;
  private $maxRetries = 3;
  private $retryDelay = 1; // seconds

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

  public function getConnection()
  {
    if ($this->pdo === null) {
      $this->connect();
    }
    return $this->pdo;
  }

  private function connect()
  {
    $retries = 0;
    $lastException = null;

    while ($retries < $this->maxRetries) {
      try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
          PDO::ATTR_EMULATE_PREPARES => false,
          PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ];

        $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return;
      } catch (\PDOException $e) {
        $lastException = $e;
        $retries++;
        $this->logger->error("Database connection attempt {$retries} failed", [
          'error' => $e->getMessage(),
          'host' => DB_HOST,
          'database' => DB_NAME
        ]);

        if ($retries < $this->maxRetries) {
          sleep($this->retryDelay);
        }
      }
    }

    throw new \Exception("Failed to connect to database after {$this->maxRetries} attempts: " . $lastException->getMessage());
  }

  public function query($sql, $params = [], $fetchAll = true)
  {
    try {
      $stmt = $this->getConnection()->prepare($sql);
      $stmt->execute($params);

      if (stripos($sql, 'SELECT') === 0) {
        return $fetchAll ? $stmt->fetchAll() : $stmt->fetch();
      }

      return true;
    } catch (\PDOException $e) {
      $this->logger->error("Database query failed", [
        'query' => $sql,
        'params' => $params,
        'error' => $e->getMessage()
      ]);
      throw $e;
    }
  }

  public function beginTransaction()
  {
    return $this->getConnection()->beginTransaction();
  }

  public function commit()
  {
    return $this->getConnection()->commit();
  }

  public function rollBack()
  {
    return $this->getConnection()->rollBack();
  }

  public function lastInsertId()
  {
    return $this->getConnection()->lastInsertId();
  }
}

function db_query(string $sql, array $params = [], bool $fetch_all = true): mixed
{
  return Database::getInstance()->query($sql, $params, $fetch_all);
}

// Initial database setup
function db_setup_initial_data()
{
  $db = Database::getInstance();
  $logger = Logger::getInstance();

  try {
    $db->beginTransaction();

    // Create users table
    $db->query("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            email VARCHAR(100),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

    // Create remember_tokens table
    $db->query("CREATE TABLE IF NOT EXISTS remember_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token_hash VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

    // Create default admin user if not exists
    $adminExists = $db->query(
      "SELECT COUNT(*) as count FROM users WHERE username = ?",
      [ADMIN_USERNAME],
      false
    );

    if ($adminExists['count'] == 0) {
      $db->query(
        "INSERT INTO users (username, password_hash) VALUES (?, ?)",
        [ADMIN_USERNAME, ADMIN_PASSWORD_HASH]
      );
      $logger->info("Default admin user created");
    }

    // Create stats table
    $db->query("CREATE TABLE IF NOT EXISTS stats (
                id INT AUTO_INCREMENT PRIMARY KEY,
                date DATE NOT NULL,
                page_url VARCHAR(255) NOT NULL,
                visits INT DEFAULT 0,
                unique_visits INT DEFAULT 0,
                referrer_source VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `date_page_referrer` (`date`, `page_url`, `referrer_source`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->commit();
    $logger->info("Database setup completed successfully");
  } catch (\Exception $e) {
    $db->rollBack();
    $logger->error("Database setup failed", ['error' => $e->getMessage()]);
    throw $e;
  }
}

// Initialize tables if needed
try {
  db_setup_initial_data();
} catch (\Exception $e) {
  return;
}

// --- Session Helper ---
function is_logged_in(): bool
{
  return isset($_SESSION['user_id']);
}

function require_login()
{
  if (!is_logged_in()) {
    header('Location: ' . BASE_URL . '/../index.php');
    exit;
  }
}