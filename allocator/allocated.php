<?php
$pageTitle = "Allocated & Active Assignments";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Allocator');
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';
$user = Auth::currentUser();
$allocated = DataStore::filter('assignments', function($a) use ($user) {
    return ($a['allocator_id'] ?? '') === $user['id'] && in_array($a['status'], ['Allocated', 'In Progress', 'Quality Check', 'Pending Admin Approval']);
});
?>

<div class="table-card">
  <div class="table-header">
    <h3 style="font-size:1.1rem; color:var(--text-main);"><i class="fa-solid fa-bars-staggered" style="color:var(--primary);"></i> Active Assignments In Production</h3>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Assignment ID</th>
          <th>Student (Masked)</th>
          <th>Allocated Expert</th>
          <th>Subject</th>
          <th>SLA Deadline</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($allocated as $asm): 
          $student = DataStore::findOne('students', 'student_id', $asm['student_id']);
          $expert = DataStore::findOne('experts', 'expert_id', $asm['expert_id']);
          $sla = get_sla_status($asm['deadline']);
        ?>
          <tr>
            <td><strong style="color:var(--secondary);"><?php echo htmlspecialchars($asm['assignment_id']); ?></strong></td>
            <td><i class="fa-solid fa-user-lock"></i> <?php echo htmlspecialchars(mask_student_name($student['name'] ?? '')); ?></td>
            <td><strong><?php echo htmlspecialchars($expert['name'] ?? 'Unassigned'); ?></strong></td>
            <td><?php echo htmlspecialchars($asm['subject']); ?></td>
            <td><span class="badge <?php echo $sla['badge_class']; ?>"><?php echo $sla['label']; ?></span></td>
            <td><span class="badge <?php echo get_status_badge_class($asm['status']); ?>"><?php echo htmlspecialchars($asm['status']); ?></span></td>
            <td>
              <a href="/allocator/assignment-detail.php?id=<?php echo urlencode($asm['assignment_id']); ?>" class="btn btn-outline btn-sm">
                Manage &rarr;
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

</div>
</div>
</body>
</html>
