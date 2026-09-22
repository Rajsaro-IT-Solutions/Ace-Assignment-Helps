<?php
$pageTitle = "Allocator Assignment Control";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole(['Allocator', 'Admin']);
$user = Auth::currentUser();
require_once __DIR__ . '/../includes/helpers.php';

$id = $_GET['id'] ?? '';
$asm = DataStore::findOne('assignments', 'assignment_id', $id);

if (!$asm) {
    include __DIR__ . '/../includes/portal_header.php';
    echo "<div class='badge badge-danger'>Assignment not found.</div>";
    echo "</div></div></body></html>";
    exit;
}

// Handle Allocator QA Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['allocator_qa_action'])) {
        $qaAction = $_POST['allocator_qa_action'];
        if ($qaAction === 'approve') {
            DataStore::update('assignments', 'assignment_id', $id, [
                'status' => 'Pending Admin Approval',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            add_audit_log('Allocator', $user['id'], 'Approve QA', "Allocator {$user['name']} gave QA Approval for order $id and forwarded to Admin for final release.");
            add_notification('Admin', '', "QA Approved - $id", "Allocator {$user['name']} has QA-approved the solution for order $id. Final Admin sign-off is required to release to student.", 'info', "/admin/assignment-detail.php?id=$id");
            if (!empty($asm['expert_id'])) {
                add_notification('Expert', $asm['expert_id'], "QA Passed - $id", "Your submitted solution for order $id has passed QA review and is awaiting final administrative sign-off.", 'success', "/expert/assignment-detail.php?id=$id");
            }
            $_SESSION['flash_msg'] = "QA Approval granted! The assignment has been forwarded for Final Admin Approval & Release.";
            $_SESSION['flash_type'] = 'success';
        } elseif ($qaAction === 'revision') {
            $notes = trim($_POST['revision_notes'] ?? '');
            DataStore::update('assignments', 'assignment_id', $id, [
                'status' => 'Revision Requested',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            if (!empty($notes)) {
                DataStore::insert('notes', [
                    'note_id' => 'NOTE-' . rand(100, 999),
                    'assignment_id' => $id,
                    'user_id' => $user['id'],
                    'user_role' => $user['role'],
                    'user_name' => $user['name'],
                    'message' => 'QA Revision Requested: ' . $notes,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
            add_audit_log('Allocator', $user['id'], 'Request Revision', "Allocator {$user['name']} requested revisions on order $id");
            if (!empty($asm['expert_id'])) {
                add_notification('Expert', $asm['expert_id'], "Revision Requested - $id", "Allocator {$user['name']} requested revisions on order $id: " . substr($notes, 0, 80), 'warning', "/expert/assignment-detail.php?id=$id");
            }
            $_SESSION['flash_msg'] = "Revision request sent to expert. Order status set to 'Revision Requested'.";
            $_SESSION['flash_type'] = 'warning';
        }
        header("Location: /allocator/assignment-detail.php?id=" . urlencode($id));
        exit;
    }

    if (isset($_POST['reassign_expert_revision'])) {
        $newExpertId = trim($_POST['expert_id'] ?? '');
        $revNotes = trim($_POST['revision_instructions'] ?? '');
        if ($newExpertId) {
            $expertObj = DataStore::findOne('experts', 'expert_id', $newExpertId);
            $expertName = $expertObj['name'] ?? $newExpertId;

            DataStore::update('assignments', 'assignment_id', $id, [
                'expert_id' => $newExpertId,
                'status' => 'In Progress',
                'revision_notes' => $revNotes,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            if (!empty($revNotes)) {
                DataStore::insert('notes', [
                    'note_id' => 'NOTE-' . rand(100, 999),
                    'assignment_id' => $id,
                    'user_id' => $user['id'],
                    'user_role' => 'Allocator',
                    'user_name' => $user['name'],
                    'message' => "Revision Assigned to Expert {$expertName}: " . $revNotes,
                    'visibility' => 'Internal',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }

            add_audit_log('Allocator', $user['id'], 'Reassign Expert for Revision', "Allocated revision for order $id to Expert $expertName");
            add_notification('Expert', $newExpertId, "Revision Order Assigned - $id", "Allocator {$user['name']} has assigned you order $id for revisions: " . substr($revNotes, 0, 80), 'warning', "/expert/assignment-detail.php?id=$id");

            $_SESSION['flash_msg'] = "Expert reassigned successfully for revision. Order status set to 'In Progress'.";
            $_SESSION['flash_type'] = 'success';
        }
        header("Location: /allocator/assignment-detail.php?id=" . urlencode($id));
        exit;
    }
}

// Reload assignment data after potential updates
$asm = DataStore::findOne('assignments', 'assignment_id', $id);
$student = DataStore::findOne('students', 'student_id', $asm['student_id']);
$experts = DataStore::getCollection('experts');
$sla = get_sla_status($asm['deadline']);

$internalNotes = DataStore::filter('notes', function($n) use ($id) {
    return isset($n['assignment_id']) && $n['assignment_id'] === $id;
});

$files = DataStore::filter('files', function($f) use ($id) {
    return isset($f['assignment_id']) && $f['assignment_id'] === $id;
});

$techFiles = array_values(array_filter($files, function($f) {
    return is_tech_file($f);
}));

$solutionFiles = array_values(array_filter($files, function($f) {
    return is_solution_file($f);
}));

$briefFiles = array_values(array_filter($files, function($f) {
    return !is_solution_file($f);
}));

$docSolutionFiles = array_values(array_filter($solutionFiles, function($f) {
    return !is_tech_file($f);
}));

$docBriefFiles = array_values(array_filter($briefFiles, function($f) {
    return !is_tech_file($f);
}));

include __DIR__ . '/../includes/portal_header.php';

$flashMsg = $_SESSION['flash_msg'] ?? '';
$flashType = $_SESSION['flash_type'] ?? 'info';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
?>

<?php if ($flashMsg): ?>
  <div class="badge badge-<?php echo $flashType; ?>" style="width:100%; padding:0.9rem; margin-bottom:1.5rem; text-align:center; font-size:0.95rem; display:block;">
    <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($flashMsg); ?>
  </div>
<?php endif; ?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <a href="/allocator/index.php" style="color:var(--text-muted); font-size:0.9rem; text-decoration:none;"><i class="fa-solid fa-arrow-left"></i> Back to Command Center</a>
    <h2 style="font-size:1.8rem; margin-top:0.3rem; color:var(--text-main);"><?php echo htmlspecialchars($asm['title']); ?></h2>
    <div style="display:flex; gap:10px; align-items:center; margin-top:0.4rem; flex-wrap:wrap;">
      <span class="badge badge-info"><?php echo htmlspecialchars($asm['assignment_id']); ?></span>
      <span class="badge <?php echo get_status_badge_class($asm['status']); ?>"><?php echo htmlspecialchars($asm['status']); ?></span>
      <span class="badge <?php echo $sla['badge_class']; ?>"><i class="fa-solid fa-clock"></i> <?php echo $sla['label']; ?></span>
    </div>
  </div>
</div>

<!-- Privacy Protection Notice -->
<div class="privacy-banner" style="margin-bottom:1.5rem;">
  <i class="fa-solid fa-user-shield"></i>
  <div>
    <strong>Strict Privacy Masking Active:</strong> Student Email, Phone, Address, and Payment information are hidden from Allocator view.
  </div>
</div>

<!-- Revision Requested Banner & Re-assign Expert Action Card -->
<?php if ($asm['status'] === 'Revision Requested'): ?>
  <div style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.12) 0%, rgba(217, 119, 6, 0.12) 100%); border: 1.5px solid #f59e0b; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem;">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem;">
      <div style="max-width:750px;">
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
          <span class="badge badge-warning" style="font-weight:800; font-size:0.85rem; padding:4px 10px;">
            <i class="fa-solid fa-rotate-left"></i> Revision Requested
          </span>
          <span style="color:#b45309; font-size:0.85rem; font-weight:700;">Action Required: Change or Re-assign Subject Expert</span>
        </div>
        <h3 style="color:var(--text-main); margin:0 0 6px 0; font-size:1.25rem;">Revision in Progress</h3>
        <?php if (!empty($asm['revision_notes'])): ?>
          <div style="background:#fff; border-left:4px solid #f59e0b; padding:10px 14px; border-radius:6px; margin:10px 0; font-size:0.9rem; color:var(--text-main);">
            <strong>Revision Notes / Student Feedback:</strong><br>
            <?php echo nl2br(htmlspecialchars($asm['revision_notes'])); ?>
          </div>
        <?php else: ?>
          <p style="color:var(--text-muted); margin:0 0 1rem 0; font-size:0.9rem; line-height:1.5;">
            The student or admin has requested revisions on this assignment. You can re-assign this order to a new expert or provide updated revision instructions to the current specialist.
          </p>
        <?php endif; ?>
      </div>

      <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-top:0.5rem;">
        <button type="button" class="btn btn-warning" style="font-weight:700; padding:0.8rem 1.4rem;" onclick="const b = document.getElementById('allocatorReassignExpertBox'); b.style.display = (b.style.display === 'block' ? 'none' : 'block');">
          <i class="fa-solid fa-user-pen"></i> Change / Re-assign Expert
        </button>
      </div>
    </div>

    <!-- Collapsible Re-assign Expert Box -->
    <div id="allocatorReassignExpertBox" style="display:none; margin-top:1.2rem; background:#fff; border:1px solid #f59e0b; border-radius:8px; padding:1.2rem;">
      <h4 style="color:#b45309; margin-bottom:0.8rem; font-size:1rem;"><i class="fa-solid fa-user-gear"></i> Re-allocate to New or Current Expert for Revision</h4>
      <form method="POST">
        <input type="hidden" name="reassign_expert_revision" value="1">
        <div class="grid-2" style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:0.8rem;">
          <div class="form-group">
            <label style="font-size:0.85rem; font-weight:600;">Select Expert *</label>
            <select name="expert_id" class="form-control" required>
              <option value="">-- Choose Subject Specialist --</option>
              <?php foreach ($experts as $exp): ?>
                <option value="<?php echo htmlspecialchars($exp['expert_id']); ?>" <?php echo ($asm['expert_id'] === $exp['expert_id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($exp['name']); ?> (Rating: <?php echo $exp['rating']; ?> | <?php echo $exp['status']; ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label style="font-size:0.85rem; font-weight:600;">Current Allocated Expert</label>
            <input type="text" class="form-control" value="<?php 
              $curExp = DataStore::findOne('experts', 'expert_id', $asm['expert_id']);
              echo htmlspecialchars($curExp['name'] ?? ($asm['expert_id'] ?: 'None')); 
            ?>" readonly style="background:#f8fafc;">
          </div>
        </div>
        <div class="form-group" style="margin-bottom:0.8rem;">
          <label style="font-size:0.85rem; font-weight:600;">Specific Revision Brief / Instructions for Expert *</label>
          <textarea name="revision_instructions" class="form-control" rows="3" placeholder="Provide detailed pointers on what needs to be reworked, added, or corrected..." required><?php echo htmlspecialchars($asm['revision_notes'] ?? ''); ?></textarea>
        </div>
        <div style="display:flex; gap:8px;">
          <button type="submit" class="btn btn-warning btn-sm" style="font-weight:700;"><i class="fa-solid fa-check"></i> Reassign & Set to In Progress</button>
          <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('allocatorReassignExpertBox').style.display='none';">Cancel</button>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<!-- Allocator QA Review Gate Card -->
<?php if ($asm['status'] === 'Quality Check'): ?>
  <div style="background: linear-gradient(135deg, rgba(147, 51, 234, 0.12) 0%, rgba(79, 70, 229, 0.12) 100%); border: 1.5px solid #a855f7; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem;">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem;">
      <div>
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
          <span class="badge badge-purple" style="font-weight:800; font-size:0.85rem; padding:4px 10px;">
            <i class="fa-solid fa-microscope"></i> Allocator QA Review Gate
          </span>
          <span style="color:#7e22ce; font-size:0.85rem; font-weight:700;">Expert Solution Uploaded — Your Quality Approval Required</span>
        </div>
        <h3 style="color:var(--text-main); margin:0 0 6px 0; font-size:1.25rem;">Quality Verification & Admin Forwarding</h3>
        <p style="color:var(--text-muted); margin:0 0 1rem 0; font-size:0.9rem; max-width:700px; line-height:1.5;">
          The allocated specialist has submitted their solution deliverables. Inspect the solution files and Turnitin report below. Approve QA to forward this order for <strong>Final Admin Approval & Release</strong>, or request revisions.
        </p>

        <?php if (!empty($solutionFiles)): ?>
          <div style="background:#ffffff; border:1px solid var(--portal-border); border-radius:8px; padding:0.8rem 1rem; margin-bottom:1rem;">
            <strong style="font-size:0.85rem; color:var(--text-main); display:block; margin-bottom:6px;"><i class="fa-solid fa-file-circle-check" style="color:var(--primary);"></i> Submitted Deliverables for Review:</strong>
            <div style="display:flex; flex-direction:column; gap:6px;">
              <?php foreach ($solutionFiles as $sf): ?>
                <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.88rem;">
                  <span><i class="fa-solid fa-file-lines" style="color:var(--primary); margin-right:6px;"></i> <?php echo htmlspecialchars($sf['file_name']); ?> <small style="color:var(--text-muted);">(<?php echo htmlspecialchars($sf['uploaded_by']); ?>)</small></span>
                  <a href="/<?php echo htmlspecialchars($sf['path']); ?>" download class="btn btn-outline btn-sm" style="padding:2px 8px; font-size:0.75rem;"><i class="fa-solid fa-download"></i> Inspect</a>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-top:0.5rem;">
        <form method="POST" style="margin:0;">
          <input type="hidden" name="allocator_qa_action" value="approve">
          <button type="submit" class="btn btn-success btn-lg" style="font-weight:800; padding:0.8rem 1.6rem; box-shadow:0 4px 15px rgba(16,185,129,0.4); border:none; cursor:pointer;" onclick="return confirm('Confirm QA Approval? This will forward the order to Admin for final approval & release to student.');">
            <i class="fa-solid fa-circle-check"></i> Approve QA & Forward to Admin
          </button>
        </form>
        <button type="button" class="btn btn-warning" style="font-weight:700; padding:0.8rem 1.25rem;" onclick="document.getElementById('allocatorRevisionBox').style.display='block';">
          <i class="fa-solid fa-rotate-left"></i> Request Expert Revision
        </button>
      </div>
    </div>

    <!-- Collapsible Revision Request Box -->
    <div id="allocatorRevisionBox" style="display:none; margin-top:1.2rem; background:#fff; border:1px solid #f59e0b; border-radius:8px; padding:1.2rem;">
      <h4 style="color:#b45309; margin-bottom:0.6rem; font-size:1rem;"><i class="fa-solid fa-triangle-exclamation"></i> Send Revision Request to Expert</h4>
      <form method="POST">
        <input type="hidden" name="allocator_qa_action" value="revision">
        <div class="form-group" style="margin-bottom:0.8rem;">
          <label style="font-size:0.85rem; font-weight:600;">Specify Exact Adjustments Required *</label>
          <textarea name="revision_notes" class="form-control" rows="3" placeholder="Detail the necessary corrections, missing references, formatting improvements, or rubrics to fulfill..." required></textarea>
        </div>
        <div style="display:flex; gap:8px;">
          <button type="submit" class="btn btn-warning btn-sm" style="font-weight:700;"><i class="fa-solid fa-paper-plane"></i> Submit Revision Request</button>
          <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('allocatorRevisionBox').style.display='none';">Cancel</button>
        </div>
      </form>
    </div>
  </div>
<?php elseif ($asm['status'] === 'Pending Admin Approval'): ?>
  <div style="background: linear-gradient(135deg, rgba(6, 182, 212, 0.12) 0%, rgba(59, 130, 246, 0.12) 100%); border: 1.5px solid #06b6d4; border-radius: 12px; padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
    <div style="display:flex; align-items:center; gap:12px;">
      <div style="width:42px; height:42px; border-radius:50%; background:#06b6d4; color:#fff; display:flex; align-items:center; justify-content:center; font-size:1.2rem;">
        <i class="fa-solid fa-check-double"></i>
      </div>
      <div>
        <div style="display:flex; align-items:center; gap:8px;">
          <span class="badge badge-cyan" style="font-weight:800; font-size:0.8rem;">QA Approved by Allocator</span>
          <strong style="color:var(--text-main); font-size:0.95rem;">Awaiting Final Administrative Sign-Off</strong>
        </div>
        <p style="color:var(--text-muted); font-size:0.85rem; margin:2px 0 0 0;">
          You have QA-approved this order. The Admin must now grant final sign-off to officially mark the order Completed and release solution files to the student.
        </p>
      </div>
    </div>
    <form method="POST" style="margin:0;">
      <input type="hidden" name="allocator_qa_action" value="revision">
      <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('allocatorRevisionBoxReopen').style.display='block';">
        <i class="fa-solid fa-rotate-left"></i> Reopen for Revision
      </button>
      <div id="allocatorRevisionBoxReopen" style="display:none; margin-top:0.8rem; background:#fff; border:1px solid #f59e0b; border-radius:8px; padding:1rem; position:absolute; right:20px; z-index:20; max-width:400px; box-shadow:0 10px 25px rgba(0,0,0,0.15);">
        <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:4px;">Revision Reason:</label>
        <textarea name="revision_notes" class="form-control" rows="2" placeholder="Explain why reopening..." required></textarea>
        <div style="display:flex; gap:6px; margin-top:6px;">
          <button type="submit" class="btn btn-warning btn-sm">Confirm Revision</button>
          <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('allocatorRevisionBoxReopen').style.display='none';">Cancel</button>
        </div>
      </div>
    </form>
  </div>
<?php elseif (in_array($asm['status'], ['Completed', 'Delivered'])): ?>
  <div style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.1) 100%); border: 1.5px solid #10b981; border-radius: 12px; padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; display:flex; align-items:center; gap:12px;">
    <div style="width:42px; height:42px; border-radius:50%; background:#10b981; color:#fff; display:flex; align-items:center; justify-content:center; font-size:1.2rem;">
      <i class="fa-solid fa-circle-check"></i>
    </div>
    <div>
      <div style="display:flex; align-items:center; gap:8px;">
        <span class="badge badge-success" style="font-weight:800; font-size:0.8rem;">Final Approval Granted</span>
        <strong style="color:var(--text-main); font-size:0.95rem;">Order Completed & Released to Student Portal</strong>
      </div>
      <p style="color:var(--text-muted); font-size:0.85rem; margin:2px 0 0 0;">
        Admin has signed off on the deliverable. If student's payment is settled, files are immediately downloadable in their portal.
      </p>
    </div>
  </div>
<?php endif; ?>

<div class="grid-2" style="display:grid; grid-template-columns: 2fr 1fr; gap:1.5rem;">
  <div>
    <!-- Allocation Control & Status Switcher Card -->
    <div class="table-card" style="padding:1.5rem;">
      <h3 style="font-size:1.1rem; margin-bottom:1.2rem; color:var(--text-main);"><i class="fa-solid fa-user-gear" style="color:var(--primary);"></i> Expert Allocation & Workflow Control</h3>
      
      <form id="allocateForm">
        <input type="hidden" name="assignment_id" value="<?php echo htmlspecialchars($asm['assignment_id']); ?>">
        
        <div class="grid-2" style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1rem;">
          <div class="form-group">
            <label>Assign Primary Expert *</label>
            <select name="expert_id" class="form-control" required>
              <option value="">-- Select Subject Expert --</option>
              <?php foreach ($experts as $exp): ?>
                <option value="<?php echo htmlspecialchars($exp['expert_id']); ?>" <?php echo ($asm['expert_id'] === $exp['expert_id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($exp['name']); ?> (Rating: <?php echo $exp['rating']; ?> | <?php echo $exp['status']; ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label>Update Production Status</label>
            <select name="status" id="statusSelect" class="form-control">
              <option value="New" <?php if ($asm['status'] === 'New') echo 'selected'; ?>>New</option>
              <option value="Pending Review" <?php if ($asm['status'] === 'Pending Review') echo 'selected'; ?>>Pending Review</option>
              <option value="Allocated" <?php if ($asm['status'] === 'Allocated') echo 'selected'; ?>>Allocated</option>
              <option value="In Progress" <?php if ($asm['status'] === 'In Progress') echo 'selected'; ?>>In Progress</option>
              <option value="Quality Check" <?php if ($asm['status'] === 'Quality Check') echo 'selected'; ?>>Quality Check</option>
              <option value="Pending Admin Approval" <?php if ($asm['status'] === 'Pending Admin Approval') echo 'selected'; ?>>Pending Admin Approval (QA Approved)</option>
              <option value="Completed" <?php if ($asm['status'] === 'Completed') echo 'selected'; ?>>Completed</option>
              <option value="Revision Requested" <?php if ($asm['status'] === 'Revision Requested') echo 'selected'; ?>>Revision Requested</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label>Internal Notes for Expert / QA Team</label>
          <textarea name="notes" class="form-control" rows="2" placeholder="Add internal guidelines for the allocated expert..."></textarea>
        </div>

        <div id="allocMsg" style="margin-top:0.8rem;"></div>

        <button type="submit" class="btn btn-primary" style="margin-top:0.8rem;"><i class="fa-solid fa-check"></i> Save Allocation & Status Changes</button>
      </form>
    </div>

    <!-- Student Requirements / Prompt -->
    <div class="table-card" style="padding:1.5rem;">
      <h3 style="font-size:1.1rem; margin-bottom:0.8rem; color:var(--text-main);"><i class="fa-solid fa-file-lines" style="color:var(--secondary);"></i> Assignment Requirements</h3>
      <p style="color:var(--text-main); white-space:pre-line; line-height:1.6; font-size:0.92rem;">
        <?php echo htmlspecialchars($asm['instructions'] ?? 'No special instructions provided.'); ?>
      </p>
    </div>

    <!-- Dedicated Technical Files & Archive Section (.ZIP, .RAR, Code, Archives) -->
    <div class="table-card" style="padding:1.5rem; border:1.5px solid #0284c7; background:linear-gradient(180deg, #f0f9ff 0%, #ffffff 100%);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.8rem; flex-wrap:wrap; gap:0.5rem;">
        <h3 style="font-size:1.1rem; color:#0369a1; margin:0; display:flex; align-items:center; gap:8px;">
          <i class="fa-solid fa-file-zipper" style="color:#0284c7;"></i> Technical Archives & Code (.ZIP, .RAR, Code) (<?php echo count($techFiles); ?>)
        </h3>
        <span class="badge" style="background:#e0f2fe; color:#0369a1; font-size:0.75rem; font-weight:700;">
          <i class="fa-solid fa-box-archive"></i> Technical Repository
        </span>
      </div>
      <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:1rem;">
        Dedicated section for compressed archive packages (.zip, .rar, .7z, .tar.gz), databases (.sql), and development source files.
      </p>

      <?php if (empty($techFiles)): ?>
        <div style="background:#f8fafc; border:1px dashed #93c5fd; border-radius:8px; padding:1.2rem; text-align:center; color:var(--text-muted); font-size:0.88rem;">
          <i class="fa-solid fa-file-zipper" style="font-size:1.3rem; color:#38bdf8; margin-bottom:6px; display:block;"></i>
          No technical archive packages (.zip, .rar, code) uploaded to this assignment yet.
        </div>
      <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:10px;">
          <?php foreach ($techFiles as $file): 
            $ext = strtoupper(pathinfo($file['file_name'], PATHINFO_EXTENSION));
            $isInternal = !empty($file['is_internal']);
          ?>
            <div style="display:flex; justify-content:space-between; align-items:center; background:#ffffff; border:1px solid #bae6fd; padding:0.8rem 1rem; border-radius:var(--radius-sm); flex-wrap:wrap; gap:0.5rem;">
              <div style="display:flex; align-items:center; gap:10px;">
                <span class="badge" style="background:#0284c7; color:#ffffff; font-weight:800; font-size:0.75rem; letter-spacing:0.5px; padding:4px 8px;">
                  .<?php echo htmlspecialchars($ext ?: 'ZIP'); ?>
                </span>
                <div>
                  <strong style="color:var(--text-main); font-size:0.92rem;"><?php echo htmlspecialchars($file['file_name']); ?></strong>
                  <?php if ($isInternal): ?>
                    <span class="badge badge-warning" style="font-size:0.7rem; margin-left:6px;">Staff Only</span>
                  <?php else: ?>
                    <span class="badge badge-success" style="font-size:0.7rem; margin-left:6px;">Student Visible</span>
                  <?php endif; ?>
                  <small style="color:var(--text-muted); display:block; margin-top:2px;">Uploaded by <?php echo htmlspecialchars($file['uploaded_by']); ?> &bull; <?php echo $file['upload_date']; ?></small>
                </div>
              </div>
              <a href="/<?php echo htmlspecialchars($file['path']); ?>" download class="btn btn-primary btn-sm" style="font-weight:700;">
                <i class="fa-solid fa-download"></i> Download Package
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Submitted Solution Deliverables (Segregated) -->
    <?php if (!empty($docSolutionFiles)): ?>
      <div class="table-card" style="padding:1.5rem; border:1.5px solid #a855f7;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
          <h3 style="font-size:1.1rem; color:var(--text-main); margin:0;">
            <i class="fa-solid fa-microscope" style="color:var(--primary);"></i> Expert Deliverables & Documents (<?php echo count($docSolutionFiles); ?>)
          </h3>
          <span class="badge badge-purple" style="font-size:0.75rem;">Quality Control</span>
        </div>
        <div style="display:flex; flex-direction:column; gap:10px;">
          <?php foreach ($docSolutionFiles as $file): 
            $ext = strtolower($file['file_type'] ?? '');
            $icon = 'fa-file';
            if (in_array($ext, ['pdf'])) $icon = 'fa-file-pdf';
            elseif (in_array($ext, ['doc', 'docx'])) $icon = 'fa-file-word';
            elseif (in_array($ext, ['xls', 'xlsx', 'csv'])) $icon = 'fa-file-excel';
            elseif (in_array($ext, ['ppt', 'pptx'])) $icon = 'fa-file-powerpoint';
            elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'])) $icon = 'fa-file-image';
            $isTurnitin = stripos($file['file_name'], 'turnitin') !== false;
          ?>
            <div style="display:flex; justify-content:space-between; align-items:center; background:#faf5ff; border:1px solid #e9d5ff; padding:0.8rem 1rem; border-radius:var(--radius-sm); flex-wrap:wrap; gap:0.5rem;">
              <div>
                <i class="fa-solid <?php echo $icon; ?>" style="color:#9333ea; margin-right:8px; font-size:1.1rem;"></i>
                <strong style="color:var(--text-main);"><?php echo htmlspecialchars($file['file_name']); ?></strong>
                <?php if ($isTurnitin): ?>
                  <span class="badge" style="background:#fef3c7; color:#92400e; font-size:0.7rem; margin-left:6px;"><i class="fa-solid fa-chart-pie"></i> Turnitin Report</span>
                <?php else: ?>
                  <span class="badge badge-purple" style="font-size:0.7rem; margin-left:6px;"><i class="fa-solid fa-file-circle-check"></i> Solution File</span>
                <?php endif; ?>
                <small style="color:var(--text-muted); display:block; margin-top:2px;">Uploaded by <?php echo htmlspecialchars($file['uploaded_by']); ?> &bull; <?php echo $file['upload_date']; ?></small>
              </div>
              <a href="/<?php echo htmlspecialchars($file['path']); ?>" download class="btn btn-outline btn-sm">
                <i class="fa-solid fa-download"></i> Inspect & Download
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Student Brief Files & Guidelines -->
    <div class="table-card" style="padding:1.5rem;">
      <h3 style="font-size:1.1rem; margin-bottom:1rem; color:var(--text-main);"><i class="fa-solid fa-paperclip" style="color:var(--success);"></i> Student Brief & Uploaded Materials (<?php echo count($docBriefFiles); ?>)</h3>
      <?php if (empty($docBriefFiles)): ?>
        <p style="color:var(--text-muted); font-size:0.9rem;">No document brief files uploaded yet.</p>
      <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:10px;">
          <?php foreach ($docBriefFiles as $file): 
            $ext = strtolower($file['file_type'] ?? '');
            $icon = 'fa-file';
            if (in_array($ext, ['pdf'])) $icon = 'fa-file-pdf';
            elseif (in_array($ext, ['doc', 'docx'])) $icon = 'fa-file-word';
            elseif (in_array($ext, ['xls', 'xlsx', 'csv'])) $icon = 'fa-file-excel';
            elseif (in_array($ext, ['ppt', 'pptx'])) $icon = 'fa-file-powerpoint';
            elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'])) $icon = 'fa-file-image';
            $isInternal = !empty($file['is_internal']);
          ?>
            <div style="display:flex; justify-content:space-between; align-items:center; background:#f8fafc; border:1px solid var(--portal-border); padding:0.8rem 1rem; border-radius:var(--radius-sm); flex-wrap:wrap; gap:0.5rem;">
              <div>
                <i class="fa-solid <?php echo $icon; ?>" style="color:var(--primary); margin-right:8px; font-size:1.1rem;"></i>
                <strong style="color:var(--text-main);"><?php echo htmlspecialchars($file['file_name']); ?></strong>
                <?php if ($isInternal): ?>
                  <span class="badge badge-warning" style="font-size:0.7rem; margin-left:6px;">Staff Only</span>
                <?php else: ?>
                  <span class="badge badge-success" style="font-size:0.7rem; margin-left:6px;">Student Brief</span>
                <?php endif; ?>
                <small style="color:var(--text-muted); display:block; margin-top:2px;">Uploaded by <?php echo htmlspecialchars($file['uploaded_by']); ?> &bull; <?php echo $file['upload_date']; ?></small>
              </div>
              <a href="/<?php echo htmlspecialchars($file['path']); ?>" download class="btn btn-outline btn-sm">
                <i class="fa-solid fa-download"></i> Download
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Upload Files Section (Accepts Any Format) -->
    <div class="table-card" style="padding:1.5rem;">
      <h3 style="font-size:1.1rem; margin-bottom:0.4rem; color:var(--text-main);"><i class="fa-solid fa-cloud-arrow-up" style="color:var(--primary);"></i> Upload Assignment Files / Drafts / Solutions</h3>
      <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:1rem;">
        Upload expert work, drafts, QA checks, or final deliverables in <strong>ANY format</strong> (PDF, DOCX, ZIP, RAR, TXT, PY, IPYNB, XLS, PPTX, images, etc.).
      </p>
      <form id="allocatorUploadForm" enctype="multipart/form-data">
        <input type="hidden" name="assignment_id" value="<?php echo htmlspecialchars($asm['assignment_id']); ?>">
        <div style="margin-bottom:0.8rem;">
          <input type="file" name="assignment_files[]" multiple class="form-control" required>
        </div>
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
          <label style="display:flex; align-items:center; gap:6px; font-size:0.85rem; color:var(--text-muted); margin:0; cursor:pointer;">
            <input type="checkbox" name="is_internal" value="1">
            <span>Mark as Staff Internal File (Hidden from student)</span>
          </label>
          <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-cloud-arrow-up"></i> Upload Files</button>
        </div>
        <div id="allocUploadMsg" style="margin-top:0.8rem;"></div>
      </form>
    </div>

    <!-- Internal Notes Thread -->
    <div class="table-card" style="padding:1.5rem;">
      <h3 style="font-size:1.1rem; margin-bottom:1rem; color:var(--text-main);"><i class="fa-solid fa-lock" style="color:var(--warning);"></i> Internal Notes Thread (Hidden From Student)</h3>
      <?php if (empty($internalNotes)): ?>
        <p style="color:var(--text-muted); font-size:0.9rem;">No internal notes added yet.</p>
      <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:10px;">
          <?php foreach ($internalNotes as $note): ?>
            <div style="background:#f8fafc; border:1px solid var(--portal-border); padding:0.8rem 1rem; border-radius:var(--radius-sm);">
              <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                <strong style="color:var(--primary); font-size:0.85rem;"><?php echo htmlspecialchars($note['user_name']); ?> (<?php echo htmlspecialchars($note['user_role']); ?>)</strong>
                <small style="color:var(--text-dim);"><?php echo $note['created_at']; ?></small>
              </div>
              <p style="color:var(--text-main); font-size:0.9rem; margin:0;"><?php echo htmlspecialchars($note['message']); ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div>
    <!-- Masked Student Summary Card (Permitted Fields Only) -->
    <div class="table-card" style="padding:1.5rem;">
      <h3 style="font-size:1.1rem; margin-bottom:1rem; color:var(--text-main);"><i class="fa-solid fa-eye-slash" style="color:var(--warning);"></i> Masked Student Info</h3>
      <table style="width:100%; font-size:0.9rem; border-collapse:collapse;">
        <tr style="border-bottom:1px solid var(--portal-border);"><td style="padding:8px 0; color:var(--text-muted);">Student Name:</td><td style="text-align:right; font-weight:700; color:var(--text-main);"><?php echo htmlspecialchars(mask_student_name($student['name'] ?? '')); ?></td></tr>
        <tr style="border-bottom:1px solid var(--portal-border);"><td style="padding:8px 0; color:var(--text-muted);">Country:</td><td style="text-align:right; color:var(--text-main);"><?php echo htmlspecialchars($asm['country']); ?></td></tr>
        <tr style="border-bottom:1px solid var(--portal-border);"><td style="padding:8px 0; color:var(--text-muted);">Subject:</td><td style="text-align:right; color:var(--text-main);"><?php echo htmlspecialchars($asm['subject']); ?></td></tr>
        <tr style="border-bottom:1px solid var(--portal-border);"><td style="padding:8px 0; color:var(--text-muted);">Word Count:</td><td style="text-align:right; color:var(--text-main);"><?php echo $asm['word_count']; ?> Words</td></tr>
        <tr style="border-bottom:1px solid var(--portal-border);"><td style="padding:8px 0; color:var(--text-muted);">Priority:</td><td style="text-align:right; color:var(--text-main);"><?php echo htmlspecialchars($asm['priority']); ?></td></tr>
        <tr><td style="padding:8px 0; color:var(--text-muted);">Student Email:</td><td style="text-align:right; color:var(--danger); font-size:0.8rem; font-weight:700;">[RESTRICTED / HIDDEN]</td></tr>
      </table>
    </div>
  </div>
</div>

<script>
document.getElementById('allocateForm').addEventListener('submit', (e) => {
  e.preventDefault();
  const allocMsg = document.getElementById('allocMsg');
  allocMsg.innerHTML = '<div class="badge badge-info"><i class="fa-solid fa-spinner fa-spin"></i> Saving changes...</div>';

  fetch('/api.php?action=allocate_expert', {
    method: 'POST',
    body: new FormData(e.target)
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      allocMsg.innerHTML = `<div class="badge badge-success">${data.message}</div>`;
      setTimeout(() => { window.location.reload(); }, 1000);
    }
  });
});

const allocUploadForm = document.getElementById('allocatorUploadForm');
if (allocUploadForm) {
  allocUploadForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const msg = document.getElementById('allocUploadMsg');
    msg.innerHTML = '<div class="badge badge-info"><i class="fa-solid fa-spinner fa-spin"></i> Uploading files...</div>';

    fetch('/api.php?action=upload_assignment_file', {
      method: 'POST',
      body: new FormData(allocUploadForm)
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        msg.innerHTML = `<div class="badge badge-success">${data.message}</div>`;
        setTimeout(() => { window.location.reload(); }, 1000);
      } else {
        msg.innerHTML = `<div class="badge badge-danger">${data.message || 'Upload failed'}</div>`;
      }
    })
    .catch(err => {
      msg.innerHTML = `<div class="badge badge-danger">An error occurred during upload.</div>`;
    });
  });
}
</script>

<script src="/assets/js/portal.js"></script>
</div>
</div>
</body>
</html>
