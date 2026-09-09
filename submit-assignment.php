<?php
$pageTitle = "Order Academic Assignment Writing Help";
require_once __DIR__ . '/includes/auth.php';
$user = Auth::currentUser();
include __DIR__ . '/includes/header.php';

$getCurrency = strtoupper(trim($_GET['currency'] ?? 'USD'));
$getWords = (int)($_GET['words'] ?? 1000);
if ($getWords < 250) $getWords = 1000;
$getDeadline = (int)($_GET['deadline'] ?? 120);
$getCoupon = strtoupper(trim($_GET['coupon'] ?? 'ACE20'));
?>

<div class="container" style="padding-top: 3rem; padding-bottom: 5rem; max-width: 960px;">
  <div class="section-header" style="margin-bottom: 2.5rem;">
    <div class="badge badge-primary" style="margin-bottom: 0.8rem;">Instant Assignment Submission</div>
    <h2>Submit Your Assignment Requirements</h2>
    <p>Get matched with a PhD specialist in your subject. 100% confidential, secure checkout, Turnitin report included.</p>
  </div>

  <form id="studentSubmitForm" class="calc-card" style="padding: 2.5rem; background: #ffffff;" enctype="multipart/form-data">
    <!-- Step 1: Contact Details -->
    <h3 style="border-bottom:1px solid var(--border-color); padding-bottom:0.8rem; margin-bottom:1.5rem; color:var(--primary);">
      <i class="fa-solid fa-user-check"></i> 1. Contact & Currency Preferences
    </h3>

    <div class="grid-2" style="display:grid; grid-template-columns:1fr 1fr; gap:1.2rem; margin-bottom:1.2rem;">
      <div class="form-group">
        <label>Full Name *</label>
        <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" placeholder="e.g. Sarah Jenkins">
      </div>

      <div class="form-group">
        <label>University Email *</label>
        <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="sarah@university.edu">
      </div>
    </div>

    <div class="grid-4" style="display:grid; grid-template-columns:1.2fr 1fr 1fr 1.2fr; gap:1.2rem; margin-bottom:2rem;">
      <div class="form-group">
        <label>Phone / WhatsApp *</label>
        <input type="tel" name="phone" class="form-control" required value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+1 (555) 000-0000">
      </div>

      <div class="form-group">
        <label>Country *</label>
        <select name="country" id="formCountry" class="form-control">
          <option value="United States" <?php echo ($getCurrency === 'USD') ? 'selected' : ''; ?>>United States</option>
          <option value="India" <?php echo ($getCurrency === 'INR') ? 'selected' : ''; ?>>India</option>
          <option value="United Kingdom" <?php echo ($getCurrency === 'GBP') ? 'selected' : ''; ?>>United Kingdom</option>
          <option value="Australia" <?php echo ($getCurrency === 'AUD') ? 'selected' : ''; ?>>Australia</option>
          <option value="Canada" <?php echo ($getCurrency === 'CAD') ? 'selected' : ''; ?>>Canada</option>
          <option value="Ireland" <?php echo ($getCurrency === 'EUR') ? 'selected' : ''; ?>>Ireland / EU</option>
          <option value="Other">Other</option>
        </select>
      </div>

      <div class="form-group">
        <label>Billing Currency *</label>
        <select name="currency" id="formCurrency" class="form-control">
          <option value="USD" <?php echo ($getCurrency === 'USD') ? 'selected' : ''; ?>>USD ($)</option>
          <option value="INR" <?php echo ($getCurrency === 'INR') ? 'selected' : ''; ?>>INR (₹)</option>
          <option value="GBP" <?php echo ($getCurrency === 'GBP') ? 'selected' : ''; ?>>GBP (£)</option>
          <option value="EUR" <?php echo ($getCurrency === 'EUR') ? 'selected' : ''; ?>>EUR (€)</option>
          <option value="AUD" <?php echo ($getCurrency === 'AUD') ? 'selected' : ''; ?>>AUD (A$)</option>
          <option value="CAD" <?php echo ($getCurrency === 'CAD') ? 'selected' : ''; ?>>CAD (C$)</option>
        </select>
      </div>

      <div class="form-group">
        <label>University Name</label>
        <input type="text" name="university" class="form-control" placeholder="e.g. Stanford University">
      </div>
    </div>

    <!-- Step 2: Assignment Information -->
    <h3 style="border-bottom:1px solid var(--border-color); padding-bottom:0.8rem; margin-bottom:1.5rem; color:var(--primary);">
      <i class="fa-solid fa-file-signature"></i> 2. Assignment Details & Deadline
    </h3>

    <div class="form-group">
      <label>Assignment Title / Topic *</label>
      <input type="text" name="title" class="form-control" required placeholder="e.g. Machine Learning Hyperparameter Optimization Paper">
    </div>

    <div class="grid-3" style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:1.2rem; margin-bottom:1.2rem;">
      <div class="form-group">
        <label>Subject Discipline *</label>
        <select name="subject" id="formSubject" class="form-control">
          <option value="Computer Science">Computer Science & IT</option>
          <option value="Business Management">Business & Management</option>
          <option value="Nursing & Healthcare">Nursing & Healthcare</option>
          <option value="Law & Legal Studies">Law & Legal Studies</option>
          <option value="Engineering">Engineering & Physics</option>
          <option value="Finance">Finance & Accounting</option>
          <option value="General">General Academic</option>
        </select>
      </div>

      <div class="form-group">
        <label>Assignment Type *</label>
        <select name="assignment_type" class="form-control">
          <option value="Essay">Essay</option>
          <option value="Research Paper">Research Paper</option>
          <option value="Case Study">Case Study</option>
          <option value="Dissertation">Dissertation / Thesis</option>
          <option value="Programming / Code">Programming / Code</option>
          <option value="Coursework">Coursework</option>
        </select>
      </div>

      <div class="form-group">
        <label>Deadline Urgency *</label>
        <select name="deadline_hours" id="formDeadline" class="form-control">
          <option value="120" <?php echo ($getDeadline === 120) ? 'selected' : ''; ?>>5 Days (Standard - Base Rate)</option>
          <option value="96" <?php echo ($getDeadline === 96) ? 'selected' : ''; ?>>4 Days (1 Day Expedited)</option>
          <option value="72" <?php echo ($getDeadline === 72) ? 'selected' : ''; ?>>3 Days (72 Hours)</option>
          <option value="48" <?php echo ($getDeadline === 48) ? 'selected' : ''; ?>>2 Days (48 Hours)</option>
          <option value="24" <?php echo ($getDeadline === 24) ? 'selected' : ''; ?>>24 Hours (Urgent)</option>
          <option value="12" <?php echo ($getDeadline === 12) ? 'selected' : ''; ?>>12 Hours (Super Urgent)</option>
          <option value="240" <?php echo ($getDeadline === 240) ? 'selected' : ''; ?>>10 Days (Relaxed)</option>
        </select>
      </div>
    </div>

    <div class="grid-3" style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:1.2rem; margin-bottom:1.2rem;">
      <div class="form-group">
        <label>Word Count (Approx) *</label>
        <input type="number" name="word_count" id="formWords" class="form-control" value="<?php echo $getWords; ?>" min="250" step="250">
      </div>

      <div class="form-group">
        <label>Reference Style</label>
        <select name="reference_style" class="form-control">
          <option value="APA 7th">APA 7th Edition</option>
          <option value="Harvard">Harvard</option>
          <option value="IEEE">IEEE</option>
          <option value="OSCOLA">OSCOLA (Law)</option>
          <option value="MLA">MLA</option>
          <option value="Chicago">Chicago</option>
          <option value="None">None / Standard</option>
        </select>
      </div>

      <div class="form-group">
        <label>Priority Level</label>
        <select name="priority" class="form-control">
          <option value="Normal">Normal</option>
          <option value="High">High Priority</option>
          <option value="Urgent">Urgent Priority</option>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label>Detailed Instructions & Requirements *</label>
      <textarea name="instructions" class="form-control" rows="4" required placeholder="Paste full prompt, rubrics, formatting rules, or special guidelines here..."></textarea>
    </div>

    <!-- Step 3: File Uploads (Supports Any Format) -->
    <h3 style="border-bottom:1px solid var(--border-color); padding-bottom:0.8rem; margin-bottom:1.5rem; margin-top:2rem; color:var(--primary);">
      <i class="fa-solid fa-cloud-arrow-up"></i> 3. Upload Files (Any Format Accepted)
    </h3>

    <div class="form-group">
      <div style="border: 2px dashed var(--portal-border); padding: 2rem; border-radius: var(--radius-sm); text-align: center; background: #f8fafc;">
        <i class="fa-solid fa-cloud-arrow-up" style="font-size: 2.5rem; color: var(--primary); margin-bottom: 0.8rem;"></i>
        <p style="color:var(--text-main); font-weight:700; margin-bottom:0.3rem;">Drag & drop your files here, or click to browse</p>
        <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:1rem;">
          Accepts <strong>ANY</strong> format: PDF, DOC, DOCX, ZIP, RAR, PPT, XLS, XLSX, JPG, PNG, PY, JAVA, CPP, IPYNB, SQL, TXT, etc. (Multiple files supported)
        </p>
        <input type="file" name="assignment_files[]" id="assignment_files" class="form-control" multiple style="max-width:400px; margin: 0 auto;">
        <div id="fileListSummary" style="margin-top:1rem; font-size:0.88rem; color:var(--primary); font-weight:600;"></div>
      </div>
    </div>

    <!-- Step 4: Price & Payment Overview -->
    <div style="background: rgba(79, 70, 229, 0.06); border: 1px solid var(--portal-border); border-radius: var(--radius-md); padding: 1.5rem; margin-top: 2rem;">
      <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
        <div>
          <h4 style="font-size:1.1rem; color:var(--text-main); margin-bottom:0.3rem;">Estimated Total Investment:</h4>
          <span style="color:var(--text-muted); font-size:0.9rem;" id="formPagesText">4 Pages (1000 Words)</span>
          <small id="formRateDetails" style="display:block; color:var(--text-dim); margin-top:2px; font-size:0.8rem;"></small>
        </div>
        <div style="text-align:right;">
          <div style="font-size:2.4rem; font-weight:800; color:var(--secondary);" id="formPriceText">$11.00</div>
          <span style="color:var(--success); font-size:0.85rem; font-weight:700;" id="formDiscountBadge"></span>
        </div>
      </div>

      <div style="display:flex; gap:10px; margin-top:1.2rem; align-items:center;">
        <input type="text" name="discount_code" id="formCoupon" class="form-control" placeholder="Promo Code (e.g. ACE20)" value="<?php echo htmlspecialchars($getCoupon); ?>" style="max-width:220px; text-transform:uppercase;">
        <button type="button" id="btnApplyCoupon" class="btn btn-outline btn-sm">Apply Code</button>
      </div>
    </div>

    <div style="margin-top:1.5rem; display:flex; align-items:center; gap:10px;">
      <input type="checkbox" id="termsCheck" required checked style="width:18px; height:18px;">
      <label for="termsCheck" style="font-size:0.85rem; color:var(--text-muted); margin:0;">
        I agree to the <a href="/terms.php" style="color:var(--primary);">Terms of Service</a> and <a href="/privacy.php" style="color:var(--primary);">Privacy Policy</a>.
      </label>
    </div>

    <div id="submitResultMsg" style="margin-top:1.2rem;"></div>

    <button type="submit" class="btn btn-primary btn-lg" style="width:100%; margin-top:1.5rem;">
      <i class="fa-solid fa-paper-plane"></i> Confirm & Submit Assignment Order
    </button>
  </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('studentSubmitForm');
  const formWords = document.getElementById('formWords');
  const formDeadline = document.getElementById('formDeadline');
  const formSubject = document.getElementById('formSubject');
  const formCountry = document.getElementById('formCountry');
  const formCurrency = document.getElementById('formCurrency');
  const formCoupon = document.getElementById('formCoupon');
  const btnApplyCoupon = document.getElementById('btnApplyCoupon');
  const formPriceText = document.getElementById('formPriceText');
  const formPagesText = document.getElementById('formPagesText');
  const formRateDetails = document.getElementById('formRateDetails');
  const formDiscountBadge = document.getElementById('formDiscountBadge');
  const fileInput = document.getElementById('assignment_files');
  const fileListSummary = document.getElementById('fileListSummary');
  const submitResultMsg = document.getElementById('submitResultMsg');

  // Auto-switch currency when country changes
  formCountry.addEventListener('change', () => {
    const c = formCountry.value;
    if (c === 'India') formCurrency.value = 'INR';
    else if (c === 'United Kingdom') formCurrency.value = 'GBP';
    else if (c === 'United States') formCurrency.value = 'USD';
    else if (c === 'Australia') formCurrency.value = 'AUD';
    else if (c === 'Canada') formCurrency.value = 'CAD';
    else if (c === 'Ireland') formCurrency.value = 'EUR';
    updatePrice();
  });

  // Display selected files list
  fileInput.addEventListener('change', () => {
    if (fileInput.files.length > 0) {
      const names = Array.from(fileInput.files).map(f => f.name).join(', ');
      fileListSummary.innerHTML = `<i class="fa-solid fa-check-circle" style="color:var(--success);"></i> Selected ${fileInput.files.length} file(s): <span style="color:var(--text-main); font-weight:normal;">${names}</span>`;
    } else {
      fileListSummary.innerHTML = '';
    }
  });

  function updatePrice() {
    const words = parseInt(formWords.value) || 250;
    const deadlineHours = parseFloat(formDeadline.value) || 120;
    const subject = formSubject.value || 'General';
    const currency = formCurrency.value || 'USD';
    const coupon = formCoupon.value.trim().toUpperCase();

    fetch(`/api.php?action=price_calc&word_count=${words}&deadline_hours=${deadlineHours}&subject=${encodeURIComponent(subject)}&currency=${currency}&coupon_code=${encodeURIComponent(coupon)}`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          const d = res.data;
          const formatted = (d.currency === 'INR')
            ? `${d.currency_symbol}${Math.round(d.final_price).toLocaleString('en-IN')}`
            : `${d.currency_symbol}${d.final_price.toFixed(2)}`;

          formPriceText.textContent = formatted;
          formPagesText.textContent = `${d.pages} Pages (${d.word_count} Words)`;
          const effRateFormatted = (d.currency === 'INR') ? d.effective_rate_per_word.toFixed(2) : d.effective_rate_per_word.toFixed(3);
          const baseRateFormatted = (d.currency === 'INR') ? d.base_rate_per_word.toFixed(2) : d.base_rate_per_word.toFixed(3);
          formRateDetails.textContent = `Rate: ${d.currency_symbol}${effRateFormatted}/word (Base: ${d.currency_symbol}${baseRateFormatted} for 3+ days)`;

          if (d.discount_percent > 0) {
            formDiscountBadge.textContent = `✓ ${d.discount_percent}% Discount Applied (-${d.currency_symbol}${d.discount_amount.toFixed(2)})`;
          } else {
            formDiscountBadge.textContent = '';
          }
        }
      });
  }

  [formWords, formDeadline, formSubject, formCurrency].forEach(el => {
    el.addEventListener('change', updatePrice);
    el.addEventListener('input', updatePrice);
  });
  btnApplyCoupon.addEventListener('click', updatePrice);
  updatePrice();

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    submitResultMsg.innerHTML = '<div class="badge badge-info" style="display:block; padding:0.8rem;"><i class="fa-solid fa-spinner fa-spin"></i> Submitting assignment order and uploading files...</div>';

    const formData = new FormData(form);

    fetch('/api.php?action=submit_assignment', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        submitResultMsg.innerHTML = `<div class="badge badge-success" style="font-size:1rem; padding:1rem; display:block;"><i class="fa-solid fa-circle-check"></i> ${data.message} Redirecting to your assignments...</div>`;
        setTimeout(() => {
          window.location.href = `/student/assignments.php`;
        }, 1500);
      } else {
        submitResultMsg.innerHTML = `<div class="badge badge-danger" style="display:block; padding:0.8rem;">${data.message || 'Submission error'}</div>`;
      }
    })
    .catch(err => {
      submitResultMsg.innerHTML = `<div class="badge badge-danger" style="display:block; padding:0.8rem;">An error occurred during submission.</div>`;
    });
  });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
