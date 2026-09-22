<?php
$pageTitle = "My Assigned Tasks & Projects";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Expert');
$user = Auth::currentUser();
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

// Fetch all assignments assigned to this expert
$myAssignments = DataStore::filter('assignments', function($a) use ($user) {
    return isset($a['expert_id']) && $a['expert_id'] === $user['id'] && ($a['status'] ?? '') !== 'Deleted';
});

// Sort by urgency / deadline
usort($myAssignments, function($a, $b) {
    return strtotime($a['deadline']) - strtotime($b['deadline']);
});

$statusFilter = trim($_GET['status'] ?? '');
if ($statusFilter) {
    $filteredAssignments = array_filter($myAssignments, function($a) use ($statusFilter) {
        if ($statusFilter === 'Active') {
            return in_array($a['status'], ['Allocated', 'In Progress']);
        }
        return strtolower($a['status']) === strtolower($statusFilter);
    });
} else {
    $filteredAssignments = $myAssignments;
}

$allocatedCount = count(array_filter($myAssignments, fn($a) => $a['status'] === 'Allocated'));
$inProgressCount = count(array_filter($myAssignments, fn($a) => $a['status'] === 'In Progress'));
$qaCount = count(array_filter($myAssignments, fn($a) => in_array($a['status'], ['Quality Check', 'Revision Requested'])));
$completedCount = count(array_filter($myAssignments, fn($a) => in_array($a['status'], ['Completed', 'Delivered'])));
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h2 style="font-size:1.5rem; color:var(--text-main); margin-bottom:0.25rem;">
      <i class="fa-solid fa-list-check" style="color:var(--primary);"></i> My Assigned Tasks & Projects
    </h2>
    <p style="color:var(--text-muted); font-size:0.9rem; margin:0;">
      Academic orders allocated to your roster queue for research drafting, coding, and solution delivery.
    </p>
  </div>

  <div style="display:flex; gap:10px;">
    <a href="/expert/index.php" class="btn btn-outline btn-sm">
      <i class="fa-solid fa-gauge"></i> Dashboard Overview
    </a>
  </div>
</div>

<!-- Status Navigation Tabs -->
<div style="display:flex; gap:8px; margin-bottom:1.5rem; flex-wrap:wrap; background:#f1f5f9; padding:6px; border-radius:12px;">
  <a href="/expert/assignments.php" class="btn btn-sm <?php echo empty($statusFilter) ? 'btn-primary' : 'btn-outline'; ?>" style="border:none; border-radius:8px; font-weight:700;">
    All Orders (<?php echo count($myAssignments); ?>)
  </a>
  <a href="/expert/assignments.php?status=Allocated" class="btn btn-sm <?php echo ($statusFilter === 'Allocated') ? 'btn-primary' : 'btn-outline'; ?>" style="border:none; border-radius:8px; font-weight:700;">
    <i class="fa-solid fa-hourglass-start"></i> Allocated / New (<?php echo $allocatedCount; ?>)
  </a>
  <a href="/expert/assignments.php?status=In Progress" class="btn btn-sm <?php echo ($statusFilter === 'In Progress') ? 'btn-primary' : 'btn-outline'; ?>" style="border:none; border-radius:8px; font-weight:700;">
    <i class="fa-solid fa-pen-ruler"></i> In Progress (<?php echo $inProgressCount; ?>)
  </a>
  <a href="/expert/assignments.php?status=Quality Check" class="btn btn-sm <?php echo ($statusFilter === 'Quality Check') ? 'btn-primary' : 'btn-outline'; ?>" style="border:none; border-radius:8px; font-weight:700;">
    <i class="fa-solid fa-microscope"></i> In QA Review (<?php echo $qaCount; ?>)
  </a>
  <a href="/expert/assignments.php?status=Completed" class="btn btn-sm <?php echo ($statusFilter === 'Completed') ? 'btn-primary' : 'btn-outline'; ?>" style="border:none; border-radius:8px; font-weight:700;">
    <i class="fa-solid fa-circle-check"></i> Completed (<?php echo $completedCount; ?>)
  </a>
</div>

<!-- Assignments Table Card -->
<div class="table-card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Order ID</th>
          <th>Assignment Title</th>
          <th>Subject & Citation Style</th>
          <th>Length</th>
          <th>Target SLA Deadline</th>
          <th>Status</th>
          <th style="text-align:center;">Workspace Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($filteredAssignments)): ?>
          <tr>
            <td colspan="7" style="text-align:center; padding:3rem 1rem; color:var(--text-muted);">
              <i class="fa-solid fa-folder-open" style="font-size:2rem; color:#cbd5e1; margin-bottom:0.8rem; display:block;"></i>
              No assignments found in this status filter.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($filteredAssignments as $asm): 
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
                <div style="font-weight:700; color:var(--text-main); max-width:320px;">
                  <?php echo htmlspecialchars($asm['title']); ?>
                </div>
                <small style="color:var(--text-muted);">Type: <?php echo htmlspecialchars($asm['assignment_type']); ?></small>
              </td>
              <td>
                <span class="badge badge-info" style="font-size:0.75rem;"><?php echo htmlspecialchars($asm['subject']); ?></span><br>
                <small style="color:var(--text-muted);">Cite: <?php echo htmlspecialchars($asm['reference_style'] ?? 'APA 7th'); ?></small>
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
                  <i class="fa-solid fa-arrow-right-to-bracket"></i> Open Workspace &rarr;
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
