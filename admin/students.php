<?php
$pageTitle = "Student Management";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Admin');
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

$students = DataStore::getCollection('students');
?>

<div class="filter-bar">
  <input type="text" id="tableSearchInput" class="form-control" placeholder="Search student by name, email, phone...">
</div>

<div class="table-card">
  <div class="table-header">
    <h3 style="font-size:1.1rem; color:#fff;"><i class="fa-solid fa-users" style="color:var(--primary);"></i> Registered Students Roster</h3>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Student ID</th>
          <th>Name</th>
          <th>Email Address</th>
          <th>Phone / WhatsApp</th>
          <th>Country</th>
          <th>University</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($students as $stu): ?>
          <tr>
            <td><strong style="color:var(--secondary);"><?php echo htmlspecialchars($stu['student_id']); ?></strong></td>
            <td><strong style="color:#fff;"><?php echo htmlspecialchars($stu['name']); ?></strong></td>
            <td><?php echo htmlspecialchars($stu['email']); ?></td>
            <td><?php echo htmlspecialchars($stu['phone']); ?></td>
            <td><?php echo htmlspecialchars($stu['country']); ?></td>
            <td><?php echo htmlspecialchars($stu['university']); ?></td>
            <td><span class="badge badge-success"><?php echo htmlspecialchars($stu['status']); ?></span></td>
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
