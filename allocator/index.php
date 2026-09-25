<?php
$pageTitle = "Allocator Command Center";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Allocator');
$user = Auth::currentUser();
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

$allAssignments = DataStore::filter('assignments', function($a) use ($user) {
    return ($a['allocator_id'] ?? '') === $user['id'] && ($a['status'] ?? '') !== 'Deleted';
});
$pendingAllocation = DataStore::filter('assignments', function($a) use ($user) {
    return ($a['allocator_id'] ?? '') === $user['id'] && (empty($a['expert_id']) || in_array($a['status'], ['New', 'Pending Review', 'Confirmed', 'Allocated']));
});
$urgentSLA = DataStore::filter('assignments', function($a) use ($user) {
    if (($a['allocator_id'] ?? '') !== $user['id']) return false;
    $sla = get_sla_status($a['deadline']);
    return in_array($sla['level'], ['red', 'overdue', 'amber']);
});
$completedToday = DataStore::filter('assignments', function($a) use ($user) {
    return ($a['allocator_id'] ?? '') === $user['id'] && in_array($a['status'], ['Completed', 'Delivered']);
});
$qaQueue = DataStore::filter('assignments', function($a) use ($user) {
    return ($a['allocator_id'] ?? '') === $user['id'] && in_array($a['status'], ['Quality Check', 'Pending Admin Approval']);
});
$revisionQueue = DataStore::filter('assignments', function($a) use ($user) {
    return ($a['allocator_id'] ?? '') === $user['id'] && $a['status'] === 'Revision Requested';
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

<?php if (!empty($qaQueue)): ?>
<!-- QA Review Queue (Solutions Submitted) -->
<div class="table-card" style="border: 1.5px solid #a855f7; margin-bottom: 1.75rem;">
  <div class="table-header" style="background: rgba(168, 85, 247, 0.08);">
    <div style="display:flex; align-items:center; gap:8px;">
      <span class="badge badge-purple" style="font-size:0.8rem; font-weight:800;"><i class="fa-solid fa-microscope"></i> Action Required</span>
      <h3 style="font-size:1.1rem; color:var(--text-main); margin:0;">Expert Solution QA Review Queue (<?php echo count($qaQueue); ?>)</h3>
    </div>
    <span style="color:var(--text-muted); font-size:0.85rem;">Inspect & Grant Allocator QA Approval</span>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Assignment ID</th>
          <th>Student (Masked)</th>
          <th>Subject & Type</th>
          <th>Allocated Expert</th>
          <th>Status</th>
          <th>QA Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($qaQueue as $qAsm): 
          $qStudent = DataStore::findOne('students', 'student_id', $qAsm['student_id']);
          $qExpert = DataStore::findOne('experts', 'expert_id', $qAsm['expert_id']);
        ?>
          <tr>
            <td><strong style="color:var(--secondary);"><?php echo htmlspecialchars($qAsm['assignment_id']); ?></strong></td>
            <td><span style="color:var(--text-muted); font-size:0.85rem;"><i class="fa-solid fa-user-lock"></i> <?php echo htmlspecialchars(mask_student_name($qStudent['name'] ?? 'Student')); ?></span></td>
            <td>
              <div style="font-weight:700; color:var(--text-main);"><?php echo htmlspecialchars($qAsm['subject']); ?></div>
              <small style="color:var(--text-muted);"><?php echo $qAsm['word_count']; ?> words &bull; <?php echo htmlspecialchars($qAsm['assignment_type']); ?></small>
            </td>
            <td><strong><?php echo htmlspecialchars($qExpert['name'] ?? 'Expert'); ?></strong></td>
            <td><span class="badge <?php echo get_status_badge_class($qAsm['status']); ?>"><?php echo htmlspecialchars($qAsm['status']); ?></span></td>
            <td>
              <a href="/allocator/assignment-detail.php?id=<?php echo urlencode($qAsm['assignment_id']); ?>" class="btn btn-primary btn-sm" style="font-weight:700;">
                <i class="fa-solid fa-microscope"></i> Review QA &rarr;
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if (!empty($revisionQueue)): ?>
<!-- Revisions Queue (Action Required) -->
<div class="table-card" style="border: 1.5px solid #f59e0b; margin-bottom: 1.75rem;">
  <div class="table-header" style="background: rgba(245, 158, 11, 0.08);">
    <div style="display:flex; align-items:center; gap:8px;">
      <span class="badge badge-warning" style="font-size:0.8rem; font-weight:800;"><i class="fa-solid fa-rotate-left"></i> Revisions Active</span>
      <h3 style="font-size:1.1rem; color:var(--text-main); margin:0;">Revision Requested Queue — Expert Reassignment Option (<?php echo count($revisionQueue); ?>)</h3>
    </div>
    <span style="color:var(--text-muted); font-size:0.85rem;">Assign Expert or Provide Revision Directives</span>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Assignment ID</th>
          <th>Student (Masked)</th>
          <th>Subject & Type</th>
          <th>Allocated Expert</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($revisionQueue as $rAsm): 
          $rStudent = DataStore::findOne('students', 'student_id', $rAsm['student_id']);
          $rExpert = DataStore::findOne('experts', 'expert_id', $rAsm['expert_id']);
        ?>
          <tr>
            <td><strong style="color:var(--secondary);"><?php echo htmlspecialchars($rAsm['assignment_id']); ?></strong></td>
            <td><span style="color:var(--text-muted); font-size:0.85rem;"><i class="fa-solid fa-user-lock"></i> <?php echo htmlspecialchars(mask_student_name($rStudent['name'] ?? 'Student')); ?></span></td>
            <td>
              <div style="font-weight:700; color:var(--text-main);"><?php echo htmlspecialchars($rAsm['subject']); ?></div>
              <small style="color:var(--text-muted);"><?php echo $rAsm['word_count']; ?> words &bull; <?php echo htmlspecialchars($rAsm['assignment_type']); ?></small>
            </td>
            <td><strong><?php echo htmlspecialchars($rExpert['name'] ?? 'None'); ?></strong></td>
            <td><span class="badge badge-warning"><i class="fa-solid fa-rotate-left"></i> Revision Requested</span></td>
            <td>
              <a href="/allocator/assignment-detail.php?id=<?php echo urlencode($rAsm['assignment_id']); ?>" class="btn btn-warning btn-sm" style="font-weight:700;">
                <i class="fa-solid fa-user-gear"></i> Change / Reassign Expert &rarr;
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

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
