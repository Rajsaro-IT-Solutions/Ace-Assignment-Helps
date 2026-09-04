<?php
$pageTitle = "Notifications Center";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Student');
$user = Auth::currentUser();
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

$notifs = DataStore::filter('notifications', function($n) use ($user) {
    return (isset($n['user_id']) && $n['user_id'] === $user['id']) || (isset($n['user_role']) && $n['user_role'] === 'Student');
});
?>

<div class="table-card">
  <div class="table-header">
    <h3 style="font-size:1.1rem; color:var(--text-main);"><i class="fa-solid fa-bell" style="color:var(--primary);"></i> Notification Alerts</h3>
  </div>

  <div style="padding:1.5rem;">
    <?php if (empty($notifs)): ?>
      <p style="color:var(--text-muted);">No new notifications.</p>
    <?php else: ?>
      <?php foreach ($notifs as $n): ?>
        <div style="background:#f8fafc; border:1px solid var(--portal-border); border-radius:var(--radius-sm); padding:1rem 1.2rem; margin-bottom:0.8rem; display:flex; justify-content:space-between; align-items:center;">
          <div>
            <strong style="color:var(--text-main); display:block; font-size:0.95rem; margin-bottom:4px;"><?php echo htmlspecialchars($n['title']); ?></strong>
            <span style="color:var(--text-muted); font-size:0.85rem;"><?php echo htmlspecialchars($n['message']); ?></span>
          </div>
          <small style="color:var(--text-dim); font-size:0.75rem;"><?php echo htmlspecialchars($n['created_at']); ?></small>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

</div>
</div>
</body>
</html>
