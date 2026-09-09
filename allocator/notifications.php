<?php
$pageTitle = "Allocator Notifications & Alerts";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole(['Allocator', 'Admin']);
$currentUser = Auth::currentUser();
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

$msg = '';
$msgType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Mark Read
    if ($action === 'mark_read') {
        $notifId = trim($_POST['notif_id'] ?? '');
        if ($notifId) {
            mark_notification_read($notifId);
            $msg = "Alert marked as read.";
            $msgType = 'success';
        }
    }

    // 2. Mark All Read
    if ($action === 'mark_all_read') {
        mark_all_notifications_read($currentUser['id'], 'Allocator');
        $msg = "All notifications marked as read.";
        $msgType = 'success';
    }

    // 3. Delete Notification
    if ($action === 'delete_notif') {
        $notifId = trim($_POST['notif_id'] ?? '');
        if ($notifId) {
            delete_notification($notifId);
            $msg = "Alert deleted.";
            $msgType = 'info';
        }
    }
}

$notifs = get_user_notifications($currentUser['id'], 'Allocator');
$totalCount = count($notifs);
$unreadCount = get_unread_notifications_count($currentUser['id'], 'Allocator');
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h2 style="font-size:1.5rem; color:var(--text-main); margin-bottom:0.25rem;">
      <i class="fa-solid fa-bell" style="color:var(--primary);"></i> Allocator Alert Feed & Assignment Updates
    </h2>
    <p style="color:var(--text-muted); font-size:0.9rem; margin:0;">Real-time notifications regarding incoming assignments, expert responses, and allocation tasks.</p>
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
    <i class="fa-solid fa-circle-info"></i> <?php echo htmlspecialchars($msg); ?>
  </div>
<?php endif; ?>

<!-- Filter Controls -->
<div class="filter-bar" style="margin-bottom:1.2rem; display:flex; gap:12px; flex-wrap:wrap;">
  <input type="text" id="allocNotifSearch" class="form-control" style="flex:2; min-width:240px;" placeholder="Search alerts by title or content keyword...">
  <select id="allocNotifStatus" class="form-control" style="flex:1; min-width:160px;">
    <option value="">All Notifications</option>
    <option value="unread">Unread Only (<?php echo $unreadCount; ?>)</option>
    <option value="read">Read Only</option>
  </select>
</div>

<div class="table-card" style="padding:1.5rem;">
  <?php if (empty($notifs)): ?>
    <div style="text-align:center; padding:3rem 1rem;">
      <i class="fa-regular fa-bell-slash" style="font-size:3rem; color:var(--text-dim); margin-bottom:1rem; display:block;"></i>
      <h4 style="color:var(--text-main); margin-bottom:0.5rem;">No Notifications Currently</h4>
      <p style="color:var(--text-muted); font-size:0.9rem;">You have no active alerts. New student submissions and workflow tasks will appear here.</p>
    </div>
  <?php else: ?>
    <?php foreach ($notifs as $n): 
      $isRead = !empty($n['is_read']);
      $notifId = $n['id'] ?? ($n['notification_id'] ?? '');
      $borderStyle = $isRead ? 'border:1px solid var(--portal-border); background:#ffffff;' : 'border:1px solid #bfdbfe; background:#f0f9ff;';
      
      // Check if message references an assignment ID (e.g. ACE-2026-...)
      preg_match('/(ACE-\d{4}-\d+)/', $n['message'] ?? '', $matches);
      $referencedAsm = $matches[1] ?? '';
    ?>
      <div class="alloc-notif-item" data-read="<?php echo $isRead ? 'read' : 'unread'; ?>" style="<?php echo $borderStyle; ?> border-radius:var(--radius-sm); padding:1.2rem; margin-bottom:0.9rem; display:flex; justify-content:space-between; align-items:flex-start; gap:16px;">
        <div style="display:flex; gap:12px; align-items:flex-start; flex:1;">
          <div style="font-size:1.25rem; color:var(--secondary); margin-top:2px;">
            <i class="fa-solid fa-circle-exclamation"></i>
          </div>
          <div style="flex:1;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
              <strong style="color:var(--text-main); font-size:0.98rem;"><?php echo htmlspecialchars($n['title']); ?></strong>
              <?php if (!$isRead): ?>
                <span class="badge badge-info" style="font-size:0.7rem; padding:2px 8px;"><i class="fa-solid fa-circle" style="font-size:0.4rem; margin-right:4px;"></i> UNREAD</span>
              <?php endif; ?>
            </div>
            <p style="color:var(--text-main); font-size:0.9rem; margin:0 0 8px 0; line-height:1.5;">
              <?php echo nl2br(htmlspecialchars($n['message'])); ?>
            </p>
            <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
              <small style="color:var(--text-dim); font-size:0.78rem;">
                <i class="fa-regular fa-clock"></i> <?php echo htmlspecialchars($n['created_at'] ?? 'Recently'); ?>
              </small>
              <?php if ($referencedAsm): ?>
                <a href="/allocator/assignment-detail.php?id=<?php echo urlencode($referencedAsm); ?>" class="btn btn-outline btn-sm" style="padding:2px 8px; font-size:0.75rem;">
                  Open Assignment <?php echo htmlspecialchars($referencedAsm); ?> &rarr;
                </a>
              <?php elseif (!empty($n['link'])): ?>
                <a href="<?php echo htmlspecialchars($n['link']); ?>" class="btn btn-outline btn-sm" style="padding:2px 8px; font-size:0.75rem;">
                  View Link &rarr;
                </a>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div style="display:flex; gap:6px; align-items:center;">
          <?php if (!$isRead): ?>
            <form method="POST" style="display:inline;">
              <input type="hidden" name="action" value="mark_read">
              <input type="hidden" name="notif_id" value="<?php echo htmlspecialchars($notifId); ?>">
              <button type="submit" class="btn btn-outline btn-sm" title="Mark as Read">
                <i class="fa-solid fa-check"></i>
              </button>
            </form>
          <?php endif; ?>
          <form method="POST" style="display:inline;" onsubmit="return confirm('Dismiss this notification?');">
            <input type="hidden" name="action" value="delete_notif">
            <input type="hidden" name="notif_id" value="<?php echo htmlspecialchars($notifId); ?>">
            <button type="submit" class="btn btn-danger btn-sm" title="Delete Alert" style="padding:4px 8px;">
              <i class="fa-solid fa-trash"></i>
            </button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const search = document.getElementById('allocNotifSearch');
  const statusFilter = document.getElementById('allocNotifStatus');
  const items = document.querySelectorAll('.alloc-notif-item');

  function filterItems() {
    const q = (search ? search.value : '').toLowerCase().trim();
    const st = statusFilter ? statusFilter.value : '';

    items.forEach(el => {
      const txt = el.innerText.toLowerCase();
      const readState = el.dataset.read || '';

      const matchQ = !q || txt.includes(q);
      const matchSt = !st || readState === st;

      el.style.display = (matchQ && matchSt) ? 'flex' : 'none';
    });
  }

  if (search) search.addEventListener('input', filterItems);
  if (statusFilter) statusFilter.addEventListener('change', filterItems);
});
</script>

<script src="/assets/js/portal.js"></script>
</div>
</div>
</body>
</html>
