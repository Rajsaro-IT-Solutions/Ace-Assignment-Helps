<?php
$pageTitle = "Master Assignments Directory";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Admin');
$adminUser = Auth::currentUser();
require_once __DIR__ . '/../includes/helpers.php';
// Handle POST actions BEFORE sending any headers/HTML
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $assignment_id = trim($_POST['assignment_id'] ?? '');

    if ($action === 'delete_assignment') {
        if ($assignment_id) {
            $asm = DataStore::findOne('assignments', 'assignment_id', $assignment_id);
            if ($asm) {
                DataStore::update('assignments', 'assignment_id', $assignment_id, [
                    'status' => 'Deleted',
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                DataStore::update('payments', 'assignment_id', $assignment_id, [
                    'status' => 'Cancelled'
                ]);
                add_audit_log('Admin', $adminUser['id'], 'Delete Assignment', "Moved assignment $assignment_id to History (Soft Delete)");
                $_SESSION['flash_msg'] = "Assignment $assignment_id has been moved to History / Trash. It remains stored in History and can be viewed or restored.";
                $_SESSION['flash_type'] = 'warning';
            }
        }
        header('Location: /admin/assignments.php');
        echo "<script>window.location.href='/admin/assignments.php';</script>";
        exit;
    } elseif ($action === 'permanent_delete_assignment') {
        if ($assignment_id) {
            $deleted = DataStore::purgeAssignment($assignment_id);
            if ($deleted) {
                add_audit_log('Admin', $adminUser['id'], 'Permanent Delete Assignment', "Permanently wiped assignment $assignment_id from everywhere (database, history, files, payments)");
                $_SESSION['flash_msg'] = "Assignment $assignment_id has been PERMANENTLY deleted from everywhere. It has been completely removed from history, payments, files, and database.";
                $_SESSION['flash_type'] = 'danger';
            } else {
                $_SESSION['flash_msg'] = "Failed to permanently delete assignment $assignment_id.";
                $_SESSION['flash_type'] = 'danger';
            }
        }
        header('Location: /admin/assignments.php');
        echo "<script>window.location.href='/admin/assignments.php';</script>";
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
        header('Location: /admin/assignments.php');
        echo "<script>window.location.href='/admin/assignments.php';</script>";
        exit;
    }
}

// Also handle GET action for permanent delete (safe fallback)
if (isset($_GET['action']) && $_GET['action'] === 'permanent_delete_assignment' && !empty($_GET['assignment_id'])) {
    $targetId = trim($_GET['assignment_id']);
    DataStore::purgeAssignment($targetId);
    add_audit_log('Admin', $adminUser['id'], 'Permanent Delete Assignment', "Permanently wiped assignment $targetId from everywhere");
    $_SESSION['flash_msg'] = "Assignment $targetId has been PERMANENTLY deleted from everywhere (removed from history, payments, files, and database).";
    $_SESSION['flash_type'] = 'danger';
    header('Location: /admin/assignments.php');
    echo "<script>window.location.href='/admin/assignments.php';</script>";
    exit;
}

$msg = $_SESSION['flash_msg'] ?? '';
$msgType = $_SESSION['flash_type'] ?? 'info';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);

include __DIR__ . '/../includes/portal_header.php';
$assignments = DataStore::getCollection('assignments');
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h2 style="font-size:1.5rem; color:var(--text-main); margin-bottom:0.25rem;">
      <i class="fa-solid fa-folder-tree" style="color:var(--primary);"></i> Master Assignments Directory
    </h2>
    <p style="color:var(--text-muted); font-size:0.9rem; margin:0;">Complete view of student orders, workflow statuses, expert assignments, and dual deletion controls.</p>
  </div>
  <div style="display:flex; gap:10px; flex-wrap:wrap;">
    <a href="/admin/history.php" class="btn btn-outline btn-sm">
      <i class="fa-solid fa-clock-rotate-left"></i> Assignment History & Trash
    </a>
  </div>
</div>

<?php if ($msg): ?>
  <div class="badge badge-<?php echo $msgType; ?>" style="width:100%; padding:0.9rem; margin-bottom:1.5rem; text-align:center; font-size:0.95rem; display:block;">
    <i class="fa-solid fa-circle-info"></i> <?php echo htmlspecialchars($msg); ?>
  </div>
<?php endif; ?>

<div class="filter-bar">
  <input type="text" id="tableSearchInput" class="form-control" placeholder="Search by ID, title, student, university...">
  <select id="tableStatusFilter" class="form-control">
    <option value="">All Statuses</option>
    <option value="New">New</option>
    <option value="Pending Review">Pending Review</option>
    <option value="Waiting for Payment">Waiting for Payment</option>
    <option value="Confirmed">Confirmed</option>
    <option value="Allocated">Allocated</option>
    <option value="In Progress">In Progress</option>
    <option value="Quality Check">Quality Check</option>
    <option value="Completed">Completed</option>
    <option value="Delivered">Delivered</option>
    <option value="Deleted">Deleted / Archived</option>
  </select>
  <select id="tablePriorityFilter" class="form-control">
    <option value="">All Priorities</option>
    <option value="Normal">Normal</option>
    <option value="High">High</option>
    <option value="Urgent">Urgent</option>
  </select>
</div>

<div class="table-card">
  <div class="table-header">
    <h3 style="font-size:1.1rem; color:var(--text-main);"><i class="fa-solid fa-folder-tree" style="color:var(--primary);"></i> Global Assignments Master List (<?php echo count($assignments); ?>)</h3>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Assignment ID</th>
          <th>Student Details</th>
          <th>Subject & Type</th>
          <th>Allocated Expert</th>
          <th>Country</th>
          <th>SLA Deadline</th>
          <th>Status</th>
          <th>Price</th>
          <th style="text-align:center; min-width:185px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($assignments as $asm): 
          $student = DataStore::findOne('students', 'student_id', $asm['student_id']);
          $expert = DataStore::findOne('experts', 'expert_id', $asm['expert_id']);
          $sla = get_sla_status($asm['deadline']);
        ?>
          <tr data-status="<?php echo htmlspecialchars($asm['status']); ?>" data-priority="<?php echo htmlspecialchars($asm['priority']); ?>">
            <td><strong style="color:var(--secondary); font-family:monospace;"><?php echo htmlspecialchars($asm['assignment_id']); ?></strong></td>
            <td>
              <strong style="color:var(--text-main);"><?php echo htmlspecialchars($student['name'] ?? 'Student'); ?></strong><br>
              <small style="color:var(--text-muted);"><?php echo htmlspecialchars($student['email'] ?? ''); ?></small>
            </td>
            <td>
              <div style="font-weight:700; color:var(--text-main);"><?php echo htmlspecialchars($asm['subject']); ?></div>
              <small style="color:var(--text-muted);"><?php echo $asm['word_count']; ?> words &bull; <?php echo htmlspecialchars($asm['assignment_type']); ?></small>
            </td>
            <td><?php echo htmlspecialchars($expert['name'] ?? 'Unassigned'); ?></td>
            <td><span class="badge badge-info"><?php echo htmlspecialchars($asm['country']); ?></span></td>
            <td><span class="badge <?php echo $sla['badge_class']; ?>"><i class="fa-solid fa-clock"></i> <?php echo $sla['label']; ?></span></td>
            <td><span class="badge <?php echo get_status_badge_class($asm['status']); ?>"><?php echo htmlspecialchars($asm['status']); ?></span></td>
            <td><strong><?php echo format_currency_amount($asm['final_price'], $asm['currency'] ?? 'USD'); ?></strong></td>
            <td style="text-align:center;">
              <div style="display:inline-flex; gap:5px; align-items:center; justify-content:center;">
                <a href="/admin/assignment-detail.php?id=<?php echo urlencode($asm['assignment_id']); ?>" class="btn btn-primary btn-sm" title="View & Edit Assignment">
                  Control &rarr;
                </a>
                <?php if ($asm['status'] === 'Deleted'): ?>
                  <span class="badge badge-warning" style="font-size:0.75rem; padding:4px 6px;" title="Archived in History"><i class="fa-solid fa-clock-rotate-left"></i> In History</span>
                  <!-- Restore Action -->
                  <form method="POST" style="display:inline;" onsubmit="return confirmRestore('<?php echo htmlspecialchars($asm['assignment_id'], ENT_QUOTES); ?>');">
                    <input type="hidden" name="action" value="restore_assignment">
                    <input type="hidden" name="assignment_id" value="<?php echo htmlspecialchars($asm['assignment_id']); ?>">
                    <button type="submit" class="btn btn-sm" style="background:#10b981; color:#fff; border:none; padding:5px 8px;" title="Restore to Active">
                      <i class="fa-solid fa-rotate-left"></i>
                    </button>
                  </form>
                  <!-- Permanent Delete Action (Modal Trigger) -->
                  <button type="button" class="btn btn-sm" style="background:#b91c1c; color:#fff; border:none; padding:5px 8px;" onclick="openPermDeleteModal('<?php echo htmlspecialchars($asm['assignment_id'], ENT_QUOTES); ?>')" title="Permanently Delete from Everywhere (Wipe)">
                    <i class="fa-solid fa-trash-can"></i>
                  </button>
                <?php else: ?>
                  <!-- Soft Delete: Move to History -->
                  <form method="POST" style="display:inline;" onsubmit="return confirmSoftDelete('<?php echo htmlspecialchars($asm['assignment_id'], ENT_QUOTES); ?>');">
                    <input type="hidden" name="action" value="delete_assignment">
                    <input type="hidden" name="assignment_id" value="<?php echo htmlspecialchars($asm['assignment_id']); ?>">
                    <button type="submit" class="btn btn-sm" style="background:#f59e0b; color:#fff; border:none; padding:5px 8px;" title="Delete & Move to History">
                      <i class="fa-solid fa-box-archive"></i>
                    </button>
                  </form>
                  <!-- Permanent Delete Action (Modal Trigger) -->
                  <button type="button" class="btn btn-sm" style="background:#b91c1c; color:#fff; border:none; padding:5px 8px;" onclick="openPermDeleteModal('<?php echo htmlspecialchars($asm['assignment_id'], ENT_QUOTES); ?>')" title="Permanently Delete from Everywhere (Wipe)">
                    <i class="fa-solid fa-trash-can"></i>
                  </button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
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

    <form method="POST" id="permDeleteForm" action="/admin/assignments.php">
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
function confirmSoftDelete(asmId) {
  return confirm("Move assignment " + asmId + " to History? (It will remain in History and can be restored)");
}
function confirmRestore(asmId) {
  return confirm("Restore assignment " + asmId + " from History back to Active?");
}
</script>
<script src="/assets/js/portal.js"></script>
</div>
</div>
</body>
</html>
