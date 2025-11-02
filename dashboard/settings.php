<?php
require_once 'config.php';
require_once INC_PATH . 'db.php';
require_once INC_PATH . 'template.php';

require_login();

$message = '';
$error = '';

// Function to safely read the current settings from the file (simulated)
function get_current_settings()
{
  // In a real application, this would read from a database or a secure config file.
  // Since we are using a simple config.php for initial setup, we'll use the global.
  global $SETTINGS;
  return $SETTINGS;
}

// Function to safely write the new settings (simulated)
function update_settings_file($new_settings)
{
  // This is a highly simplified and insecure way to update settings in a file.
  // In a production environment, you would update a database table.
  // For this exercise, we will simply update the global variable and pretend it's persistent.
  // To make it slightly more realistic for a file-based config, we'll write a new config file.

  $config_content = file_get_contents(dirname(__DIR__) . '/config.php');

  // Regex to find and replace the $GLOBALS['SETTINGS'] array
  $pattern = '/\$GLOBALS\[\'SETTINGS\'\]\s*=\s*\[(.*?)\];/s';

  $new_settings_str = "[\n    'ALLOWED_ORIGINS' => '" . addslashes($new_settings['ALLOWED_ORIGINS']) . "',\n    'API_KEY' => '" . addslashes($new_settings['API_KEY']) . "',\n];";

  $new_config_content = preg_replace($pattern, "\$GLOBALS['SETTINGS'] = " . $new_settings_str, $config_content, 1);

  if ($new_config_content) {
    file_put_contents(dirname(__DIR__) . '/config.php', $new_config_content);
    // Re-include config to update the global variable in the current request
    require_once dirname(__DIR__) . '/config.php';
    return true;
  }
  return false;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $allowed_origins = trim($_POST['allowed_origins'] ?? '');
  $api_key = trim($_POST['api_key'] ?? '');

  if (empty($allowed_origins) || empty($api_key)) {
    $error = 'Both Allowed Origins and API Key are required.';
  } else {
    $new_settings = [
      'ALLOWED_ORIGINS' => $allowed_origins,
      'API_KEY' => $api_key,
    ];

    if (update_settings_file($new_settings)) {
      $message = 'Settings updated successfully! You may need to refresh the page to see changes reflected in the API endpoint.';
    } else {
      $error = 'Failed to update settings file.';
    }
  }
}

$current_settings = get_current_settings();

render_header('Settings');
render_sidebar('Settings');
?>

<header class="page-header">
  <h1><i class="fas fa-cog"></i> System Settings</h1>
  <p>Update configuration variables for your Medieval Portfolio API backend.</p>
</header>

<section class="system-settings">
  <h2>API Configuration</h2>

  <?php if ($message): ?>
    <p class="success-message"><?php echo htmlspecialchars($message); ?></p>
  <?php endif; ?>
  <?php if ($error): ?>
    <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
  <?php endif; ?>

  <form method="POST" action="settings.php" class="form-card">
    <div class="form-group">
      <label for="allowed_origins">ALLOWED_ORIGINS (CORS)</label>
      <input type="text" id="allowed_origins" name="allowed_origins"
        value="<?php echo htmlspecialchars($current_settings['ALLOWED_ORIGINS']); ?>" required>
      <small>Comma-separated list of domains allowed to access the API. Use '*' for all.</small>
    </div>
    <div class="form-group">
      <label for="api_key">API_KEY</label>
      <input type="text" id="api_key" name="api_key"
        value="<?php echo htmlspecialchars($current_settings['API_KEY']); ?>" required>
      <small>The secret key required to access the API endpoints.</small>
    </div>
    <button type="submit" class="btn-primary">Save Settings</button>
  </form>
</section>

<?php
render_footer();
?>