function drawBarChart(containerId, labels, data, color = '#3498db') {
  const container = document.getElementById(containerId);
  if (!container) return;

  container.innerHTML = '';
  container.style.display = 'flex';
  container.style.alignItems = 'flex-start';
  container.style.gap = '6px';
  container.style.height = '100%';
  container.style.position = 'relative'; // for line SVG

  const maxValue = Math.max(...data) || 1;

  data.forEach((val, i) => {
    const barWrapper = document.createElement('div');
    barWrapper.style.flex = '1';
    barWrapper.style.display = 'flex';
    barWrapper.style.flexDirection = 'column';
    barWrapper.style.alignItems = 'center';
    barWrapper.style.justifyContent = 'flex-end';
    barWrapper.style.position = 'relative';
    barWrapper.style.height = '100%';

    const bar = document.createElement('div');
    bar.className = 'bar';
    bar.style.height = `${(val / maxValue) * 100}%`;
    bar.style.width = '70%';
    bar.style.backgroundColor = color;
    bar.style.borderRadius = '4px 4px 0 0';
    bar.style.transition = '0.3s';
    barWrapper.appendChild(bar);

    const label = document.createElement('div');
    label.textContent = labels[i];
    label.style.marginTop = '4px';
    label.style.fontSize = '0.8em';
    label.style.textAlign = 'center';
    barWrapper.appendChild(label);

    container.appendChild(barWrapper);
  });
}

function drawLineChart(containerId, labels, data, color = '#3498db') {
  drawBarChart(containerId, labels, data, color);

  const container = document.getElementById(containerId);
  const bars = Array.from(container.querySelectorAll('.bar'));
  if (bars.length < 2) return;

  const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
  svg.setAttribute('width', '100%');
  svg.setAttribute('height', '100%');
  svg.style.position = 'absolute';
  svg.style.top = '0';
  svg.style.left = '0';
  svg.style.pointerEvents = 'none';
  svg.style.zIndex = '1';

  let d = '';
  bars.forEach((bar, i) => {
    const rect = bar.getBoundingClientRect();
    const containerRect = container.getBoundingClientRect();
    const x = rect.left - containerRect.left + rect.width / 2;
    const y = containerRect.bottom - rect.top; // distance from bottom
    d += i === 0 ? `M ${x} ${y}` : ` L ${x} ${y}`;
  });

  const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
  path.setAttribute('d', d);
  path.setAttribute('stroke', color);
  path.setAttribute('stroke-width', '2');
  path.setAttribute('fill', 'none');
  svg.appendChild(path);
  container.appendChild(svg);
}

/**
 * Draw a pie chart using pure CSS (conic-gradient)
 */
function drawPieChart(containerId, labels, data) {
  const container = document.getElementById(containerId);
  if (!container) return;

  container.innerHTML = '';
  container.style.display = 'flex';
  container.style.flexDirection = 'column';
  container.style.alignItems = 'center';
  container.style.justifyContent = 'center';
  container.style.height = '200px';

  const total = data.reduce((a, b) => a + b, 0);
  const colors = ['#e74c3c', '#3498db', '#2ecc71', '#f1c40f', '#9b59b6', '#1abc9c', '#e67e22'];

  let gradient = 'conic-gradient(';
  let currentAngle = 0;

  data.forEach((val, i) => {
    const angle = (val / total) * 360;
    gradient += `${colors[i % colors.length]} ${currentAngle}deg ${currentAngle + angle}deg${i < data.length - 1 ? ', ' : ''}`;
    currentAngle += angle;
  });
  gradient += ')';

  const pie = document.createElement('div');
  pie.style.width = '150px';
  pie.style.height = '150px';
  pie.style.borderRadius = '50%';
  pie.style.background = gradient;
  pie.style.boxShadow = '0 0 10px rgba(0,0,0,0.2)';
  container.appendChild(pie);

  // Legend
  const legend = document.createElement('ul');
  legend.style.listStyle = 'none';
  legend.style.padding = '0';
  legend.style.marginTop = '10px';
  legend.style.fontSize = '0.85em';
  labels.forEach((label, i) => {
    const li = document.createElement('li');
    li.style.display = 'flex';
    li.style.alignItems = 'center';
    li.style.marginBottom = '4px';

    const box = document.createElement('span');
    box.style.width = '10px';
    box.style.height = '10px';
    box.style.marginRight = '6px';
    box.style.background = colors[i % colors.length];
    li.appendChild(box);

    li.appendChild(document.createTextNode(`${label} (${((data[i] / total) * 100).toFixed(1)}%)`));
    legend.appendChild(li);
  });
  container.appendChild(legend);
}

/**
 * Load all charts from API and render using Flex/Grid charts
 */
function loadAllCharts() {
  fetch('./api/chart-data.php')
    .then(res => res.json())
    .then(data => {
      console.log('Chart data loaded:', data);

      // Bar charts
      drawBarChart('dailyVisitsChart', data.daily_visits_7_days.labels, data.daily_visits_7_days.data, '#2ecc71');
      drawBarChart('topPagesChart', data.top_pages_dashboard.labels, data.top_pages_dashboard.data, '#9b59b6');
      drawBarChart('dailyVisitsTrendChart', data.daily_visits_trend.labels, data.daily_visits_trend.data, '#3498db');
      drawBarChart('topPagesPopularityChart', data.top_pages_stats.labels, data.top_pages_stats.data, '#e67e22');

      // Pie chart
      drawPieChart('referrersChart', data.referrers.labels, data.referrers.data);

      // Optionally: line chart over bar chart
      // drawLineChart('dailyVisitsTrendChart', data.daily_visits_trend.labels, data.daily_visits_trend.data, '#3498db');
    })
    .catch(err => {
      console.error('Chart load error:', err);
      document.querySelectorAll('.chart-wrapper').forEach(c => {
        c.innerHTML = '<p style="color:red;text-align:center;">Failed to load chart data</p>';
      });
    });
}

document.addEventListener('DOMContentLoaded', loadAllCharts);
