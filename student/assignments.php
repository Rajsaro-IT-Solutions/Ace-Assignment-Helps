<?php
$pageTitle = "My Assignments";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Student');
$user = Auth::currentUser();
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

$assignments = DataStore::filter('assignments', function($a) use ($user) {
    return isset($a['student_id']) && $a['student_id'] === $user['id'];
});
?>

<div class="filter-bar">
  <input type="text" id="tableSearchInput" class="form-control" placeholder="Search title or ID...">
  <select id="tableStatusFilter" class="form-control">
    <option value="">All Statuses</option>
    <option value="New">New</option>
    <option value="Pending Review">Pending Review</option>
    <option value="Waiting for Payment">Waiting for Payment</option>
    <option value="Confirmed">Confirmed</option>
    <option value="Allocated">Allocated</option>
    <option value="In Progress">In Progress</option>
    <option value="Quality Check">Quality Check</option>
    <option value="Completed">Completed</option>
    <option value="Revision Requested">Revision Requested</option>
  </select>
  <select id="tablePriorityFilter" class="form-control">
    <option value="">All Priorities</option>
    <option value="Normal">Normal</option>
    <option value="High">High</option>
    <option value="Urgent">Urgent</option>
  </select>
</div>

<div class="table-card">
  <div class="table-header">
    <h3 style="font-size:1.1rem; color:var(--text-main);"><i class="fa-solid fa-book-open" style="color:var(--primary);"></i> Assignment Orders History</h3>
    <a href="/student/new-assignment.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-circle-plus"></i> Submit New Assignment</a>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Assignment ID</th>
          <th>Title & Subject</th>
          <th>Type</th>
          <th>Priority</th>
          <th>SLA Deadline</th>
          <th>Status</th>
          <th>Amount</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($assignments)): ?>
          <tr>
            <td colspan="8" style="text-align:center; padding:3rem 1rem;">
              <div style="font-size:2.5rem; color:var(--text-dim); margin-bottom:0.8rem;"><i class="fa-solid fa-folder-open"></i></div>
              <h4 style="color:var(--text-main); margin-bottom:0.4rem;">No Assignments Found</h4>
              <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1rem;">Ready to get top-grade academic support from PhD experts?</p>
              <a href="/student/new-assignment.php" class="btn btn-primary"><i class="fa-solid fa-circle-plus"></i> Submit New Assignment</a>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($assignments as $asm): 
            $sla = get_sla_status($asm['deadline']);
            $badgeClass = get_status_badge_class($asm['status']);
          ?>
            <tr data-status="<?php echo htmlspecialchars($asm['status']); ?>" data-priority="<?php echo htmlspecialchars($asm['priority']); ?>" data-subject="<?php echo htmlspecialchars($asm['subject']); ?>">
              <td><strong style="color:var(--secondary);"><?php echo htmlspecialchars($asm['assignment_id']); ?></strong></td>
              <td>
                <div style="font-weight:700; color:var(--text-main);"><?php echo htmlspecialchars($asm['title']); ?></div>
                <small style="color:var(--text-muted);"><?php echo htmlspecialchars($asm['subject']); ?> &bull; <?php echo $asm['word_count']; ?> words</small>
              </td>
              <td><?php echo htmlspecialchars($asm['assignment_type']); ?></td>
              <td><span class="badge badge-secondary"><?php echo htmlspecialchars($asm['priority']); ?></span></td>
              <td><span class="badge <?php echo $sla['badge_class']; ?>"><i class="fa-solid fa-clock"></i> <?php echo $sla['label']; ?></span></td>
              <td><span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($asm['status']); ?></span></td>
              <td><strong>$<?php echo number_format($asm['final_price'], 2); ?></strong></td>
              <td>
                <a href="/student/assignment-detail.php?id=<?php echo urlencode($asm['assignment_id']); ?>" class="btn btn-outline btn-sm">
                  Details &rarr;
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
