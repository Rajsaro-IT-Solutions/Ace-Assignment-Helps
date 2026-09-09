<?php
$pageTitle = "Allocator Staff Management";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Admin');
$adminUser = Auth::currentUser();
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

$msg = '';
$msgType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Create Allocator
    if ($action === 'create_allocator') {
        $name = trim($_POST['name'] ?? '');
        $email = trim(strtolower($_POST['email'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');
        $pass = trim($_POST['password'] ?? 'password');
        $status = $_POST['status'] ?? 'Active';
        $score = (float)($_POST['performance_score'] ?? 100.0);

        if (!empty($name) && !empty($email)) {
            $existing = DataStore::findOne('allocators', 'email', $email);
            if ($existing) {
                $msg = "An allocator with this email address already exists!";
                $msgType = 'danger';
            } else {
                $idCount = count(DataStore::getCollection('allocators')) + 501;
                $allocId = 'ALL-' . $idCount;
                DataStore::insert('allocators', [
                    'allocator_id' => $allocId,
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'password' => password_hash($pass, PASSWORD_DEFAULT),
                    'status' => $status,
                    'performance_score' => $score
                ]);
                add_audit_log('Admin', $adminUser['id'], 'Create Allocator', "Created allocator $name ($allocId)");
                $msg = "New Allocator Account created successfully for $name ($allocId)!";
                $msgType = 'success';
            }
        } else {
            $msg = "Allocator Name and Work Email are required!";
            $msgType = 'danger';
        }
    }

    // 2. Update Allocator
    if ($action === 'update_allocator') {
        $allocator_id = trim($_POST['allocator_id'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $email = trim(strtolower($_POST['email'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');
        $status = $_POST['status'] ?? 'Active';
        $score = (float)($_POST['performance_score'] ?? 95.0);
        $newPassword = trim($_POST['new_password'] ?? '');

        if ($allocator_id && $name && $email) {
            $updates = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'status' => $status,
                'performance_score' => $score
            ];
            if (!empty($newPassword)) {
                $updates['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
            }
            DataStore::update('allocators', 'allocator_id', $allocator_id, $updates);
            add_audit_log('Admin', $adminUser['id'], 'Update Allocator', "Updated allocator $allocator_id");
            $msg = "Allocator account $allocator_id ($name) updated successfully!";
            $msgType = 'success';
        } else {
            $msg = "Required fields missing for allocator update.";
            $msgType = 'danger';
        }
    }

    // 3. Toggle Status (Block / Unblock Access)
    if ($action === 'toggle_status') {
        $allocator_id = trim($_POST['allocator_id'] ?? '');
        $newStatus = trim($_POST['status'] ?? 'Active');
        if ($allocator_id) {
            DataStore::update('allocators', 'allocator_id', $allocator_id, ['status' => $newStatus]);
            $actionLabel = ($newStatus === 'Blocked') ? 'BLOCKED access for' : 'UNBLOCKED access for';
            add_audit_log('Admin', $adminUser['id'], 'Allocator Access Status', "$actionLabel $allocator_id");
            $msg = "Successfully " . ($newStatus === 'Blocked' ? 'blocked portal access for' : 'restored access for') . " allocator $allocator_id.";
            $msgType = ($newStatus === 'Blocked') ? 'warning' : 'success';
        }
    }

    // 4. Delete Allocator
    if ($action === 'delete_allocator') {
        $allocator_id = trim($_POST['allocator_id'] ?? '');
        if ($allocator_id) {
            DataStore::delete('allocators', 'allocator_id', $allocator_id);
            add_audit_log('Admin', $adminUser['id'], 'Delete Allocator', "Permanently deleted allocator $allocator_id");
            $msg = "Allocator $allocator_id has been permanently deleted.";
            $msgType = 'danger';
        }
    }
}

$allocators = DataStore::getCollection('allocators');
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h2 style="font-size:1.5rem; color:var(--text-main); margin-bottom:0.25rem;">
      <i class="fa-solid fa-user-shield" style="color:var(--primary);"></i> Allocator Staff Roster & Access Control
    </h2>
    <p style="color:var(--text-muted); font-size:0.9rem; margin:0;">Allocators handle order distribution, expert assignment, and workflow management.</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('addAllocatorModal')">
    <i class="fa-solid fa-user-plus"></i> Create New Allocator
  </button>
</div>

<?php if ($msg): ?>
  <div class="badge badge-<?php echo $msgType; ?>" style="width:100%; padding:0.9rem; margin-bottom:1.5rem; text-align:center; font-size:0.95rem; display:block;">
    <i class="fa-solid fa-circle-info"></i> <?php echo htmlspecialchars($msg); ?>
  </div>
<?php endif; ?>

<div class="filter-bar" style="margin-bottom:1.2rem; display:flex; gap:12px; flex-wrap:wrap;">
  <input type="text" id="tableSearchInput" class="form-control" style="flex:2; min-width:240px;" placeholder="Search allocator by ID, name, email, phone...">
  <select id="tableStatusFilter" class="form-control" style="flex:1; min-width:180px;">
    <option value="">All Statuses (Active & Blocked)</option>
    <option value="Active">Active Allocators</option>
    <option value="Blocked">Blocked Allocators</option>
  </select>
</div>

<div class="table-card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Allocator ID</th>
          <th>Full Name</th>
          <th>Email Address</th>
          <th>Phone</th>
          <th>SLA On-Time Rating</th>
          <th>Status</th>
          <th style="text-align:center; min-width:210px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($allocators)): ?>
          <tr>
            <td colspan="7" style="text-align:center; padding:2rem; color:var(--text-muted);">No allocators found. Click "Create New Allocator" to add staff.</td>
          </tr>
        <?php endif; ?>
        <?php foreach ($allocators as $all): 
          $isBlocked = (isset($all['status']) && strtolower($all['status']) === 'blocked');
        ?>
          <tr data-status="<?php echo htmlspecialchars($all['status']); ?>">
            <td><strong style="color:var(--secondary); font-family:monospace;"><?php echo htmlspecialchars($all['allocator_id']); ?></strong></td>
            <td><strong style="color:var(--text-main);"><?php echo htmlspecialchars($all['name']); ?></strong></td>
            <td><?php echo htmlspecialchars($all['email']); ?></td>
            <td><?php echo htmlspecialchars($all['phone'] ?? 'N/A'); ?></td>
            <td><strong style="color:var(--success);"><?php echo htmlspecialchars($all['performance_score'] ?? '100'); ?>% SLA</strong></td>
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
                <button type="button" class="btn btn-outline btn-sm" onclick='editAllocator(<?php echo json_encode($all); ?>)' title="Edit Allocator Staff">
                  <i class="fa-solid fa-pen-to-square"></i> Edit
                </button>

                <!-- Block / Unblock Access Button -->
                <?php if ($isBlocked): ?>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Restore portal access for allocator <?php echo addslashes($all['name']); ?>?');">
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="allocator_id" value="<?php echo htmlspecialchars($all['allocator_id']); ?>">
                    <input type="hidden" name="status" value="Active">
                    <button type="submit" class="btn btn-success btn-sm" title="Restore portal access">
                      <i class="fa-solid fa-unlock"></i> Unblock
                    </button>
                  </form>
                <?php else: ?>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Block portal access for allocator <?php echo addslashes($all['name']); ?>? Staff will not be able to log in.');">
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="allocator_id" value="<?php echo htmlspecialchars($all['allocator_id']); ?>">
                    <input type="hidden" name="status" value="Blocked">
                    <button type="submit" class="btn btn-warning btn-sm" title="Block portal access">
                      <i class="fa-solid fa-ban"></i> Block
                    </button>
                  </form>
                <?php endif; ?>

                <!-- Delete Allocator Button -->
                <form method="POST" style="display:inline;" onsubmit="return confirm('Permanently DELETE allocator <?php echo addslashes($all['name']); ?> (<?php echo htmlspecialchars($all['allocator_id']); ?>)? This cannot be undone!');">
                  <input type="hidden" name="action" value="delete_allocator">
                  <input type="hidden" name="allocator_id" value="<?php echo htmlspecialchars($all['allocator_id']); ?>">
                  <button type="submit" class="btn btn-danger btn-sm" title="Permanently Delete Staff">
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

<!-- Add Allocator Modal -->
<div id="addAllocatorModal" class="modal-overlay">
  <div class="modal-box" style="padding:2rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem;">
      <h3 style="margin:0; color:var(--text-main);"><i class="fa-solid fa-user-shield" style="color:var(--primary);"></i> Create Allocator Account</h3>
      <button type="button" onclick="closeModal('addAllocatorModal')" style="background:none; border:none; font-size:1.3rem; color:var(--text-muted); cursor:pointer;">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="create_allocator">
      <div class="form-group">
        <label>Full Name *</label>
        <input type="text" name="name" class="form-control" required placeholder="e.g. David Vance">
      </div>
      <div class="form-group">
        <label>Work Email Address *</label>
        <input type="email" name="email" class="form-control" required placeholder="allocator@aceassign.com">
      </div>
      <div class="form-group">
        <label>Phone Number</label>
        <input type="text" name="phone" class="form-control" placeholder="+1 (555) 000-0000">
      </div>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div class="form-group">
          <label>Initial Password *</label>
          <input type="password" name="password" class="form-control" required value="password">
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="status" class="form-control">
            <option value="Active">Active (Permitted)</option>
            <option value="Blocked">Blocked (Restricted)</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Initial SLA Score (%)</label>
        <input type="number" step="0.1" name="performance_score" class="form-control" value="100.0" min="0" max="100">
      </div>
      <div style="display:flex; gap:10px; margin-top:1.5rem;">
        <button type="submit" class="btn btn-primary" style="flex:1;">Create Allocator</button>
        <button type="button" class="btn btn-outline" onclick="closeModal('addAllocatorModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Allocator Modal -->
<div id="editAllocatorModal" class="modal-overlay">
  <div class="modal-box" style="padding:2rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem;">
      <h3 style="margin:0; color:var(--text-main);"><i class="fa-solid fa-user-pen" style="color:var(--primary);"></i> Edit Allocator Staff</h3>
      <button type="button" onclick="closeModal('editAllocatorModal')" style="background:none; border:none; font-size:1.3rem; color:var(--text-muted); cursor:pointer;">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="update_allocator">
      <input type="hidden" name="allocator_id" id="edit_allocator_id">

      <div class="form-group">
        <label>Allocator ID</label>
        <input type="text" id="edit_allocator_display_id" class="form-control" readonly style="background:#f1f5f9; font-family:monospace; font-weight:700;">
      </div>
      <div class="form-group">
        <label>Full Name *</label>
        <input type="text" name="name" id="edit_allocator_name" class="form-control" required>
      </div>
      <div class="form-group">
        <label>Work Email Address *</label>
        <input type="email" name="email" id="edit_allocator_email" class="form-control" required>
      </div>
      <div class="form-group">
        <label>Phone Number</label>
        <input type="text" name="phone" id="edit_allocator_phone" class="form-control">
      </div>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div class="form-group">
          <label>SLA Score (%)</label>
          <input type="number" step="0.1" name="performance_score" id="edit_allocator_score" class="form-control" min="0" max="100">
        </div>
        <div class="form-group">
          <label>Access Status</label>
          <select name="status" id="edit_allocator_status" class="form-control">
            <option value="Active">Active (Permitted)</option>
            <option value="Blocked">Blocked (Restricted)</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Reset Password <small style="color:var(--text-muted);">(Leave blank to keep)</small></label>
        <input type="password" name="new_password" class="form-control" placeholder="••••••••">
      </div>
      <div style="display:flex; gap:10px; margin-top:1.5rem;">
        <button type="submit" class="btn btn-primary" style="flex:1;">Save Changes</button>
        <button type="button" class="btn btn-outline" onclick="closeModal('editAllocatorModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function editAllocator(data) {
  document.getElementById('edit_allocator_id').value = data.allocator_id || '';
  document.getElementById('edit_allocator_display_id').value = data.allocator_id || '';
  document.getElementById('edit_allocator_name').value = data.name || '';
  document.getElementById('edit_allocator_email').value = data.email || '';
  document.getElementById('edit_allocator_phone').value = data.phone || '';
  document.getElementById('edit_allocator_score').value = data.performance_score || '100';
  document.getElementById('edit_allocator_status').value = data.status || 'Active';
  openModal('editAllocatorModal');
}
</script>

<script src="/assets/js/portal.js"></script>
</div>
</div>
</body>
</html>
