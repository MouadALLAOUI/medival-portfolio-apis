<?php

class Logger
{
  private static $instance = null;
  private $logPath;
  private $config;

  private function __construct()
  {
    $this->config = ConfigLoader::getInstance();
    $this->logPath = dirname(dirname(__DIR__)) . '/logs';

    if (!file_exists($this->logPath)) {
      mkdir($this->logPath, 0755, true);
    }
  }

  public static function getInstance()
  {
    if (self::$instance === null) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  public function error($message, $context = [])
  {
    $this->log('ERROR', $message, $context);
  }

  public function info($message, $context = [])
  {
    $this->log('INFO', $message, $context);
  }

  public function warning($message, $context = [])
  {
    $this->log('WARNING', $message, $context);
  }

  public function debug($message, $context = [])
  {
    if (!$this->config->isProduction()) {
      $this->log('DEBUG', $message, $context);
    }
  }

  private function log($level, $message, $context = [])
  {
    $date = date('Y-m-d H:i:s');
    $logFile = $this->logPath . '/' . date('Y-m-d') . '.log';

    $contextStr = empty($context) ? '' : json_encode($context);
    $logMessage = "[$date] [$level] $message $contextStr\n";

    error_log($logMessage, 3, $logFile);

    if ($level === 'ERROR' && $this->config->isProduction()) {
      // In production, you might want to send critical errors to an external service
      // or notify administrators
    }
  }
}
