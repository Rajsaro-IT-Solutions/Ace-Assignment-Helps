<?php
$pageTitle = "Expert Roster & Management";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Admin');
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

$experts = DataStore::getCollection('experts');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subjects = array_map('trim', explode(',', $_POST['subjects'] ?? 'General'));
    if (!empty($name)) {
        DataStore::insert('experts', [
            'expert_id' => 'EXP-' . rand(305, 999),
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'subjects' => $subjects,
            'rating' => 5.0,
            'completed_count' => 0,
            'status' => 'Available'
        ]);
        header("Location: /admin/experts.php");
        exit;
    }
}
?>

<div style="margin-bottom:1.5rem; display:flex; justify-content:space-between; align-items:center;">
  <h3 style="color:#fff;"><i class="fa-solid fa-user-ninja" style="color:var(--primary);"></i> PhD Experts Directory</h3>
  <button class="btn btn-primary btn-sm" onclick="openModal('addExpertModal')"><i class="fa-solid fa-plus"></i> Add New Expert</button>
</div>

<div class="table-card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Expert Name</th>
          <th>Contact Info</th>
          <th>Subjects</th>
          <th>Rating</th>
          <th>Completed</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($experts as $exp): ?>
          <tr>
            <td><strong style="color:var(--secondary);"><?php echo htmlspecialchars($exp['expert_id']); ?></strong></td>
            <td><strong style="color:#fff;"><?php echo htmlspecialchars($exp['name']); ?></strong></td>
            <td><?php echo htmlspecialchars($exp['email']); ?><br><small style="color:var(--text-muted);"><?php echo htmlspecialchars($exp['phone']); ?></small></td>
            <td>
              <?php foreach ($exp['subjects'] as $subj): ?>
                <span class="badge badge-info" style="font-size:0.7rem;"><?php echo htmlspecialchars($subj); ?></span>
              <?php endforeach; ?>
            </td>
            <td><strong style="color:#fbbf24;"><i class="fa-solid fa-star"></i> <?php echo $exp['rating']; ?></strong></td>
            <td><?php echo $exp['completed_count']; ?></td>
            <td><span class="badge <?php echo ($exp['status'] === 'Available') ? 'badge-success' : 'badge-warning'; ?>"><?php echo htmlspecialchars($exp['status']); ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Expert Modal -->
<div id="addExpertModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.85); backdrop-filter:blur(10px); z-index:2000; align-items:center; justify-content:center;">
  <div class="calc-card" style="max-width:480px; width:100%; padding:2rem;">
    <h3 style="margin-bottom:1rem; color:#fff;"><i class="fa-solid fa-user-plus"></i> Add New Academic Expert</h3>
    <form method="POST">
      <div class="form-group">
        <label>Full Name & Academic Title *</label>
        <input type="text" name="name" class="form-control" required placeholder="e.g. Dr. Arthur Pendelton (PhD)">
      </div>
      <div class="form-group">
        <label>Email Address *</label>
        <input type="email" name="email" class="form-control" required placeholder="arthur@experts.com">
      </div>
      <div class="form-group">
        <label>Phone Number *</label>
        <input type="tel" name="phone" class="form-control" required placeholder="+1 555 999 0000">
      </div>
      <div class="form-group">
        <label>Subject Specialties (Comma Separated)</label>
        <input type="text" name="subjects" class="form-control" placeholder="Computer Science, Python, AI">
      </div>
      <div style="display:flex; gap:10px; margin-top:1.5rem;">
        <button type="submit" class="btn btn-primary" style="flex:1;">Save Expert</button>
        <button type="button" class="btn btn-outline" onclick="closeModal('addExpertModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script src="/assets/js/portal.js"></script>
</div>
</div>
</body>
</html>
