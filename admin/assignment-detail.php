<?php
$pageTitle = "Admin Master Assignment Control";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Admin');
$user = Auth::currentUser();
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

$id = $_GET['id'] ?? '';
$asm = DataStore::findOne('assignments', 'assignment_id', $id);
if (!$asm) {
    echo "<div class='badge badge-danger'>Assignment not found.</div>";
    echo "</div></div></body></html>";
    exit;
}

$student = DataStore::findOne('students', 'student_id', $asm['student_id']);
$experts = DataStore::getCollection('experts');
$allocators = DataStore::getCollection('allocators');
$files = DataStore::filter('files', function($f) use ($id) { return isset($f['assignment_id']) && $f['assignment_id'] === $id; });

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_assignment'])) {
    $status = $_POST['status'] ?? $asm['status'];
    $price = (float)($_POST['final_price'] ?? $asm['final_price']);
    $expert_id = $_POST['expert_id'] ?? $asm['expert_id'];
    $allocator_id = $_POST['allocator_id'] ?? $asm['allocator_id'];

    DataStore::update('assignments', 'assignment_id', $id, [
        'status' => $status,
        'final_price' => $price,
        'expert_id' => $expert_id,
        'allocator_id' => $allocator_id,
        'updated_at' => date('Y-m-d H:i:s')
    ]);

    // Handle Uploading Final Solution PDF
    if (isset($_FILES['solution_file']) && $_FILES['solution_file']['error'] === UPLOAD_ERR_OK) {
        $fileName = 'FINAL_SOLUTION_' . basename($_FILES['solution_file']['name']);
        $targetDir = __DIR__ . '/../assets/uploads/';
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $targetPath = $targetDir . time() . '_' . $fileName;
        if (move_uploaded_file($_FILES['solution_file']['tmp_name'], $targetPath)) {
            DataStore::insert('files', [
                'file_id' => 'FILE-' . rand(800, 999),
                'assignment_id' => $id,
                'file_name' => $fileName,
                'path' => 'assets/uploads/' . basename($targetPath),
                'file_type' => pathinfo($fileName, PATHINFO_EXTENSION),
                'uploaded_by' => 'Expert / Admin',
                'upload_date' => date('Y-m-d H:i:s'),
                'is_internal' => false
            ]);
            DataStore::update('assignments', 'assignment_id', $id, ['status' => 'Completed']);
        }
    }

    add_audit_log('Admin', $user['id'], 'Admin Override Assignment', "Updated $id parameters.");
    header("Location: /admin/assignment-detail.php?id=" . urlencode($id) . "&msg=Updated");
    exit;
}
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
  <div>
    <a href="/admin/assignments.php" style="color:var(--text-muted); font-size:0.9rem;"><i class="fa-solid fa-arrow-left"></i> Back to Master Assignments</a>
    <h2 style="font-size:1.8rem; margin-top:0.3rem; color:#fff;"><?php echo htmlspecialchars($asm['title']); ?></h2>
  </div>
  <a href="/student/invoice.php?id=<?php echo urlencode($id); ?>" target="_blank" class="btn btn-outline btn-sm"><i class="fa-solid fa-print"></i> Print Official Invoice</a>
</div>

<div class="grid-2" style="display:grid; grid-template-columns:2fr 1fr; gap:1.5rem;">
  <div>
    <form method="POST" enctype="multipart/form-data" class="table-card" style="padding:1.5rem;">
      <input type="hidden" name="update_assignment" value="1">
      <h3 style="font-size:1.1rem; color:#fff; margin-bottom:1.2rem;"><i class="fa-solid fa-sliders" style="color:var(--primary);"></i> Admin Override Settings</h3>

      <div class="grid-2" style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
        <div class="form-group">
          <label>Assignment Status</label>
          <select name="status" class="form-control">
            <?php 
            $statuses = ['New', 'Pending Review', 'Waiting for Payment', 'Confirmed', 'Allocated', 'In Progress', 'Quality Check', 'Completed', 'Delivered', 'Revision Requested', 'Cancelled', 'Refunded'];
            foreach ($statuses as $st) {
                echo "<option value='$st' " . ($asm['status'] === $st ? 'selected' : '') . ">$st</option>";
            }
            ?>
          </select>
        </div>

        <div class="form-group">
          <label>Override Price ($)</label>
          <input type="number" step="0.01" name="final_price" class="form-control" value="<?php echo $asm['final_price']; ?>">
        </div>
      </div>

      <div class="grid-2" style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
        <div class="form-group">
          <label>Assigned Expert</label>
          <select name="expert_id" class="form-control">
            <option value="">-- Unassigned --</option>
            <?php foreach ($experts as $exp): ?>
              <option value="<?php echo $exp['expert_id']; ?>" <?php echo ($asm['expert_id'] === $exp['expert_id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($exp['name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Assigned Allocator</label>
          <select name="allocator_id" class="form-control">
            <option value="">-- Unassigned --</option>
            <?php foreach ($allocators as $all): ?>
              <option value="<?php echo $all['allocator_id']; ?>" <?php echo ($asm['allocator_id'] === $all['allocator_id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($all['name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Upload Final Solution File -->
      <div style="background:rgba(16, 185, 129, 0.1); border:1px dashed var(--success); border-radius:var(--radius-sm); padding:1rem; margin-top:1rem;">
        <h5 style="color:var(--success); margin-bottom:0.4rem;"><i class="fa-solid fa-cloud-arrow-up"></i> Upload Final Completed Solution PDF</h5>
        <input type="file" name="solution_file" class="form-control" style="background:#090d16;">
        <small style="color:var(--text-muted); display:block; margin-top:4px;">Uploading solution automatically changes status to 'Completed'.</small>
      </div>

      <button type="submit" class="btn btn-primary" style="margin-top:1.2rem;"><i class="fa-solid fa-floppy-disk"></i> Save Admin Changes</button>
    </form>
  </div>

  <div>
    <!-- Full Unmasked Student Card (Admin Access Only) -->
    <div class="table-card" style="padding:1.5rem;">
      <h3 style="font-size:1.1rem; color:#fff; margin-bottom:1rem;"><i class="fa-solid fa-user-gear" style="color:var(--primary);"></i> Full Unmasked Student Record</h3>
      <table style="width:100%; font-size:0.9rem;">
        <tr><td style="color:var(--text-muted); padding:6px 0;">Name:</td><td style="text-align:right; font-weight:700; color:#fff;"><?php echo htmlspecialchars($student['name'] ?? ''); ?></td></tr>
        <tr><td style="color:var(--text-muted); padding:6px 0;">Email:</td><td style="text-align:right; color:var(--secondary);"><?php echo htmlspecialchars($student['email'] ?? ''); ?></td></tr>
        <tr><td style="color:var(--text-muted); padding:6px 0;">Phone:</td><td style="text-align:right; color:#fff;"><?php echo htmlspecialchars($student['phone'] ?? ''); ?></td></tr>
        <tr><td style="color:var(--text-muted); padding:6px 0;">University:</td><td style="text-align:right; color:#fff;"><?php echo htmlspecialchars($asm['university']); ?></td></tr>
        <tr><td style="color:var(--text-muted); padding:6px 0;">Country:</td><td style="text-align:right; color:#fff;"><?php echo htmlspecialchars($asm['country']); ?></td></tr>
      </table>
    </div>
  </div>
</div>

</div>
</div>
</body>
</html>
