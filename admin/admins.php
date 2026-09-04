<?php
$pageTitle = "Administrator Accounts Management";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Admin');
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_admin'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $pass = trim($_POST['password'] ?? 'password');

    if (!empty($name) && !empty($email)) {
        $existing = DataStore::findOne('admins', 'email', $email);
        if ($existing) {
            $msg = "An administrator with this email already exists!";
        } else {
            $idCount = count(DataStore::getCollection('admins')) + 1;
            DataStore::insert('admins', [
                'admin_id' => 'ADM-' . sprintf('%03d', $idCount),
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'password' => password_hash($pass, PASSWORD_DEFAULT),
                'status' => 'Active'
            ]);
            $msg = "New Admin Account created successfully for $name!";
        }
    }
}

$admins = DataStore::getCollection('admins');
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
  <div>
    <h2 style="font-size:1.5rem; color:var(--text-main);"><i class="fa-solid fa-user-gear" style="color:var(--primary);"></i> Platform Administrator Roster</h2>
    <p style="color:var(--text-muted); font-size:0.9rem;">Admins have full administrative control, financial access, and account creation privileges.</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('addAdminModal')"><i class="fa-solid fa-user-plus"></i> Create New Admin</button>
</div>

<?php if ($msg): ?>
  <div class="badge badge-info" style="width:100%; padding:0.8rem; margin-bottom:1.5rem; text-align:center; font-size:0.95rem;">
    <i class="fa-solid fa-circle-info"></i> <?php echo htmlspecialchars($msg); ?>
  </div>
<?php endif; ?>

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
        </tr>
      </thead>
      <tbody>
        <?php foreach ($admins as $ad): ?>
          <tr>
            <td><strong style="color:var(--primary);"><?php echo htmlspecialchars($ad['admin_id']); ?></strong></td>
            <td><strong><?php echo htmlspecialchars($ad['name']); ?></strong></td>
            <td><?php echo htmlspecialchars($ad['email']); ?></td>
            <td><?php echo htmlspecialchars($ad['phone']); ?></td>
            <td><span class="badge badge-success"><?php echo htmlspecialchars($ad['status']); ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Admin Modal -->
<div id="addAdminModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(15, 23, 42, 0.7); backdrop-filter:blur(6px); z-index:2000; align-items:center; justify-content:center;">
  <div class="calc-card" style="max-width:450px; width:100%; padding:2rem; background:#ffffff;">
    <h3 style="margin-bottom:1.2rem; color:var(--text-main);"><i class="fa-solid fa-user-gear" style="color:var(--primary);"></i> Create Platform Admin Account</h3>
    <form method="POST">
      <input type="hidden" name="create_admin" value="1">
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
      <div class="form-group">
        <label>Password *</label>
        <input type="password" name="password" class="form-control" required value="password">
      </div>
      <div style="display:flex; gap:10px; margin-top:1.5rem;">
        <button type="submit" class="btn btn-primary" style="flex:1;">Create Admin</button>
        <button type="button" class="btn btn-outline" onclick="closeModal('addAdminModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script src="/assets/js/portal.js"></script>
</div>
</div>
</body>
</html>
