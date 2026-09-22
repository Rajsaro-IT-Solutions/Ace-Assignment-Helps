<?php
$pageTitle = "Expert Notifications & Alerts";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Expert');
$currentUser = Auth::currentUser();
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

$msg = '';
$msgType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'mark_read') {
        $notifId = trim($_POST['notif_id'] ?? '');
        if ($notifId && function_exists('mark_notification_read')) {
            mark_notification_read($notifId);
            $msg = "Notification marked as read.";
            $msgType = 'success';
        }
    }

    if ($action === 'mark_all_read') {
        if (function_exists('mark_all_notifications_read')) {
            mark_all_notifications_read($currentUser['id'], 'Expert');
            $msg = "All notifications marked as read.";
            $msgType = 'success';
        }
    }

    if ($action === 'delete_notif') {
        $notifId = trim($_POST['notif_id'] ?? '');
        if ($notifId && function_exists('delete_notification')) {
            delete_notification($notifId);
            $msg = "Notification dismissed.";
            $msgType = 'info';
        }
    }
}

$notifs = function_exists('get_user_notifications') ? get_user_notifications($currentUser['id'], 'Expert') : [];
$unreadCount = function_exists('get_unread_notifications_count') ? get_unread_notifications_count($currentUser['id'], 'Expert') : 0;
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h2 style="font-size:1.5rem; color:var(--text-main); margin-bottom:0.25rem;">
      <i class="fa-solid fa-bell" style="color:var(--primary);"></i> Expert Task Alerts & System Notifications
    </h2>
    <p style="color:var(--text-muted); font-size:0.9rem; margin:0;">Real-time notifications regarding newly allocated orders, QA review feedback, and messages.</p>
  </div>
  <?php if ($unreadCount > 0): ?>
    <form method="POST" style="display:inline;">
      <input type="hidden" name="action" value="mark_all_read">
      <button type="submit" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-check-double"></i> Mark All as Read (<?php echo $unreadCount; ?>)
      </button>
    </form>
  <?php endif; ?>
</div>

<?php if ($msg): ?>
  <div class="badge badge-<?php echo $msgType; ?>" style="width:100%; padding:0.9rem; margin-bottom:1.5rem; text-align:center; font-size:0.95rem; display:block;">
    <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($msg); ?>
  </div>
<?php endif; ?>

<div class="table-card">
  <?php if (empty($notifs)): ?>
    <div style="text-align:center; padding:3.5rem 1rem; color:var(--text-muted);">
      <i class="fa-regular fa-bell-slash" style="font-size:2.5rem; color:#cbd5e1; margin-bottom:1rem; display:block;"></i>
      <h3 style="font-size:1.15rem; color:var(--text-main); margin-bottom:0.3rem;">No Notifications Yet</h3>
      <p style="font-size:0.88rem; margin:0;">When you are allocated new projects or receive QA updates, notifications will appear here.</p>
    </div>
  <?php else: ?>
    <div style="display:flex; flex-direction:column; divide-y:1px solid #f1f5f9;">
      <?php foreach ($notifs as $n): 
        $isRead = !empty($n['is_read']);
      ?>
        <div style="display:flex; justify-content:space-between; align-items:flex-start; padding:1.25rem; border-bottom:1px solid #f1f5f9; background:<?php echo $isRead ? '#ffffff' : '#f0fdf4'; ?>; gap:12px;">
          <div style="display:flex; gap:12px; align-items:flex-start;">
            <div style="width:36px; height:36px; border-radius:50%; background:<?php echo $isRead ? '#f1f5f9' : '#dcfce7'; ?>; color:<?php echo $isRead ? '#64748b' : '#10b981'; ?>; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
              <i class="fa-solid fa-bell"></i>
            </div>
            <div>
              <div style="font-weight:700; color:var(--text-main); font-size:0.95rem; margin-bottom:3px;">
                <?php echo htmlspecialchars($n['title']); ?>
              </div>
              <p style="color:var(--text-muted); font-size:0.88rem; margin:0 0 6px 0; line-height:1.5;">
                <?php echo htmlspecialchars($n['message']); ?>
              </p>
              <small style="color:var(--text-dim); font-size:0.75rem;">
                <i class="fa-regular fa-clock"></i> <?php echo htmlspecialchars($n['timestamp'] ?? date('Y-m-d H:i')); ?>
              </small>
            </div>
          </div>

          <div style="display:flex; gap:6px; align-items:center;">
            <?php if (!empty($n['link'])): ?>
              <a href="<?php echo htmlspecialchars($n['link']); ?>" class="btn btn-primary btn-sm">
                View &rarr;
              </a>
            <?php endif; ?>
            <?php if (!$isRead): ?>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="mark_read">
                <input type="hidden" name="notif_id" value="<?php echo htmlspecialchars($n['notif_id']); ?>">
                <button type="submit" class="btn btn-outline btn-sm" title="Mark as read">
                  <i class="fa-solid fa-check"></i>
                </button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<script src="/assets/js/portal.js"></script>
</div>
</div>
</div>
</body>
</html>
