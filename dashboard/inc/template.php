<?php
// dashboard/inc/template.php

// Ensure config is loaded
if (!defined('SITE_NAME')) {
  require_once __DIR__ . '/../config.php';
}

/**
 * Renders the HTML header part of the page.
 * @param string $title The title of the page.
 */
function render_header(string $title = 'Dashboard')
{
  $site_name = SITE_NAME;
  $base_url = './../dashboard/';
  $css_path = $base_url . 'assets/css/style.css';
  $js_path = $base_url . 'assets/js/main.js';
  $js_chart_path = $base_url . 'assets/js/charts.js';

  echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title} | {$site_name}</title>
    <link rel="stylesheet" href="{$css_path}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
HTML;
}

/**
 * Renders the sidebar navigation.
 * @param string $active_page The name of the currently active page.
 */
function render_sidebar(string $active_page)
{
  $base_url = './../dashboard/';
  $nav_items = [
    'Dashboard' => ['icon' => 'fa-tachometer-alt', 'url' => 'index.php'],
    'Statistics' => ['icon' => 'fa-chart-bar', 'url' => 'statistics.php'],
    'Profile' => ['icon' => 'fa-user', 'url' => 'profile.php'],
    'Settings' => ['icon' => 'fa-cog', 'url' => 'settings.php'],
  ];

  echo '<aside class="sidebar">';
  echo '<div class="sidebar-header">';
  echo '<h2>' . SITE_NAME . '</h2>';
  echo '</div>';
  echo '<nav class="sidebar-nav">';
  echo '<ul>';
  foreach ($nav_items as $name => $item) {
    $class = (strtolower($name) == strtolower($active_page)) ? 'active' : '';
    echo '<li><a href="' . $base_url . $item['url'] . '" class="' . $class . '">';
    echo '<i class="fas ' . $item['icon'] . '"></i>';
    echo '<span>' . $name . '</span>';
    echo '</a></li>';
  }
  echo '</ul>';
  echo '</nav>';
  echo '<div class="sidebar-footer">';
  echo '<a href="' . $base_url . 'logout.php" class="logout-btn">';
  echo '<i class="fas fa-sign-out-alt"></i>';
  echo '<span>Logout</span>';
  echo '</a>';
  echo '</div>';
  echo '</aside>';
  echo '<main class="main-content">';
}

/**
 * Renders the HTML footer part of the page.
 * Includes script tags for JS files.
 */
function render_footer()
{
  $base_url = './../dashboard/';
  $js_path = $base_url . 'assets/js/main.js';
  $js_chart_path = $base_url . 'assets/js/charts.js';

  echo <<<HTML
    </main>
    </div>
    <script src="{$js_chart_path}"></script>
    <script src="{$js_path}"></script>
</body>
</html>
HTML;
}