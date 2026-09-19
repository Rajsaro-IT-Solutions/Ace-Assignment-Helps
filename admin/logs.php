<?php
$pageTitle = "System Audit & Activity Logs";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Admin');
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

$logs = DataStore::getCollection('audit_logs');
?>

<div class="table-card">
  <div class="table-header">
    <h3 style="font-size:1.1rem; color:#fff;"><i class="fa-solid fa-list-check" style="color:var(--primary);"></i> Full System Audit Trail</h3>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Log ID</th>
          <th>User Role</th>
          <th>User ID</th>
          <th>Action</th>
          <th>Details</th>
          <th>Timestamp</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($logs as $log): ?>
          <tr>
            <td><strong style="color:var(--secondary);"><?php echo htmlspecialchars($log['log_id']); ?></strong></td>
            <td><span class="badge badge-primary"><?php echo htmlspecialchars($log['user_role']); ?></span></td>
            <td><?php echo htmlspecialchars($log['user_id']); ?></td>
            <td><strong><?php echo htmlspecialchars($log['action']); ?></strong></td>
            <td><small style="color:var(--text-muted);"><?php echo htmlspecialchars($log['details']); ?></small></td>
            <td><?php echo htmlspecialchars($log['timestamp']); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="/assets/js/portal.js"></script>
</div>
</div>
</body>
</html>
