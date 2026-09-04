<?php
$pageTitle = "Student Dashboard";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Student');
$user = Auth::currentUser();
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

$studentAssignments = DataStore::filter('assignments', function($a) use ($user) {
    return isset($a['student_id']) && $a['student_id'] === $user['id'];
});

$totalAssigned = count($studentAssignments);
$pendingAssigned = count(array_filter($studentAssignments, function($a) {
    return in_array($a['status'], ['New', 'Pending Review', 'Waiting for Payment', 'Allocated', 'In Progress', 'Quality Check']);
}));
$completedAssigned = count(array_filter($studentAssignments, function($a) {
    return in_array($a['status'], ['Completed', 'Delivered']);
}));

$studentPayments = DataStore::filter('payments', function($p) use ($user) {
    return isset($p['student_id']) && $p['student_id'] === $user['id'];
});
$totalSpent = array_reduce($studentPayments, function($sum, $p) { return $sum + (float)$p['amount']; }, 0);
?>

<div class="metrics-grid">
  <div class="metric-card">
    <div class="metric-icon icon-blue"><i class="fa-solid fa-folder-open"></i></div>
    <div class="metric-info">
      <div class="m-val"><?php echo $totalAssigned; ?></div>
      <div class="m-lbl">Total Assignments</div>
    </div>
  </div>

  <div class="metric-card">
    <div class="metric-icon icon-amber"><i class="fa-solid fa-hourglass-half"></i></div>
    <div class="metric-info">
      <div class="m-val"><?php echo $pendingAssigned; ?></div>
      <div class="m-lbl">In Progress</div>
    </div>
  </div>

  <div class="metric-card">
    <div class="metric-icon icon-green"><i class="fa-solid fa-circle-check"></i></div>
    <div class="metric-info">
      <div class="m-val"><?php echo $completedAssigned; ?></div>
      <div class="m-lbl">Completed</div>
    </div>
  </div>

  <div class="metric-card">
    <div class="metric-icon icon-cyan"><i class="fa-solid fa-wallet"></i></div>
    <div class="metric-info">
      <div class="m-val">$<?php echo number_format($totalSpent, 2); ?></div>
      <div class="m-lbl">Total Invested</div>
    </div>
  </div>
</div>

<!-- Active Assignments Table -->
<div class="table-card">
  <div class="table-header">
    <h3 style="font-size:1.1rem; color:var(--text-main);"><i class="fa-solid fa-list-check" style="color:var(--primary);"></i> Active Assignments & SLA Tracking</h3>
    <a href="/student/new-assignment.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Submit New Assignment</a>
  </div>
  
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Title & Subject</th>
          <th>Type</th>
          <th>SLA Deadline</th>
          <th>Status</th>
          <th>Investment</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($studentAssignments)): ?>
          <tr><td colspan="7" style="text-align:center; color:var(--text-muted);">No assignments submitted yet. Click Submit New Assignment to get started!</td></tr>
        <?php else: ?>
          <?php foreach ($studentAssignments as $asm): 
            $sla = get_sla_status($asm['deadline']);
            $badgeClass = get_status_badge_class($asm['status']);
          ?>
            <tr>
              <td><strong style="color:var(--secondary);"><?php echo htmlspecialchars($asm['assignment_id']); ?></strong></td>
              <td>
                <div style="font-weight:700; color:var(--text-main);"><?php echo htmlspecialchars($asm['title']); ?></div>
                <small style="color:var(--text-muted);"><?php echo htmlspecialchars($asm['subject']); ?> &bull; <?php echo $asm['word_count']; ?> words</small>
              </td>
              <td><?php echo htmlspecialchars($asm['assignment_type']); ?></td>
              <td><span class="badge <?php echo $sla['badge_class']; ?>"><i class="fa-solid fa-clock"></i> <?php echo $sla['label']; ?></span></td>
              <td><span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($asm['status']); ?></span></td>
              <td><strong>$<?php echo number_format($asm['final_price'], 2); ?></strong></td>
              <td>
                <a href="/student/assignment-detail.php?id=<?php echo urlencode($asm['assignment_id']); ?>" class="btn btn-outline btn-sm">
                  View <i class="fa-solid fa-arrow-right"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Quick Widgets: WhatsApp Reminder & Support -->
<div class="grid-2" style="display:grid; grid-template-columns:1.2fr 1fr; gap:1.5rem;">
  <div class="table-card" style="padding:1.5rem;">
    <h3 style="font-size:1.1rem; margin-bottom:1rem; color:var(--text-main);"><i class="fa-brands fa-whatsapp" style="color:#25D366;"></i> WhatsApp Alert Status</h3>
    <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1rem;">
      Your WhatsApp updates are <strong style="color:var(--success);">ACTIVE</strong> for phone number <strong><?php echo htmlspecialchars($user['phone'] ?? '+1 555-234-5678'); ?></strong>.
    </p>
    <div style="display:flex; gap:10px;">
      <a href="/student/whatsapp.php" class="btn btn-outline btn-sm" style="color:#059669; border-color:#059669;"><i class="fa-brands fa-whatsapp"></i> Test WhatsApp Link</a>
    </div>
  </div>

  <div class="table-card" style="padding:1.5rem;">
    <h3 style="font-size:1.1rem; margin-bottom:1rem; color:var(--text-main);"><i class="fa-solid fa-headset" style="color:var(--primary);"></i> Support Desk</h3>
    <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1rem;">
      Need special formatting or urgent assistance for an ongoing assignment?
    </p>
    <a href="/student/messages.php" class="btn btn-outline btn-sm"><i class="fa-solid fa-comments"></i> Open Support Ticket</a>
  </div>
</div>

</div>
</div>
</body>
</html>
