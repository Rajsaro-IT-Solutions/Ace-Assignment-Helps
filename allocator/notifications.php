<?php
$pageTitle = "Allocator Notifications";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole(['Allocator', 'Admin']);
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

$notifs = DataStore::filter('notifications', function($n) {
    return isset($n['user_role']) && in_array($n['user_role'], ['Allocator', 'Admin']);
});
?>

<div class="table-card" style="padding:1.5rem;">
  <h3 style="font-size:1.1rem; color:var(--text-main); margin-bottom:1rem;"><i class="fa-solid fa-bell" style="color:var(--primary);"></i> Allocator Alert Feed</h3>
  <?php foreach ($notifs as $n): ?>
    <div style="background:#f8fafc; border:1px solid var(--portal-border); padding:1rem; border-radius:var(--radius-sm); margin-bottom:0.8rem;">
      <strong style="color:var(--text-main); display:block; font-size:0.95rem; margin-bottom:4px;"><?php echo htmlspecialchars($n['title']); ?></strong>
      <span style="color:var(--text-muted); font-size:0.88rem;"><?php echo htmlspecialchars($n['message']); ?></span>
    </div>
  <?php endforeach; ?>
</div>

</div>
</div>
</body>
</html>
