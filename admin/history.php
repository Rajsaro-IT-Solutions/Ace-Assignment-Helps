<?php
$pageTitle = "Assignment History & Trash";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Admin');
$adminUser = Auth::currentUser();
require_once __DIR__ . '/../includes/helpers.php';
// Handle POST actions BEFORE sending any headers/HTML
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $assignment_id = trim($_POST['assignment_id'] ?? '');

    if ($action === 'permanent_delete_assignment') {
        if ($assignment_id) {
            $deleted = DataStore::purgeAssignment($assignment_id);
            if ($deleted) {
                add_audit_log('Admin', $adminUser['id'], 'Permanent Delete Assignment', "Permanently wiped assignment $assignment_id from History and entire system");
                $_SESSION['flash_msg'] = "Assignment $assignment_id has been PERMANENTLY deleted from everywhere (removed from history, payments, files, and database).";
                $_SESSION['flash_type'] = 'danger';
            } else {
                $_SESSION['flash_msg'] = "Failed to permanently delete assignment $assignment_id.";
                $_SESSION['flash_type'] = 'danger';
            }
        }
        header('Location: /admin/history.php');
        echo "<script>window.location.href='/admin/history.php';</script>";
        exit;
    } elseif ($action === 'restore_assignment') {
        if ($assignment_id) {
            $asm = DataStore::findOne('assignments', 'assignment_id', $assignment_id);
            if ($asm) {
                DataStore::update('assignments', 'assignment_id', $assignment_id, [
                    'status' => 'Pending Review',
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                add_audit_log('Admin', $adminUser['id'], 'Restore Assignment', "Restored assignment $assignment_id from History to Pending Review");
                $_SESSION['flash_msg'] = "Assignment $assignment_id has been restored from History to active status (Pending Review).";
                $_SESSION['flash_type'] = 'success';
            }
        }
        header('Location: /admin/history.php');
        echo "<script>window.location.href='/admin/history.php';</script>";
        exit;
    }
}

// GET action fallback for permanent delete
if (isset($_GET['action']) && $_GET['action'] === 'permanent_delete_assignment' && !empty($_GET['assignment_id'])) {
    $targetId = trim($_GET['assignment_id']);
    DataStore::purgeAssignment($targetId);
    add_audit_log('Admin', $adminUser['id'], 'Permanent Delete Assignment', "Permanently wiped assignment $targetId from History and entire system");
    $_SESSION['flash_msg'] = "Assignment $targetId has been PERMANENTLY deleted from everywhere.";
    $_SESSION['flash_type'] = 'danger';
    header('Location: /admin/history.php');
    echo "<script>window.location.href='/admin/history.php';</script>";
    exit;
}

$msg = $_SESSION['flash_msg'] ?? '';
$msgType = $_SESSION['flash_type'] ?? 'info';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);

include __DIR__ . '/../includes/portal_header.php';

// History includes: Deleted, Completed, Delivered, Cancelled, Refunded
$historyAssignments = DataStore::filter('assignments', function($a) {
    return in_array($a['status'] ?? '', ['Deleted', 'Completed', 'Delivered', 'Cancelled', 'Refunded']);
});

// Sort descending by updated_at or created_at
usort($historyAssignments, function($a, $b) {
    $tA = strtotime($a['updated_at'] ?? ($a['created_at'] ?? '0'));
    $tB = strtotime($b['updated_at'] ?? ($b['created_at'] ?? '0'));
    return $tB - $tA;
});

$totalHistory = count($historyAssignments);
$deletedCount = count(array_filter($historyAssignments, function($a) {
    return ($a['status'] ?? '') === 'Deleted';
}));
$completedCount = count(array_filter($historyAssignments, function($a) {
    return in_array($a['status'] ?? '', ['Completed', 'Delivered']);
}));
$cancelledCount = count(array_filter($historyAssignments, function($a) {
    return in_array($a['status'] ?? '', ['Cancelled', 'Refunded']);
}));
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h2 style="font-size:1.5rem; color:var(--text-main); margin-bottom:0.25rem;">
      <i class="fa-solid fa-clock-rotate-left" style="color:var(--primary);"></i> Assignment History & Archives
    </h2>
    <p style="color:var(--text-muted); font-size:0.9rem; margin:0;">
      View all soft-deleted assignments, completed student submissions, and manage permanent purging.
    </p>
  </div>
  <div style="display:flex; gap:10px;">
    <a href="/admin/assignments.php" class="btn btn-primary btn-sm">
      <i class="fa-solid fa-folder-tree"></i> Master Assignments Directory
    </a>
  </div>
</div>

<?php if ($msg): ?>
  <div class="badge badge-<?php echo $msgType; ?>" style="width:100%; padding:0.9rem; margin-bottom:1.5rem; text-align:center; font-size:0.95rem; display:block;">
    <i class="fa-solid fa-circle-info"></i> <?php echo htmlspecialchars($msg); ?>
  </div>
<?php endif; ?>

<!-- Metrics Grid -->
<div class="metrics-grid" style="margin-bottom:1.8rem;">
  <div class="metric-card">
    <div class="metric-icon icon-blue"><i class="fa-solid fa-box-archive"></i></div>
    <div class="metric-info">
      <div class="m-val"><?php echo $totalHistory; ?></div>
      <div class="m-lbl">Total Archived</div>
    </div>
  </div>
  <div class="metric-card">
    <div class="metric-icon icon-amber"><i class="fa-solid fa-trash-can-arrow-up"></i></div>
    <div class="metric-info">
      <div class="m-val" style="color:#f59e0b;"><?php echo $deletedCount; ?></div>
      <div class="m-lbl">Soft-Deleted in Trash</div>
    </div>
  </div>
  <div class="metric-card">
    <div class="metric-icon icon-green"><i class="fa-solid fa-circle-check"></i></div>
    <div class="metric-info">
      <div class="m-val" style="color:#10b981;"><?php echo $completedCount; ?></div>
      <div class="m-lbl">Completed & Delivered</div>
    </div>
  </div>
  <div class="metric-card">
    <div class="metric-icon icon-red"><i class="fa-solid fa-ban"></i></div>
    <div class="metric-info">
      <div class="m-val" style="color:#ef4444;"><?php echo $cancelledCount; ?></div>
      <div class="m-lbl">Cancelled / Refunded</div>
    </div>
  </div>
</div>

<div class="filter-bar">
  <input type="text" id="tableSearchInput" class="form-control" placeholder="Search by ID, title, student email...">
  <select id="tableStatusFilter" class="form-control">
    <option value="">All History Statuses</option>
    <option value="Deleted">Deleted / Soft-Deleted</option>
    <option value="Completed">Completed</option>
    <option value="Delivered">Delivered</option>
    <option value="Cancelled">Cancelled</option>
    <option value="Refunded">Refunded</option>
  </select>
</div>

<div class="table-card">
  <div class="table-header">
    <h3 style="font-size:1.1rem; color:var(--text-main);"><i class="fa-solid fa-clock-rotate-left" style="color:var(--primary);"></i> History & Trash Records (<?php echo count($historyAssignments); ?>)</h3>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Assignment ID</th>
          <th>Student Details</th>
          <th>Subject & Type</th>
          <th>Status in History</th>
          <th>Price</th>
          <th>Last Activity</th>
          <th style="text-align:center; min-width:185px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($historyAssignments)): ?>
          <tr>
            <td colspan="7" style="text-align:center; padding:2rem; color:var(--text-muted);">
              <i class="fa-solid fa-box-open" style="font-size:2rem; display:block; margin-bottom:0.5rem; opacity:0.5;"></i>
              No historical or deleted assignments found in archives.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($historyAssignments as $asm): 
            $student = DataStore::findOne('students', 'student_id', $asm['student_id']);
            $isDeleted = ($asm['status'] ?? '') === 'Deleted';
          ?>
            <tr data-status="<?php echo htmlspecialchars($asm['status']); ?>">
              <td><strong style="color:var(--secondary); font-family:monospace;"><?php echo htmlspecialchars($asm['assignment_id']); ?></strong></td>
              <td>
                <strong style="color:var(--text-main);"><?php echo htmlspecialchars($student['name'] ?? 'Student'); ?></strong><br>
                <small style="color:var(--text-muted);"><?php echo htmlspecialchars($student['email'] ?? ''); ?></small>
              </td>
              <td>
                <div style="font-weight:700; color:var(--text-main);"><?php echo htmlspecialchars($asm['subject']); ?></div>
                <small style="color:var(--text-muted);"><?php echo $asm['word_count']; ?> words &bull; <?php echo htmlspecialchars($asm['assignment_type']); ?></small>
              </td>
              <td>
                <?php if ($isDeleted): ?>
                  <span class="badge badge-danger" style="background:#dc2626; color:#fff;"><i class="fa-solid fa-trash-can"></i> Soft-Deleted</span>
                <?php else: ?>
                  <span class="badge <?php echo get_status_badge_class($asm['status']); ?>"><?php echo htmlspecialchars($asm['status']); ?></span>
                <?php endif; ?>
              </td>
              <td><strong><?php echo format_currency_amount($asm['final_price'], $asm['currency'] ?? 'USD'); ?></strong></td>
              <td><small style="color:var(--text-muted);"><?php echo date('M d, Y H:i', strtotime($asm['updated_at'] ?? ($asm['created_at'] ?? 'now'))); ?></small></td>
              <td style="text-align:center;">
                <div style="display:inline-flex; gap:6px; align-items:center; justify-content:center;">
                  <a href="/admin/assignment-detail.php?id=<?php echo urlencode($asm['assignment_id']); ?>" class="btn btn-primary btn-sm" title="View & Edit Details">
                    Control &rarr;
                  </a>

                  <?php if ($isDeleted): ?>
                    <!-- Restore from Trash to Active -->
                    <form method="POST" style="display:inline;" onsubmit="return confirmRestore('<?php echo htmlspecialchars($asm['assignment_id'], ENT_QUOTES); ?>');">
                      <input type="hidden" name="action" value="restore_assignment">
                      <input type="hidden" name="assignment_id" value="<?php echo htmlspecialchars($asm['assignment_id']); ?>">
                      <button type="submit" class="btn btn-sm" style="background:#10b981; color:#fff; border:none; padding:5px 8px;" title="Restore to Active">
                        <i class="fa-solid fa-rotate-left"></i> Restore
                      </button>
                    </form>
                  <?php endif; ?>

                  <!-- Permanent Delete / Deep Wipe (Modal Trigger) -->
                  <button type="button" class="btn btn-sm" style="background:#b91c1c; color:#fff; border:none; padding:5px 8px;" onclick="openPermDeleteModal('<?php echo htmlspecialchars($asm['assignment_id'], ENT_QUOTES); ?>')" title="Permanently Wipe from Everywhere">
                    <i class="fa-solid fa-trash-can"></i> Wipe
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
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
      <strong id="permModalAsmId" style="font-size:1.2rem; color:#ef4444; font-family:monospace;"></strong>
    </div>

    <div style="font-size:0.88rem; color:#b91c1c; background:#fee2e2; border-radius:6px; padding:0.85rem; margin-bottom:1.5rem; line-height:1.45;">
      <i class="fa-solid fa-circle-exclamation"></i> <strong>IRREVERSIBLE ACTION:</strong> This will completely remove this assignment from the database, uploaded solution/brief files, payment logs, allocation records, and History. It CANNOT be recovered!
    </div>

    <form method="POST" id="permDeleteForm" action="/admin/history.php">
      <input type="hidden" name="action" value="permanent_delete_assignment">
      <input type="hidden" name="assignment_id" id="permModalInputId" value="">
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
  document.getElementById('permModalAsmId').innerText = asmId;
  document.getElementById('permModalInputId').value = asmId;
  document.getElementById('permanentDeleteModal').style.display = 'flex';
}
function closePermDeleteModal() {
  document.getElementById('permanentDeleteModal').style.display = 'none';
}
function confirmRestore(asmId) {
  return confirm("Restore assignment " + asmId + " from History back to Active (Pending Review)?");
}
</script>
<script src="/assets/js/portal.js"></script>
</div>
</div>
</body>
</html>
