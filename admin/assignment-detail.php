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
    } elseif ($action === 'admin_reassign_allocator') {
        $newAllocatorId = trim($_POST['allocator_id'] ?? '');
        $adminNotes = trim($_POST['admin_notes'] ?? '');
        if ($targetId && $newAllocatorId) {
            $asm = DataStore::findOne('assignments', 'assignment_id', $targetId);
            $allocatorObj = DataStore::findOne('allocators', 'allocator_id', $newAllocatorId);
            $allocatorName = $allocatorObj['name'] ?? $newAllocatorId;

            DataStore::update('assignments', 'assignment_id', $targetId, [
                'allocator_id' => $newAllocatorId,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            if (!empty($adminNotes)) {
                DataStore::insert('notes', [
                    'note_id' => 'NOTE-' . rand(100, 999),
                    'assignment_id' => $targetId,
                    'user_id' => $user['id'],
                    'user_role' => 'Admin',
                    'user_name' => $user['name'],
                    'message' => "Admin Reassigned Allocator to {$allocatorName}: " . $adminNotes,
                    'visibility' => 'Internal',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }

            add_audit_log('Admin', $user['id'], 'Reassign Allocator', "Admin assigned revision of order $targetId to Allocator $allocatorName");
            add_notification('Allocator', $newAllocatorId, "Revision Order Assigned - $targetId", "Admin {$user['name']} has assigned you to manage the revision for order $targetId. " . substr($adminNotes, 0, 80), 'warning', "/allocator/assignment-detail.php?id=$targetId");

            $_SESSION['flash_msg'] = "Allocator successfully reassigned to {$allocatorName} for revision.";
            $_SESSION['flash_type'] = 'success';
        }
        header("Location: /admin/assignment-detail.php?id=" . urlencode($targetId));
        echo "<script>window.location.href='/admin/assignment-detail.php?id=" . urlencode($targetId) . "';</script>";
        exit;
    } elseif ($action === 'admin_approve_refund') {
        if ($targetId) {
            $asm = DataStore::findOne('assignments', 'assignment_id', $targetId);
            DataStore::update('assignments', 'assignment_id', $targetId, [
                'status' => 'Refunded',
                'payment_status' => 'Refunded',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            DataStore::update('payments', 'assignment_id', $targetId, [
                'status' => 'Refunded'
            ]);
            DataStore::insert('notes', [
                'note_id' => 'NOTE-' . rand(100, 999),
                'assignment_id' => $targetId,
                'user_id' => $user['id'],
                'user_role' => 'Admin',
                'user_name' => $user['name'],
                'message' => "Refund Approved by Admin for order $targetId. Order and payment set to Refunded.",
                'visibility' => 'Internal',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            add_audit_log('Admin', $user['id'], 'Approve Refund', "Admin approved refund for order $targetId");
            if (!empty($asm['student_id'])) {
                add_notification('Student', $asm['student_id'], "Refund Approved - $targetId", "Your refund request for assignment $targetId has been approved and processed.", 'success', "/student/assignment-detail.php?id=$targetId");
            }
            $_SESSION['flash_msg'] = "Refund approved! Order $targetId marked as Refunded.";
            $_SESSION['flash_type'] = 'success';
        }
        header("Location: /admin/assignment-detail.php?id=" . urlencode($targetId));
        echo "<script>window.location.href='/admin/assignment-detail.php?id=" . urlencode($targetId) . "';</script>";
        exit;
    } elseif ($action === 'admin_reject_refund') {
        $rejectReason = trim($_POST['reject_reason'] ?? 'Did not meet refund criteria.');
        if ($targetId) {
            $asm = DataStore::findOne('assignments', 'assignment_id', $targetId);
            $newStatus = in_array($asm['status'], ['Completed', 'Delivered']) ? $asm['status'] : 'In Progress';
            $oldReason = $asm['refund_reason'] ?? '';
            DataStore::update('assignments', 'assignment_id', $targetId, [
                'status' => $newStatus,
                'refund_reason' => $oldReason . " [DENIED: $rejectReason]",
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            DataStore::insert('notes', [
                'note_id' => 'NOTE-' . rand(100, 999),
                'assignment_id' => $targetId,
                'user_id' => $user['id'],
                'user_role' => 'Admin',
                'user_name' => $user['name'],
                'message' => "Refund Request Declined: $rejectReason",
                'visibility' => 'Internal',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            add_audit_log('Admin', $user['id'], 'Reject Refund', "Admin declined refund request for $targetId");
            if (!empty($asm['student_id'])) {
                add_notification('Student', $asm['student_id'], "Refund Request Declined - $targetId", "Your refund request for order $targetId was reviewed and declined. Reason: $rejectReason", 'warning', "/student/assignment-detail.php?id=$targetId");
            }
            $_SESSION['flash_msg'] = "Refund request declined. Order restored to {$newStatus}.";
            $_SESSION['flash_type'] = 'warning';
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

        // Handle Uploading Solution/Draft File
        if (isset($_FILES['solution_file']) && $_FILES['solution_file']['error'] === UPLOAD_ERR_OK) {
            $solStage = trim($_POST['solution_file_stage'] ?? 'complete');
            if (!in_array($solStage, ['draft', 'complete'])) $solStage = 'complete';

            $origName = basename($_FILES['solution_file']['name']);
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $targetDir = __DIR__ . '/../assets/uploads/';
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            $safePrefix = preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($origName, PATHINFO_FILENAME));
            $stageTag = ($solStage === 'complete') ? '_SOLUTION_' : '_DRAFT_';
            $uniqueName = time() . $stageTag . rand(100, 999) . '_' . $safePrefix . ($ext ? '.' . $ext : '');
            $targetPath = $targetDir . $uniqueName;

            if (move_uploaded_file($_FILES['solution_file']['tmp_name'], $targetPath)) {
                DataStore::insert('files', [
                    'file_id' => 'FILE-' . rand(8000, 9999),
                    'assignment_id' => $targetId,
                    'file_name' => $origName,
                    'path' => 'assets/uploads/' . $uniqueName,
                    'file_type' => $ext ?: 'file',
                    'file_stage' => $solStage,
                    'uploaded_by' => 'Admin (' . $user['name'] . ')',
                    'upload_date' => date('Y-m-d H:i:s'),
                    'is_internal' => 0
                ]);
            }
        }

        // Handle Uploading Additional Materials / Drafts
        if (isset($_FILES['assignment_files'])) {
            $addStage = trim($_POST['additional_files_stage'] ?? 'draft');
            if (!in_array($addStage, ['draft', 'complete'])) $addStage = 'draft';
            handle_uploaded_files('assignment_files', $targetId, 'Admin (' . $user['name'] . ')', !empty($_POST['is_internal']), $addStage);
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

<?php if ($asm['status'] === 'Revision Requested'): ?>
  <div style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.18) 0%, rgba(217, 119, 6, 0.18) 100%); border: 1.5px solid #f59e0b; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem;">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1.25rem;">
      <div style="max-width:750px;">
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
          <span class="badge badge-warning" style="font-weight:800; font-size:0.85rem; padding:4px 10px;">
            <i class="fa-solid fa-rotate-left"></i> Revision Requested
          </span>
          <span style="color:#fcd34d; font-size:0.85rem; font-weight:700;">Student / QA Revision Workflow Active</span>
        </div>
        <h3 style="color:#ffffff; margin:0 0 6px 0; font-size:1.25rem;">
          Revision Management: Supervise & Reassign Allocator
        </h3>
        <p style="color:#fef3c7; margin:0 0 0.75rem 0; font-size:0.9rem; line-height:1.5;">
          <?php echo htmlspecialchars($asm['revision_notes'] ?: 'Student requested revisions on the delivered solution files.'); ?>
        </p>
        <div style="font-size:0.85rem; color:#fde68a;">
          Current Assigned Allocator: <strong><?php 
            $currAll = DataStore::findOne('allocators', 'allocator_id', $asm['allocator_id']);
            echo htmlspecialchars($currAll['name'] ?? 'Unassigned'); 
          ?></strong>
        </div>
      </div>
      <button type="button" class="btn btn-warning" onclick="document.getElementById('reassignAllocatorModal').style.display='flex';" style="font-weight:800; padding:0.85rem 1.4rem;">
        <i class="fa-solid fa-user-gear"></i> Change Allocator
      </button>
    </div>
  </div>
<?php endif; ?>

<?php if ($asm['status'] === 'Refund Requested'): ?>
  <div style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.2) 0%, rgba(185, 28, 28, 0.2) 100%); border: 1.5px solid #ef4444; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem;">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1.25rem;">
      <div style="max-width:750px;">
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
          <span class="badge badge-danger" style="font-weight:800; font-size:0.85rem; padding:4px 10px;">
            <i class="fa-solid fa-hand-holding-dollar"></i> Student Refund Requested
          </span>
          <span style="color:#fca5a5; font-size:0.85rem; font-weight:700;">Immediate Administrative Review Required</span>
        </div>
        <h3 style="color:#ffffff; margin:0 0 6px 0; font-size:1.25rem;">
          Refund Claim Filed by Student
        </h3>
        <div style="background:rgba(0,0,0,0.3); border:1px solid rgba(239,68,68,0.3); border-radius:8px; padding:0.9rem 1.1rem; margin-bottom:0.75rem; color:#fee2e2; font-size:0.9rem; line-height:1.5;">
          <strong>Grounds for Refund:</strong> <?php echo htmlspecialchars($asm['refund_reason'] ?: 'No details provided.'); ?>
        </div>
        <?php 
          $refundProofFiles = array_values(array_filter($files, function($f) { 
            return isset($f['file_stage']) && $f['file_stage'] === 'refund_proof'; 
          }));
        ?>
        <div style="margin-bottom:0.75rem; background:rgba(0,0,0,0.25); border:1px dashed rgba(239,68,68,0.4); border-radius:8px; padding:0.8rem 1rem;">
          <div style="font-size:0.82rem; font-weight:700; color:#fca5a5; margin-bottom:6px; display:flex; align-items:center; gap:6px;">
            <i class="fa-solid fa-paperclip"></i> Attached Student Proof & Evidence (<?php echo count($refundProofFiles); ?> files):
          </div>
          <?php if (empty($refundProofFiles)): ?>
            <div style="font-size:0.82rem; color:#f87171; font-style:italic;">No files attached by the student with this refund request.</div>
          <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:6px;">
              <?php foreach ($refundProofFiles as $rpf): ?>
                <div style="display:flex; justify-content:space-between; align-items:center; background:rgba(0,0,0,0.4); padding:6px 10px; border-radius:6px; border:1px solid rgba(239,68,68,0.25);">
                  <span style="color:#ffffff; font-size:0.84rem; font-weight:600;">
                    <i class="fa-solid fa-file" style="color:#f87171; margin-right:6px;"></i> <?php echo htmlspecialchars($rpf['file_name']); ?>
                  </span>
                  <a href="/<?php echo htmlspecialchars($rpf['path']); ?>" download class="btn btn-sm btn-outline" style="border-color:#ef4444; color:#fca5a5; padding:3px 8px; font-size:0.75rem;">
                    <i class="fa-solid fa-download"></i> View / Download Proof
                  </a>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
        <div style="font-size:0.85rem; color:#fca5a5;">
          Total Price: <strong><?php echo format_currency_amount($asm['final_price'], $currency); ?></strong> &bull; Amount Paid: <strong><?php echo format_currency_amount($asm['paid_amount'] ?? $asm['final_price'], $currency); ?></strong>
        </div>
      </div>
      <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
        <form method="POST" style="margin:0;">
          <input type="hidden" name="action" value="admin_approve_refund">
          <input type="hidden" name="assignment_id" value="<?php echo htmlspecialchars($id); ?>">
          <button type="submit" class="btn btn-danger btn-lg" style="font-weight:800; padding:0.85rem 1.6rem; border:none; cursor:pointer;" onclick="return confirm('Approve Refund? This will officially set order status and payment status to Refunded.');">
            <i class="fa-solid fa-check"></i> Approve & Refund
          </button>
        </form>
        <button type="button" class="btn btn-outline" style="border-color:#ef4444; color:#fee2e2;" onclick="document.getElementById('rejectRefundModal').style.display='flex';">
          <i class="fa-solid fa-xmark"></i> Reject Refund
        </button>
      </div>
    </div>
  </div>
<?php endif; ?>

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
        <div style="margin-bottom:0.6rem; display:flex; gap:16px; align-items:center; flex-wrap:wrap; background:rgba(0,0,0,0.3); padding:8px 12px; border-radius:6px; border:1px solid rgba(16,185,129,0.3);">
          <span style="font-size:0.82rem; color:#f8fafc; font-weight:700;"><i class="fa-solid fa-tag"></i> Classification:</span>
          <label style="display:flex; align-items:center; gap:5px; font-size:0.82rem; color:#93c5fd; cursor:pointer; margin:0;">
            <input type="radio" name="solution_file_stage" value="complete" checked>
            <span><strong>Complete Final Solution</strong> (Requires 100% full payment)</span>
          </label>
          <label style="display:flex; align-items:center; gap:5px; font-size:0.82rem; color:#fde047; cursor:pointer; margin:0;">
            <input type="radio" name="solution_file_stage" value="draft">
            <span><strong>Milestone Draft File</strong> (Progressive download limit)</span>
          </label>
        </div>
        <input type="file" name="solution_file" class="form-control" style="background:#090d16;">
      </div>

      <!-- Upload Additional Materials / Guidelines (Any Format) -->
      <div style="background:rgba(99, 102, 241, 0.08); border:1px dashed var(--portal-border); border-radius:var(--radius-sm); padding:1.2rem; margin-top:1rem;">
        <h5 style="color:var(--secondary); margin-bottom:0.3rem;"><i class="fa-solid fa-cloud-arrow-up"></i> Upload Additional Files / Materials (Any Format)</h5>
        <p style="color:var(--text-muted); font-size:0.83rem; margin-bottom:0.6rem;">
          Upload reference materials, rubrics, drafts, or code. Accepts <strong>ANY</strong> format (multiple files supported).
        </p>
        <div style="margin-bottom:0.6rem; display:flex; gap:16px; align-items:center; flex-wrap:wrap; background:rgba(0,0,0,0.3); padding:8px 12px; border-radius:6px; border:1px solid rgba(99,102,241,0.3);">
          <span style="font-size:0.82rem; color:#f8fafc; font-weight:700;"><i class="fa-solid fa-tag"></i> Classification:</span>
          <label style="display:flex; align-items:center; gap:5px; font-size:0.82rem; color:#fde047; cursor:pointer; margin:0;">
            <input type="radio" name="additional_files_stage" value="draft" checked>
            <span><strong>Draft / Work-in-Progress</strong> (Milestone restricted)</span>
          </label>
          <label style="display:flex; align-items:center; gap:5px; font-size:0.82rem; color:#93c5fd; cursor:pointer; margin:0;">
            <input type="radio" name="additional_files_stage" value="complete">
            <span><strong>Complete Deliverable</strong> (Blurred until 100% paid)</span>
          </label>
        </div>
        <input type="file" name="assignment_files[]" multiple class="form-control" style="background:#090d16; margin-bottom:0.6rem;">
        <label style="display:flex; align-items:center; gap:6px; font-size:0.85rem; color:var(--text-muted); margin:0; cursor:pointer;">
          <input type="checkbox" name="is_internal" value="1">
          <span>Mark additional files as Staff Internal Only (Hidden from student)</span>
        </label>
      </div>

      <button type="submit" class="btn btn-primary" style="margin-top:1.2rem;"><i class="fa-solid fa-floppy-disk"></i> Save Admin Changes</button>
    </form>

    <?php 
      $techFiles = array_values(array_filter($files, function($f) { return is_tech_file($f); }));
      $docFiles = array_values(array_filter($files, function($f) { return !is_tech_file($f); }));
    ?>

    <!-- 1. Dedicated Technical Files & Archive Section (.zip, .rar, .7z, scripts) -->
    <div class="table-card" style="padding:1.5rem; margin-bottom:1.5rem; border:1.5px solid #2563eb; background:linear-gradient(180deg, rgba(30, 58, 138, 0.12) 0%, rgba(13, 21, 39, 0.95) 100%);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:0.5rem;">
        <h3 style="font-size:1.15rem; color:#fff; margin:0; display:flex; align-items:center; gap:8px;">
          <i class="fa-solid fa-file-zipper" style="color:#60a5fa;"></i> Technical Archives & Code (.ZIP, .RAR, Code) (<?php echo count($techFiles); ?>)
        </h3>
        <span class="badge" style="background:#1e3a8a; color:#bfdbfe; font-size:0.75rem; font-weight:700;">
          <i class="fa-solid fa-box-archive"></i> Archives & Formats
        </span>
      </div>
      <p style="color:#94a3b8; font-size:0.83rem; margin-bottom:1rem;">
        Dedicated repository for compressed archives (.zip, .rar, .7z, .tar.gz), databases (.sql), and programmatic project source files.
      </p>

      <?php if (empty($techFiles)): ?>
        <div style="background:rgba(0,0,0,0.25); border:1px dashed #3b82f6; border-radius:8px; padding:1.2rem; text-align:center; color:#94a3b8; font-size:0.88rem;">
          <i class="fa-solid fa-file-zipper" style="font-size:1.4rem; color:#60a5fa; margin-bottom:6px; display:block;"></i>
          No technical archive packages (.zip, .rar, code) uploaded to this assignment yet.
        </div>
      <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:10px;">
          <?php foreach ($techFiles as $file): 
            $ext = strtoupper(pathinfo($file['file_name'], PATHINFO_EXTENSION));
            $isInternal = !empty($file['is_internal']);
            $stage = $file['file_stage'] ?? 'complete';
          ?>
            <div style="display:flex; justify-content:space-between; align-items:center; background:#0b1120; border:1px solid #1e3a8a; padding:0.85rem 1.1rem; border-radius:var(--radius-sm); flex-wrap:wrap; gap:0.6rem;">
              <div style="display:flex; align-items:center; gap:10px;">
                <span class="badge" style="background:#1d4ed8; color:#ffffff; font-weight:800; font-size:0.75rem; letter-spacing:0.5px; padding:4px 8px;">
                  .<?php echo htmlspecialchars($ext ?: 'ZIP'); ?>
                </span>
                <div>
                  <strong style="color:#f8fafc; font-size:0.92rem;"><?php echo htmlspecialchars($file['file_name']); ?></strong>
                  <?php if ($stage === 'draft'): ?>
                    <span class="badge" style="background:rgba(234, 179, 8, 0.2); color:#fde047; border:1px solid #ca8a04; font-size:0.68rem; margin-left:6px;"><i class="fa-solid fa-clock-rotate-left"></i> Draft</span>
                  <?php else: ?>
                    <span class="badge" style="background:rgba(37, 99, 235, 0.25); color:#60a5fa; border:1px solid #2563eb; font-size:0.68rem; margin-left:6px;"><i class="fa-solid fa-circle-check"></i> Complete Solution</span>
                  <?php endif; ?>
                  <?php if ($isInternal): ?>
                    <span class="badge badge-warning" style="font-size:0.68rem; margin-left:4px;">Staff Only</span>
                  <?php else: ?>
                    <span class="badge badge-success" style="font-size:0.68rem; margin-left:4px;">Public / Student</span>
                  <?php endif; ?>
                  <small style="color:#94a3b8; display:block; margin-top:2px;">Uploaded by <?php echo htmlspecialchars($file['uploaded_by']); ?> &bull; <?php echo $file['upload_date']; ?></small>
                </div>
              </div>
              <div style="display:flex; gap:8px; align-items:center;">
                <button type="button" class="btn btn-outline btn-sm" onclick="toggleFileStage('<?php echo htmlspecialchars($file['file_id']); ?>', '<?php echo $stage === 'draft' ? 'complete' : 'draft'; ?>')" title="Switch between Draft and Complete">
                  <i class="fa-solid fa-repeat"></i> Set <?php echo $stage === 'draft' ? 'Complete' : 'Draft'; ?>
                </button>
                <a href="/<?php echo htmlspecialchars($file['path']); ?>" download class="btn btn-primary btn-sm" style="font-weight:700;">
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

    <!-- 2. Academic Documents & Materials Section (.pdf, .docx, .xlsx, images) -->
    <div class="table-card" style="padding:1.5rem; margin-bottom:1.5rem;">
      <h3 style="font-size:1.15rem; color:#fff; margin-bottom:1rem; display:flex; align-items:center; gap:8px;">
        <i class="fa-solid fa-file-lines" style="color:var(--success);"></i> Academic Documents & Materials (<?php echo count($docFiles); ?>)
      </h3>
      <?php if (empty($docFiles)): ?>
        <p style="color:var(--text-muted); font-size:0.9rem;">No documents or PDF deliverables attached yet.</p>
      <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:10px;">
          <?php foreach ($docFiles as $file): 
            $ext = strtolower($file['file_type'] ?? '');
            $icon = 'fa-file';
            if (in_array($ext, ['pdf'])) $icon = 'fa-file-pdf';
            elseif (in_array($ext, ['doc', 'docx'])) $icon = 'fa-file-word';
            elseif (in_array($ext, ['xls', 'xlsx', 'csv'])) $icon = 'fa-file-excel';
            elseif (in_array($ext, ['ppt', 'pptx'])) $icon = 'fa-file-powerpoint';
            elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'])) $icon = 'fa-file-image';
            $isInternal = !empty($file['is_internal']);
            $stage = $file['file_stage'] ?? 'complete';
          ?>
            <div style="display:flex; justify-content:space-between; align-items:center; background:#090d16; border:1px solid var(--portal-border); padding:0.8rem 1rem; border-radius:var(--radius-sm); flex-wrap:wrap; gap:0.5rem;">
              <div>
                <i class="fa-solid <?php echo $icon; ?>" style="color:var(--primary); margin-right:8px; font-size:1.1rem;"></i>
                <strong style="color:#fff;"><?php echo htmlspecialchars($file['file_name']); ?></strong>
                <?php if ($stage === 'draft'): ?>
                  <span class="badge" style="background:rgba(234, 179, 8, 0.2); color:#fde047; border:1px solid #ca8a04; font-size:0.68rem; margin-left:6px;"><i class="fa-solid fa-clock-rotate-left"></i> Draft</span>
                <?php else: ?>
                  <span class="badge" style="background:rgba(37, 99, 235, 0.25); color:#60a5fa; border:1px solid #2563eb; font-size:0.68rem; margin-left:6px;"><i class="fa-solid fa-circle-check"></i> Complete Solution</span>
                <?php endif; ?>
                <?php if ($isInternal): ?>
                  <span class="badge badge-warning" style="font-size:0.7rem; margin-left:4px;">Staff Only</span>
                <?php else: ?>
                  <span class="badge badge-success" style="font-size:0.7rem; margin-left:4px;">Public / Student</span>
                <?php endif; ?>
                <small style="color:var(--text-muted); display:block; margin-top:2px;">Uploaded by <?php echo htmlspecialchars($file['uploaded_by']); ?> &bull; <?php echo $file['upload_date']; ?></small>
              </div>
              <div style="display:flex; gap:8px; align-items:center;">
                <button type="button" class="btn btn-outline btn-sm" onclick="toggleFileStage('<?php echo htmlspecialchars($file['file_id']); ?>', '<?php echo $stage === 'draft' ? 'complete' : 'draft'; ?>')" title="Switch between Draft and Complete">
                  <i class="fa-solid fa-repeat"></i> Set <?php echo $stage === 'draft' ? 'Complete' : 'Draft'; ?>
                </button>
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

<!-- Modal 1: Reassign Allocator on Revision Requested -->
<div id="reassignAllocatorModal" class="modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.75); z-index:9999; align-items:center; justify-content:center; padding:1rem;">
  <div class="table-card" style="max-width:520px; width:100%; padding:2rem; background:#0d1527; border:1.5px solid var(--primary); border-radius:14px; box-shadow:0 10px 40px rgba(0,0,0,0.5);">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1rem;">
      <h3 style="color:#ffffff; margin:0; font-size:1.25rem; display:flex; align-items:center; gap:8px;">
        <i class="fa-solid fa-user-gear" style="color:var(--primary);"></i> Change Allocator for Revision
      </h3>
      <button type="button" onclick="document.getElementById('reassignAllocatorModal').style.display='none';" style="background:none; border:none; color:var(--text-muted); font-size:1.4rem; cursor:pointer; line-height:1;">&times;</button>
    </div>
    <p style="color:var(--text-muted); font-size:0.88rem; margin-bottom:1.5rem;">
      Reassign this revision to a specific allocator to supervise adjustments with the expert team.
    </p>
    <form method="POST">
      <input type="hidden" name="action" value="admin_reassign_allocator">
      <input type="hidden" name="assignment_id" value="<?php echo htmlspecialchars($id); ?>">
      <div class="form-group" style="margin-bottom:1.2rem;">
        <label style="color:#ffffff; font-weight:700; display:block; margin-bottom:6px;">Select Allocator *</label>
        <select name="allocator_id" class="form-control" required style="background:#090d16; color:#fff;">
          <option value="">-- Choose Allocator --</option>
          <?php foreach ($allocators as $all): ?>
            <option value="<?php echo htmlspecialchars($all['allocator_id']); ?>" <?php echo ($asm['allocator_id'] === $all['allocator_id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($all['name']); ?> (<?php echo htmlspecialchars($all['email']); ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="margin-bottom:1.5rem;">
        <label style="color:#ffffff; font-weight:700; display:block; margin-bottom:6px;">Revision Instructions / Allocator Remarks</label>
        <textarea name="admin_notes" class="form-control" rows="3" style="background:#090d16; color:#fff;" placeholder="Guidance for the allocator regarding this revision..."></textarea>
      </div>
      <div style="display:flex; justify-content:flex-end; gap:10px;">
        <button type="button" class="btn btn-outline" onclick="document.getElementById('reassignAllocatorModal').style.display='none';">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Assign Revision to Allocator</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal 2: Reject Refund Request -->
<div id="rejectRefundModal" class="modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.75); z-index:9999; align-items:center; justify-content:center; padding:1rem;">
  <div class="table-card" style="max-width:520px; width:100%; padding:2rem; background:#0d1527; border:1.5px solid #ef4444; border-radius:14px; box-shadow:0 10px 40px rgba(0,0,0,0.5);">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1rem;">
      <h3 style="color:#ffffff; margin:0; font-size:1.25rem; display:flex; align-items:center; gap:8px;">
        <i class="fa-solid fa-triangle-exclamation" style="color:#ef4444;"></i> Decline Refund Request
      </h3>
      <button type="button" onclick="document.getElementById('rejectRefundModal').style.display='none';" style="background:none; border:none; color:var(--text-muted); font-size:1.4rem; cursor:pointer; line-height:1;">&times;</button>
    </div>
    <p style="color:var(--text-muted); font-size:0.88rem; margin-bottom:1.5rem;">
      State the formal reason why this assignment does not qualify for a monetary refund. The order will be reverted to active status for further revision or delivery.
    </p>
    <form method="POST">
      <input type="hidden" name="action" value="admin_reject_refund">
      <input type="hidden" name="assignment_id" value="<?php echo htmlspecialchars($id); ?>">
      <div class="form-group" style="margin-bottom:1.5rem;">
        <label style="color:#ffffff; font-weight:700; display:block; margin-bottom:6px;">Administrative Reason *</label>
        <textarea name="reject_reason" class="form-control" rows="3" required style="background:#090d16; color:#fff;" placeholder="Explain why the claim does not qualify for refund as per platform guidelines..."></textarea>
      </div>
      <div style="display:flex; justify-content:flex-end; gap:10px;">
        <button type="button" class="btn btn-outline" onclick="document.getElementById('rejectRefundModal').style.display='none';">Cancel</button>
        <button type="submit" class="btn btn-danger"><i class="fa-solid fa-xmark"></i> Confirm Rejection</button>
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
}

function toggleFileStage(fileId, targetStage) {
  const fd = new FormData();
  fd.append('file_id', fileId);
  fd.append('file_stage', targetStage);

  fetch('/api.php?action=set_file_stage', {
    method: 'POST',
    body: fd
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      window.location.reload();
    } else {
      alert(data.message || 'Failed to update file stage.');
    }
  })
  .catch(err => alert('Error changing file stage.'));
}

</script>
</div>
</div>
</body>
</html>
