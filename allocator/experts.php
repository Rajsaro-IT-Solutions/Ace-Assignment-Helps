<?php
$pageTitle = "Expert Directory & Availability";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole(['Allocator', 'Admin']);
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

$experts = DataStore::getCollection('experts');
?>

<div class="table-card">
  <div class="table-header">
    <h3 style="font-size:1.1rem; color:var(--text-main);"><i class="fa-solid fa-user-graduate" style="color:var(--primary);"></i> Verified Experts Roster</h3>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Expert ID</th>
          <th>Name & Title</th>
          <th>Specialization Subjects</th>
          <th>Rating</th>
          <th>Completed Count</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($experts as $exp): ?>
          <tr>
            <td><strong style="color:var(--secondary);"><?php echo htmlspecialchars($exp['expert_id']); ?></strong></td>
            <td><strong style="color:var(--text-main);"><?php echo htmlspecialchars($exp['name']); ?></strong></td>
            <td>
              <?php foreach ($exp['subjects'] as $subj): ?>
                <span class="badge badge-info" style="font-size:0.7rem;"><?php echo htmlspecialchars($subj); ?></span>
              <?php endforeach; ?>
            </td>
            <td><strong style="color:#d97706;"><i class="fa-solid fa-star"></i> <?php echo $exp['rating']; ?> / 5.0</strong></td>
            <td><strong><?php echo $exp['completed_count']; ?> assignments</strong></td>
            <td>
              <span class="badge <?php echo ($exp['status'] === 'Available') ? 'badge-success' : 'badge-warning'; ?>">
                <?php echo htmlspecialchars($exp['status']); ?>
              </span>
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
