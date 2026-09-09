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

$files = DataStore::filter('files', function($f) use ($id) {
    return isset($f['assignment_id']) && $f['assignment_id'] === $id && empty($f['is_internal']);
});

$payments = DataStore::filter('payments', function($p) use ($id) {
    return isset($p['assignment_id']) && $p['assignment_id'] === $id;
});
$isPaid = !empty($payments) && $payments[0]['status'] === 'Paid';

// Workflow steps array
$workflow = [
    'Student Submit' => ['New', 'Pending Review', 'Waiting for Payment', 'Confirmed', 'Allocated', 'In Progress', 'Quality Check', 'Completed', 'Delivered'],
    'Admin Review' => ['Pending Review', 'Waiting for Payment', 'Confirmed', 'Allocated', 'In Progress', 'Quality Check', 'Completed', 'Delivered'],
    'Allocator' => ['Confirmed', 'Allocated', 'In Progress', 'Quality Check', 'Completed', 'Delivered'],
    'Allocate Expert' => ['Allocated', 'In Progress', 'Quality Check', 'Completed', 'Delivered'],
    'Expert Working' => ['In Progress', 'Quality Check', 'Completed', 'Delivered'],
    'Quality Check' => ['Quality Check', 'Completed', 'Delivered'],
    'Upload Final Solution' => ['Completed', 'Delivered'],
    'Completed' => ['Completed', 'Delivered']
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

  <div style="display:flex; gap:10px;">
    <?php if (!$isPaid): ?>
      <button class="btn btn-primary" onclick="triggerPaymentModal('<?php echo $asm['assignment_id']; ?>', <?php echo $asm['final_price']; ?>)">
        <i class="fa-solid fa-credit-card"></i> Pay Now (<?php echo $currencySymbol . number_format($asm['final_price'], ($currency === 'INR' ? 0 : 2)); ?>)
      </button>
    <?php endif; ?>
    <?php if (in_array($asm['status'], ['Completed', 'Delivered'])): ?>
      <button class="btn btn-warning" onclick="openModal('revisionModal')">
        <i class="fa-solid fa-rotate-left"></i> Request Revision
      </button>
    <?php endif; ?>
    <a href="/student/invoice.php?id=<?php echo urlencode($asm['assignment_id']); ?>" class="btn btn-outline" target="_blank">
      <i class="fa-solid fa-file-invoice"></i> View Invoice
    </a>
  </div>
</div>

<div class="grid-2" style="display:grid; grid-template-columns: 2fr 1fr; gap:1.5rem;">
  <div>
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

    <!-- Attachments & Solution Files -->
    <div class="table-card" style="padding:1.5rem;">
      <h3 style="font-size:1.1rem; margin-bottom:1rem; color:var(--text-main);"><i class="fa-solid fa-paperclip" style="color:var(--success);"></i> Uploaded Files & Solution Downloads</h3>
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
        <tr style="border-bottom:1px solid var(--portal-border);"><td style="padding:8px 0; color:var(--text-muted);">Currency:</td><td style="text-align:right; color:var(--text-main); font-weight:700;"><?php echo htmlspecialchars($currency); ?></td></tr>
        <tr style="border-bottom:1px solid var(--portal-border);"><td style="padding:8px 0; color:var(--text-muted);">Timezone:</td><td style="text-align:right; color:var(--text-main);"><?php echo htmlspecialchars($asm['timezone']); ?></td></tr>
        <tr><td style="padding:12px 0; color:var(--text-muted); font-weight:700;">Final Investment:</td><td style="text-align:right; font-size:1.4rem; font-weight:800; color:var(--secondary);"><?php echo $currencySymbol . number_format($asm['final_price'], ($currency === 'INR' ? 0 : 2)); ?></td></tr>
      </table>
    </div>

    <!-- Privacy Compliance Notice -->
    <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:var(--radius-md); padding:1.2rem; font-size:0.85rem; color:#1e40af;">
      <h5 style="color:#1d4ed8; margin-bottom:0.4rem; font-size:0.95rem;"><i class="fa-solid fa-user-shield"></i> Privacy Protection Enforced</h5>
      <p style="line-height:1.5;">As per strict platform security, expert writer contact details and internal allocator logs are hidden from student view to ensure complete anonymity.</p>
    </div>
  </div>
</div>

<!-- Pay Now Modal -->
<div id="paymentModal" class="modal-overlay">
  <div class="modal-box" style="padding:2rem;">
    <h3 style="margin-bottom:1rem; color:var(--text-main);"><i class="fa-solid fa-lock" style="color:var(--success);"></i> Secure Payment Gateway</h3>
    <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1.5rem;">Choose your preferred payment method to authorize assignment processing:</p>
    
    <div class="form-group">
      <label>Payment Provider</label>
      <select id="payMethod" class="form-control">
        <option value="Stripe Credit Card">Stripe (International Visa / Mastercard)</option>
        <option value="Razorpay NetBanking">Razorpay (NetBanking / UPI)</option>
        <option value="PayPal">PayPal Checkout</option>
      </select>
    </div>

    <div style="background:#d1fae5; border:1px solid #6ee7b7; border-radius:var(--radius-sm); padding:1rem; text-align:center; margin-bottom:1.5rem;">
      <span style="font-size:0.8rem; color:#047857; font-weight:600;">Total Payable Amount:</span>
      <div style="font-family:var(--font-head); font-size:2rem; font-weight:800; color:#047857;" id="modalPayAmount">$0.00</div>
    </div>

    <div id="payStatusMsg" style="margin-bottom:1rem;"></div>

    <div style="display:flex; gap:10px;">
      <button class="btn btn-primary" style="flex:1;" onclick="executeSimulatedPayment()"><i class="fa-solid fa-check"></i> Authorize & Pay</button>
      <button class="btn btn-outline" onclick="closeModal('paymentModal')">Cancel</button>
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
      <textarea id="revisionInstructionsInput" class="form-control" rows="3" placeholder="e.g. Please expand the scikit-learn hyperparameter evaluation section by adding 500 words on cross-validation folds..."></textarea>
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
let currentPayAsmId = '';

function triggerPaymentModal(asmId, amount) {
  currentPayAsmId = asmId;
  document.getElementById('modalPayAmount').textContent = '$' + amount.toFixed(2);
  openModal('paymentModal');
}

function executeSimulatedPayment() {
  const method = document.getElementById('payMethod').value;
  const msgDiv = document.getElementById('payStatusMsg');
  msgDiv.innerHTML = '<div class="badge badge-info"><i class="fa-solid fa-spinner fa-spin"></i> Processing payment transaction...</div>';

  fetch('/api.php?action=pay_now', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `assignment_id=${encodeURIComponent(currentPayAsmId)}&payment_method=${encodeURIComponent(method)}`
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      msgDiv.innerHTML = `<div class="badge badge-success">${data.message}</div>`;
      setTimeout(() => { window.location.reload(); }, 1200);
    }
  });
}

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
    }
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
