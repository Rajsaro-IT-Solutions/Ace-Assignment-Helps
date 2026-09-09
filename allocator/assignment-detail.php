<?php
$pageTitle = "Allocator Assignment Control";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole(['Allocator', 'Admin']);
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
$sla = get_sla_status($asm['deadline']);

$internalNotes = DataStore::filter('notes', function($n) use ($id) {
    return isset($n['assignment_id']) && $n['assignment_id'] === $id;
});

$files = DataStore::filter('files', function($f) use ($id) {
    return isset($f['assignment_id']) && $f['assignment_id'] === $id;
});
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
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

    <!-- Uploaded Files List -->
    <div class="table-card" style="padding:1.5rem;">
      <h3 style="font-size:1.1rem; margin-bottom:1rem; color:var(--text-main);"><i class="fa-solid fa-paperclip" style="color:var(--success);"></i> Attached Files & Materials</h3>
      <?php if (empty($files)): ?>
        <p style="color:var(--text-muted); font-size:0.9rem;">No files uploaded yet for this assignment.</p>
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
            <div style="display:flex; justify-content:space-between; align-items:center; background:#f8fafc; border:1px solid var(--portal-border); padding:0.8rem 1rem; border-radius:var(--radius-sm); flex-wrap:wrap; gap:0.5rem;">
              <div>
                <i class="fa-solid <?php echo $icon; ?>" style="color:var(--primary); margin-right:8px; font-size:1.1rem;"></i>
                <strong style="color:var(--text-main);"><?php echo htmlspecialchars($file['file_name']); ?></strong>
                <?php if ($isInternal): ?>
                  <span class="badge badge-warning" style="font-size:0.7rem; margin-left:6px;">Staff Only</span>
                <?php else: ?>
                  <span class="badge badge-success" style="font-size:0.7rem; margin-left:6px;">Student Visible</span>
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
