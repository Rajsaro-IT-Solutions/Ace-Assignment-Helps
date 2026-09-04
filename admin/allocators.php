<?php
$pageTitle = "Allocator Staff Management";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Admin');
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_allocator'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $pass = trim($_POST['password'] ?? 'password');

    if (!empty($name) && !empty($email)) {
        $existing = DataStore::findOne('allocators', 'email', $email);
        if ($existing) {
            $msg = "An allocator with this email already exists!";
        } else {
            $idCount = count(DataStore::getCollection('allocators')) + 501;
            DataStore::insert('allocators', [
                'allocator_id' => 'ALL-' . $idCount,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'password' => password_hash($pass, PASSWORD_DEFAULT),
                'status' => 'Active',
                'performance_score' => 100.0
            ]);
            $msg = "New Allocator Account created successfully for $name!";
        }
    }
}

$allocators = DataStore::getCollection('allocators');
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
  <div>
    <h2 style="font-size:1.5rem; color:var(--text-main);"><i class="fa-solid fa-user-shield" style="color:var(--primary);"></i> Allocator Staff Roster</h2>
    <p style="color:var(--text-muted); font-size:0.9rem;">Allocators handle order distribution, expert assignment, and workflow management.</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('addAllocatorModal')"><i class="fa-solid fa-user-plus"></i> Create New Allocator</button>
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
          <th>Allocator ID</th>
          <th>Full Name</th>
          <th>Email Address</th>
          <th>Phone</th>
          <th>SLA On-Time Rating</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($allocators as $all): ?>
          <tr>
            <td><strong style="color:var(--secondary);"><?php echo htmlspecialchars($all['allocator_id']); ?></strong></td>
            <td><strong><?php echo htmlspecialchars($all['name']); ?></strong></td>
            <td><?php echo htmlspecialchars($all['email']); ?></td>
            <td><?php echo htmlspecialchars($all['phone']); ?></td>
            <td><strong style="color:var(--success);"><?php echo $all['performance_score']; ?>% SLA On-Time</strong></td>
            <td><span class="badge badge-success"><?php echo htmlspecialchars($all['status']); ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Allocator Modal -->
<div id="addAllocatorModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(15, 23, 42, 0.7); backdrop-filter:blur(6px); z-index:2000; align-items:center; justify-content:center;">
  <div class="calc-card" style="max-width:450px; width:100%; padding:2rem; background:#ffffff;">
    <h3 style="margin-bottom:1.2rem; color:var(--text-main);"><i class="fa-solid fa-user-shield" style="color:var(--primary);"></i> Create Allocator Staff Account</h3>
    <form method="POST">
      <input type="hidden" name="create_allocator" value="1">
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
      <div class="form-group">
        <label>Initial Password *</label>
        <input type="password" name="password" class="form-control" required value="password">
      </div>
      <div style="display:flex; gap:10px; margin-top:1.5rem;">
        <button type="submit" class="btn btn-primary" style="flex:1;">Create Allocator</button>
        <button type="button" class="btn btn-outline" onclick="closeModal('addAllocatorModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script src="/assets/js/portal.js"></script>
</div>
</div>
</body>
</html>
