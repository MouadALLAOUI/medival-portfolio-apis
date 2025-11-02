<?php
require_once __DIR__ . '/inc/config_loader.php';

// --- Configuration Settings ---
define('ENV', env('APP_ENV', 'local'));

// Database credentials
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_NAME', env('DB_NAME', 'portfolio_db'));

define('SITE_NAME', env('SITE_NAME', 'Medieval Portfolio Dashboard'));
define('BASE_URL', './../dashboard/');
define('SECRET_KEY', env('SECRET_KEY'));

define('ADMIN_USERNAME', env('ADMIN_USERNAME', 'admin'));
define('ADMIN_PASSWORD_HASH', password_hash(env('ADMIN_PASSWORD', 'password123'), PASSWORD_DEFAULT));

$GLOBALS['SETTINGS'] = [
  'ALLOWED_ORIGINS' => env('ALLOWED_ORIGINS', '*'),
  'API_KEY' => env('API_KEY'),
];

define('INC_PATH', __DIR__ . '/inc/');
define('PAGES_PATH', __DIR__ . '/pages/');
define('API_PATH', __DIR__ . '/api/');
define('ASSETS_PATH', __DIR__ . '/assets/');

// --- Session Management ---
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

session_set_cookie_params([
  'lifetime' => (int)env('SESSION_LIFETIME', 3600),
  'path' => BASE_URL,
  'domain' => env('SESSION_DOMAIN', ''),
  'secure' => env('SESSION_SECURE', false) || ConfigLoader::getInstance()->isProduction(),
  'httponly' => true,
  'samesite' => 'Strict'
]);

function checkLogin()
{
  if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
  }
}

if (session_status() == PHP_SESSION_NONE) {
  session_start();
}
