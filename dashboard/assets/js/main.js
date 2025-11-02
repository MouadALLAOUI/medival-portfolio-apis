document.addEventListener('DOMContentLoaded', () => {

  console.log('Main JS loaded');
  const sidebar = document.querySelector('.sidebar');
  const mainContent = document.querySelector('.main-content');

  if (document.getElementById('dailyVisitsChart')) {
    fetchDataAndPopulateStats();
  }
});

function fetchDataAndPopulateStats() {
  // The API endpoint is relative to the dashboard root
  const apiPath = 'api/chart-data.php';

  fetch(apiPath)
    .then(response => {
      if (!response.ok) {
        throw new Error('Network response was not ok');
      }
      return response.json();
    })
    .then(data => {
      // Calculate Daily Avg. (Last 7 Days)
      const dailyVisits = data.daily_visits_7_days.data;
      if (dailyVisits.length > 0) {
        const sum = dailyVisits.reduce((a, b) => a + b, 0);
        const avg = sum / dailyVisits.length;
        document.getElementById('daily-visits-avg').textContent = avg.toFixed(1);
      } else {
        document.getElementById('daily-visits-avg').textContent = '0';
      }

      // Update Top Pages Tracked count
      const topPages = data.top_pages_dashboard.labels;
      document.getElementById('top-page-count').textContent = topPages.length;
    })
    .catch(error => {
      console.error('Error fetching data for stats:', error);
    });
}