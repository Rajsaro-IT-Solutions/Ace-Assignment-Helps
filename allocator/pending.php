<?php
$pageTitle = "Pending Allocation Queue";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Allocator');
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';
$user = Auth::currentUser();
$pending = DataStore::filter('assignments', function($a) use ($user) {
    return ($a['allocator_id'] ?? '') === $user['id'] && (empty($a['expert_id']) || in_array($a['status'], ['New', 'Pending Review', 'Confirmed', 'Allocated']));
});
?>

<div class="privacy-banner">
  <i class="fa-solid fa-user-shield"></i>
  <span>Privacy Notice: Student email, phone, and billing details are hidden for Allocator role.</span>
</div>

<div class="table-card">
  <div class="table-header">
    <h3 style="font-size:1.1rem; color:var(--text-main);"><i class="fa-solid fa-hourglass-start" style="color:var(--warning);"></i> Unallocated Assignments</h3>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Student (Masked)</th>
          <th>Subject</th>
          <th>Country</th>
          <th>Deadline</th>
          <th>Priority</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pending as $asm): 
          $student = DataStore::findOne('students', 'student_id', $asm['student_id']);
          $sla = get_sla_status($asm['deadline']);
        ?>
          <tr>
            <td><strong style="color:var(--secondary);"><?php echo htmlspecialchars($asm['assignment_id']); ?></strong></td>
            <td><i class="fa-solid fa-user-lock"></i> <?php echo htmlspecialchars(mask_student_name($student['name'] ?? '')); ?></td>
            <td><strong><?php echo htmlspecialchars($asm['subject']); ?></strong> (<?php echo $asm['word_count']; ?>w)</td>
            <td><?php echo htmlspecialchars($asm['country']); ?></td>
            <td><span class="badge <?php echo $sla['badge_class']; ?>"><?php echo $sla['label']; ?></span></td>
            <td><span class="badge badge-warning"><?php echo htmlspecialchars($asm['priority']); ?></span></td>
            <td>
              <a href="/allocator/assignment-detail.php?id=<?php echo urlencode($asm['assignment_id']); ?>" class="btn btn-primary btn-sm">
                Allocate &rarr;
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
