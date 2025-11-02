<?php
// dashboard/index.php
require_once __DIR__ . '/config.php';
require_once INC_PATH . 'db.php';
require_once INC_PATH . 'template.php';

require_login();

// Fetch summary data
$total_visits = db_query("SELECT COUNT(*) FROM stats", [], false)['COUNT(*)'] ?? 0;
$unique_visitors = db_query("SELECT COUNT(DISTINCT visits) FROM stats WHERE unique_visits = TRUE", [], false)['COUNT(DISTINCT visits)'] ?? 0;

render_header('Dashboard');
render_sidebar('Dashboard');
?>

<header class="page-header">
  <h1><i class="fas fa-tachometer-alt"></i> Dashboard Overview</h1>
  <p>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>. Here is a summary of your portfolio's
    performance.</p>
</header>

<section class="dashboard-stats">
  <div class="stat-card">
    <i class="fas fa-eye stat-icon"></i>
    <div class="stat-info">
      <span class="stat-value"><?php echo number_format($total_visits); ?></span>
      <span class="stat-label">Total Visits</span>
    </div>
  </div>
  <div class="stat-card">
    <i class="fas fa-user-check stat-icon"></i>
    <div class="stat-info">
      <span class="stat-value"><?php echo number_format($unique_visitors); ?></span>
      <span class="stat-label">Unique Visitors</span>
    </div>
  </div>
  <div class="stat-card">
    <i class="fas fa-chart-line stat-icon"></i>
    <div class="stat-info">
      <span class="stat-value" id="daily-visits-avg">...</span>
      <span class="stat-label">Daily Avg. (Last 7 Days)</span>
    </div>
  </div>
  <div class="stat-card">
    <i class="fas fa-file-alt stat-icon"></i>
    <div class="stat-info">
      <span class="stat-value" id="top-page-count">...</span>
      <span class="stat-label">Top Pages Tracked</span>
    </div>
  </div>
</section>

<section class="dashboard-charts">
  <div class="chart-container">
    <h2>Daily Visits (Last 7 Days)</h2>
    <div class="chart-wrapper">
      <div id="dailyVisitsChart"></div>
    </div>
  </div>
  <div class="chart-container">
    <h2>Top Pages</h2>
    <div class="chart-wrapper">
      <div id="topPagesChart"></div>
    </div>
  </div>
</section>

<?php
render_footer();
?>