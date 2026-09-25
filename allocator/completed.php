<?php
$pageTitle = "Completed Assignments Log";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Allocator');
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';
$user = Auth::currentUser();
$completed = DataStore::filter('assignments', function($a) use ($user) {
    return ($a['allocator_id'] ?? '') === $user['id'] && in_array($a['status'], ['Completed', 'Delivered']);
});
?>

<div class="table-card">
  <div class="table-header">
    <h3 style="font-size:1.1rem; color:var(--text-main);"><i class="fa-solid fa-circle-check" style="color:var(--success);"></i> Delivered Assignments Archive</h3>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Assignment ID</th>
          <th>Student (Masked)</th>
          <th>Expert</th>
          <th>Subject</th>
          <th>Completed Date</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($completed as $asm): 
          $student = DataStore::findOne('students', 'student_id', $asm['student_id']);
          $expert = DataStore::findOne('experts', 'expert_id', $asm['expert_id']);
        ?>
          <tr>
            <td><strong style="color:var(--secondary);"><?php echo htmlspecialchars($asm['assignment_id']); ?></strong></td>
            <td><i class="fa-solid fa-user-lock"></i> <?php echo htmlspecialchars(mask_student_name($student['name'] ?? '')); ?></td>
            <td><strong><?php echo htmlspecialchars($expert['name'] ?? ''); ?></strong></td>
            <td><?php echo htmlspecialchars($asm['subject']); ?></td>
            <td><?php echo htmlspecialchars($asm['updated_at']); ?></td>
            <td><span class="badge badge-success"><?php echo htmlspecialchars($asm['status']); ?></span></td>
            <td>
              <a href="/allocator/assignment-detail.php?id=<?php echo urlencode($asm['assignment_id']); ?>" class="btn btn-outline btn-sm">
                View Log
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
