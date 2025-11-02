<?php
require_once 'config.php';
require_once INC_PATH . 'db.php';
require_once INC_PATH . 'template.php';

require_login();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
  $current_password = $_POST['current_password'] ?? '';
  $new_password = $_POST['new_password'] ?? '';
  $confirm_password = $_POST['confirm_password'] ?? '';
  $user_id = $_SESSION['user_id'];

  if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    $error = 'All fields are required.';
  } elseif ($new_password !== $confirm_password) {
    $error = 'New password and confirmation do not match.';
  } elseif (strlen($new_password) < 8) {
    $error = 'New password must be at least 8 characters long.';
  } else {
    try {
      $user = db_query("SELECT password_hash FROM users WHERE id = ?", [$user_id], false);

      if ($user && password_verify($current_password, $user['password_hash'])) {
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        db_query("UPDATE users SET password_hash = ? WHERE id = ?", [$new_password_hash, $user_id]);
        $message = 'Password updated successfully!';
      } else {
        $error = 'Incorrect current password.';
      }
    } catch (Exception $e) {
      $error = 'An error occurred while updating the password.';
    }
  }
}

render_header('Profile');
render_sidebar('Profile');
?>

<header class="page-header">
  <h1><i class="fas fa-user"></i> Admin Profile</h1>
  <p>Manage your account information and security settings.</p>
</header>

<section class="profile-info">
  <h2>Account Details</h2>
  <div class="info-card">
    <p><strong>Username:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?></p>
    <p><strong>User ID:</strong> <?php echo htmlspecialchars($_SESSION['user_id']); ?></p>
    <p><strong>Role:</strong> Administrator</p>
  </div>
</section>

<section class="password-change">
  <h2>Change Password</h2>

  <?php if ($message): ?>
    <p class="success-message"><?php echo htmlspecialchars($message); ?></p>
  <?php endif; ?>
  <?php if ($error): ?>
    <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
  <?php endif; ?>

  <form method="POST" action="profile.php" class="form-card">
    <input type="hidden" name="action" value="change_password">
    <div class="form-group">
      <label for="current_password">Current Password</label>
      <input type="password" id="current_password" name="current_password" required>
    </div>
    <div class="form-group">
      <label for="new_password">New Password</label>
      <input type="password" id="new_password" name="new_password" required>
    </div>
    <div class="form-group">
      <label for="confirm_password">Confirm New Password</label>
      <input type="password" id="confirm_password" name="confirm_password" required>
    </div>
    <button type="submit" class="btn-primary">Update Password</button>
  </form>
</section>

<?php
render_footer();
?>