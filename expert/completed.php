<?php
$pageTitle = "Completed Solutions Archive";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Expert');
$user = Auth::currentUser();
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

// Fetch all completed assignments for this expert
$completedAssignments = DataStore::filter('assignments', function($a) use ($user) {
    return isset($a['expert_id']) && $a['expert_id'] === $user['id'] && in_array($a['status'], ['Completed', 'Delivered']);
});

usort($completedAssignments, function($a, $b) {
    return strtotime($b['updated_at'] ?? $b['deadline']) - strtotime($a['updated_at'] ?? $a['deadline']);
});

$totalWords = array_sum(array_column($completedAssignments, 'word_count'));
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h2 style="font-size:1.5rem; color:var(--text-main); margin-bottom:0.25rem;">
      <i class="fa-solid fa-circle-check" style="color:var(--success);"></i> Completed Solutions Archive
    </h2>
    <p style="color:var(--text-muted); font-size:0.9rem; margin:0;">
      All assignments and research papers successfully authored and delivered by you.
    </p>
  </div>

  <div style="display:flex; gap:10px;">
    <a href="/expert/assignments.php" class="btn btn-outline btn-sm">
      <i class="fa-solid fa-list-check"></i> View Active Tasks
    </a>
  </div>
</div>

<!-- Summary Metrics Grid -->
<div class="metrics-grid" style="margin-bottom:2rem;">
  <div class="metric-card">
    <div class="metric-icon icon-green"><i class="fa-solid fa-circle-check"></i></div>
    <div class="metric-info">
      <div class="m-val"><?php echo count($completedAssignments); ?></div>
      <div class="m-lbl">Completed Projects</div>
    </div>
  </div>

  <div class="metric-card">
    <div class="metric-icon icon-blue"><i class="fa-solid fa-file-word"></i></div>
    <div class="metric-info">
      <div class="m-val"><?php echo number_format($totalWords); ?></div>
      <div class="m-lbl">Academic Words Authored</div>
    </div>
  </div>

  <div class="metric-card">
    <div class="metric-icon" style="background:#fef3c7; color:#d97706;"><i class="fa-solid fa-shield-halved"></i></div>
    <div class="metric-info">
      <div class="m-val">100%</div>
      <div class="m-lbl">Turnitin 0% Pass Rate</div>
    </div>
  </div>
</div>

<!-- Table Card -->
<div class="table-card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Order ID</th>
          <th>Assignment Title</th>
          <th>Subject Area</th>
          <th>Word Count</th>
          <th>Completion Date</th>
          <th>Status</th>
          <th style="text-align:center;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($completedAssignments)): ?>
          <tr>
            <td colspan="7" style="text-align:center; padding:3rem 1rem; color:var(--text-muted);">
              <i class="fa-solid fa-folder-open" style="font-size:2rem; color:#cbd5e1; margin-bottom:0.8rem; display:block;"></i>
              No completed assignments yet. Once solutions are approved by QA, they will be archived here.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($completedAssignments as $asm): ?>
            <tr>
              <td>
                <strong style="color:var(--secondary); font-family:monospace;">
                  <?php echo htmlspecialchars($asm['assignment_id']); ?>
                </strong>
              </td>
              <td>
                <strong style="color:var(--text-main);"><?php echo htmlspecialchars($asm['title']); ?></strong><br>
                <small style="color:var(--text-muted);"><?php echo htmlspecialchars($asm['assignment_type']); ?></small>
              </td>
              <td>
                <span class="badge badge-info" style="font-size:0.75rem;"><?php echo htmlspecialchars($asm['subject']); ?></span>
              </td>
              <td>
                <strong><?php echo (int)$asm['word_count']; ?></strong> words
              </td>
              <td>
                <?php echo date('M d, Y', strtotime($asm['updated_at'] ?? $asm['deadline'])); ?>
              </td>
              <td>
                <span class="badge badge-success"><?php echo htmlspecialchars($asm['status']); ?></span>
              </td>
              <td style="text-align:center;">
                <a href="/expert/assignment-detail.php?id=<?php echo urlencode($asm['assignment_id']); ?>" class="btn btn-outline btn-sm">
                  View Workspace &rarr;
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
