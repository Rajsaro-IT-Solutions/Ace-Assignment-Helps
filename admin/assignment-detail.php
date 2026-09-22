<?php
$pageTitle = "Admin Master Assignment Control";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Admin');
$user = Auth::currentUser();
require_once __DIR__ . '/../includes/helpers.php';

$id = trim($_GET['id'] ?? ($_POST['assignment_id'] ?? ''));

// Handle POST actions BEFORE sending any headers/HTML
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $targetId = trim($_POST['assignment_id'] ?? $id);

    if ($action === 'delete_assignment') {
        if ($targetId) {
            DataStore::update('assignments', 'assignment_id', $targetId, [
                'status' => 'Deleted',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            DataStore::update('payments', 'assignment_id', $targetId, [
                'status' => 'Cancelled'
            ]);
            add_audit_log('Admin', $user['id'], 'Delete Assignment', "Moved assignment $targetId to History (Soft Delete)");
            $_SESSION['flash_msg'] = "Assignment $targetId moved to History / Trash (Soft Delete).";
            $_SESSION['flash_type'] = 'warning';
        }
        header('Location: /admin/assignments.php');
        echo "<script>window.location.href='/admin/assignments.php';</script>";
        exit;
    } elseif ($action === 'permanent_delete_assignment') {
        if ($targetId) {
            DataStore::purgeAssignment($targetId);
            add_audit_log('Admin', $user['id'], 'Permanent Delete Assignment', "Permanently wiped assignment $targetId from everywhere");
            $_SESSION['flash_msg'] = "Assignment $targetId has been PERMANENTLY deleted from everywhere (removed from history, payments, files, and database).";
            $_SESSION['flash_type'] = 'danger';
        }
        header('Location: /admin/assignments.php');
        echo "<script>window.location.href='/admin/assignments.php';</script>";
        exit;
    } elseif ($action === 'restore_assignment') {
        if ($targetId) {
            DataStore::update('assignments', 'assignment_id', $targetId, [
                'status' => 'Pending Review',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            add_audit_log('Admin', $user['id'], 'Restore Assignment', "Restored assignment $targetId to Pending Review");
            $_SESSION['flash_msg'] = "Assignment $targetId restored from History to active status.";
            $_SESSION['flash_type'] = 'success';
        }
        header("Location: /admin/assignment-detail.php?id=" . urlencode($targetId));
        echo "<script>window.location.href='/admin/assignment-detail.php?id=" . urlencode($targetId) . "';</script>";
        exit;
    } elseif (isset($_POST['update_assignment']) && $targetId) {
        $asm = DataStore::findOne('assignments', 'assignment_id', $targetId);
        $status = $_POST['status'] ?? ($asm['status'] ?? 'New');
        $price = (float)($_POST['final_price'] ?? ($asm['final_price'] ?? 0));
        $expert_id = $_POST['expert_id'] ?? ($asm['expert_id'] ?? '');
        $allocator_id = $_POST['allocator_id'] ?? ($asm['allocator_id'] ?? '');

        DataStore::update('assignments', 'assignment_id', $targetId, [
            'status' => $status,
            'final_price' => $price,
            'expert_id' => $expert_id,
            'allocator_id' => $allocator_id,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        if ($status === 'Completed') {
            if (!empty($expert_id)) {
                add_notification('Expert', $expert_id, "Solution Approved - $targetId", "Your solution for order $targetId has been approved and marked Completed!", 'success', "/expert/assignment-detail.php?id=$targetId");
            }
            if (!empty($asm['student_id'])) {
                add_notification('Student', $asm['student_id'], "Solution Approved & Released - $targetId", "Great news! Your assignment $targetId has received final administrative approval and is now released in your portal.", 'success', "/student/assignment-detail.php?id=$targetId");
            }
            add_audit_log('Admin', $user['id'], 'Final Admin Approval', "Admin granted final approval and released solution for order $targetId to student");
        }

        // Handle Uploading Final Solution File
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
                    'assignment_id' => $targetId,
                    'file_name' => $origName,
                    'path' => 'assets/uploads/' . $uniqueName,
                    'file_type' => $ext ?: 'file',
                    'uploaded_by' => 'Admin (' . $user['name'] . ')',
                    'upload_date' => date('Y-m-d H:i:s'),
                    'is_internal' => 0
                ]);
            }
        }

        $_SESSION['flash_msg'] = "Assignment updated successfully.";
        $_SESSION['flash_type'] = 'success';
        header("Location: /admin/assignment-detail.php?id=" . urlencode($targetId));
        echo "<script>window.location.href='/admin/assignment-detail.php?id=" . urlencode($targetId) . "';</script>";
        exit;
    }
}

$asm = DataStore::findOne('assignments', 'assignment_id', $id);
include __DIR__ . '/../includes/portal_header.php';

$flashMsg = $_SESSION['flash_msg'] ?? '';
$flashType = $_SESSION['flash_type'] ?? 'info';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);

if (!$asm) {
    echo "<div class='table-card' style='padding:2rem; text-align:center;'>";
    echo "<div class='badge badge-danger' style='font-size:1rem; padding:10px 16px; margin-bottom:1rem;'><i class='fa-solid fa-circle-exclamation'></i> Assignment not found or has been permanently deleted.</div>";
    echo "<p style='color:var(--text-muted);'>This assignment does not exist in the database.</p>";
    echo "<a href='/admin/assignments.php' class='btn btn-primary btn-sm'><i class='fa-solid fa-folder-tree'></i> Return to Master Assignments</a>";
    echo "</div></div></div></body></html>";
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

<?php if (in_array($asm['status'], ['Pending Admin Approval', 'Quality Check'])): 
  $isAllocatorApproved = ($asm['status'] === 'Pending Admin Approval');
?>
  <div style="background: <?php echo $isAllocatorApproved ? 'linear-gradient(135deg, rgba(6, 182, 212, 0.2) 0%, rgba(59, 130, 246, 0.2) 100%)' : 'linear-gradient(135deg, rgba(147, 51, 234, 0.18) 0%, rgba(79, 70, 229, 0.18) 100%)'; ?>; border: 1.5px solid <?php echo $isAllocatorApproved ? '#06b6d4' : '#a855f7'; ?>; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.25rem;">
    <div>
      <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
        <?php if ($isAllocatorApproved): ?>
          <span class="badge badge-cyan" style="font-weight:800; font-size:0.85rem; padding:4px 10px;">
            <i class="fa-solid fa-check-double"></i> Allocator QA Approved
          </span>
          <span style="color:#67e8f9; font-size:0.85rem; font-weight:700;">Verified by Allocator — Final Admin Sign-off & Student Release Required</span>
        <?php else: ?>
          <span class="badge badge-purple" style="font-weight:800; font-size:0.85rem; padding:4px 10px;">
            <i class="fa-solid fa-microscope"></i> Quality Check Review
          </span>
          <span style="color:#d8b4fe; font-size:0.85rem; font-weight:600;">Solution files submitted by expert</span>
        <?php endif; ?>
      </div>
      <h3 style="color:#ffffff; margin:0 0 4px 0; font-size:1.25rem;">
        <?php echo $isAllocatorApproved ? 'Allocator QA Passed — Grant Final Admin Approval & Release' : 'Expert Solution Ready for Quality Review & Final Release'; ?>
      </h3>
      <p style="color:#e0f2fe; margin:0; font-size:0.88rem;">
        <?php echo $isAllocatorApproved ? 'The allocator has reviewed and approved the expert\'s deliverable. Review files below and grant final admin approval to officially complete the order and release it to the student.' : 'Review the attached solution and Turnitin documents below. Approve to mark Completed and release files to student portal.'; ?>
      </p>
    </div>
    <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
      <form method="POST" style="margin:0;">
        <input type="hidden" name="update_assignment" value="1">
        <input type="hidden" name="status" value="Completed">
        <button type="submit" class="btn btn-success btn-lg" style="font-weight:800; padding:0.85rem 1.75rem; box-shadow:0 4px 15px rgba(16,185,129,0.4); border:none; cursor:pointer;" onclick="return confirm('Grant Final Admin Approval? This will officially mark the order Completed and release solution files to the student.');">
          <i class="fa-solid fa-circle-check"></i> Grant Final Admin Approval & Release
        </button>
      </form>
      <form method="POST" style="margin:0;">
        <input type="hidden" name="update_assignment" value="1">
        <input type="hidden" name="status" value="Revision Requested">
        <button type="submit" class="btn btn-warning" style="font-weight:700; padding:0.85rem 1.25rem; border:none; cursor:pointer;">
          <i class="fa-solid fa-rotate-left"></i> Request Revision
        </button>
      </form>
    </div>
  </div>
<?php endif; ?>

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
            $statuses = ['New', 'Pending Review', 'Waiting for Payment', 'Confirmed', 'Allocated', 'In Progress', 'Quality Check', 'Pending Admin Approval', 'Completed', 'Delivered', 'Revision Requested', 'Cancelled', 'Refunded'];
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
    <div class="table-card" style="padding:1.5rem; margin-bottom:1.5rem;">
      <h3 style="font-size:1.1rem; color:#fff; margin-bottom:0.8rem;"><i class="fa-solid fa-align-left" style="color:var(--secondary);"></i> Assignment Requirements</h3>
      <p style="color:var(--text-main); font-size:0.92rem; white-space:pre-line; line-height:1.6; margin:0;">
        <?php echo htmlspecialchars($asm['instructions'] ?? 'None'); ?>
      </p>
    </div>

    <!-- Danger Zone Card: Dual Delete & History Controls -->
    <div class="table-card" style="padding:1.5rem; border:1px solid rgba(239, 68, 68, 0.4); background:rgba(239, 68, 68, 0.04);">
      <h3 style="font-size:1.05rem; color:#ef4444; margin-bottom:0.6rem;">
        <i class="fa-solid fa-triangle-exclamation"></i> Danger Zone & Deletion Controls
      </h3>
      <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:1rem;">
        Choose between soft-deleting (stays in archives/history) or permanently wiping this assignment from everywhere.
      </p>

      <div style="display:flex; flex-direction:column; gap:10px;">
        <?php if ($asm['status'] === 'Deleted'): ?>
          <div class="badge badge-warning" style="display:block; text-align:center; padding:8px; margin-bottom:4px;">
            <i class="fa-solid fa-clock-rotate-left"></i> Currently Archived in History
          </div>
          <form method="POST" onsubmit="return confirm('Restore assignment <?php echo addslashes($id); ?> to Active (Pending Review)?');">
            <input type="hidden" name="action" value="restore_assignment">
            <input type="hidden" name="assignment_id" value="<?php echo htmlspecialchars($id); ?>">
            <button type="submit" class="btn btn-sm" style="width:100%; background:#10b981; color:#fff; border:none; padding:8px;">
              <i class="fa-solid fa-rotate-left"></i> Restore to Active Status
            </button>
          </form>
        <?php else: ?>
          <form method="POST" onsubmit="return confirm('Move assignment <?php echo addslashes($id); ?> to History? (It will stay in History and can be restored)');">
            <input type="hidden" name="action" value="delete_assignment">
            <input type="hidden" name="assignment_id" value="<?php echo htmlspecialchars($id); ?>">
            <button type="submit" class="btn btn-sm" style="width:100%; background:#f59e0b; color:#fff; border:none; padding:8px;" title="Soft Delete">
              <i class="fa-solid fa-box-archive"></i> Move to History (Soft Delete)
            </button>
          </form>
        <?php endif; ?>

        <!-- Permanent Delete Button (Triggers In-Page Modal) -->
        <button type="button" class="btn btn-danger btn-sm" onclick="openPermDeleteModal('<?php echo htmlspecialchars($id, ENT_QUOTES); ?>')" style="width:100%; padding:9px; background:#b91c1c;" title="Permanent Delete">
          <i class="fa-solid fa-trash-can"></i> Permanently Delete from Everywhere (Wipe)
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modern In-Page Confirmation Modal for Permanent Deletion -->
<div id="permanentDeleteModal" class="modal-overlay" style="display:none;">
  <div class="modal-box" style="padding:1.8rem; border-top:4px solid #ef4444; max-width:480px;">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1rem;">
      <h3 style="margin:0; font-size:1.2rem; color:#ef4444; display:flex; align-items:center; gap:8px;">
        <i class="fa-solid fa-triangle-exclamation"></i> Permanent Wipe Confirmation
      </h3>
      <button type="button" class="modal-close" onclick="closePermDeleteModal()" style="background:none; border:none; color:var(--text-muted); font-size:1.4rem; cursor:pointer; line-height:1;">&times;</button>
    </div>
    
    <p style="color:var(--text-main); font-size:0.95rem; margin-bottom:0.8rem;">
      Are you sure you want to permanently delete assignment:
    </p>
    <div style="background:rgba(239, 68, 68, 0.08); border:1px solid rgba(239, 68, 68, 0.3); border-radius:6px; padding:0.8rem 1rem; margin-bottom:1.2rem; text-align:center;">
      <strong id="permModalAsmId" style="font-size:1.2rem; color:#ef4444; font-family:monospace;"><?php echo htmlspecialchars($id); ?></strong>
    </div>

    <div style="font-size:0.88rem; color:#b91c1c; background:#fee2e2; border-radius:6px; padding:0.85rem; margin-bottom:1.5rem; line-height:1.45;">
      <i class="fa-solid fa-circle-exclamation"></i> <strong>IRREVERSIBLE ACTION:</strong> This will completely remove this assignment from the database, uploaded solution/brief files, payment logs, allocation records, and History. It CANNOT be recovered!
    </div>

    <form method="POST" id="permDeleteForm" action="/admin/assignments.php">
      <input type="hidden" name="action" value="permanent_delete_assignment">
      <input type="hidden" name="assignment_id" value="<?php echo htmlspecialchars($id); ?>">
      <div style="display:flex; justify-content:flex-end; gap:10px;">
        <button type="button" class="btn btn-outline btn-sm" onclick="closePermDeleteModal()">Cancel</button>
        <button type="submit" class="btn btn-danger btn-sm" style="background:#b91c1c; padding:8px 16px;">
          <i class="fa-solid fa-trash-can"></i> Yes, Permanently Delete Everywhere
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openPermDeleteModal(asmId) {
  document.getElementById('permanentDeleteModal').style.display = 'flex';
}
function closePermDeleteModal() {
  document.getElementById('permanentDeleteModal').style.display = 'none';
}

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

<script src="/assets/js/portal.js"></script>
</div>
</div>
</body>
</html>
