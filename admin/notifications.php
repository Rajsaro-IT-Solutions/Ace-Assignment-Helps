<?php
$pageTitle = "Platform Notification Center & Broadcasts";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Admin');
$adminUser = Auth::currentUser();
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

$msg = '';
$msgType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Send / Broadcast Notification
    if ($action === 'broadcast_notification') {
        $target = trim($_POST['target'] ?? 'All');
        $specificUserId = trim($_POST['specific_user_id'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $type = trim($_POST['type'] ?? 'info');
        $link = trim($_POST['link'] ?? '');

        if (!empty($title) && !empty($message)) {
            $userRole = in_array($target, ['All', 'Student', 'Allocator', 'Admin']) ? $target : 'All';
            $userId = ($target === 'Specific') ? $specificUserId : '';

            add_notification($userRole, $userId, $title, $message, $type, $link);
            add_audit_log('Admin', $adminUser['id'], 'Broadcast Notification', "Sent notification: '$title' to $target");
            $msg = "Notification broadcasted successfully to target: $target!";
            $msgType = 'success';
        } else {
            $msg = "Notification Title and Message cannot be empty!";
            $msgType = 'danger';
        }
    }

    // 2. Mark Single Read
    if ($action === 'mark_read') {
        $notifId = trim($_POST['notif_id'] ?? '');
        if ($notifId) {
            mark_notification_read($notifId);
            $msg = "Notification marked as read.";
            $msgType = 'success';
        }
    }

    // 3. Mark All Read
    if ($action === 'mark_all_read') {
        mark_all_notifications_read($adminUser['id'], 'Admin');
        $msg = "All notifications have been marked as read.";
        $msgType = 'success';
    }

    // 4. Delete Notification
    if ($action === 'delete_notif') {
        $notifId = trim($_POST['notif_id'] ?? '');
        if ($notifId) {
            delete_notification($notifId);
            $msg = "Notification removed.";
            $msgType = 'info';
        }
    }
}

$allNotifs = get_user_notifications($adminUser['id'], 'Admin');
$totalCount = count($allNotifs);
$unreadCount = get_unread_notifications_count($adminUser['id'], 'Admin');
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h2 style="font-size:1.5rem; color:var(--text-main); margin-bottom:0.25rem;">
      <i class="fa-solid fa-bell" style="color:var(--primary);"></i> Platform Notification Center & Alerts
    </h2>
    <p style="color:var(--text-muted); font-size:0.9rem; margin:0;">Send platform broadcasts, view system triggers, and monitor alerts across all user roles.</p>
  </div>
  <div style="display:flex; gap:8px; align-items:center;">
    <?php if ($unreadCount > 0): ?>
      <form method="POST" style="display:inline;">
        <input type="hidden" name="action" value="mark_all_read">
        <button type="submit" class="btn btn-outline btn-sm">
          <i class="fa-solid fa-check-double"></i> Mark All as Read
        </button>
      </form>
    <?php endif; ?>
    <button class="btn btn-primary" onclick="openModal('broadcastModal')">
      <i class="fa-solid fa-bullhorn"></i> Send Broadcast Alert
    </button>
  </div>
</div>

<?php if ($msg): ?>
  <div class="badge badge-<?php echo $msgType; ?>" style="width:100%; padding:0.9rem; margin-bottom:1.5rem; text-align:center; font-size:0.95rem; display:block;">
    <i class="fa-solid fa-circle-info"></i> <?php echo htmlspecialchars($msg); ?>
  </div>
<?php endif; ?>

<!-- Notification Metric Counters -->
<div class="metrics-grid" style="margin-bottom:1.5rem; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
  <div class="metric-card">
    <div class="metric-icon icon-blue"><i class="fa-solid fa-bell"></i></div>
    <div class="metric-info">
      <div class="m-val"><?php echo $totalCount; ?></div>
      <div class="m-lbl">Total Alerts</div>
    </div>
  </div>
  <div class="metric-card">
    <div class="metric-icon icon-purple"><i class="fa-solid fa-envelope-open-text"></i></div>
    <div class="metric-info">
      <div class="m-val" style="<?php echo $unreadCount > 0 ? 'color:#ef4444;' : ''; ?>"><?php echo $unreadCount; ?></div>
      <div class="m-lbl">Unread Notifications</div>
    </div>
  </div>
  <div class="metric-card">
    <div class="metric-icon icon-cyan"><i class="fa-solid fa-graduation-cap"></i></div>
    <div class="metric-info">
      <div class="m-val">
        <?php echo count(array_filter($allNotifs, function($n){ return ($n['user_role'] ?? '') === 'Student'; })); ?>
      </div>
      <div class="m-lbl">Student Notices</div>
    </div>
  </div>
  <div class="metric-card">
    <div class="metric-icon icon-green"><i class="fa-solid fa-user-shield"></i></div>
    <div class="metric-info">
      <div class="m-val">
        <?php echo count(array_filter($allNotifs, function($n){ return in_array($n['user_role'] ?? '', ['Allocator', 'Admin']); })); ?>
      </div>
      <div class="m-lbl">Staff / Operations</div>
    </div>
  </div>
</div>

<!-- Filters -->
<div class="filter-bar" style="margin-bottom:1.2rem; display:flex; gap:12px; flex-wrap:wrap;">
  <input type="text" id="notifSearchInput" class="form-control" style="flex:2; min-width:240px;" placeholder="Search notifications by title or message keyword...">
  <select id="notifStatusFilter" class="form-control" style="flex:1; min-width:160px;">
    <option value="">All Read / Unread</option>
    <option value="unread">Unread Only</option>
    <option value="read">Read Only</option>
  </select>
  <select id="notifRoleFilter" class="form-control" style="flex:1; min-width:160px;">
    <option value="">All Audiences</option>
    <option value="all">Broadcast (All Users)</option>
    <option value="student">Students</option>
    <option value="allocator">Allocators</option>
    <option value="admin">Administrators</option>
  </select>
</div>

<!-- Notification Feed List -->
<div class="table-card" style="padding:1.5rem;">
  <div id="notifListContainer">
    <?php if (empty($allNotifs)): ?>
      <div style="text-align:center; padding:3rem 1rem;">
        <i class="fa-regular fa-bell-slash" style="font-size:3rem; color:var(--text-dim); margin-bottom:1rem; display:block;"></i>
        <h4 style="color:var(--text-main); margin-bottom:0.5rem;">No Notifications Yet</h4>
        <p style="color:var(--text-muted); font-size:0.9rem;">You are all caught up. Broadcast a new message to students or staff using the button above.</p>
      </div>
    <?php else: ?>
      <?php foreach ($allNotifs as $n): 
        $isRead = !empty($n['is_read']);
        $notifId = $n['id'] ?? ($n['notification_id'] ?? '');
        $roleTarget = $n['user_role'] ?? 'All';
        $type = $n['type'] ?? 'info';
        
        $borderClass = $isRead ? 'border:1px solid var(--portal-border); background:#ffffff;' : 'border:1px solid #c7d2fe; background:#f5f3ff;';
        $iconClass = 'fa-circle-info';
        $iconColor = 'color:var(--primary);';
        if ($type === 'success') { $iconClass = 'fa-circle-check'; $iconColor = 'color:var(--success);'; }
        elseif ($type === 'warning') { $iconClass = 'fa-triangle-exclamation'; $iconColor = 'color:var(--warning);'; }
        elseif ($type === 'danger') { $iconClass = 'fa-circle-exclamation'; $iconColor = 'color:#ef4444;'; }
      ?>
        <div class="notif-item-card" data-read="<?php echo $isRead ? 'read' : 'unread'; ?>" data-role="<?php echo strtolower($roleTarget); ?>" style="<?php echo $borderClass; ?> border-radius:var(--radius-sm); padding:1.2rem; margin-bottom:0.9rem; display:flex; justify-content:space-between; align-items:flex-start; gap:16px; transition:all 0.2s ease;">
          <div style="display:flex; gap:14px; align-items:flex-start; flex:1;">
            <div style="font-size:1.3rem; margin-top:2px; <?php echo $iconColor; ?>">
              <i class="fa-solid <?php echo $iconClass; ?>"></i>
            </div>
            <div style="flex:1;">
              <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px; flex-wrap:wrap;">
                <strong style="color:var(--text-main); font-size:1rem;"><?php echo htmlspecialchars($n['title']); ?></strong>
                <?php if (!$isRead): ?>
                  <span class="badge badge-primary" style="font-size:0.7rem; padding:2px 8px;"><i class="fa-solid fa-circle" style="font-size:0.4rem; margin-right:4px;"></i> NEW</span>
                <?php endif; ?>
                <span class="badge badge-info" style="font-size:0.7rem;">Target: <?php echo htmlspecialchars($roleTarget); ?></span>
                <?php if (!empty($n['user_id'])): ?>
                  <span class="badge badge-secondary" style="font-size:0.7rem;"><?php echo htmlspecialchars($n['user_id']); ?></span>
                <?php endif; ?>
              </div>
              <p style="color:var(--text-main); font-size:0.92rem; margin:0 0 6px 0; line-height:1.5;">
                <?php echo nl2br(htmlspecialchars($n['message'])); ?>
              </p>
              <div style="display:flex; gap:12px; align-items:center;">
                <small style="color:var(--text-dim); font-size:0.78rem;">
                  <i class="fa-regular fa-clock"></i> <?php echo htmlspecialchars($n['created_at'] ?? 'Just now'); ?>
                </small>
                <?php if (!empty($n['link'])): ?>
                  <a href="<?php echo htmlspecialchars($n['link']); ?>" class="btn btn-outline btn-sm" style="padding:2px 8px; font-size:0.75rem;">
                    View Referenced Page &rarr;
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
            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this notification?');">
              <input type="hidden" name="action" value="delete_notif">
              <input type="hidden" name="notif_id" value="<?php echo htmlspecialchars($notifId); ?>">
              <button type="submit" class="btn btn-danger btn-sm" title="Delete Notification" style="padding:4px 8px;">
                <i class="fa-solid fa-trash"></i>
              </button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Broadcast Modal -->
<div id="broadcastModal" class="modal-overlay">
  <div class="modal-box" style="padding:2rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem;">
      <h3 style="margin:0; color:var(--text-main);"><i class="fa-solid fa-bullhorn" style="color:var(--primary);"></i> Send Notification Broadcast</h3>
      <button type="button" onclick="closeModal('broadcastModal')" style="background:none; border:none; font-size:1.3rem; color:var(--text-muted); cursor:pointer;">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="broadcast_notification">

      <div class="form-group">
        <label>Target Audience *</label>
        <select name="target" id="broadcastTargetSelect" class="form-control" onchange="toggleSpecificUserField(this.value)">
          <option value="All">All Platform Users (Students, Allocators & Admins)</option>
          <option value="Student">All Registered Students</option>
          <option value="Allocator">All Allocator Staff</option>
          <option value="Admin">All Platform Administrators</option>
          <option value="Specific">Specific User ID</option>
        </select>
      </div>

      <div class="form-group" id="specificUserGroup" style="display:none;">
        <label>Specific User ID (e.g. STU-1001 or ALL-501) *</label>
        <input type="text" name="specific_user_id" class="form-control" placeholder="e.g. STU-1001">
      </div>

      <div class="form-group">
        <label>Alert Category / Type</label>
        <select name="type" class="form-control">
          <option value="info">Information Notice</option>
          <option value="success">Success / Confirmation</option>
          <option value="warning">Important Warning</option>
          <option value="danger">Critical Alert</option>
        </select>
      </div>

      <div class="form-group">
        <label>Notification Headline / Title *</label>
        <input type="text" name="title" class="form-control" required placeholder="e.g. System Maintenance Window Scheduled">
      </div>

      <div class="form-group">
        <label>Detailed Message Content *</label>
        <textarea name="message" class="form-control" rows="4" required placeholder="Enter the complete notification body text..."></textarea>
      </div>

      <div class="form-group">
        <label>Action Destination Link <small style="color:var(--text-muted);">(Optional)</small></label>
        <input type="text" name="link" class="form-control" placeholder="/admin/assignments.php">
      </div>

      <div style="display:flex; gap:10px; margin-top:1.5rem;">
        <button type="submit" class="btn btn-primary" style="flex:1;">Send Notification Now</button>
        <button type="button" class="btn btn-outline" onclick="closeModal('broadcastModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function toggleSpecificUserField(val) {
  const grp = document.getElementById('specificUserGroup');
  if (grp) {
    grp.style.display = (val === 'Specific') ? 'block' : 'none';
  }
}

// Client-side quick filter
document.addEventListener('DOMContentLoaded', () => {
  const search = document.getElementById('notifSearchInput');
  const statusFilter = document.getElementById('notifStatusFilter');
  const roleFilter = document.getElementById('notifRoleFilter');
  const cards = document.querySelectorAll('.notif-item-card');

  function filterNotifs() {
    const q = (search ? search.value : '').toLowerCase().trim();
    const st = statusFilter ? statusFilter.value : '';
    const rf = roleFilter ? roleFilter.value : '';

    cards.forEach(card => {
      const text = card.innerText.toLowerCase();
      const readState = card.dataset.read || '';
      const role = card.dataset.role || '';

      let matchQ = !q || text.includes(q);
      let matchStatus = !st || readState === st;
      let matchRole = !rf || role === rf || role === 'all';

      if (matchQ && matchStatus && matchRole) {
        card.style.display = 'flex';
      } else {
        card.style.display = 'none';
      }
    });
  }

  [search, statusFilter, roleFilter].forEach(el => {
    if (el) {
      el.addEventListener('input', filterNotifs);
      el.addEventListener('change', filterNotifs);
    }
  });
});
</script>

<script src="/assets/js/portal.js"></script>
</div>
</div>
</body>
</html>
