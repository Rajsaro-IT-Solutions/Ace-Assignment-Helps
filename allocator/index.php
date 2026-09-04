<?php
$pageTitle = "Allocator Command Center";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Allocator');
$user = Auth::currentUser();
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

$allAssignments = DataStore::getCollection('assignments');
$pendingAllocation = DataStore::filter('assignments', function($a) {
    return in_array($a['status'], ['New', 'Pending Review', 'Confirmed']) || empty($a['expert_id']);
});
$urgentSLA = DataStore::filter('assignments', function($a) {
    $sla = get_sla_status($a['deadline']);
    return in_array($sla['level'], ['red', 'overdue', 'amber']);
});
$completedToday = DataStore::filter('assignments', function($a) {
    return $a['status'] === 'Completed';
});
$expertsAvailable = DataStore::filter('experts', function($e) {
    return $e['status'] === 'Available';
});
?>

<!-- Privacy Protection Notice Banner -->
<div class="privacy-banner">
  <i class="fa-solid fa-user-shield" style="font-size:1.3rem;"></i>
  <div>
    <strong>Privacy Protection Enforced:</strong> As an Allocator, student contact details (email, phone, address) and payment information are strictly masked in compliance with system RBAC.
  </div>
</div>

<!-- Allocator Dashboard Widgets -->
<div class="metrics-grid">
  <div class="metric-card">
    <div class="metric-icon icon-amber"><i class="fa-solid fa-hourglass-start"></i></div>
    <div class="metric-info">
      <div class="m-val"><?php echo count($pendingAllocation); ?></div>
      <div class="m-lbl">Pending Allocation</div>
    </div>
  </div>

  <div class="metric-card">
    <div class="metric-icon icon-red"><i class="fa-solid fa-triangle-exclamation"></i></div>
    <div class="metric-info">
      <div class="m-val"><?php echo count($urgentSLA); ?></div>
      <div class="m-lbl">Urgent SLA Alerts</div>
    </div>
  </div>

  <div class="metric-card">
    <div class="metric-icon icon-green"><i class="fa-solid fa-user-graduate"></i></div>
    <div class="metric-info">
      <div class="m-val"><?php echo count($expertsAvailable); ?></div>
      <div class="m-lbl">Experts Available</div>
    </div>
  </div>

  <div class="metric-card">
    <div class="metric-icon icon-blue"><i class="fa-solid fa-circle-check"></i></div>
    <div class="metric-info">
      <div class="m-val"><?php echo count($completedToday); ?></div>
      <div class="m-lbl">Completed Queue</div>
    </div>
  </div>
</div>

<!-- Pending Allocation Queue -->
<div class="table-card">
  <div class="table-header">
    <h3 style="font-size:1.1rem; color:var(--text-main);"><i class="fa-solid fa-bars-staggered" style="color:var(--primary);"></i> Priority Pending Allocation Queue</h3>
    <a href="/allocator/pending.php" class="btn btn-outline btn-sm">View All Pending &rarr;</a>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Assignment ID</th>
          <th>Student (Masked)</th>
          <th>Subject & Word Count</th>
          <th>Country</th>
          <th>Priority</th>
          <th>SLA Deadline</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($pendingAllocation)): ?>
          <tr><td colspan="8" style="text-align:center; color:var(--text-muted);">No pending assignments waiting for allocation. Good job!</td></tr>
        <?php else: ?>
          <?php foreach ($pendingAllocation as $asm): 
            $student = DataStore::findOne('students', 'student_id', $asm['student_id']);
            $maskedName = mask_student_name($student['name'] ?? 'Student');
            $sla = get_sla_status($asm['deadline']);
          ?>
            <tr>
              <td><strong style="color:var(--secondary);"><?php echo htmlspecialchars($asm['assignment_id']); ?></strong></td>
              <td><span style="color:var(--text-muted); font-size:0.85rem;"><i class="fa-solid fa-user-lock"></i> <?php echo htmlspecialchars($maskedName); ?></span></td>
              <td>
                <div style="font-weight:700; color:var(--text-main);"><?php echo htmlspecialchars($asm['subject']); ?></div>
                <small style="color:var(--text-muted);"><?php echo $asm['word_count']; ?> words &bull; <?php echo htmlspecialchars($asm['assignment_type']); ?></small>
              </td>
              <td><?php echo htmlspecialchars($asm['country']); ?></td>
              <td><span class="badge badge-warning"><?php echo htmlspecialchars($asm['priority']); ?></span></td>
              <td><span class="badge <?php echo $sla['badge_class']; ?>"><i class="fa-solid fa-clock"></i> <?php echo $sla['label']; ?></span></td>
              <td><span class="badge <?php echo get_status_badge_class($asm['status']); ?>"><?php echo htmlspecialchars($asm['status']); ?></span></td>
              <td>
                <a href="/allocator/assignment-detail.php?id=<?php echo urlencode($asm['assignment_id']); ?>" class="btn btn-primary btn-sm">
                  <i class="fa-solid fa-user-plus"></i> Allocate Expert
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
</body>
</html>
