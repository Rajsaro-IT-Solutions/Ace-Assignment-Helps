<?php
$pageTitle = "Master Assignments Directory";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Admin');
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

$assignments = DataStore::getCollection('assignments');
?>

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
    <h3 style="font-size:1.1rem; color:#fff;"><i class="fa-solid fa-folder-tree" style="color:var(--primary);"></i> Global Assignments Master List</h3>
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
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($assignments as $asm): 
          $student = DataStore::findOne('students', 'student_id', $asm['student_id']);
          $expert = DataStore::findOne('experts', 'expert_id', $asm['expert_id']);
          $sla = get_sla_status($asm['deadline']);
        ?>
          <tr data-status="<?php echo htmlspecialchars($asm['status']); ?>" data-priority="<?php echo htmlspecialchars($asm['priority']); ?>">
            <td><strong style="color:var(--secondary);"><?php echo htmlspecialchars($asm['assignment_id']); ?></strong></td>
            <td>
              <strong style="color:#fff;"><?php echo htmlspecialchars($student['name'] ?? 'Student'); ?></strong><br>
              <small style="color:var(--text-muted);"><?php echo htmlspecialchars($student['email'] ?? ''); ?></small>
            </td>
            <td>
              <div style="font-weight:700; color:#fff;"><?php echo htmlspecialchars($asm['subject']); ?></div>
              <small style="color:var(--text-muted);"><?php echo $asm['word_count']; ?> words &bull; <?php echo htmlspecialchars($asm['assignment_type']); ?></small>
            </td>
            <td><?php echo htmlspecialchars($expert['name'] ?? 'Unassigned'); ?></td>
            <td><?php echo htmlspecialchars($asm['country']); ?></td>
            <td><span class="badge <?php echo $sla['badge_class']; ?>"><i class="fa-solid fa-clock"></i> <?php echo $sla['label']; ?></span></td>
            <td><span class="badge <?php echo get_status_badge_class($asm['status']); ?>"><?php echo htmlspecialchars($asm['status']); ?></span></td>
            <td><strong>$<?php echo number_format($asm['final_price'], 2); ?></strong></td>
            <td>
              <a href="/admin/assignment-detail.php?id=<?php echo urlencode($asm['assignment_id']); ?>" class="btn btn-primary btn-sm">
                Control &rarr;
              </a>
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
