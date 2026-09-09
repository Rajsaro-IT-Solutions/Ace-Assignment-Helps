<?php
$pageTitle = "Administrator Accounts Management";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Admin');
$adminUser = Auth::currentUser();
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

$msg = '';
$msgType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Create Admin
    if ($action === 'create_admin') {
        $name = trim($_POST['name'] ?? '');
        $email = trim(strtolower($_POST['email'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');
        $pass = trim($_POST['password'] ?? 'password');
        $status = $_POST['status'] ?? 'Active';

        if (!empty($name) && !empty($email)) {
            $existing = DataStore::findOne('admins', 'email', $email);
            if ($existing) {
                $msg = "An administrator with this email already exists!";
                $msgType = 'danger';
            } else {
                $idCount = count(DataStore::getCollection('admins')) + 1;
                $newAdminId = 'ADM-' . sprintf('%03d', $idCount);
                DataStore::insert('admins', [
                    'admin_id' => $newAdminId,
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'password' => password_hash($pass, PASSWORD_DEFAULT),
                    'status' => $status
                ]);
                add_audit_log('Admin', $adminUser['id'], 'Create Admin', "Created administrator $name ($newAdminId)");
                $msg = "New Admin Account created successfully for $name ($newAdminId)!";
                $msgType = 'success';
            }
        } else {
            $msg = "Admin Name and Email are required!";
            $msgType = 'danger';
        }
    }

    // 2. Update Admin
    if ($action === 'update_admin') {
        $admin_id = trim($_POST['admin_id'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $email = trim(strtolower($_POST['email'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');
        $status = $_POST['status'] ?? 'Active';
        $newPassword = trim($_POST['new_password'] ?? '');

        if ($admin_id && $name && $email) {
            // Self-status check: do not let logged-in admin set themselves to Blocked
            if ($admin_id === $adminUser['id'] && $status === 'Blocked') {
                $status = 'Active';
                $msg = "Note: You cannot block your own active account. Status remained Active.";
            }

            $updates = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'status' => $status
            ];
            if (!empty($newPassword)) {
                $updates['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
            }
            DataStore::update('admins', 'admin_id', $admin_id, $updates);
            add_audit_log('Admin', $adminUser['id'], 'Update Admin', "Updated administrator $admin_id");
            $msg = ($msg ? $msg . " " : "") . "Administrator $admin_id ($name) updated successfully!";
            $msgType = 'success';
        } else {
            $msg = "Required fields missing for admin update.";
            $msgType = 'danger';
        }
    }

    // 3. Toggle Status (Block / Unblock Access)
    if ($action === 'toggle_status') {
        $admin_id = trim($_POST['admin_id'] ?? '');
        $newStatus = trim($_POST['status'] ?? 'Active');

        if ($admin_id === $adminUser['id']) {
            $msg = "Security Alert: You cannot block your own logged-in administrator account!";
            $msgType = 'danger';
        } elseif ($admin_id) {
            DataStore::update('admins', 'admin_id', $admin_id, ['status' => $newStatus]);
            $actionLabel = ($newStatus === 'Blocked') ? 'BLOCKED access for' : 'RESTORED access for';
            add_audit_log('Admin', $adminUser['id'], 'Admin Access Status', "$actionLabel $admin_id");
            $msg = "Successfully " . ($newStatus === 'Blocked' ? 'blocked portal access for' : 'restored access for') . " administrator $admin_id.";
            $msgType = ($newStatus === 'Blocked') ? 'warning' : 'success';
        }
    }

    // 4. Delete Admin
    if ($action === 'delete_admin') {
        $admin_id = trim($_POST['admin_id'] ?? '');
        $currentAdmins = DataStore::getCollection('admins');

        if ($admin_id === $adminUser['id']) {
            $msg = "Security Alert: You cannot delete your own logged-in administrator account!";
            $msgType = 'danger';
        } elseif (count($currentAdmins) <= 1) {
            $msg = "Security Alert: Cannot delete the last remaining platform administrator!";
            $msgType = 'danger';
        } elseif ($admin_id) {
            DataStore::delete('admins', 'admin_id', $admin_id);
            add_audit_log('Admin', $adminUser['id'], 'Delete Admin', "Permanently deleted administrator $admin_id");
            $msg = "Administrator $admin_id has been permanently deleted.";
            $msgType = 'danger';
        }
    }
}

$admins = DataStore::getCollection('admins');
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h2 style="font-size:1.5rem; color:var(--text-main); margin-bottom:0.25rem;">
      <i class="fa-solid fa-user-gear" style="color:var(--primary);"></i> Platform Administrator Roster & Security
    </h2>
    <p style="color:var(--text-muted); font-size:0.9rem; margin:0;">Admins have full administrative control, financial access, and security account management.</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('addAdminModal')">
    <i class="fa-solid fa-user-plus"></i> Create New Admin
  </button>
</div>

<?php if ($msg): ?>
  <div class="badge badge-<?php echo $msgType; ?>" style="width:100%; padding:0.9rem; margin-bottom:1.5rem; text-align:center; font-size:0.95rem; display:block;">
    <i class="fa-solid fa-circle-info"></i> <?php echo htmlspecialchars($msg); ?>
  </div>
<?php endif; ?>

<div class="filter-bar" style="margin-bottom:1.2rem; display:flex; gap:12px; flex-wrap:wrap;">
  <input type="text" id="tableSearchInput" class="form-control" style="flex:2; min-width:240px;" placeholder="Search admin by ID, name, email, phone...">
  <select id="tableStatusFilter" class="form-control" style="flex:1; min-width:180px;">
    <option value="">All Statuses (Active & Blocked)</option>
    <option value="Active">Active Admins</option>
    <option value="Blocked">Blocked Admins</option>
  </select>
</div>

<div class="table-card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Admin ID</th>
          <th>Full Name</th>
          <th>Email Address</th>
          <th>Phone</th>
          <th>Status</th>
          <th style="text-align:center; min-width:210px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($admins as $ad): 
          $isBlocked = (isset($ad['status']) && strtolower($ad['status']) === 'blocked');
          $isSelf = ($ad['admin_id'] === $adminUser['id']);
        ?>
          <tr data-status="<?php echo htmlspecialchars($ad['status']); ?>">
            <td>
              <strong style="color:var(--primary); font-family:monospace;"><?php echo htmlspecialchars($ad['admin_id']); ?></strong>
              <?php if ($isSelf): ?>
                <span class="badge badge-info" style="font-size:0.7rem; margin-left:4px;">You</span>
              <?php endif; ?>
            </td>
            <td><strong style="color:var(--text-main);"><?php echo htmlspecialchars($ad['name']); ?></strong></td>
            <td><?php echo htmlspecialchars($ad['email']); ?></td>
            <td><?php echo htmlspecialchars($ad['phone'] ?? 'N/A'); ?></td>
            <td>
              <?php if ($isBlocked): ?>
                <span class="badge badge-danger"><i class="fa-solid fa-ban"></i> Blocked</span>
              <?php else: ?>
                <span class="badge badge-success"><i class="fa-solid fa-check"></i> Active</span>
              <?php endif; ?>
            </td>
            <td style="text-align:center;">
              <div style="display:inline-flex; gap:6px; align-items:center; justify-content:center;">
                <!-- Edit Button -->
                <button type="button" class="btn btn-outline btn-sm" onclick='editAdmin(<?php echo json_encode($ad); ?>)' title="Edit Administrator">
                  <i class="fa-solid fa-pen-to-square"></i> Edit
                </button>

                <!-- Block / Unblock Access Button -->
                <?php if ($isSelf): ?>
                  <button type="button" class="btn btn-outline btn-sm" disabled style="opacity:0.5; cursor:not-allowed;" title="Cannot block own account">
                    <i class="fa-solid fa-shield"></i> Protected
                  </button>
                <?php elseif ($isBlocked): ?>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Restore portal access for admin <?php echo addslashes($ad['name']); ?>?');">
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="admin_id" value="<?php echo htmlspecialchars($ad['admin_id']); ?>">
                    <input type="hidden" name="status" value="Active">
                    <button type="submit" class="btn btn-success btn-sm" title="Restore portal access">
                      <i class="fa-solid fa-unlock"></i> Unblock
                    </button>
                  </form>
                <?php else: ?>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Block portal access for administrator <?php echo addslashes($ad['name']); ?>? They will not be able to log in.');">
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="admin_id" value="<?php echo htmlspecialchars($ad['admin_id']); ?>">
                    <input type="hidden" name="status" value="Blocked">
                    <button type="submit" class="btn btn-warning btn-sm" title="Block portal access">
                      <i class="fa-solid fa-ban"></i> Block
                    </button>
                  </form>
                <?php endif; ?>

                <!-- Delete Admin Button -->
                <?php if ($isSelf): ?>
                  <!-- Cannot delete self -->
                <?php else: ?>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Permanently DELETE administrator <?php echo addslashes($ad['name']); ?> (<?php echo htmlspecialchars($ad['admin_id']); ?>)? This cannot be undone!');">
                    <input type="hidden" name="action" value="delete_admin">
                    <input type="hidden" name="admin_id" value="<?php echo htmlspecialchars($ad['admin_id']); ?>">
                    <button type="submit" class="btn btn-danger btn-sm" title="Permanently Delete Admin">
                      <i class="fa-solid fa-trash"></i> Delete
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Admin Modal -->
<div id="addAdminModal" class="modal-overlay">
  <div class="modal-box" style="padding:2rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem;">
      <h3 style="margin:0; color:var(--text-main);"><i class="fa-solid fa-user-gear" style="color:var(--primary);"></i> Create Platform Admin Account</h3>
      <button type="button" onclick="closeModal('addAdminModal')" style="background:none; border:none; font-size:1.3rem; color:var(--text-muted); cursor:pointer;">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="create_admin">
      <div class="form-group">
        <label>Full Name *</label>
        <input type="text" name="name" class="form-control" required placeholder="e.g. System Admin">
      </div>
      <div class="form-group">
        <label>Admin Email Address *</label>
        <input type="email" name="email" class="form-control" required placeholder="admin@aceassign.com">
      </div>
      <div class="form-group">
        <label>Phone Number</label>
        <input type="text" name="phone" class="form-control" placeholder="+1 (800) 555-0000">
      </div>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div class="form-group">
          <label>Password *</label>
          <input type="password" name="password" class="form-control" required value="password">
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="status" class="form-control">
            <option value="Active">Active</option>
            <option value="Blocked">Blocked</option>
          </select>
        </div>
      </div>
      <div style="display:flex; gap:10px; margin-top:1.5rem;">
        <button type="submit" class="btn btn-primary" style="flex:1;">Create Admin</button>
        <button type="button" class="btn btn-outline" onclick="closeModal('addAdminModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Admin Modal -->
<div id="editAdminModal" class="modal-overlay">
  <div class="modal-box" style="padding:2rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem;">
      <h3 style="margin:0; color:var(--text-main);"><i class="fa-solid fa-user-pen" style="color:var(--primary);"></i> Edit Administrator</h3>
      <button type="button" onclick="closeModal('editAdminModal')" style="background:none; border:none; font-size:1.3rem; color:var(--text-muted); cursor:pointer;">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="update_admin">
      <input type="hidden" name="admin_id" id="edit_admin_id">

      <div class="form-group">
        <label>Admin ID</label>
        <input type="text" id="edit_admin_display_id" class="form-control" readonly style="background:#f1f5f9; font-family:monospace; font-weight:700;">
      </div>
      <div class="form-group">
        <label>Full Name *</label>
        <input type="text" name="name" id="edit_admin_name" class="form-control" required>
      </div>
      <div class="form-group">
        <label>Admin Email Address *</label>
        <input type="email" name="email" id="edit_admin_email" class="form-control" required>
      </div>
      <div class="form-group">
        <label>Phone Number</label>
        <input type="text" name="phone" id="edit_admin_phone" class="form-control">
      </div>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div class="form-group">
          <label>Reset Password <small style="color:var(--text-muted);">(Leave blank to keep)</small></label>
          <input type="password" name="new_password" class="form-control" placeholder="••••••••">
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="status" id="edit_admin_status" class="form-control">
            <option value="Active">Active</option>
            <option value="Blocked">Blocked</option>
          </select>
        </div>
      </div>
      <div style="display:flex; gap:10px; margin-top:1.5rem;">
        <button type="submit" class="btn btn-primary" style="flex:1;">Save Changes</button>
        <button type="button" class="btn btn-outline" onclick="closeModal('editAdminModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function editAdmin(data) {
  document.getElementById('edit_admin_id').value = data.admin_id || '';
  document.getElementById('edit_admin_display_id').value = data.admin_id || '';
  document.getElementById('edit_admin_name').value = data.name || '';
  document.getElementById('edit_admin_email').value = data.email || '';
  document.getElementById('edit_admin_phone').value = data.phone || '';
  document.getElementById('edit_admin_status').value = data.status || 'Active';
  openModal('editAdminModal');
}
</script>

<script src="/assets/js/portal.js"></script>
</div>
</div>
</body>
</html>
