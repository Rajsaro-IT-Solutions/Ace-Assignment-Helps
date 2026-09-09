<?php
$pageTitle = "Master Assignments Directory";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Admin');
$adminUser = Auth::currentUser();
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

$msg = '';
$msgType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'delete_assignment') {
        $assignment_id = trim($_POST['assignment_id'] ?? '');
        if ($assignment_id) {
            DataStore::delete('assignments', 'assignment_id', $assignment_id);
            add_audit_log('Admin', $adminUser['id'], 'Delete Assignment', "Deleted assignment $assignment_id");
            $msg = "Assignment $assignment_id has been deleted successfully.";
            $msgType = 'danger';
        }
    }
}

$assignments = DataStore::getCollection('assignments');
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h2 style="font-size:1.5rem; color:var(--text-main); margin-bottom:0.25rem;">
      <i class="fa-solid fa-folder-tree" style="color:var(--primary);"></i> Master Assignments Directory
    </h2>
    <p style="color:var(--text-muted); font-size:0.9rem; margin:0;">Complete view of student orders, workflow statuses, expert assignments, and controls.</p>
  </div>
</div>

<?php if ($msg): ?>
  <div class="badge badge-<?php echo $msgType; ?>" style="width:100%; padding:0.9rem; margin-bottom:1.5rem; text-align:center; font-size:0.95rem; display:block;">
    <i class="fa-solid fa-circle-info"></i> <?php echo htmlspecialchars($msg); ?>
  </div>
<?php endif; ?>

<div class="filter-bar">
  <input type="text" id="tableSearchInput" class="form-control" placeholder="Search by ID, title, student, university...">
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
    <option value="Delivered">Delivered</option>
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
    <h3 style="font-size:1.1rem; color:var(--text-main);"><i class="fa-solid fa-folder-tree" style="color:var(--primary);"></i> Global Assignments Master List (<?php echo count($assignments); ?>)</h3>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Assignment ID</th>
          <th>Student Details</th>
          <th>Subject & Type</th>
          <th>Allocated Expert</th>
          <th>Country</th>
          <th>SLA Deadline</th>
          <th>Status</th>
          <th>Price</th>
          <th style="text-align:center; min-width:140px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($assignments as $asm): 
          $student = DataStore::findOne('students', 'student_id', $asm['student_id']);
          $expert = DataStore::findOne('experts', 'expert_id', $asm['expert_id']);
          $sla = get_sla_status($asm['deadline']);
        ?>
          <tr data-status="<?php echo htmlspecialchars($asm['status']); ?>" data-priority="<?php echo htmlspecialchars($asm['priority']); ?>">
            <td><strong style="color:var(--secondary); font-family:monospace;"><?php echo htmlspecialchars($asm['assignment_id']); ?></strong></td>
            <td>
              <strong style="color:var(--text-main);"><?php echo htmlspecialchars($student['name'] ?? 'Student'); ?></strong><br>
              <small style="color:var(--text-muted);"><?php echo htmlspecialchars($student['email'] ?? ''); ?></small>
            </td>
            <td>
              <div style="font-weight:700; color:var(--text-main);"><?php echo htmlspecialchars($asm['subject']); ?></div>
              <small style="color:var(--text-muted);"><?php echo $asm['word_count']; ?> words &bull; <?php echo htmlspecialchars($asm['assignment_type']); ?></small>
            </td>
            <td><?php echo htmlspecialchars($expert['name'] ?? 'Unassigned'); ?></td>
            <td><span class="badge badge-info"><?php echo htmlspecialchars($asm['country']); ?></span></td>
            <td><span class="badge <?php echo $sla['badge_class']; ?>"><i class="fa-solid fa-clock"></i> <?php echo $sla['label']; ?></span></td>
            <td><span class="badge <?php echo get_status_badge_class($asm['status']); ?>"><?php echo htmlspecialchars($asm['status']); ?></span></td>
            <td><strong>$<?php echo number_format($asm['final_price'], 2); ?></strong></td>
            <td style="text-align:center;">
              <div style="display:inline-flex; gap:6px; align-items:center; justify-content:center;">
                <a href="/admin/assignment-detail.php?id=<?php echo urlencode($asm['assignment_id']); ?>" class="btn btn-primary btn-sm" title="View & Edit Assignment">
                  Control &rarr;
                </a>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Permanently DELETE assignment <?php echo addslashes($asm['assignment_id']); ?>?');">
                  <input type="hidden" name="action" value="delete_assignment">
                  <input type="hidden" name="assignment_id" value="<?php echo htmlspecialchars($asm['assignment_id']); ?>">
                  <button type="submit" class="btn btn-danger btn-sm" title="Delete Assignment">
                    <i class="fa-solid fa-trash"></i>
                  </button>
                </form>
              </div>
            </td>
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
