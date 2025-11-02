<?php
require_once __DIR__ . '/config.php';
require_once INC_PATH . 'db.php';
require_once INC_PATH . 'template.php';

require_login();

render_header('Statistics');
render_sidebar('Statistics');
?>

<header class="page-header">
  <h1><i class="fas fa-chart-bar"></i> Detailed Statistics</h1>
  <p>In-depth analysis of user traffic, page popularity, and referral sources.</p>
</header>

<section class="statistics-charts">
  <div class="chart-container full-width">
    <h2>Referrers Breakdown</h2>
    <div class="chart-wrapper">
      <div id="referrersChart"></div>
    </div>
  </div>

  <div class="chart-container">
    <h2>Daily Visits Trend</h2>
    <div class="chart-wrapper">
      <div id="dailyVisitsTrendChart"></div>
    </div>
  </div>

  <div class="chart-container">
    <h2>Top Pages Popularity</h2>
    <div class="chart-wrapper">
      <div id="topPagesPopularityChart"></div>
    </div>
  </div>
</section>

<?php
render_footer();
?>