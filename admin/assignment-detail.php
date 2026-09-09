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

$currency = $asm['currency'] ?? 'USD';
$currencySymbol = '$';
if ($currency === 'INR') $currencySymbol = '₹';
elseif ($currency === 'GBP') $currencySymbol = '£';
elseif ($currency === 'EUR') $currencySymbol = '€';
elseif ($currency === 'AUD') $currencySymbol = 'A$';
elseif ($currency === 'CAD') $currencySymbol = 'C$';

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

    // Handle Uploading Final Solution File (Any Format Accepted)
    if (isset($_FILES['solution_file']) && $_FILES['solution_file']['error'] === UPLOAD_ERR_OK) {
        $origName = basename($_FILES['solution_file']['name']);
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $targetDir = __DIR__ . '/../assets/uploads/';
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $safePrefix = preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($origName, PATHINFO_FILENAME));
        $uniqueName = time() . '_SOLUTION_' . rand(100, 999) . '_' . $safePrefix . ($ext ? '.' . $ext : '');
        $targetPath = $targetDir . $uniqueName;

        if (move_uploaded_file($_FILES['solution_file']['tmp_name'], $targetPath)) {
            DataStore::insert('files', [
                'file_id' => 'FILE-' . rand(8000, 9999),
                'assignment_id' => $id,
                'file_name' => $origName,
                'path' => 'assets/uploads/' . $uniqueName,
                'file_type' => $ext ?: 'file',
                'uploaded_by' => 'Admin (Completed Solution)',
                'upload_date' => date('Y-m-d H:i:s'),
                'is_internal' => false
            ]);
            DataStore::update('assignments', 'assignment_id', $id, ['status' => 'Completed']);
        }
    }

    // Handle Additional General Files (Any Format Accepted)
    if (isset($_FILES['assignment_files'])) {
        handle_uploaded_files('assignment_files', $id, 'Admin', !empty($_POST['is_internal']));
    }

    add_audit_log('Admin', $user['id'], 'Admin Override Assignment', "Updated $id parameters.");
    header("Location: /admin/assignment-detail.php?id=" . urlencode($id) . "&msg=Updated");
    exit;
}
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <a href="/admin/assignments.php" style="color:var(--text-muted); font-size:0.9rem;"><i class="fa-solid fa-arrow-left"></i> Back to Master Assignments</a>
    <h2 style="font-size:1.8rem; margin-top:0.3rem; color:#fff;"><?php echo htmlspecialchars($asm['title']); ?></h2>
    <div style="display:flex; gap:10px; align-items:center; margin-top:0.4rem; flex-wrap:wrap;">
      <span class="badge badge-info"><?php echo htmlspecialchars($asm['assignment_id']); ?></span>
      <span class="badge <?php echo get_status_badge_class($asm['status']); ?>"><?php echo htmlspecialchars($asm['status']); ?></span>
      <span class="badge badge-primary"><?php echo htmlspecialchars($currency); ?> <?php echo $currencySymbol . number_format($asm['final_price'], ($currency === 'INR' ? 0 : 2)); ?></span>
    </div>
  </div>
  <a href="/student/invoice.php?id=<?php echo urlencode($id); ?>" target="_blank" class="btn btn-outline btn-sm"><i class="fa-solid fa-print"></i> Print Official Invoice</a>
</div>

<div class="grid-2" style="display:grid; grid-template-columns:2fr 1fr; gap:1.5rem;">
  <div>
    <!-- Admin Settings & File Upload Form -->
    <form method="POST" enctype="multipart/form-data" class="table-card" style="padding:1.5rem; margin-bottom:1.5rem;">
      <input type="hidden" name="update_assignment" value="1">
      <h3 style="font-size:1.1rem; color:#fff; margin-bottom:1.2rem;"><i class="fa-solid fa-sliders" style="color:var(--primary);"></i> Admin Override & Workflow Settings</h3>

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
          <label>Override Price (<?php echo htmlspecialchars($currency); ?> <?php echo $currencySymbol; ?>)</label>
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

      <!-- Upload Final Solution File (Any Format) -->
      <div style="background:rgba(16, 185, 129, 0.1); border:1px dashed var(--success); border-radius:var(--radius-sm); padding:1.2rem; margin-top:1rem;">
        <h5 style="color:var(--success); margin-bottom:0.3rem;"><i class="fa-solid fa-file-circle-check"></i> Upload Final Completed Solution File (Any Format)</h5>
        <p style="color:var(--text-muted); font-size:0.83rem; margin-bottom:0.6rem;">
          Accepts <strong>ANY</strong> file format: PDF, DOCX, ZIP, RAR, TXT, PY, IPYNB, XLS, PPTX, etc. Uploading automatically marks order as 'Completed'.
        </p>
        <input type="file" name="solution_file" class="form-control" style="background:#090d16;">
      </div>

      <!-- Upload Additional Materials / Guidelines (Any Format) -->
      <div style="background:rgba(99, 102, 241, 0.08); border:1px dashed var(--portal-border); border-radius:var(--radius-sm); padding:1.2rem; margin-top:1rem;">
        <h5 style="color:var(--secondary); margin-bottom:0.3rem;"><i class="fa-solid fa-cloud-arrow-up"></i> Upload Additional Files / Materials (Any Format)</h5>
        <p style="color:var(--text-muted); font-size:0.83rem; margin-bottom:0.6rem;">
          Upload reference materials, rubrics, drafts, or code. Accepts <strong>ANY</strong> format (multiple files supported).
        </p>
        <input type="file" name="assignment_files[]" multiple class="form-control" style="background:#090d16; margin-bottom:0.6rem;">
        <label style="display:flex; align-items:center; gap:6px; font-size:0.85rem; color:var(--text-muted); margin:0; cursor:pointer;">
          <input type="checkbox" name="is_internal" value="1">
          <span>Mark additional files as Staff Internal Only (Hidden from student)</span>
        </label>
      </div>

      <button type="submit" class="btn btn-primary" style="margin-top:1.2rem;"><i class="fa-solid fa-floppy-disk"></i> Save Admin Changes</button>
    </form>

    <!-- Attached Files Table -->
    <div class="table-card" style="padding:1.5rem;">
      <h3 style="font-size:1.1rem; color:#fff; margin-bottom:1rem;"><i class="fa-solid fa-paperclip" style="color:var(--success);"></i> All Attached Files (<?php echo count($files); ?>)</h3>
      <?php if (empty($files)): ?>
        <p style="color:var(--text-muted); font-size:0.9rem;">No files attached to this assignment yet.</p>
      <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:10px;">
          <?php foreach ($files as $file): 
            $ext = strtolower($file['file_type'] ?? '');
            $icon = 'fa-file';
            if (in_array($ext, ['pdf'])) $icon = 'fa-file-pdf';
            elseif (in_array($ext, ['doc', 'docx'])) $icon = 'fa-file-word';
            elseif (in_array($ext, ['xls', 'xlsx', 'csv'])) $icon = 'fa-file-excel';
            elseif (in_array($ext, ['ppt', 'pptx'])) $icon = 'fa-file-powerpoint';
            elseif (in_array($ext, ['zip', 'rar', 'tar', 'gz', '7z'])) $icon = 'fa-file-zipper';
            elseif (in_array($ext, ['py', 'java', 'cpp', 'c', 'js', 'html', 'css', 'sql', 'php', 'ipynb'])) $icon = 'fa-file-code';
            elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'])) $icon = 'fa-file-image';
            $isInternal = !empty($file['is_internal']);
          ?>
            <div style="display:flex; justify-content:space-between; align-items:center; background:#090d16; border:1px solid var(--portal-border); padding:0.8rem 1rem; border-radius:var(--radius-sm); flex-wrap:wrap; gap:0.5rem;">
              <div>
                <i class="fa-solid <?php echo $icon; ?>" style="color:var(--primary); margin-right:8px; font-size:1.1rem;"></i>
                <strong style="color:#fff;"><?php echo htmlspecialchars($file['file_name']); ?></strong>
                <?php if ($isInternal): ?>
                  <span class="badge badge-warning" style="font-size:0.7rem; margin-left:6px;">Staff Only</span>
                <?php else: ?>
                  <span class="badge badge-success" style="font-size:0.7rem; margin-left:6px;">Public / Student</span>
                <?php endif; ?>
                <small style="color:var(--text-muted); display:block; margin-top:2px;">Uploaded by <?php echo htmlspecialchars($file['uploaded_by']); ?> &bull; <?php echo $file['upload_date']; ?></small>
              </div>
              <div style="display:flex; gap:8px;">
                <a href="/<?php echo htmlspecialchars($file['path']); ?>" download class="btn btn-outline btn-sm">
                  <i class="fa-solid fa-download"></i> Download
                </a>
                <button type="button" class="btn btn-danger btn-sm" onclick="deleteFile('<?php echo htmlspecialchars($file['file_id']); ?>')">
                  <i class="fa-solid fa-trash"></i>
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div>
    <!-- Full Unmasked Student Card (Admin Access Only) -->
    <div class="table-card" style="padding:1.5rem; margin-bottom:1.5rem;">
      <h3 style="font-size:1.1rem; color:#fff; margin-bottom:1rem;"><i class="fa-solid fa-user-gear" style="color:var(--primary);"></i> Full Student Record</h3>
      <table style="width:100%; font-size:0.9rem;">
        <tr><td style="color:var(--text-muted); padding:6px 0;">Name:</td><td style="text-align:right; font-weight:700; color:#fff;"><?php echo htmlspecialchars($student['name'] ?? ''); ?></td></tr>
        <tr><td style="color:var(--text-muted); padding:6px 0;">Email:</td><td style="text-align:right; color:var(--secondary);"><?php echo htmlspecialchars($student['email'] ?? ''); ?></td></tr>
        <tr><td style="color:var(--text-muted); padding:6px 0;">Phone:</td><td style="text-align:right; color:#fff;"><?php echo htmlspecialchars($student['phone'] ?? ''); ?></td></tr>
        <tr><td style="color:var(--text-muted); padding:6px 0;">University:</td><td style="text-align:right; color:#fff;"><?php echo htmlspecialchars($asm['university'] ?? 'N/A'); ?></td></tr>
        <tr><td style="color:var(--text-muted); padding:6px 0;">Country:</td><td style="text-align:right; color:#fff;"><?php echo htmlspecialchars($asm['country']); ?></td></tr>
        <tr><td style="color:var(--text-muted); padding:6px 0;">Currency:</td><td style="text-align:right; color:#fff; font-weight:700;"><?php echo htmlspecialchars($currency); ?></td></tr>
        <tr><td style="color:var(--text-muted); padding:6px 0;">Word Count:</td><td style="text-align:right; color:#fff;"><?php echo $asm['word_count']; ?> Words</td></tr>
        <tr><td style="color:var(--text-muted); padding:6px 0;">Deadline:</td><td style="text-align:right; color:#fff;"><?php echo $asm['deadline']; ?></td></tr>
      </table>
    </div>

    <!-- Student Instructions -->
    <div class="table-card" style="padding:1.5rem;">
      <h3 style="font-size:1.1rem; color:#fff; margin-bottom:0.8rem;"><i class="fa-solid fa-align-left" style="color:var(--secondary);"></i> Assignment Requirements</h3>
      <p style="color:var(--text-main); font-size:0.92rem; white-space:pre-line; line-height:1.6; margin:0;">
        <?php echo htmlspecialchars($asm['instructions'] ?? 'None'); ?>
      </p>
    </div>
  </div>
</div>

<script>
function deleteFile(fileId) {
  if (!confirm('Are you sure you want to delete this file permanently?')) return;

  const fd = new FormData();
  fd.append('file_id', fileId);

  fetch('/api.php?action=delete_assignment_file', {
    method: 'POST',
    body: fd
  })
  .then(res => res.json())
  .then(data => {
    alert(data.message);
    if (data.success) {
      window.location.reload();
    }
  })
  .catch(err => alert('Error deleting file.'));
}
</script>

</div>
</div>
</body>
</html>
