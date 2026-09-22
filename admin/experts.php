<?php
$pageTitle = "PhD Expert Roster & Management";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Admin');
$adminUser = Auth::currentUser();
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

$msg = '';
$msgType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Create Expert
    if ($action === 'create_expert') {
        $name = trim($_POST['name'] ?? '');
        $email = trim(strtolower($_POST['email'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');
        $rawPass = trim($_POST['password'] ?? '') ?: 'password';
        $subjRaw = trim($_POST['subjects'] ?? 'General Studies');
        $subjects = array_filter(array_map('trim', explode(',', $subjRaw)));
        if (empty($subjects)) $subjects = ['General Studies'];
        $rating = (float)($_POST['rating'] ?? 5.0);
        $status = $_POST['status'] ?? 'Available';

        if (!empty($name)) {
            $idCount = count(DataStore::getCollection('experts')) + 301;
            $expId = 'EXP-' . $idCount;
            DataStore::insert('experts', [
                'expert_id' => $expId,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'password' => password_hash($rawPass, PASSWORD_DEFAULT),
                'subjects' => array_values($subjects),
                'rating' => $rating,
                'completed_count' => 0,
                'status' => $status
            ]);
            add_audit_log('Admin', $adminUser['id'], 'Create Expert', "Created expert $name ($expId)");
            $msg = "New Expert account created successfully for $name ($expId)!";
            $msgType = 'success';
        } else {
            $msg = "Expert name is required!";
            $msgType = 'danger';
        }
    }

    // 2. Update Expert
    if ($action === 'update_expert') {
        $expert_id = trim($_POST['expert_id'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $email = trim(strtolower($_POST['email'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');
        $subjRaw = trim($_POST['subjects'] ?? 'General Studies');
        $subjects = array_filter(array_map('trim', explode(',', $subjRaw)));
        if (empty($subjects)) $subjects = ['General Studies'];
        $rating = (float)($_POST['rating'] ?? 5.0);
        $completed_count = (int)($_POST['completed_count'] ?? 0);
        $status = $_POST['status'] ?? 'Available';

        if ($expert_id && $name) {
            $updateData = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'subjects' => array_values($subjects),
                'rating' => $rating,
                'completed_count' => $completed_count,
                'status' => $status
            ];
            $rawPass = trim($_POST['password'] ?? '');
            if (!empty($rawPass)) {
                $updateData['password'] = password_hash($rawPass, PASSWORD_DEFAULT);
            }
            DataStore::update('experts', 'expert_id', $expert_id, $updateData);
            add_audit_log('Admin', $adminUser['id'], 'Update Expert', "Updated expert $expert_id");
            $msg = "Expert $expert_id ($name) updated successfully!";
            $msgType = 'success';
        } else {
            $msg = "Required fields missing for expert update.";
            $msgType = 'danger';
        }
    }

    // 3. Toggle Status (Block / Unblock Access)
    if ($action === 'toggle_status') {
        $expert_id = trim($_POST['expert_id'] ?? '');
        $newStatus = trim($_POST['status'] ?? 'Available');
        if ($expert_id) {
            DataStore::update('experts', 'expert_id', $expert_id, ['status' => $newStatus]);
            $actionLabel = ($newStatus === 'Blocked') ? 'BLOCKED access for' : 'RESTORED access for';
            add_audit_log('Admin', $adminUser['id'], 'Expert Access Status', "$actionLabel $expert_id");
            $msg = "Successfully " . ($newStatus === 'Blocked' ? 'blocked expert' : 'unblocked expert') . " $expert_id.";
            $msgType = ($newStatus === 'Blocked') ? 'warning' : 'success';
        }
    }

    // 4. Reset Expert Password
    if ($action === 'reset_expert_password') {
        $expert_id = trim($_POST['expert_id'] ?? '');
        $new_pass = trim($_POST['password'] ?? '');
        if ($expert_id && !empty($new_pass)) {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            DataStore::update('experts', 'expert_id', $expert_id, ['password' => $hashed]);
            add_audit_log('Admin', $adminUser['id'], 'Reset Expert Password', "Reset password for expert $expert_id");
            $msg = "Password for expert $expert_id has been successfully updated!";
            $msgType = 'success';
        } else {
            $msg = "Password cannot be empty!";
            $msgType = 'danger';
        }
    }

    // 5. Delete Expert
    if ($action === 'delete_expert') {
        $expert_id = trim($_POST['expert_id'] ?? '');
        if ($expert_id) {
            DataStore::delete('experts', 'expert_id', $expert_id);
            add_audit_log('Admin', $adminUser['id'], 'Delete Expert', "Permanently deleted expert $expert_id");
            $msg = "Expert $expert_id has been permanently deleted.";
            $msgType = 'danger';
        }
    }
}

$experts = DataStore::getCollection('experts');
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h2 style="font-size:1.5rem; color:var(--text-main); margin-bottom:0.25rem;">
      <i class="fa-solid fa-user-ninja" style="color:var(--primary);"></i> PhD Experts Directory & Assignment Allocation
    </h2>
    <p style="color:var(--text-muted); font-size:0.9rem; margin:0;">Verified PhD academic specialists, assignment allocation capacity, ratings, and roster controls.</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('addExpertModal')">
    <i class="fa-solid fa-user-plus"></i> Add New Expert
  </button>
</div>

<?php if ($msg): ?>
  <div class="badge badge-<?php echo $msgType; ?>" style="width:100%; padding:0.9rem; margin-bottom:1.5rem; text-align:center; font-size:0.95rem; display:block;">
    <i class="fa-solid fa-circle-info"></i> <?php echo htmlspecialchars($msg); ?>
  </div>
<?php endif; ?>

<div class="filter-bar" style="margin-bottom:1.2rem; display:flex; gap:12px; flex-wrap:wrap;">
  <input type="text" id="tableSearchInput" class="form-control" style="flex:2; min-width:240px;" placeholder="Search expert by ID, name, email, subject...">
  <select id="tableStatusFilter" class="form-control" style="flex:1; min-width:180px;">
    <option value="">All Statuses (Available, Busy, Blocked)</option>
    <option value="Available">Available for Orders</option>
    <option value="Busy">Currently Busy</option>
    <option value="Blocked">Blocked Experts</option>
  </select>
</div>

<div class="table-card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Expert ID</th>
          <th>Expert Name</th>
          <th>Contact Info</th>
          <th>Specialization Subjects</th>
          <th>Rating</th>
          <th>Completed</th>
          <th>Status</th>
          <th style="text-align:center; min-width:210px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($experts)): ?>
          <tr>
            <td colspan="8" style="text-align:center; padding:2rem; color:var(--text-muted);">No experts found in roster. Click "Add New Expert" to register one.</td>
          </tr>
        <?php endif; ?>
        <?php foreach ($experts as $exp): 
          $isBlocked = (isset($exp['status']) && strtolower($exp['status']) === 'blocked');
          $statusClass = 'badge-warning';
          if ($exp['status'] === 'Available') $statusClass = 'badge-success';
          elseif ($isBlocked) $statusClass = 'badge-danger';
        ?>
          <tr data-status="<?php echo htmlspecialchars($exp['status']); ?>">
            <td><strong style="color:var(--secondary); font-family:monospace;"><?php echo htmlspecialchars($exp['expert_id']); ?></strong></td>
            <td><strong style="color:var(--text-main);"><?php echo htmlspecialchars($exp['name']); ?></strong></td>
            <td>
              <?php echo htmlspecialchars($exp['email'] ?? ''); ?><br>
              <small style="color:var(--text-muted);"><?php echo htmlspecialchars($exp['phone'] ?? 'N/A'); ?></small>
            </td>
            <td>
              <?php 
                $subjs = is_array($exp['subjects']) ? $exp['subjects'] : explode(',', (string)$exp['subjects']);
                foreach ($subjs as $subj): 
              ?>
                <span class="badge badge-info" style="font-size:0.75rem; margin:2px;"><?php echo htmlspecialchars(trim($subj)); ?></span>
              <?php endforeach; ?>
            </td>
            <td><strong style="color:#d97706;"><i class="fa-solid fa-star"></i> <?php echo number_format((float)$exp['rating'], 1); ?></strong></td>
            <td><strong><?php echo (int)($exp['completed_count'] ?? 0); ?> orders</strong></td>
            <td>
              <span class="badge <?php echo $statusClass; ?>">
                <?php if ($isBlocked): ?>
                  <i class="fa-solid fa-ban"></i> Blocked
                <?php else: ?>
                  <?php echo htmlspecialchars($exp['status']); ?>
                <?php endif; ?>
              </span>
            </td>
            <td style="text-align:center;">
              <div style="display:inline-flex; gap:6px; align-items:center; justify-content:center;">
                <!-- Edit Button -->
                <button type="button" class="btn btn-outline btn-sm" onclick='editExpert(<?php echo json_encode($exp); ?>)' title="Edit Expert Profile">
                  <i class="fa-solid fa-pen-to-square"></i> Edit
                </button>

                <!-- Password Button -->
                <button type="button" class="btn btn-outline btn-sm" onclick='openResetPasswordModal(<?php echo json_encode($exp); ?>)' title="Manage Login Password">
                  <i class="fa-solid fa-key" style="color:#d97706;"></i> Password
                </button>

                <!-- Block / Unblock Access Button -->
                <?php if ($isBlocked): ?>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Restore availability for expert <?php echo addslashes($exp['name']); ?>?');">
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="expert_id" value="<?php echo htmlspecialchars($exp['expert_id']); ?>">
                    <input type="hidden" name="status" value="Available">
                    <button type="submit" class="btn btn-success btn-sm" title="Restore expert availability">
                      <i class="fa-solid fa-unlock"></i> Unblock
                    </button>
                  </form>
                <?php else: ?>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Block expert <?php echo addslashes($exp['name']); ?>? They will not be assigned orders.');">
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="expert_id" value="<?php echo htmlspecialchars($exp['expert_id']); ?>">
                    <input type="hidden" name="status" value="Blocked">
                    <button type="submit" class="btn btn-warning btn-sm" title="Block expert">
                      <i class="fa-solid fa-ban"></i> Block
                    </button>
                  </form>
                <?php endif; ?>

                <!-- Delete Expert Button -->
                <form method="POST" style="display:inline;" onsubmit="return confirm('Permanently DELETE expert <?php echo addslashes($exp['name']); ?> (<?php echo htmlspecialchars($exp['expert_id']); ?>)? This cannot be undone!');">
                  <input type="hidden" name="action" value="delete_expert">
                  <input type="hidden" name="expert_id" value="<?php echo htmlspecialchars($exp['expert_id']); ?>">
                  <button type="submit" class="btn btn-danger btn-sm" title="Permanently Delete Expert">
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

<!-- Add Expert Modal -->
<div id="addExpertModal" class="modal-overlay">
  <div class="modal-box" style="padding:2rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem;">
      <h3 style="margin:0; color:var(--text-main);"><i class="fa-solid fa-user-plus" style="color:var(--primary);"></i> Add Academic Expert</h3>
      <button type="button" onclick="closeModal('addExpertModal')" style="background:none; border:none; font-size:1.3rem; color:var(--text-muted); cursor:pointer;">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="create_expert">
      <div class="form-group">
        <label>Full Name & Academic Title *</label>
        <input type="text" name="name" class="form-control" required placeholder="e.g. Dr. Arthur Pendelton (PhD)">
      </div>
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" class="form-control" placeholder="expert@university.edu">
      </div>
      <div class="form-group">
        <label>Phone / WhatsApp Number</label>
        <input type="text" name="phone" class="form-control" placeholder="+1 555 999 0000">
      </div>
      <div class="form-group">
        <label>Subject Specialties (Comma Separated) *</label>
        <input type="text" name="subjects" class="form-control" required placeholder="Computer Science, Python, Artificial Intelligence">
      </div>
      <div class="form-group">
        <label>Portal Login Password (Default: 'password')</label>
        <input type="text" name="password" class="form-control" placeholder="password" value="password">
        <small style="color:var(--text-muted); font-size:0.75rem;">Default password is 'password'. You can change or reset this at any time.</small>
      </div>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div class="form-group">
          <label>Expert Rating</label>
          <input type="number" step="0.1" name="rating" class="form-control" value="5.0" min="1" max="5">
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="status" class="form-control">
            <option value="Available">Available</option>
            <option value="Busy">Busy</option>
            <option value="Blocked">Blocked</option>
          </select>
        </div>
      </div>
      <div style="display:flex; gap:10px; margin-top:1.5rem;">
        <button type="submit" class="btn btn-primary" style="flex:1;">Save Expert</button>
        <button type="button" class="btn btn-outline" onclick="closeModal('addExpertModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Expert Modal -->
<div id="editExpertModal" class="modal-overlay">
  <div class="modal-box" style="padding:2rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem;">
      <h3 style="margin:0; color:var(--text-main);"><i class="fa-solid fa-user-pen" style="color:var(--primary);"></i> Edit Expert Details</h3>
      <button type="button" onclick="closeModal('editExpertModal')" style="background:none; border:none; font-size:1.3rem; color:var(--text-muted); cursor:pointer;">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="update_expert">
      <input type="hidden" name="expert_id" id="edit_expert_id">

      <div class="form-group">
        <label>Expert ID</label>
        <input type="text" id="edit_expert_display_id" class="form-control" readonly style="background:#f1f5f9; font-family:monospace; font-weight:700;">
      </div>
      <div class="form-group">
        <label>Full Name & Academic Title *</label>
        <input type="text" name="name" id="edit_expert_name" class="form-control" required>
      </div>
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" id="edit_expert_email" class="form-control">
      </div>
      <div class="form-group">
        <label>Phone / WhatsApp Number</label>
        <input type="text" name="phone" id="edit_expert_phone" class="form-control">
      </div>
      <div class="form-group">
        <label>Subject Specialties (Comma Separated)</label>
        <input type="text" name="subjects" id="edit_expert_subjects" class="form-control">
      </div>
      <div class="form-group">
        <label>Change Login Password (leave blank to keep current)</label>
        <input type="text" name="password" id="edit_expert_password" class="form-control" placeholder="Enter new password to update">
      </div>
      <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px;">
        <div class="form-group">
          <label>Rating</label>
          <input type="number" step="0.1" name="rating" id="edit_expert_rating" class="form-control" min="1" max="5">
        </div>
        <div class="form-group">
          <label>Completed Orders</label>
          <input type="number" name="completed_count" id="edit_expert_completed" class="form-control" min="0">
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="status" id="edit_expert_status" class="form-control">
            <option value="Available">Available</option>
            <option value="Busy">Busy</option>
            <option value="Blocked">Blocked</option>
          </select>
        </div>
      </div>
      <div style="display:flex; gap:10px; margin-top:1.5rem;">
        <button type="submit" class="btn btn-primary" style="flex:1;">Save Changes</button>
        <button type="button" class="btn btn-outline" onclick="closeModal('editExpertModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Reset Password Modal -->
<div id="resetPasswordModal" class="modal-overlay">
  <div class="modal-box" style="padding:2rem; max-width:460px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem;">
      <h3 style="margin:0; color:var(--text-main);"><i class="fa-solid fa-key" style="color:var(--warning);"></i> Manage Expert Password</h3>
      <button type="button" onclick="closeModal('resetPasswordModal')" style="background:none; border:none; font-size:1.3rem; color:var(--text-muted); cursor:pointer;">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="reset_expert_password">
      <input type="hidden" name="expert_id" id="reset_expert_id">

      <p style="font-size:0.9rem; color:var(--text-muted); margin-bottom:1.2rem;">
        Setting login password for <strong id="reset_expert_name" style="color:var(--text-main);"></strong> (<code id="reset_expert_display_id" style="font-weight:700;"></code>).
      </p>

      <div class="form-group">
        <label>New Password *</label>
        <input type="text" name="password" id="reset_expert_password_input" class="form-control" required placeholder="Enter new password">
      </div>

      <div style="display:flex; gap:8px; margin-bottom:1.5rem; flex-wrap:wrap;">
        <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('reset_expert_password_input').value='password';">
          <i class="fa-solid fa-rotate-left"></i> Set to 'password'
        </button>
        <button type="button" class="btn btn-outline btn-sm" onclick="generateRandomPass()">
          <i class="fa-solid fa-wand-magic-sparkles"></i> Generate Strong
        </button>
      </div>

      <div style="display:flex; gap:10px;">
        <button type="submit" class="btn btn-primary" style="flex:1;">Update Password</button>
        <button type="button" class="btn btn-outline" onclick="closeModal('resetPasswordModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function editExpert(data) {
  document.getElementById('edit_expert_id').value = data.expert_id || '';
  document.getElementById('edit_expert_display_id').value = data.expert_id || '';
  document.getElementById('edit_expert_name').value = data.name || '';
  document.getElementById('edit_expert_email').value = data.email || '';
  document.getElementById('edit_expert_phone').value = data.phone || '';
  const subjs = Array.isArray(data.subjects) ? data.subjects.join(', ') : (data.subjects || '');
  document.getElementById('edit_expert_subjects').value = subjs;
  document.getElementById('edit_expert_rating').value = data.rating || '5.0';
  document.getElementById('edit_expert_completed').value = data.completed_count || '0';
  document.getElementById('edit_expert_status').value = data.status || 'Available';
  document.getElementById('edit_expert_password').value = '';
  openModal('editExpertModal');
}

function openResetPasswordModal(data) {
  document.getElementById('reset_expert_id').value = data.expert_id || '';
  document.getElementById('reset_expert_display_id').textContent = data.expert_id || '';
  document.getElementById('reset_expert_name').textContent = data.name || 'Expert';
  document.getElementById('reset_expert_password_input').value = 'password';
  openModal('resetPasswordModal');
}

function generateRandomPass() {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%';
  let pass = '';
  for (let i = 0; i < 10; i++) {
    pass += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  document.getElementById('reset_expert_password_input').value = pass;
}
</script>

<script src="/assets/js/portal.js"></script>
</div>
</div>
</body>
</html>
