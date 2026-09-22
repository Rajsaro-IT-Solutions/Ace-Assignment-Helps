<?php
$pageTitle = "Expert Workspace Dashboard";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Expert');
$user = Auth::currentUser();
require_once __DIR__ . '/../includes/helpers.php';

// Fetch full expert profile from DB
$expert = DataStore::findOne('experts', 'expert_id', $user['id']);
if (!$expert) {
    // Fallback if expert object created dynamically
    $expert = [
        'expert_id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'subjects' => $user['subjects'] ?? ['Academic Research'],
        'rating' => 4.9,
        'completed_count' => 0,
        'status' => 'Available'
    ];
}

// Handle quick availability toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_availability'])) {
    $newStatus = ($expert['status'] === 'Available') ? 'Busy' : 'Available';
    DataStore::update('experts', 'expert_id', $user['id'], ['status' => $newStatus]);
    $expert['status'] = $newStatus;
    $_SESSION['flash_msg'] = "Your availability status has been set to {$newStatus}.";
    $_SESSION['flash_type'] = 'success';
    header("Location: /expert/index.php");
    exit;
}

// Fetch assignments assigned to this expert
$myAssignments = DataStore::filter('assignments', function($a) use ($user) {
    return isset($a['expert_id']) && $a['expert_id'] === $user['id'] && ($a['status'] ?? '') !== 'Deleted';
});

// Sort by deadline urgency (closest first)
usort($myAssignments, function($a, $b) {
    return strtotime($a['deadline']) - strtotime($b['deadline']);
});

$activeAssignments = array_filter($myAssignments, function($a) {
    return in_array($a['status'], ['Allocated', 'In Progress']);
});

$reviewAssignments = array_filter($myAssignments, function($a) {
    return in_array($a['status'], ['Quality Check', 'Revision Requested']);
});

$completedAssignments = array_filter($myAssignments, function($a) {
    return in_array($a['status'], ['Completed', 'Delivered']);
});

$totalAssignedCount = count($myAssignments);
$activeCount = count($activeAssignments);
$reviewCount = count($reviewAssignments);
$completedCount = max((int)($expert['completed_count'] ?? 0), count($completedAssignments));

include __DIR__ . '/../includes/portal_header.php';

$flashMsg = $_SESSION['flash_msg'] ?? '';
$flashType = $_SESSION['flash_type'] ?? 'info';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
?>

<?php if ($flashMsg): ?>
  <div class="badge badge-<?php echo $flashType; ?>" style="width:100%; padding:0.9rem; margin-bottom:1.5rem; text-align:center; font-size:0.95rem; display:block;">
    <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($flashMsg); ?>
  </div>
<?php endif; ?>

<!-- Welcome & Expert Status Header -->
<div class="table-card" style="padding: 1.75rem 2rem; margin-bottom: 2rem; background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 60%, #312e81 100%); color:#ffffff; border:none; border-radius:16px;">
  <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1.5rem;">
    <div>
      <div style="display:flex; align-items:center; gap:10px; margin-bottom:0.4rem;">
        <span class="badge" style="background:rgba(255,255,255,0.15); color:#ffffff; font-family:monospace; font-weight:700;">
          <?php echo htmlspecialchars($expert['expert_id']); ?>
        </span>
        <span class="badge" style="background:#f59e0b; color:#000000; font-weight:800;">
          <i class="fa-solid fa-star"></i> <?php echo number_format((float)($expert['rating'] ?? 4.9), 2); ?> Rating
        </span>
      </div>
      <h1 style="font-size:1.85rem; margin:0 0 0.5rem 0; font-weight:800; color:#ffffff;">
        Welcome back, <?php echo htmlspecialchars($expert['name']); ?>
      </h1>
      <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; opacity:0.9; font-size:0.88rem;">
        <span><i class="fa-solid fa-graduation-cap"></i> Specializations:</span>
        <?php 
          $subjs = is_array($expert['subjects']) ? $expert['subjects'] : explode(',', (string)$expert['subjects']);
          foreach ($subjs as $s): 
        ?>
          <span style="background:rgba(255,255,255,0.18); padding:2px 10px; border-radius:12px; font-size:0.78rem; font-weight:600;">
            <?php echo htmlspecialchars(trim($s)); ?>
          </span>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Status & Quick Toggle -->
    <div style="text-align:right;">
      <div style="font-size:0.75rem; text-transform:uppercase; letter-spacing:1px; opacity:0.75; margin-bottom:4px;">Allocation Availability</div>
      <form method="POST" style="margin:0;">
        <input type="hidden" name="toggle_availability" value="1">
        <?php if ($expert['status'] === 'Available'): ?>
          <button type="submit" class="btn" style="background:#10b981; color:#ffffff; font-weight:700; border:none; padding:8px 18px; border-radius:20px; box-shadow:0 4px 12px rgba(16,185,129,0.35); cursor:pointer;">
            <i class="fa-solid fa-circle-check"></i> Available for Tasks (Click to Toggle)
          </button>
        <?php else: ?>
          <button type="submit" class="btn" style="background:#d97706; color:#ffffff; font-weight:700; border:none; padding:8px 18px; border-radius:20px; box-shadow:0 4px 12px rgba(217,119,6,0.35); cursor:pointer;">
            <i class="fa-solid fa-hourglass-half"></i> Busy / On Break (Click to Toggle)
          </button>
        <?php endif; ?>
      </form>
    </div>
  </div>
</div>

<!-- Metrics KPI Grid -->
<div class="metrics-grid" style="margin-bottom:2rem;">
  <div class="metric-card">
    <div class="metric-icon icon-blue"><i class="fa-solid fa-pen-ruler"></i></div>
    <div class="metric-info">
      <div class="m-val"><?php echo $activeCount; ?></div>
      <div class="m-lbl">Active Assigned Tasks</div>
    </div>
  </div>

  <div class="metric-card">
    <div class="metric-icon icon-orange"><i class="fa-solid fa-microscope"></i></div>
    <div class="metric-info">
      <div class="m-val"><?php echo $reviewCount; ?></div>
      <div class="m-lbl">In Quality Review</div>
    </div>
  </div>

  <div class="metric-card">
    <div class="metric-icon icon-green"><i class="fa-solid fa-circle-check"></i></div>
    <div class="metric-info">
      <div class="m-val"><?php echo $completedCount; ?></div>
      <div class="m-lbl">Completed Solutions</div>
    </div>
  </div>

  <div class="metric-card">
    <div class="metric-icon" style="background:#fef3c7; color:#d97706;"><i class="fa-solid fa-star"></i></div>
    <div class="metric-info">
      <div class="m-val"><?php echo number_format((float)($expert['rating'] ?? 4.9), 1); ?> <small style="font-size:0.9rem; color:var(--text-muted);">/ 5.0</small></div>
      <div class="m-lbl">Academic Quality Rating</div>
    </div>
  </div>
</div>

<!-- Active Task Queue Table -->
<div class="table-card">
  <div class="table-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
    <div>
      <h3 style="font-size:1.15rem; color:var(--text-main); margin:0 0 0.2rem 0;">
        <i class="fa-solid fa-list-check" style="color:var(--primary);"></i> Current Active Task Queue
      </h3>
      <small style="color:var(--text-muted);">Orders allocated to you requiring solution drafting, coding, or quality revisions.</small>
    </div>
    <a href="/expert/assignments.php" class="btn btn-outline btn-sm">
      View All Tasks (<?php echo $totalAssignedCount; ?>) &rarr;
    </a>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Order ID</th>
          <th>Assignment Title</th>
          <th>Subject & Type</th>
          <th>Length</th>
          <th>Target SLA Deadline</th>
          <th>Status</th>
          <th style="text-align:center;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($activeAssignments) && empty($reviewAssignments)): ?>
          <tr>
            <td colspan="7" style="text-align:center; padding:3rem 1rem; color:var(--text-muted);">
              <i class="fa-solid fa-mug-hot" style="font-size:2.2rem; color:#cbd5e1; margin-bottom:0.8rem; display:block;"></i>
              <strong>Your active queue is completely clear!</strong><br>
              New orders matched to your subject specialization by allocators will appear here.
            </td>
          </tr>
        <?php else: ?>
          <?php 
            $queue = array_merge($activeAssignments, $reviewAssignments);
            foreach ($queue as $asm): 
              $sla = get_sla_status($asm['deadline']);
              $badgeClass = get_status_badge_class($asm['status']);
          ?>
            <tr>
              <td>
                <strong style="color:var(--secondary); font-family:monospace;">
                  <?php echo htmlspecialchars($asm['assignment_id']); ?>
                </strong>
              </td>
              <td>
                <div style="font-weight:700; color:var(--text-main);">
                  <?php echo htmlspecialchars($asm['title']); ?>
                </div>
                <small style="color:var(--text-muted);">Ref Style: <?php echo htmlspecialchars($asm['reference_style'] ?? 'APA 7th'); ?></small>
              </td>
              <td>
                <span class="badge badge-info" style="font-size:0.75rem;"><?php echo htmlspecialchars($asm['subject']); ?></span><br>
                <small style="color:var(--text-muted);"><?php echo htmlspecialchars($asm['assignment_type']); ?></small>
              </td>
              <td>
                <strong><?php echo (int)$asm['word_count']; ?></strong> words<br>
                <small style="color:var(--text-muted);">(<?php echo ceil($asm['word_count'] / 250); ?> pages)</small>
              </td>
              <td>
                <div style="font-weight:700;"><?php echo date('M d, Y H:i', strtotime($asm['deadline'])); ?></div>
                <span class="badge <?php echo $sla['badge_class']; ?>" style="font-size:0.7rem; margin-top:2px;">
                  <i class="fa-solid fa-clock"></i> <?php echo $sla['label']; ?>
                </span>
              </td>
              <td>
                <span class="badge <?php echo $badgeClass; ?>">
                  <?php echo htmlspecialchars($asm['status']); ?>
                </span>
              </td>
              <td style="text-align:center;">
                <a href="/expert/assignment-detail.php?id=<?php echo urlencode($asm['assignment_id']); ?>" class="btn btn-primary btn-sm" style="font-weight:700; white-space:nowrap;">
                  <i class="fa-solid fa-folder-open"></i> Workspace & Solution
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="/assets/js/portal.js"></script>
</div>
</div>
</div>
</body>
</html>
