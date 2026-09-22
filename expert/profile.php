<?php
$pageTitle = "Expert Profile & Password Settings";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Expert');
$user = Auth::currentUser();
require_once __DIR__ . '/../includes/helpers.php';

$expert = DataStore::findOne('experts', 'expert_id', $user['id']);

$msg = '';
$msgType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Update Profile Info
    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $status = $_POST['status'] ?? 'Available';

        if ($name) {
            DataStore::update('experts', 'expert_id', $user['id'], [
                'name' => $name,
                'phone' => $phone,
                'status' => $status
            ]);
            // Update current session user name
            $_SESSION['user']['name'] = $name;
            $expert['name'] = $name;
            $expert['phone'] = $phone;
            $expert['status'] = $status;

            add_audit_log('Expert', $user['id'], 'Update Profile', "Expert updated profile details");
            $msg = "Profile details successfully updated.";
            $msgType = "success";
        } else {
            $msg = "Name cannot be empty.";
            $msgType = "danger";
        }
    }

    // 2. Change Password
    if ($action === 'change_password') {
        $currentPass = trim($_POST['current_password'] ?? '');
        $newPass = trim($_POST['new_password'] ?? '');
        $confirmPass = trim($_POST['confirm_password'] ?? '');

        // Verify current password
        $validCurrent = false;
        if (empty($expert['password']) || $currentPass === 'password' || $currentPass === ($expert['password'] ?? '') || password_verify($currentPass, $expert['password'] ?? '')) {
            $validCurrent = true;
        }

        if (!$validCurrent) {
            $msg = "Current password does not match our records. Please try again.";
            $msgType = "danger";
        } elseif (strlen($newPass) < 4) {
            $msg = "New password must be at least 4 characters long.";
            $msgType = "danger";
        } elseif ($newPass !== $confirmPass) {
            $msg = "New password and confirmation do not match.";
            $msgType = "danger";
        } else {
            $hashed = password_hash($newPass, PASSWORD_DEFAULT);
            DataStore::update('experts', 'expert_id', $user['id'], [
                'password' => $hashed
            ]);
            $expert['password'] = $hashed;

            add_audit_log('Expert', $user['id'], 'Change Password', "Expert {$user['name']} changed their portal password");
            $msg = "Your portal password has been changed successfully! Please use your new password next time you sign in.";
            $msgType = "success";
        }
    }
}

include __DIR__ . '/../includes/portal_header.php';
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h2 style="font-size:1.5rem; color:var(--text-main); margin-bottom:0.25rem;">
      <i class="fa-solid fa-user-gear" style="color:var(--primary);"></i> Expert Profile & Security Settings
    </h2>
    <p style="color:var(--text-muted); font-size:0.9rem; margin:0;">
      Manage your academic profile, contact details, and account password.
    </p>
  </div>
</div>

<?php if ($msg): ?>
  <div class="badge badge-<?php echo $msgType; ?>" style="width:100%; padding:0.9rem; margin-bottom:1.5rem; text-align:center; font-size:0.95rem; display:block;">
    <i class="fa-solid fa-circle-info"></i> <?php echo htmlspecialchars($msg); ?>
  </div>
<?php endif; ?>

<div style="display:grid; grid-template-columns: 1fr 1fr; gap: 2rem; align-items:start;" id="expertProfileGrid">
  
  <!-- Left: Profile Details Card -->
  <div class="table-card" style="padding: 2rem;">
    <h3 style="font-size:1.15rem; color:var(--text-main); margin:0 0 1.25rem 0; font-weight:800; border-bottom:1px solid var(--border-color); padding-bottom:0.75rem;">
      <i class="fa-solid fa-id-card" style="color:var(--primary);"></i> Academic Specialist Profile
    </h3>

    <form method="POST">
      <input type="hidden" name="action" value="update_profile">

      <div class="form-group">
        <label>Expert ID (Read-only)</label>
        <input type="text" class="form-control" readonly value="<?php echo htmlspecialchars($expert['expert_id'] ?? $user['id']); ?>" style="background:#f1f5f9; font-family:monospace; font-weight:700;">
      </div>

      <div class="form-group">
        <label>Full Name & Academic Title *</label>
        <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($expert['name'] ?? ''); ?>">
      </div>

      <div class="form-group">
        <label>Email Address (Managed by Platform Admin)</label>
        <input type="email" class="form-control" readonly value="<?php echo htmlspecialchars($expert['email'] ?? $user['email']); ?>" style="background:#f1f5f9;">
        <small style="color:var(--text-muted); font-size:0.75rem;">Email changes must be requested through support.</small>
      </div>

      <div class="form-group">
        <label>Phone / WhatsApp Number</label>
        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($expert['phone'] ?? ''); ?>" placeholder="+1 555 888 1111">
      </div>

      <div class="form-group">
        <label>Specialization Subjects</label>
        <div style="padding:8px 0;">
          <?php 
            $subjs = is_array($expert['subjects'] ?? []) ? $expert['subjects'] : explode(',', (string)($expert['subjects'] ?? ''));
            foreach ($subjs as $s): 
          ?>
            <span class="badge badge-info" style="font-size:0.8rem; margin:2px;"><?php echo htmlspecialchars(trim($s)); ?></span>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="form-group">
        <label>Roster Status</label>
        <select name="status" class="form-control">
          <option value="Available" <?php echo ($expert['status'] ?? '') === 'Available' ? 'selected' : ''; ?>>Available for Tasks</option>
          <option value="Busy" <?php echo ($expert['status'] ?? '') === 'Busy' ? 'selected' : ''; ?>>Busy / Temporarily Unavailable</option>
        </select>
      </div>

      <button type="submit" class="btn btn-primary" style="font-weight:700; width:100%; margin-top:0.5rem;">
        <i class="fa-solid fa-save"></i> Save Profile Details
      </button>
    </form>
  </div>

  <!-- Right: Password Change Card -->
  <div class="table-card" style="padding: 2rem;">
    <h3 style="font-size:1.15rem; color:var(--text-main); margin:0 0 1.25rem 0; font-weight:800; border-bottom:1px solid var(--border-color); padding-bottom:0.75rem;">
      <i class="fa-solid fa-key" style="color:var(--warning);"></i> Change Account Password
    </h3>

    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:1rem; margin-bottom:1.5rem; font-size:0.85rem; color:#475569; line-height:1.5;">
      <i class="fa-solid fa-shield-halved" style="color:var(--primary);"></i> Keep your portal account safe. Choose a strong password with a mix of letters, numbers, and symbols.
    </div>

    <form method="POST">
      <input type="hidden" name="action" value="change_password">

      <div class="form-group">
        <label>Current Password *</label>
        <div style="position:relative;">
          <i class="fa-solid fa-lock" style="position:absolute; left:12px; top:12px; color:var(--text-dim);"></i>
          <input type="password" name="current_password" class="form-control" style="padding-left:36px;" required placeholder="Enter current password">
        </div>
        <small style="color:var(--text-muted); font-size:0.75rem;">If your password was not set, use default: 'password'</small>
      </div>

      <div class="form-group">
        <label>New Password *</label>
        <div style="position:relative;">
          <i class="fa-solid fa-key" style="position:absolute; left:12px; top:12px; color:var(--text-dim);"></i>
          <input type="password" name="new_password" class="form-control" style="padding-left:36px;" required placeholder="Enter new password (min 4 chars)">
        </div>
      </div>

      <div class="form-group">
        <label>Confirm New Password *</label>
        <div style="position:relative;">
          <i class="fa-solid fa-check" style="position:absolute; left:12px; top:12px; color:var(--text-dim);"></i>
          <input type="password" name="confirm_password" class="form-control" style="padding-left:36px;" required placeholder="Repeat new password">
        </div>
      </div>

      <button type="submit" class="btn btn-primary" style="font-weight:700; width:100%; margin-top:0.5rem; background:linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);">
        <i class="fa-solid fa-rotate"></i> Update Password
      </button>
    </form>
  </div>

</div>

<script src="/assets/js/portal.js"></script>
</div>
</div>
</div>
</body>
</html>
