<?php
$pageTitle = "Student Account Management";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Admin');
$adminUser = Auth::currentUser();
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

$msg = '';
$msgType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Create Student
    if ($action === 'create_student') {
        $name = trim($_POST['name'] ?? '');
        $email = trim(strtolower($_POST['email'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $university = trim($_POST['university'] ?? '');
        $course = trim($_POST['course'] ?? '');
        $password = trim($_POST['password'] ?? 'password');
        $status = $_POST['status'] ?? 'Active';

        if (empty($name) || empty($email)) {
            $msg = "Student Name and Email Address are required!";
            $msgType = 'danger';
        } else {
            $existing = DataStore::findOne('students', 'email', $email);
            if ($existing) {
                $msg = "A student with email '$email' already exists!";
                $msgType = 'danger';
            } else {
                $count = count(DataStore::getCollection('students')) + 1001;
                $newId = 'STU-' . $count;
                DataStore::insert('students', [
                    'student_id' => $newId,
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'country' => $country ?: 'United Kingdom',
                    'university' => $university ?: 'University',
                    'course' => $course ?: 'Academic Studies',
                    'status' => $status,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                add_audit_log('Admin', $adminUser['id'], 'Create Student', "Added student $name ($newId)");
                $msg = "New student account created successfully for $name ($newId)!";
                $msgType = 'success';
            }
        }
    }

    // 2. Update Student
    if ($action === 'update_student') {
        $student_id = trim($_POST['student_id'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $email = trim(strtolower($_POST['email'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $university = trim($_POST['university'] ?? '');
        $course = trim($_POST['course'] ?? '');
        $status = $_POST['status'] ?? 'Active';
        $newPassword = trim($_POST['new_password'] ?? '');

        if ($student_id && $name && $email) {
            $updates = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'country' => $country,
                'university' => $university,
                'course' => $course,
                'status' => $status
            ];
            if (!empty($newPassword)) {
                $updates['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
            }
            DataStore::update('students', 'student_id', $student_id, $updates);
            add_audit_log('Admin', $adminUser['id'], 'Update Student', "Updated student details for $student_id");
            $msg = "Student account $student_id ($name) updated successfully!";
            $msgType = 'success';
        } else {
            $msg = "Required fields missing for student update.";
            $msgType = 'danger';
        }
    }

    // 3. Toggle Status (Block / Unblock Access)
    if ($action === 'toggle_status') {
        $student_id = trim($_POST['student_id'] ?? '');
        $newStatus = trim($_POST['status'] ?? 'Active');
        if ($student_id) {
            DataStore::update('students', 'student_id', $student_id, ['status' => $newStatus]);
            $actionLabel = ($newStatus === 'Blocked') ? 'BLOCKED access for' : 'UNBLOCKED access for';
            add_audit_log('Admin', $adminUser['id'], 'Student Access Status', "$actionLabel student $student_id");
            $msg = "Successfully " . ($newStatus === 'Blocked' ? 'blocked portal access for' : 'restored access for') . " student $student_id.";
            $msgType = ($newStatus === 'Blocked') ? 'warning' : 'success';
        }
    }

    // 4. Delete Student
    if ($action === 'delete_student') {
        $student_id = trim($_POST['student_id'] ?? '');
        if ($student_id) {
            DataStore::delete('students', 'student_id', $student_id);
            add_audit_log('Admin', $adminUser['id'], 'Delete Student', "Permanently deleted student $student_id");
            $msg = "Student $student_id has been permanently deleted from the system.";
            $msgType = 'danger';
        }
    }
}

$students = DataStore::getCollection('students');
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h2 style="font-size:1.5rem; color:var(--text-main); margin-bottom:0.25rem;">
      <i class="fa-solid fa-user-graduate" style="color:var(--primary);"></i> Student Accounts & Access Management
    </h2>
    <p style="color:var(--text-muted); font-size:0.9rem; margin:0;">Manage enrolled students, update profiles, grant or block portal access, and register new accounts.</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('addStudentModal')">
    <i class="fa-solid fa-user-plus"></i> Register New Student
  </button>
</div>

<?php if ($msg): ?>
  <div class="badge badge-<?php echo $msgType; ?>" style="width:100%; padding:0.9rem; margin-bottom:1.5rem; text-align:center; font-size:0.95rem; display:block;">
    <i class="fa-solid fa-circle-info"></i> <?php echo htmlspecialchars($msg); ?>
  </div>
<?php endif; ?>

<div class="filter-bar" style="margin-bottom:1.2rem; display:flex; gap:12px; flex-wrap:wrap;">
  <input type="text" id="tableSearchInput" class="form-control" style="flex:2; min-width:240px;" placeholder="Search student by ID, name, email, university, country...">
  <select id="tableStatusFilter" class="form-control" style="flex:1; min-width:180px;">
    <option value="">All Statuses (Active & Blocked)</option>
    <option value="Active">Active Students Only</option>
    <option value="Blocked">Blocked Students Only</option>
  </select>
</div>

<div class="table-card">
  <div class="table-header" style="display:flex; justify-content:space-between; align-items:center;">
    <h3 style="font-size:1.1rem; color:var(--text-main); margin:0;">
      <i class="fa-solid fa-users" style="color:var(--primary);"></i> Registered Students Roster (<?php echo count($students); ?>)
    </h3>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Student ID</th>
          <th>Student Name</th>
          <th>Email Address</th>
          <th>Phone / WhatsApp</th>
          <th>Country</th>
          <th>University & Course</th>
          <th>Status</th>
          <th style="text-align:center; min-width:210px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($students)): ?>
          <tr>
            <td colspan="8" style="text-align:center; padding:2rem; color:var(--text-muted);">No student accounts found. Click "Register New Student" above to add one.</td>
          </tr>
        <?php endif; ?>
        <?php foreach ($students as $stu): 
          $isBlocked = (isset($stu['status']) && strtolower($stu['status']) === 'blocked');
        ?>
          <tr data-status="<?php echo htmlspecialchars($stu['status']); ?>">
            <td><strong style="color:var(--primary); font-family:monospace; font-size:0.95rem;"><?php echo htmlspecialchars($stu['student_id']); ?></strong></td>
            <td>
              <strong style="color:var(--text-main); display:block;"><?php echo htmlspecialchars($stu['name']); ?></strong>
              <small style="color:var(--text-muted);"><?php echo htmlspecialchars($stu['course'] ?? 'General Studies'); ?></small>
            </td>
            <td><?php echo htmlspecialchars($stu['email']); ?></td>
            <td><?php echo htmlspecialchars($stu['phone'] ?? 'N/A'); ?></td>
            <td><span class="badge badge-info"><?php echo htmlspecialchars($stu['country'] ?? 'Global'); ?></span></td>
            <td><?php echo htmlspecialchars($stu['university'] ?? 'Not Specified'); ?></td>
            <td>
              <?php if ($isBlocked): ?>
                <span class="badge badge-danger"><i class="fa-solid fa-ban"></i> Blocked</span>
              <?php else: ?>
                <span class="badge badge-success"><i class="fa-solid fa-check"></i> Active</span>
              <?php endif; ?>
            </td>
            <td style="text-align:center;">
              <div style="display:inline-flex; gap:6px; align-items:center; justify-content:center; flex-wrap:nowrap;">
                <!-- Edit Button -->
                <button type="button" class="btn btn-outline btn-sm" onclick='editStudent(<?php echo json_encode($stu); ?>)' title="Edit Student Profile">
                  <i class="fa-solid fa-pen-to-square"></i> Edit
                </button>

                <!-- Block / Unblock Access Button -->
                <?php if ($isBlocked): ?>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Restore portal login access for student <?php echo addslashes($stu['name']); ?>?');">
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($stu['student_id']); ?>">
                    <input type="hidden" name="status" value="Active">
                    <button type="submit" class="btn btn-success btn-sm" title="Restore portal access">
                      <i class="fa-solid fa-unlock"></i> Unblock
                    </button>
                  </form>
                <?php else: ?>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Block portal login access for student <?php echo addslashes($stu['name']); ?>? The student will not be able to log in.');">
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($stu['student_id']); ?>">
                    <input type="hidden" name="status" value="Blocked">
                    <button type="submit" class="btn btn-warning btn-sm" title="Block portal access">
                      <i class="fa-solid fa-ban"></i> Block
                    </button>
                  </form>
                <?php endif; ?>

                <!-- Delete Student Button -->
                <form method="POST" style="display:inline;" onsubmit="return confirm('Permanently DELETE student <?php echo addslashes($stu['name']); ?> (<?php echo htmlspecialchars($stu['student_id']); ?>)? This action cannot be undone!');">
                  <input type="hidden" name="action" value="delete_student">
                  <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($stu['student_id']); ?>">
                  <button type="submit" class="btn btn-danger btn-sm" title="Permanently Delete Account">
                    <i class="fa-solid fa-trash"></i> Delete
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

<!-- Add Student Modal -->
<div id="addStudentModal" class="modal-overlay">
  <div class="modal-box" style="padding:2rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem;">
      <h3 style="margin:0; color:var(--text-main);"><i class="fa-solid fa-user-plus" style="color:var(--primary);"></i> Register New Student</h3>
      <button type="button" onclick="closeModal('addStudentModal')" style="background:none; border:none; font-size:1.3rem; color:var(--text-muted); cursor:pointer;">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="create_student">
      
      <div class="form-group">
        <label>Full Name *</label>
        <input type="text" name="name" class="form-control" required placeholder="e.g. John Doe">
      </div>

      <div class="form-group">
        <label>Email Address *</label>
        <input type="email" name="email" class="form-control" required placeholder="student@university.edu">
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div class="form-group">
          <label>Phone / WhatsApp</label>
          <input type="text" name="phone" class="form-control" placeholder="+1 (555) 000-0000">
        </div>
        <div class="form-group">
          <label>Country</label>
          <input type="text" name="country" class="form-control" placeholder="United Kingdom">
        </div>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div class="form-group">
          <label>University</label>
          <input type="text" name="university" class="form-control" placeholder="e.g. Oxford University">
        </div>
        <div class="form-group">
          <label>Course / Major</label>
          <input type="text" name="course" class="form-control" placeholder="e.g. Computer Science">
        </div>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div class="form-group">
          <label>Initial Password</label>
          <input type="password" name="password" class="form-control" required value="password">
        </div>
        <div class="form-group">
          <label>Access Status</label>
          <select name="status" class="form-control">
            <option value="Active">Active (Allowed Login)</option>
            <option value="Blocked">Blocked (Login Restricted)</option>
          </select>
        </div>
      </div>

      <div style="display:flex; gap:10px; margin-top:1.5rem;">
        <button type="submit" class="btn btn-primary" style="flex:1;">Create Student Account</button>
        <button type="button" class="btn btn-outline" onclick="closeModal('addStudentModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Student Modal -->
<div id="editStudentModal" class="modal-overlay">
  <div class="modal-box" style="padding:2rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem;">
      <h3 style="margin:0; color:var(--text-main);"><i class="fa-solid fa-user-pen" style="color:var(--primary);"></i> Edit Student Details</h3>
      <button type="button" onclick="closeModal('editStudentModal')" style="background:none; border:none; font-size:1.3rem; color:var(--text-muted); cursor:pointer;">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="update_student">
      <input type="hidden" name="student_id" id="edit_student_id">

      <div class="form-group">
        <label>Student ID</label>
        <input type="text" id="edit_display_id" class="form-control" readonly style="background:#f1f5f9; font-family:monospace; font-weight:700;">
      </div>

      <div class="form-group">
        <label>Full Name *</label>
        <input type="text" name="name" id="edit_name" class="form-control" required>
      </div>

      <div class="form-group">
        <label>Email Address *</label>
        <input type="email" name="email" id="edit_email" class="form-control" required>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div class="form-group">
          <label>Phone / WhatsApp</label>
          <input type="text" name="phone" id="edit_phone" class="form-control">
        </div>
        <div class="form-group">
          <label>Country</label>
          <input type="text" name="country" id="edit_country" class="form-control">
        </div>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div class="form-group">
          <label>University</label>
          <input type="text" name="university" id="edit_university" class="form-control">
        </div>
        <div class="form-group">
          <label>Course / Major</label>
          <input type="text" name="course" id="edit_course" class="form-control">
        </div>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div class="form-group">
          <label>Reset Password <small style="color:var(--text-muted);">(Leave blank to keep)</small></label>
          <input type="password" name="new_password" class="form-control" placeholder="••••••••">
        </div>
        <div class="form-group">
          <label>Account Access Status</label>
          <select name="status" id="edit_status" class="form-control">
            <option value="Active">Active (Allowed)</option>
            <option value="Blocked">Blocked (Access Denied)</option>
          </select>
        </div>
      </div>

      <div style="display:flex; gap:10px; margin-top:1.5rem;">
        <button type="submit" class="btn btn-primary" style="flex:1;">Save Changes</button>
        <button type="button" class="btn btn-outline" onclick="closeModal('editStudentModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function editStudent(data) {
  document.getElementById('edit_student_id').value = data.student_id || '';
  document.getElementById('edit_display_id').value = data.student_id || '';
  document.getElementById('edit_name').value = data.name || '';
  document.getElementById('edit_email').value = data.email || '';
  document.getElementById('edit_phone').value = data.phone || '';
  document.getElementById('edit_country').value = data.country || '';
  document.getElementById('edit_university').value = data.university || '';
  document.getElementById('edit_course').value = data.course || '';
  document.getElementById('edit_status').value = data.status || 'Active';
  openModal('editStudentModal');
}
</script>

<script src="/assets/js/portal.js"></script>
</div>
</div>
</body>
</html>
