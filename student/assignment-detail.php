<?php
$pageTitle = "Assignment Details";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Student');
$user = Auth::currentUser();
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

$id = $_GET['id'] ?? '';
$asm = DataStore::findOne('assignments', 'assignment_id', $id);

if (!$asm || $asm['student_id'] !== $user['id']) {
    echo "<div class='badge badge-danger'>Assignment not found or access denied.</div>";
    echo "</div></div></body></html>";
    exit;
}

$sla = get_sla_status($asm['deadline']);
$currency = $asm['currency'] ?? 'USD';
$currencySymbol = '$';
if ($currency === 'INR') $currencySymbol = '₹';
elseif ($currency === 'GBP') $currencySymbol = '£';
elseif ($currency === 'EUR') $currencySymbol = '€';
elseif ($currency === 'AUD') $currencySymbol = 'A$';
elseif ($currency === 'CAD') $currencySymbol = 'C$';

// Fetch all public files for this assignment
$files = DataStore::filter('files', function($f) use ($id) {
    return isset($f['assignment_id']) && $f['assignment_id'] === $id && empty($f['is_internal']);
});

// Segregate deliverables from initial student brief files
$solutionFiles = array_values(array_filter($files, function($f) {
    return is_solution_file($f);
}));

$studentBriefFiles = array_values(array_filter($files, function($f) {
    return !is_solution_file($f);
}));

// Verification & Payment flags
$isAdminApproved = in_array($asm['status'], ['Completed', 'Delivered']);
$isPaid = is_assignment_paid($asm['assignment_id']);

// Workflow steps array
$workflow = [
    'Order Placed' => ['New', 'Pending Review', 'Waiting for Payment', 'Confirmed', 'Allocated', 'In Progress', 'Quality Check', 'Pending Admin Approval', 'Completed', 'Delivered'],
    'Expert Allocated' => ['Allocated', 'In Progress', 'Quality Check', 'Pending Admin Approval', 'Completed', 'Delivered'],
    'Drafting & Analysis' => ['In Progress', 'Quality Check', 'Pending Admin Approval', 'Completed', 'Delivered'],
    'Quality & Plagiarism Check' => ['Quality Check', 'Pending Admin Approval', 'Completed', 'Delivered'],
    'Admin Final Sign-Off' => ['Pending Admin Approval', 'Completed', 'Delivered'],
    'Solution Delivered' => ['Completed', 'Delivered']
];
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <a href="/student/assignments.php" style="color:var(--text-muted); font-size:0.9rem; text-decoration:none;"><i class="fa-solid fa-arrow-left"></i> Back to My Assignments</a>
    <h2 style="font-size:1.8rem; margin-top:0.3rem; color:var(--text-main);"><?php echo htmlspecialchars($asm['title']); ?></h2>
    <div style="display:flex; gap:10px; align-items:center; margin-top:0.4rem; flex-wrap:wrap;">
      <span class="badge badge-info"><?php echo htmlspecialchars($asm['assignment_id']); ?></span>
      <span class="badge <?php echo get_status_badge_class($asm['status']); ?>"><?php echo htmlspecialchars($asm['status']); ?></span>
      <span class="badge <?php echo $sla['badge_class']; ?>"><i class="fa-solid fa-clock"></i> <?php echo $sla['label']; ?></span>
    </div>
  </div>

  <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
    <?php if (!$isPaid): ?>
      <a href="/checkout.php?assignment_id=<?php echo urlencode($asm['assignment_id']); ?>" class="btn btn-primary" style="font-weight:700; background:linear-gradient(135deg, #059669, #047857); border-color:#047857;">
        <i class="fa-solid fa-lock"></i> Pay Now (<?php echo format_currency_amount($asm['final_price'], $currency); ?>)
      </a>
    <?php endif; ?>
    <?php if ($isAdminApproved && $isPaid): ?>
      <button class="btn btn-warning" onclick="openModal('revisionModal')">
        <i class="fa-solid fa-rotate-left"></i> Request Free Revision
      </button>
    <?php endif; ?>
    <a href="/student/invoice.php?id=<?php echo urlencode($asm['assignment_id']); ?>" class="btn btn-outline" target="_blank">
      <i class="fa-solid fa-file-invoice"></i> View Invoice
    </a>
  </div>
</div>

<div class="grid-2" style="display:grid; grid-template-columns: 2fr 1fr; gap:1.5rem;">
  <div>

    <!-- DELIVERABLES SECTION -->
    <?php if (!$isAdminApproved): ?>
      <!-- Pre-Approval State: Solution Hidden, Quality Check & Admin Review In Progress Banner -->
      <div class="table-card" style="padding:1.75rem; background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(168, 85, 247, 0.05) 100%); border: 1.5px solid rgba(168, 85, 247, 0.35); border-radius:12px; margin-bottom:1.5rem;">
        <div style="display:flex; align-items:flex-start; gap:16px;">
          <div style="width:52px; height:52px; border-radius:12px; background:linear-gradient(135deg, #6366f1, #a855f7); color:#fff; display:flex; align-items:center; justify-content:center; font-size:1.5rem; flex-shrink:0; box-shadow:0 4px 15px rgba(99,102,241,0.3);">
            <i class="fa-solid fa-microscope"></i>
          </div>
          <div style="flex:1;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px; flex-wrap:wrap;">
              <span class="badge badge-purple" style="font-weight:700;"><i class="fa-solid fa-shield-halved"></i> Academic Quality & Originality Verification</span>
              <?php if ($asm['status'] === 'Pending Admin Approval'): ?>
                <span class="badge badge-cyan" style="font-weight:700;"><i class="fa-solid fa-circle-check"></i> Allocator QA Passed</span>
              <?php endif; ?>
            </div>
            <h3 style="font-size:1.25rem; color:var(--text-main); margin:0 0 0.5rem 0;">Solution in Quality Check & Administrative Verification</h3>
            <p style="color:var(--text-muted); font-size:0.92rem; line-height:1.6; margin:0 0 1rem 0;">
              Our Quality Assurance committee and Academic Directors are currently conducting Turnitin plagiarism detection, citation auditing, and rubric compliance checks. As per platform policy, solution files are released to your portal immediately once final administrative sign-off is completed.
            </p>
            <div style="background:#fff; border:1px solid var(--portal-border); border-radius:8px; padding:0.8rem 1.2rem; display:flex; gap:1.5rem; flex-wrap:wrap; font-size:0.85rem; color:var(--text-muted);">
              <div><i class="fa-solid fa-circle-check" style="color:var(--success);"></i> Expert Drafting: <strong>Completed</strong></div>
              <div><i class="fa-solid fa-spinner fa-spin" style="color:var(--primary);"></i> Allocator QA Check: <strong><?php echo ($asm['status'] === 'Pending Admin Approval') ? 'Approved' : 'In Progress'; ?></strong></div>
              <div><i class="fa-solid fa-clock" style="color:var(--warning);"></i> Final Admin Sign-off: <strong>Pending</strong></div>
            </div>
            <p style="color:var(--text-muted); font-size:0.82rem; margin-top:0.8rem; margin-bottom:0;">
              <i class="fa-solid fa-circle-info" style="color:var(--primary);"></i> Solution download links will become available here instantly upon final administrator approval.
            </p>
          </div>
        </div>
      </div>
    <?php else: ?>
      <!-- Admin Final Approval Granted -->
      <?php if (!$isPaid): ?>
        <!-- SUB-CASE: Admin Approved BUT Payment Still Pending -> BLURRED PREVIEW WITH PAY NOW OPTION ONLY -->
        <div class="table-card" style="padding:0; overflow:hidden; border: 1.5px solid #f59e0b; position:relative; border-radius:12px; margin-bottom:1.5rem;">
          <div style="padding:1rem 1.5rem; background:rgba(245, 158, 11, 0.08); border-bottom:1px solid #fed7aa; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem;">
            <div style="display:flex; align-items:center; gap:8px;">
              <span class="badge badge-warning" style="font-weight:800; font-size:0.8rem;"><i class="fa-solid fa-lock"></i> Payment Required to Unlock</span>
              <h3 style="font-size:1.05rem; margin:0; color:var(--text-main);">Verified Solution Deliverables (<?php echo count($solutionFiles) ?: 2; ?> Files)</h3>
            </div>
            <span style="font-size:0.85rem; color:#b45309; font-weight:700;"><i class="fa-solid fa-circle-check"></i> Approved by Administration</span>
          </div>

          <!-- Blurred Preview Container (Non-interactive & Blurred) -->
          <div style="position:relative; min-height:230px;">
            <div class="solution-blur-preview" style="filter: blur(8px); -webkit-filter: blur(8px); user-select: none; pointer-events: none; opacity: 0.55; padding: 1.5rem; display: flex; flex-direction: column; gap: 10px;">
              <?php if (!empty($solutionFiles)): ?>
                <?php foreach ($solutionFiles as $sf): 
                  $isTurnitin = stripos($sf['file_name'], 'turnitin') !== false;
                ?>
                  <div style="display:flex; justify-content:space-between; align-items:center; background:#f8fafc; border:1px solid var(--portal-border); padding:0.8rem 1rem; border-radius:var(--radius-sm);">
                    <div>
                      <i class="fa-solid <?php echo $isTurnitin ? 'fa-chart-pie' : 'fa-file-circle-check'; ?>" style="color:var(--primary); margin-right:8px; font-size:1.1rem;"></i>
                      <strong style="color:var(--text-main);"><?php echo htmlspecialchars($sf['file_name']); ?></strong>
                      <span class="badge badge-success" style="font-size:0.7rem; margin-left:6px;">Approved Solution</span>
                      <small style="color:var(--text-muted); display:block; margin-top:2px;">Final Deliverable &bull; Verified Quality &bull; Turnitin Cleared</small>
                    </div>
                    <button class="btn btn-outline btn-sm" disabled style="opacity:0.5; cursor:not-allowed;">
                      <i class="fa-solid fa-lock"></i> Locked
                    </button>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div style="display:flex; justify-content:space-between; align-items:center; background:#f8fafc; border:1px solid var(--portal-border); padding:0.8rem 1rem; border-radius:var(--radius-sm);">
                  <div>
                    <i class="fa-solid fa-file-pdf" style="color:var(--primary); margin-right:8px; font-size:1.1rem;"></i>
                    <strong style="color:var(--text-main);">Final_Completed_Solution_Report.docx</strong>
                    <span class="badge badge-success" style="font-size:0.7rem; margin-left:6px;">Approved Solution</span>
                  </div>
                  <button class="btn btn-outline btn-sm" disabled><i class="fa-solid fa-lock"></i> Locked</button>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; background:#f8fafc; border:1px solid var(--portal-border); padding:0.8rem 1rem; border-radius:var(--radius-sm);">
                  <div>
                    <i class="fa-solid fa-chart-pie" style="color:#10b981; margin-right:8px; font-size:1.1rem;"></i>
                    <strong style="color:var(--text-main);">Turnitin_Official_Similarity_Report.pdf</strong>
                    <span class="badge badge-info" style="font-size:0.7rem; margin-left:6px;">Originality Report</span>
                  </div>
                  <button class="btn btn-outline btn-sm" disabled><i class="fa-solid fa-lock"></i> Locked</button>
                </div>
              <?php endif; ?>
            </div>

            <!-- Centered High-Contrast Glassmorphism Lock Overlay (ONLY Pay Now Option) -->
            <div style="position:absolute; inset:0; background:rgba(15, 23, 42, 0.78); backdrop-filter:blur(3px); -webkit-backdrop-filter:blur(3px); display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; padding:1.5rem; z-index:10;">
              <div style="width:58px; height:58px; border-radius:50%; background:linear-gradient(135deg, #f59e0b, #d97706); color:#fff; display:flex; align-items:center; justify-content:center; font-size:1.6rem; margin-bottom:0.75rem; box-shadow:0 0 25px rgba(245,158,11,0.5);">
                <i class="fa-solid fa-lock"></i>
              </div>
              <h3 style="color:#ffffff; font-size:1.3rem; font-weight:800; margin:0 0 0.35rem 0;">
                Solution Approved & Ready — Payment Required to Download
              </h3>
              <p style="color:#cbd5e1; font-size:0.88rem; max-width:560px; line-height:1.5; margin:0 0 1.1rem 0;">
                Your solution and Turnitin originality report have received final administrative approval. Clear your outstanding balance of <strong><?php echo format_currency_amount($asm['final_price'], $currency); ?></strong> to immediately unblur and download your completed assignment files.
              </p>
              <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap; justify-content:center;">
                <a href="/checkout.php?assignment_id=<?php echo urlencode($asm['assignment_id']); ?>" class="btn btn-primary btn-lg" style="font-size:1.05rem; padding:0.85rem 2.2rem; font-weight:800; background:linear-gradient(135deg, #6366f1, #4f46e5); box-shadow:0 6px 20px rgba(99,102,241,0.55); border:none; text-decoration:none;">
                  <i class="fa-solid fa-credit-card"></i> Pay Now (<?php echo format_currency_amount($asm['final_price'], $currency); ?>)
                </a>
              </div>
              <small style="color:#94a3b8; font-size:0.78rem; margin-top:0.6rem;">
                <i class="fa-solid fa-shield-halved"></i> 256-Bit SSL Encrypted &bull; Instant File Unlocking Upon Payment
              </small>
            </div>
          </div>
        </div>
      <?php else: ?>
        <!-- SUB-CASE: Admin Approved AND Paid -> CRYSTAL CLEAR WITH ACTIVE DOWNLOADS -->
        <div class="table-card" style="padding:1.5rem; border: 1.5px solid #10b981; margin-bottom:1.5rem;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem; flex-wrap:wrap; gap:1rem;">
            <div>
              <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                <span class="badge badge-success" style="font-weight:800; font-size:0.8rem;"><i class="fa-solid fa-circle-check"></i> Approved & Ready for Download</span>
                <span class="badge badge-info" style="font-weight:700; font-size:0.8rem;"><i class="fa-solid fa-receipt"></i> Payment Settled</span>
              </div>
              <h3 style="font-size:1.2rem; color:var(--text-main); margin:0;">Final Completed Solution Deliverables</h3>
            </div>
            <button class="btn btn-warning btn-sm" onclick="openModal('revisionModal')">
              <i class="fa-solid fa-rotate-left"></i> Request Free Revision
            </button>
          </div>

          <div style="display:flex; flex-direction:column; gap:10px;">
            <?php foreach ($solutionFiles as $file): 
              $ext = strtolower($file['file_type'] ?? '');
              $icon = 'fa-file';
              if (in_array($ext, ['pdf'])) $icon = 'fa-file-pdf';
              elseif (in_array($ext, ['doc', 'docx'])) $icon = 'fa-file-word';
              elseif (in_array($ext, ['xls', 'xlsx', 'csv'])) $icon = 'fa-file-excel';
              elseif (in_array($ext, ['ppt', 'pptx'])) $icon = 'fa-file-powerpoint';
              elseif (in_array($ext, ['zip', 'rar', 'tar', 'gz', '7z'])) $icon = 'fa-file-zipper';
              elseif (in_array($ext, ['py', 'java', 'cpp', 'c', 'js', 'html', 'css', 'sql', 'php', 'ipynb'])) $icon = 'fa-file-code';
              elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'])) $icon = 'fa-file-image';
              $isTurnitin = stripos($file['file_name'], 'turnitin') !== false;
            ?>
              <div style="display:flex; justify-content:space-between; align-items:center; background:#f0fdf4; border:1px solid #bbf7d0; padding:0.9rem 1.1rem; border-radius:var(--radius-sm); flex-wrap:wrap; gap:0.5rem;">
                <div>
                  <i class="fa-solid <?php echo $isTurnitin ? 'fa-chart-pie' : $icon; ?>" style="color:var(--success); margin-right:8px; font-size:1.2rem;"></i>
                  <strong style="color:var(--text-main); font-size:0.95rem;"><?php echo htmlspecialchars($file['file_name']); ?></strong>
                  <?php if ($isTurnitin): ?>
                    <span class="badge" style="background:#dbeafe; color:#1e40af; font-size:0.75rem; margin-left:6px;"><i class="fa-solid fa-shield-check"></i> Turnitin Originality Report</span>
                  <?php else: ?>
                    <span class="badge badge-success" style="font-size:0.75rem; margin-left:6px;"><i class="fa-solid fa-check"></i> Final Solution</span>
                  <?php endif; ?>
                  <small style="color:var(--text-muted); display:block; margin-top:2px;">Delivered &bull; <?php echo $file['upload_date']; ?></small>
                </div>
                <a href="/<?php echo htmlspecialchars($file['path']); ?>" download class="btn btn-success btn-sm" style="font-weight:700;">
                  <i class="fa-solid fa-download"></i> Download Deliverable
                </a>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <!-- Workflow Tracker -->
    <div class="table-card" style="padding:1.5rem;">
      <h3 style="font-size:1.1rem; margin-bottom:1.2rem; color:var(--text-main);"><i class="fa-solid fa-diagram-project" style="color:var(--primary);"></i> Assignment Progress Lifecycle</h3>
      <div class="timeline">
        <?php foreach ($workflow as $stepName => $validStatuses): 
          $isDone = in_array($asm['status'], $validStatuses);
        ?>
          <div class="timeline-item <?php echo $isDone ? 'completed' : ''; ?>">
            <div class="timeline-title"><?php echo $stepName; ?></div>
            <div class="timeline-desc">
              <?php echo $isDone ? 'Step verified and completed.' : 'Pending completion...'; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Requirements & Prompt -->
    <div class="table-card" style="padding:1.5rem;">
      <h3 style="font-size:1.1rem; margin-bottom:1rem; color:var(--text-main);"><i class="fa-solid fa-align-left" style="color:var(--secondary);"></i> Instructions & Prompt</h3>
      <p style="color:var(--text-main); white-space:pre-line; line-height:1.7; font-size:0.95rem;">
        <?php echo htmlspecialchars($asm['instructions']); ?>
      </p>
    </div>

    <!-- My Uploaded Brief & Initial Files (Always Visible & Downloadable) -->
    <div class="table-card" style="padding:1.5rem;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
        <h3 style="font-size:1.1rem; color:var(--text-main); margin:0;">
          <i class="fa-solid fa-paperclip" style="color:var(--primary);"></i> My Uploaded Brief & Files (<?php echo count($studentBriefFiles); ?>)
        </h3>
        <span class="badge badge-secondary" style="font-size:0.75rem;">Initial Materials</span>
      </div>
      <?php if (empty($studentBriefFiles)): ?>
        <p style="color:var(--text-muted); font-size:0.9rem;">No files uploaded with initial brief.</p>
      <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:10px;">
          <?php foreach ($studentBriefFiles as $file): 
            $ext = strtolower($file['file_type'] ?? '');
            $icon = 'fa-file';
            if (in_array($ext, ['pdf'])) $icon = 'fa-file-pdf';
            elseif (in_array($ext, ['doc', 'docx'])) $icon = 'fa-file-word';
            elseif (in_array($ext, ['xls', 'xlsx', 'csv'])) $icon = 'fa-file-excel';
            elseif (in_array($ext, ['ppt', 'pptx'])) $icon = 'fa-file-powerpoint';
            elseif (in_array($ext, ['zip', 'rar', 'tar', 'gz', '7z'])) $icon = 'fa-file-zipper';
            elseif (in_array($ext, ['py', 'java', 'cpp', 'c', 'js', 'html', 'css', 'sql', 'php', 'ipynb'])) $icon = 'fa-file-code';
            elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'])) $icon = 'fa-file-image';
          ?>
            <div style="display:flex; justify-content:space-between; align-items:center; background:#f8fafc; border:1px solid var(--portal-border); padding:0.8rem 1rem; border-radius:var(--radius-sm); flex-wrap:wrap; gap:0.5rem;">
              <div>
                <i class="fa-solid <?php echo $icon; ?>" style="color:var(--primary); margin-right:8px; font-size:1.1rem;"></i>
                <strong style="color:var(--text-main);"><?php echo htmlspecialchars($file['file_name']); ?></strong>
                <small style="color:var(--text-muted); margin-left:10px;">Uploaded by <?php echo htmlspecialchars($file['uploaded_by']); ?> &bull; <?php echo $file['upload_date']; ?></small>
              </div>
              <a href="/<?php echo htmlspecialchars($file['path']); ?>" download class="btn btn-outline btn-sm">
                <i class="fa-solid fa-download"></i> Download
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Upload Additional Files Form (Accepts Any Format) -->
    <div class="table-card" style="padding:1.5rem; margin-top:1.5rem;">
      <h3 style="font-size:1.1rem; margin-bottom:0.4rem; color:var(--text-main);">
        <i class="fa-solid fa-cloud-arrow-up" style="color:var(--primary);"></i> Upload Additional Files / Guidelines
      </h3>
      <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:1rem;">
        Need to add more files, data sets, lecture notes, or guidelines? Upload files here in <strong>ANY format</strong> (PDF, DOCX, ZIP, RAR, TXT, PY, IPYNB, XLS, PPTX, images, etc.). Multiple files supported.
      </p>
      <form id="studentAddFileForm" enctype="multipart/form-data">
        <input type="hidden" name="assignment_id" value="<?php echo htmlspecialchars($asm['assignment_id']); ?>">
        <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
          <input type="file" name="assignment_files[]" id="studentDetailFiles" multiple class="form-control" style="flex:1; min-width:240px;" required>
          <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-upload"></i> Upload Now</button>
        </div>
        <div id="studentAddFileMsg" style="margin-top:0.8rem;"></div>
      </form>
    </div>
  </div>

  <div>
    <!-- Summary Card -->
    <div class="table-card" style="padding:1.5rem;">
      <h3 style="font-size:1.1rem; margin-bottom:1.2rem; color:var(--text-main);"><i class="fa-solid fa-circle-info" style="color:var(--primary);"></i> Order Summary</h3>
      <table style="width:100%; font-size:0.9rem; border-collapse:collapse;">
        <tr style="border-bottom:1px solid var(--portal-border);"><td style="padding:8px 0; color:var(--text-muted);">Subject:</td><td style="text-align:right; font-weight:700; color:var(--text-main);"><?php echo htmlspecialchars($asm['subject']); ?></td></tr>
        <tr style="border-bottom:1px solid var(--portal-border);"><td style="padding:8px 0; color:var(--text-muted);">Type:</td><td style="text-align:right; color:var(--text-main);"><?php echo htmlspecialchars($asm['assignment_type']); ?></td></tr>
        <tr style="border-bottom:1px solid var(--portal-border);"><td style="padding:8px 0; color:var(--text-muted);">Word Count:</td><td style="text-align:right; color:var(--text-main);"><?php echo $asm['word_count']; ?> Words (<?php echo $asm['pages']; ?> Pages)</td></tr>
        <tr style="border-bottom:1px solid var(--portal-border);"><td style="padding:8px 0; color:var(--text-muted);">Referencing:</td><td style="text-align:right; color:var(--text-main);"><?php echo htmlspecialchars($asm['reference_style']); ?></td></tr>
        <tr style="border-bottom:1px solid var(--portal-border);"><td style="padding:8px 0; color:var(--text-muted);">Payment:</td><td style="text-align:right; color:var(--text-main); font-weight:700;">
          <?php if ($isPaid): ?>
            <span class="badge badge-success"><i class="fa-solid fa-check"></i> Paid In Full</span>
          <?php else: ?>
            <span class="badge badge-warning"><i class="fa-solid fa-clock"></i> Payment Pending</span>
          <?php endif; ?>
        </td></tr>
        <tr style="border-bottom:1px solid var(--portal-border);"><td style="padding:8px 0; color:var(--text-muted);">Timezone:</td><td style="text-align:right; color:var(--text-main);"><?php echo htmlspecialchars($asm['timezone']); ?></td></tr>
        <tr><td style="padding:12px 0; color:var(--text-muted); font-weight:700;">Final Investment:</td><td style="text-align:right; font-size:1.4rem; font-weight:800; color:var(--secondary);"><?php echo $currencySymbol . number_format($asm['final_price'], ($currency === 'INR' ? 0 : 2)); ?></td></tr>
      </table>

      <?php if (!$isPaid): ?>
        <div style="margin-top:1.25rem;">
          <a href="/checkout.php?assignment_id=<?php echo urlencode($asm['assignment_id']); ?>" class="btn btn-primary" style="width:100%; text-align:center; font-weight:800; padding:0.8rem; box-shadow:0 4px 15px rgba(99,102,241,0.4);">
            <i class="fa-solid fa-credit-card"></i> Proceed to Pay (<?php echo format_currency_amount($asm['final_price'], $currency); ?>)
          </a>
        </div>
      <?php endif; ?>
    </div>

    <!-- Privacy Compliance Notice -->
    <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:var(--radius-md); padding:1.2rem; font-size:0.85rem; color:#1e40af;">
      <h5 style="color:#1d4ed8; margin-bottom:0.4rem; font-size:0.95rem;"><i class="fa-solid fa-user-shield"></i> Privacy Protection Enforced</h5>
      <p style="line-height:1.5; margin:0;">As per strict platform security, expert writer contact details and internal allocator logs are hidden from student view to ensure complete anonymity.</p>
    </div>
  </div>
</div>

<!-- Revision Request Modal -->
<div id="revisionModal" class="modal-overlay">
  <div class="modal-box" style="padding:2rem;">
    <h3 style="margin-bottom:1rem; color:var(--text-main);"><i class="fa-solid fa-rotate-left" style="color:var(--warning);"></i> Submit Free Revision Request</h3>
    <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1.5rem;">Explain the adjustments needed for your completed assignment. Our allocator and expert team will re-examine your work immediately.</p>
    
    <div class="form-group">
      <label>Revision Details & Feedback *</label>
      <textarea id="revisionInstructionsInput" class="form-control" rows="3" placeholder="e.g. Please expand the evaluation section by adding 500 words on cross-validation folds..."></textarea>
    </div>

    <div class="form-group">
      <label><i class="fa-solid fa-cloud-arrow-up"></i> Attach Annotated Document / Feedback Files (Any Format)</label>
      <input type="file" id="revisionFileInput" name="revision_files[]" multiple class="form-control">
      <small style="color:var(--text-muted); font-size:0.8rem; display:block; margin-top:4px;">
        Upload marked drafts, professor rubrics, or notes in ANY format (PDF, DOCX, ZIP, etc.)
      </small>
    </div>

    <div id="revisionStatusMsg" style="margin-bottom:1rem;"></div>

    <div style="display:flex; gap:10px;">
      <button class="btn btn-warning" style="flex:1;" onclick="executeRevisionRequest()"><i class="fa-solid fa-paper-plane"></i> Submit Revision Request</button>
      <button class="btn btn-outline" onclick="closeModal('revisionModal')">Cancel</button>
    </div>
  </div>
</div>

<script>
function executeRevisionRequest() {
  const inst = document.getElementById('revisionInstructionsInput').value.trim();
  const msgDiv = document.getElementById('revisionStatusMsg');

  if (!inst) {
    msgDiv.innerHTML = '<div class="badge badge-danger">Please enter revision feedback details.</div>';
    return;
  }

  msgDiv.innerHTML = '<div class="badge badge-info"><i class="fa-solid fa-spinner fa-spin"></i> Submitting revision request...</div>';

  const fd = new FormData();
  fd.append('assignment_id', '<?php echo $asm['assignment_id']; ?>');
  fd.append('instructions', inst);

  const fileInput = document.getElementById('revisionFileInput');
  if (fileInput && fileInput.files.length > 0) {
    for (let i = 0; i < fileInput.files.length; i++) {
      fd.append('revision_files[]', fileInput.files[i]);
    }
  }

  fetch('/api.php?action=request_revision', {
    method: 'POST',
    body: fd
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      msgDiv.innerHTML = `<div class="badge badge-success">${data.message}</div>`;
      setTimeout(() => { window.location.reload(); }, 1200);
    } else {
      msgDiv.innerHTML = `<div class="badge badge-danger">${data.message || 'Revision request failed.'}</div>`;
    }
  })
  .catch(err => {
    msgDiv.innerHTML = `<div class="badge badge-danger">Connection error while submitting revision.</div>`;
  });
}

const studentAddFileForm = document.getElementById('studentAddFileForm');
if (studentAddFileForm) {
  studentAddFileForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const msg = document.getElementById('studentAddFileMsg');
    msg.innerHTML = '<div class="badge badge-info"><i class="fa-solid fa-spinner fa-spin"></i> Uploading files...</div>';
    fetch('/api.php?action=upload_assignment_file', {
      method: 'POST',
      body: new FormData(studentAddFileForm)
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
      msg.innerHTML = `<div class="badge badge-danger">An error occurred while uploading.</div>`;
    });
  });
}
</script>

<script src="/assets/js/portal.js"></script>
</div>
</div>
</body>
</html>
