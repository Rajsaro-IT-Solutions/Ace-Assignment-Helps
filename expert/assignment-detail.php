<?php
$pageTitle = "Expert Project Workspace";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Expert');
$user = Auth::currentUser();
require_once __DIR__ . '/../includes/helpers.php';

$asmId = trim($_GET['id'] ?? '');
$asm = DataStore::findOne('assignments', 'assignment_id', $asmId);

if (!$asm || ($asm['expert_id'] ?? '') !== $user['id'] || ($asm['status'] ?? '') === 'Deleted') {
    include __DIR__ . '/../includes/portal_header.php';
    echo "<div class='table-card' style='padding:3rem; text-align:center;'>";
    echo "<div class='badge badge-danger' style='font-size:1rem; padding:10px 16px; margin-bottom:1rem;'><i class='fa-solid fa-circle-exclamation'></i> Access Denied or Assignment Not Found</div>";
    echo "<p style='color:var(--text-muted);'>This assignment is not allocated to your expert roster ID or does not exist.</p>";
    echo "<a href='/expert/assignments.php' class='btn btn-primary'>&larr; Back to My Assigned Tasks</a>";
    echo "</div></div></div></div></body></html>";
    exit;
}

// Handle Form Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Accept & Start Work
    if ($action === 'accept_and_start') {
        DataStore::update('assignments', 'assignment_id', $asmId, [
            'status' => 'In Progress',
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        add_audit_log('Expert', $user['id'], 'Start Assignment', "Expert {$user['name']} accepted and started order $asmId");
        
        // Notify Allocator
        if (!empty($asm['allocator_id'])) {
            add_notification('Allocator', $asm['allocator_id'], "Expert Started Order $asmId", "Expert {$user['name']} has accepted and started work on order $asmId.", 'info', "/allocator/assignment-detail.php?id=$asmId");
        }

        $_SESSION['flash_msg'] = "Order status updated to 'In Progress'. You may now begin drafting your solution.";
        $_SESSION['flash_type'] = 'success';
        header("Location: /expert/assignment-detail.php?id=" . urlencode($asmId));
        exit;
    }

    // 2. Submit Final Solution & Turnitin Report
    if ($action === 'upload_solution') {
        $notes = trim($_POST['solution_notes'] ?? '');
        $filesUploaded = 0;
        $targetDir = __DIR__ . '/../assets/uploads/';
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        // A. Process Primary Solution File
        if (isset($_FILES['solution_file']) && $_FILES['solution_file']['error'] === UPLOAD_ERR_OK) {
            $origName = basename($_FILES['solution_file']['name']);
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $safePrefix = preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($origName, PATHINFO_FILENAME));
            $uniqueName = time() . '_SOLUTION_' . rand(100, 999) . '_' . $safePrefix . ($ext ? '.' . $ext : '');
            $targetPath = $targetDir . $uniqueName;

            if (move_uploaded_file($_FILES['solution_file']['tmp_name'], $targetPath)) {
                DataStore::insert('files', [
                    'file_id' => 'FILE-' . rand(8000, 9999),
                    'assignment_id' => $asmId,
                    'file_name' => $origName,
                    'path' => 'assets/uploads/' . $uniqueName,
                    'file_type' => $ext ?: 'file',
                    'uploaded_by' => 'Expert (' . $user['name'] . ') Solution',
                    'upload_date' => date('Y-m-d H:i:s'),
                    'is_internal' => 0
                ]);
                $filesUploaded++;
            }
        }

        // B. Process Turnitin / Plagiarism Report File
        if (isset($_FILES['turnitin_file']) && $_FILES['turnitin_file']['error'] === UPLOAD_ERR_OK) {
            $origName = basename($_FILES['turnitin_file']['name']);
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $safePrefix = preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($origName, PATHINFO_FILENAME));
            $uniqueName = time() . '_TURNITIN_' . rand(100, 999) . '_' . $safePrefix . ($ext ? '.' . $ext : '');
            $targetPath = $targetDir . $uniqueName;

            if (move_uploaded_file($_FILES['turnitin_file']['tmp_name'], $targetPath)) {
                DataStore::insert('files', [
                    'file_id' => 'FILE-' . rand(8000, 9999),
                    'assignment_id' => $asmId,
                    'file_name' => 'Turnitin_Report_' . $origName,
                    'path' => 'assets/uploads/' . $uniqueName,
                    'file_type' => $ext ?: 'pdf',
                    'uploaded_by' => 'Expert (' . $user['name'] . ') Turnitin',
                    'upload_date' => date('Y-m-d H:i:s'),
                    'is_internal' => 0
                ]);
                $filesUploaded++;
            }
        }

        if ($filesUploaded > 0) {
            // Update Assignment status to Quality Check
            DataStore::update('assignments', 'assignment_id', $asmId, [
                'status' => 'Quality Check',
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            add_audit_log('Expert', $user['id'], 'Solution Upload', "Expert {$user['name']} uploaded solution for order $asmId ($filesUploaded files)");

            // Notify Allocator & Admin (Broadcast to all admins)
            if (!empty($asm['allocator_id'])) {
                add_notification('Allocator', $asm['allocator_id'], "Solution Submitted - $asmId", "Expert {$user['name']} submitted the final solution for order $asmId. Ready for QA review.", 'success', "/allocator/assignment-detail.php?id=$asmId");
            }
            add_notification('Admin', '', "Solution Submitted - $asmId", "Expert {$user['name']} uploaded final solution for order $asmId. Ready for Quality Check review.", 'success', "/admin/assignment-detail.php?id=$asmId");

            $_SESSION['flash_msg'] = "Solution successfully submitted! The project status is now 'Quality Check'.";
            $_SESSION['flash_type'] = 'success';
        } else {
            $errCode = $_FILES['solution_file']['error'] ?? -1;
            $errMsg = "Please select a valid solution file to upload.";
            if ($errCode === UPLOAD_ERR_INI_SIZE || $errCode === UPLOAD_ERR_FORM_SIZE) {
                $errMsg = "The uploaded file is too large. Please upload a file smaller than 64MB.";
            } elseif ($errCode === UPLOAD_ERR_NO_FILE) {
                $errMsg = "No file selected. Please browse and select your final solution document.";
            } elseif ($errCode !== UPLOAD_ERR_OK && $errCode !== -1) {
                $errMsg = "File upload failed with error code $errCode. Please try again.";
            }
            $_SESSION['flash_msg'] = $errMsg;
            $_SESSION['flash_type'] = 'danger';
        }

        header("Location: /expert/assignment-detail.php?id=" . urlencode($asmId));
        exit;
    }
}

// Fetch all files associated with this order
$allFiles = DataStore::filter('files', function($f) use ($asmId) {
    return isset($f['assignment_id']) && $f['assignment_id'] === $asmId;
});

// Separate student brief files from uploaded solution files
$briefFiles = array_filter($allFiles, function($f) {
    return strpos($f['uploaded_by'] ?? '', 'Expert') === false;
});

$solutionFiles = array_filter($allFiles, function($f) {
    return strpos($f['uploaded_by'] ?? '', 'Expert') !== false;
});

$sla = get_sla_status($asm['deadline']);
$statusBadge = get_status_badge_class($asm['status']);

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

<!-- Top Header Navigation & Status -->
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <a href="/expert/assignments.php" style="color:var(--text-muted); font-size:0.88rem; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
      <i class="fa-solid fa-arrow-left"></i> Back to My Assigned Tasks
    </a>
    <h2 style="font-size:1.75rem; color:var(--text-main); margin:0.35rem 0 0.25rem 0; font-weight:800;">
      <?php echo htmlspecialchars($asm['title']); ?>
    </h2>
    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin-top:4px;">
      <span class="badge badge-info" style="font-family:monospace; font-weight:700;"><?php echo htmlspecialchars($asm['assignment_id']); ?></span>
      <span class="badge <?php echo $statusBadge; ?>"><?php echo htmlspecialchars($asm['status']); ?></span>
      <span class="badge <?php echo $sla['badge_class']; ?>"><i class="fa-solid fa-clock"></i> <?php echo $sla['label']; ?></span>
    </div>
  </div>

  <div style="display:flex; gap:10px;">
    <a href="/expert/messages.php?assignment_id=<?php echo urlencode($asm['assignment_id']); ?>" class="btn btn-outline btn-sm">
      <i class="fa-solid fa-comments"></i> Chat with Allocator
    </a>
  </div>
</div>

<!-- Two-Column Workspace Layout -->
<div style="display:grid; grid-template-columns: 1.8fr 1fr; gap: 1.75rem; align-items:start;" id="expertWorkspaceGrid">
  
  <!-- Left Main Column: Project Brief & Solution Upload -->
  <div>
    
    <!-- 1. Academic Specifications Card -->
    <div class="table-card" style="padding:1.75rem; margin-bottom:1.5rem;">
      <h3 style="font-size:1.15rem; color:var(--text-main); margin:0 0 1rem 0; font-weight:800; border-bottom:1px solid var(--border-color); padding-bottom:0.75rem;">
        <i class="fa-solid fa-book-bookmark" style="color:var(--primary);"></i> Academic Requirements & Brief
      </h3>

      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:1rem; margin-bottom:1.5rem; background:#f8fafc; padding:1.25rem; border-radius:12px; border:1px solid #f1f5f9;">
        <div>
          <small style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:700; display:block;">Subject Area</small>
          <strong style="color:var(--text-main); font-size:0.95rem;"><?php echo htmlspecialchars($asm['subject']); ?></strong>
        </div>
        <div>
          <small style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:700; display:block;">Paper Type</small>
          <strong style="color:var(--text-main); font-size:0.95rem;"><?php echo htmlspecialchars($asm['assignment_type']); ?></strong>
        </div>
        <div>
          <small style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:700; display:block;">Required Word Count</small>
          <strong style="color:var(--text-main); font-size:0.95rem;"><?php echo (int)$asm['word_count']; ?> words (<?php echo ceil($asm['word_count'] / 250); ?> pages)</strong>
        </div>
        <div>
          <small style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:700; display:block;">Referencing / Citation</small>
          <strong style="color:var(--text-main); font-size:0.95rem;"><?php echo htmlspecialchars($asm['reference_style'] ?? 'APA 7th'); ?></strong>
        </div>
        <div>
          <small style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:700; display:block;">Language & Variety</small>
          <strong style="color:var(--text-main); font-size:0.95rem;"><?php echo htmlspecialchars($asm['language'] ?? 'English (US)'); ?></strong>
        </div>
        <div>
          <small style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:700; display:block;">Target Delivery SLA</small>
          <strong style="color:#d97706; font-size:0.95rem;"><?php echo date('M d, Y H:i', strtotime($asm['deadline'])); ?></strong>
        </div>
      </div>

      <!-- Student Instructions Box -->
      <div style="margin-bottom:1.5rem;">
        <h4 style="font-size:0.95rem; color:var(--text-main); margin-bottom:0.5rem; font-weight:700;">Student Instructions & Guidelines:</h4>
        <div style="background:#ffffff; border:1.5px solid #e2e8f0; border-radius:10px; padding:1.25rem; font-size:0.9rem; line-height:1.7; color:#334155; white-space:pre-wrap;">
          <?php echo htmlspecialchars($asm['instructions'] ?: 'No specific instructions provided. Follow standard academic marking rubrics.'); ?>
        </div>
      </div>

      <!-- Brief Attachments from Student -->
      <div>
        <h4 style="font-size:0.95rem; color:var(--text-main); margin-bottom:0.6rem; font-weight:700;">
          <i class="fa-solid fa-paperclip" style="color:var(--secondary);"></i> Attached Brief / Reference Documents:
        </h4>
        <?php if (empty($briefFiles)): ?>
          <div style="color:var(--text-muted); font-size:0.85rem; font-style:italic; padding:0.5rem 0;">
            No student files attached to this assignment brief.
          </div>
        <?php else: ?>
          <div style="display:flex; flex-direction:column; gap:8px;">
            <?php foreach ($briefFiles as $f): ?>
              <div style="display:flex; justify-content:space-between; align-items:center; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:8px 14px;">
                <div style="display:flex; align-items:center; gap:8px;">
                  <i class="fa-solid fa-file-lines" style="color:var(--primary); font-size:1.1rem;"></i>
                  <div>
                    <strong style="font-size:0.88rem; color:var(--text-main);"><?php echo htmlspecialchars($f['file_name']); ?></strong>
                    <small style="color:var(--text-muted); display:block; font-size:0.75rem;">Uploaded <?php echo htmlspecialchars($f['upload_date']); ?></small>
                  </div>
                </div>
                <a href="/<?php echo htmlspecialchars($f['path']); ?>" target="_blank" class="btn btn-outline btn-sm">
                  <i class="fa-solid fa-download"></i> Download Brief
                </a>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- 2. Workflow Stage & Action Area -->
    <div class="table-card" style="padding:1.75rem;">
      <h3 style="font-size:1.15rem; color:var(--text-main); margin:0 0 1rem 0; font-weight:800; border-bottom:1px solid var(--border-color); padding-bottom:0.75rem;">
        <i class="fa-solid fa-pen-nib" style="color:var(--primary);"></i> Solution Drafting & QA Submission
      </h3>

      <?php if ($asm['status'] === 'Allocated'): ?>
        <!-- State A: Newly Allocated, Need to Accept -->
        <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:12px; padding:1.5rem; text-align:center; margin-bottom:1.5rem;">
          <i class="fa-solid fa-bell" style="font-size:2rem; color:#2563eb; margin-bottom:0.5rem;"></i>
          <h4 style="color:#1e40af; font-size:1.15rem; margin-bottom:0.4rem; font-weight:800;">Order Allocated to You</h4>
          <p style="color:#1d4ed8; font-size:0.9rem; margin-bottom:1.25rem; line-height:1.5;">
            Please review the requirements above and accept the task to start the progress timer and notify the allocator.
          </p>
          <form method="POST">
            <input type="hidden" name="action" value="accept_and_start">
            <button type="submit" class="btn btn-primary btn-lg" style="font-weight:800; padding:0.9rem 2rem;">
              <i class="fa-solid fa-circle-check"></i> Accept Task & Begin Drafting
            </button>
          </form>
        </div>
      <?php endif; ?>

      <?php if ($asm['status'] === 'Revision Requested'): ?>
        <div style="background:#fff1f2; border:1px solid #fecdd3; border-radius:10px; padding:1rem; margin-bottom:1.5rem; color:#be123c; font-size:0.9rem;">
          <i class="fa-solid fa-triangle-exclamation"></i> <strong>Student Revision Requested:</strong> Please review student feedback and submit an updated revised solution below.
        </div>
      <?php endif; ?>

      <?php if (in_array($asm['status'], ['Quality Check', 'Completed', 'Delivered'])): ?>
        <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; padding:1.5rem; text-align:center; margin-bottom:1.5rem;">
          <div style="width:56px; height:56px; border-radius:50%; background:#dcfce7; color:#10b981; display:flex; align-items:center; justify-content:center; font-size:1.8rem; margin:0 auto 0.75rem auto;">
            <i class="fa-solid fa-check-double"></i>
          </div>
          <h4 style="color:#166534; font-size:1.15rem; font-weight:800; margin-bottom:0.3rem;">
            Solution Submitted (<?php echo htmlspecialchars($asm['status']); ?>)
          </h4>
          <p style="color:#15803d; font-size:0.88rem; margin-bottom:1rem;">
            Your solution file has been submitted and is currently in <strong><?php echo htmlspecialchars($asm['status']); ?></strong>.
          </p>
          <button type="button" class="btn btn-outline btn-sm" onclick="const s = document.getElementById('solutionUploadBox'); s.style.display = (s.style.display === 'none' ? 'block' : 'none');" style="border-color:#10b981; color:#15803d; font-weight:700;">
            <i class="fa-solid fa-cloud-arrow-up"></i> Upload Revised / Additional Solution File &darr;
          </button>
        </div>
      <?php endif; ?>

      <!-- Solution Upload Form -->
      <div id="solutionUploadBox" style="<?php echo in_array($asm['status'], ['Quality Check', 'Completed', 'Delivered']) ? 'display:none;' : 'display:block;'; ?>">
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="upload_solution">

          <div class="form-group" style="margin-bottom:1.5rem;">
            <label style="font-weight:700; color:var(--text-main); display:block; margin-bottom:6px;">
              1. Final Solution File (.docx, .pdf, .zip, .py) *
            </label>
            <input type="file" name="solution_file" class="form-control" required style="padding:0.6rem;">
            <small style="color:var(--text-muted); font-size:0.78rem;">Upload your completed work. Word doc, PDF, or zip archive recommended.</small>
          </div>

          <div class="form-group" style="margin-bottom:1.5rem;">
            <label style="font-weight:700; color:var(--text-main); display:block; margin-bottom:6px;">
              2. Official Turnitin 0% Plagiarism & AI Report (.pdf)
            </label>
            <input type="file" name="turnitin_file" class="form-control" accept=".pdf" style="padding:0.6rem;">
            <small style="color:var(--text-muted); font-size:0.78rem;">Upload the generated Turnitin authenticity report validating 0% similarity.</small>
          </div>

          <div class="form-group" style="margin-bottom:1.5rem;">
            <label style="font-weight:700; color:var(--text-main); display:block; margin-bottom:6px;">
              3. Solution Summary & QA Notes
            </label>
            <textarea name="solution_notes" class="form-control" rows="3" placeholder="Add any comments, methodology notes, or guidance for the student and QA allocator..."></textarea>
          </div>

          <button type="submit" class="btn btn-primary btn-lg" style="width:100%; font-weight:800; padding:1rem; background:linear-gradient(135deg, #059669 0%, #047857 100%); border:none; cursor:pointer;" onclick="return confirm('Submit this completed solution for Quality Check review?');">
            <i class="fa-solid fa-cloud-arrow-up"></i> Submit Solution for Quality Review
          </button>
        </form>
      </div>

    </div>

  </div>

  <!-- Right Sidebar Column: Timeline & Submitted Solutions -->
  <div>
    
    <!-- Timeline Card -->
    <div class="table-card" style="padding:1.5rem; margin-bottom:1.5rem;">
      <h3 style="font-size:1.05rem; color:var(--text-main); margin:0 0 1rem 0; font-weight:800; border-bottom:1px solid var(--border-color); padding-bottom:0.5rem;">
        <i class="fa-solid fa-clock-rotate-left" style="color:var(--primary);"></i> SLA Delivery Schedule
      </h3>

      <div style="display:flex; flex-direction:column; gap:12px; font-size:0.85rem;">
        <div>
          <span style="color:var(--text-muted); display:block;">Target Delivery SLA:</span>
          <strong style="color:#0f172a; font-size:1rem;"><?php echo date('M d, Y - H:i', strtotime($asm['deadline'])); ?></strong>
        </div>
        <div>
          <span style="color:var(--text-muted); display:block;">Current Urgency:</span>
          <span class="badge <?php echo $sla['badge_class']; ?>"><?php echo $sla['label']; ?></span>
        </div>
        <div>
          <span style="color:var(--text-muted); display:block;">Order Allocated Date:</span>
          <span style="color:#0f172a; font-weight:600;"><?php echo date('M d, Y H:i', strtotime($asm['created_at'])); ?></span>
        </div>
      </div>
    </div>

    <!-- Submitted Solution Files Card -->
    <div class="table-card" style="padding:1.5rem;">
      <h3 style="font-size:1.05rem; color:var(--text-main); margin:0 0 1rem 0; font-weight:800; border-bottom:1px solid var(--border-color); padding-bottom:0.5rem;">
        <i class="fa-solid fa-file-circle-check" style="color:#059669;"></i> Uploaded Solutions (<?php echo count($solutionFiles); ?>)
      </h3>

      <?php if (empty($solutionFiles)): ?>
        <p style="color:var(--text-muted); font-size:0.85rem; font-style:italic; margin:0;">
          No solution files uploaded yet for this assignment.
        </p>
      <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:10px;">
          <?php foreach ($solutionFiles as $sf): ?>
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px; font-size:0.82rem;">
              <div style="font-weight:700; color:#0f172a; margin-bottom:4px; word-break:break-all;">
                <i class="fa-solid fa-file" style="color:var(--primary);"></i> <?php echo htmlspecialchars($sf['file_name']); ?>
              </div>
              <small style="color:var(--text-muted); display:block; margin-bottom:6px;">
                <?php echo htmlspecialchars($sf['upload_date']); ?>
              </small>
              <a href="/<?php echo htmlspecialchars($sf['path']); ?>" target="_blank" class="btn btn-outline btn-sm" style="width:100%; justify-content:center; padding:4px 8px; font-size:0.75rem;">
                <i class="fa-solid fa-download"></i> Download / Inspect File
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </div>

</div>

<script src="/assets/js/portal.js"></script>
</div>
</div>
</div>
</body>
</html>
