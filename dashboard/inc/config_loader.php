<?php

class ConfigLoader
{
  private static $instance = null;
  private $env = [];

  private function __construct()
  {
    $this->loadEnvFile();
  }

  public static function getInstance()
  {
    if (self::$instance === null) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  private function loadEnvFile()
  {
    $envFile = dirname(dirname(__DIR__)) . '/.env';
    if (!file_exists($envFile)) {
      die('Environment file not found. Please copy .env.example to .env and configure it.');
    }

    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
      if (strpos($line, '#') === 0) continue;
      if (strpos($line, '=') !== false) {
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        // Remove quotes if present
        if (preg_match('/^"(.+)"$/', $value, $matches)) {
          $value = $matches[1];
        }

        $this->env[$key] = $value;
      }
    }
  }

  public function get($key, $default = null)
  {
    return $this->env[$key] ?? $default;
  }

  public function isProduction()
  {
    return strtolower($this->get('APP_ENV', 'local')) === 'production';
  }
}

function env($key, $default = null)
{
  return ConfigLoader::getInstance()->get($key, $default);
}
