<?php
$pageTitle = "Submit New Assignment";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Student');
$user = Auth::currentUser();
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';
?>

<div style="max-width: 860px; margin: 0 auto;">
  <div class="calc-card" style="padding: 2.2rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
      <div>
        <h3 style="margin:0; color:var(--primary);"><i class="fa-solid fa-file-circle-plus"></i> Submit New Assignment Order</h3>
        <p style="color:var(--text-muted); font-size:0.88rem; margin-top:4px;">Upload assignment briefs, choose your deadline, and get PhD-level assistance.</p>
      </div>
      <a href="/student/assignments.php" class="btn btn-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> My Assignments</a>
    </div>
    
    <form id="studentPortalSubmitForm" enctype="multipart/form-data">
      <div class="grid-2" style="display:grid; grid-template-columns:2fr 1fr; gap:1.2rem; margin-bottom:1.2rem;">
        <div class="form-group">
          <label>Assignment Title / Topic *</label>
          <input type="text" name="title" class="form-control" required placeholder="e.g. Advanced Nursing Clinical Risk Assessment">
        </div>

        <div class="form-group">
          <label>Billing Currency *</label>
          <select name="currency" id="pCurrency" class="form-control">
            <option value="USD" selected>USD ($)</option>
            <option value="INR">INR (₹)</option>
            <option value="GBP">GBP (£)</option>
            <option value="EUR">EUR (€)</option>
            <option value="AUD">AUD (A$)</option>
            <option value="CAD">CAD (C$)</option>
          </select>
        </div>
      </div>

      <div class="grid-3" style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:1.2rem; margin-bottom:1.2rem;">
        <div class="form-group">
          <label>Subject Discipline *</label>
          <select name="subject" id="pSubject" class="form-control">
            <?php 
            $allCourses = DataStore::getCollection('courses');
            $activeCourses = array_filter($allCourses, function($c) { return ($c['status'] ?? 'Active') === 'Active'; });
            if (!empty($activeCourses)):
              foreach ($activeCourses as $c):
            ?>
              <option value="<?php echo htmlspecialchars($c['title']); ?>"><?php echo htmlspecialchars($c['title']); ?></option>
            <?php 
              endforeach;
            else: 
            ?>
              <option value="Computer Science">Computer Science & IT</option>
              <option value="Business Management">Business & Management</option>
              <option value="Nursing & Healthcare">Nursing & Healthcare</option>
              <option value="Law & Legal Studies">Law & Legal Studies</option>
              <option value="Engineering">Engineering & Physics</option>
              <option value="Finance">Finance & Accounting</option>
              <option value="General">General Academic</option>
            <?php endif; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Assignment Type</label>
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
          <select name="deadline_hours" id="pDeadline" class="form-control">
            <option value="120" selected>5 Days (Standard - Base Rate)</option>
            <option value="96">4 Days (1 Day Expedited)</option>
            <option value="72">3 Days (72 Hours)</option>
            <option value="48">2 Days (48 Hours)</option>
            <option value="24">24 Hours (1 Day Urgent)</option>
            <option value="12">12 Hours (Super Urgent)</option>
            <option value="240">10 Days (Relaxed)</option>
          </select>
        </div>
      </div>

      <div class="grid-3" style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:1.2rem; margin-bottom:1.2rem;">
        <div class="form-group">
          <label><i class="fa-solid fa-file-lines"></i> Word Count (250 Words = 1 Page)</label>
          <select name="word_count" id="pWords" class="form-control">
            <?php echo render_word_count_options(1000); ?>
          </select>
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
          </select>
        </div>

        <div class="form-group">
          <label>Priority</label>
          <select name="priority" class="form-control">
            <option value="Normal">Normal</option>
            <option value="High">High Priority</option>
            <option value="Urgent">Urgent Priority</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label>Instructions & Prompt Requirements *</label>
        <textarea name="instructions" class="form-control" rows="4" required placeholder="Provide clear prompt guidelines, rubrics, formatting rules, or special guidelines..."></textarea>
      </div>

      <!-- File Upload Accepting Any Format -->
      <div class="form-group">
        <label><i class="fa-solid fa-cloud-arrow-up"></i> Upload Assignment Files (Any Format Accepted)</label>
        <div style="border: 2px dashed var(--portal-border); padding: 1.5rem; border-radius: var(--radius-sm); text-align: center; background: #f8fafc;">
          <input type="file" name="assignment_files[]" id="pAssignmentFiles" class="form-control" multiple style="max-width:460px; margin: 0 auto;">
          <p style="color:var(--text-muted); font-size:0.83rem; margin-top:0.6rem; margin-bottom:0;">
            Accepts <strong>ANY</strong> format: PDF, DOCX, ZIP, RAR, TXT, PY, IPYNB, JAVA, CPP, XLSX, PPTX, Images, audio, etc. (Multiple files supported)
          </p>
          <div id="pFileSummary" style="margin-top:0.6rem; font-size:0.85rem; color:var(--primary); font-weight:600;"></div>
        </div>
      </div>

      <!-- Price Breakdown Card -->
      <div style="background:rgba(99, 102, 241, 0.08); border:1px solid var(--border-glow); border-radius:var(--radius-sm); padding:1.2rem; margin-top:1.5rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
          <div>
            <span style="color:var(--text-muted); font-size:0.85rem;">Estimated Investment:</span>
            <div style="font-family:var(--font-head); font-size:2rem; font-weight:800; color:var(--secondary);" id="pPriceDisplay">$11.00</div>
            <small id="pRateBreakdown" style="display:block; color:var(--text-dim); font-size:0.8rem; margin-top:2px;"></small>
          </div>
          <div style="display:flex; gap:8px; align-items:center;">
            <input type="text" name="discount_code" id="pCoupon" class="form-control" placeholder="Code (ACE20)" value="ACE20" style="max-width:140px; text-transform:uppercase;">
            <button type="button" id="btnPApply" class="btn btn-outline btn-sm">Apply</button>
          </div>
        </div>
        <div id="pDiscountNote" style="color:var(--success); font-size:0.85rem; font-weight:700; margin-top:0.4rem;"></div>
      </div>

      <div id="pSubmitMsg" style="margin-top:1.2rem;"></div>

      <button type="submit" class="btn btn-primary btn-lg" style="width:100%; margin-top:1.2rem;">
        <i class="fa-solid fa-paper-plane"></i> Submit Order Now
      </button>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const pWords = document.getElementById('pWords');
  const pDeadline = document.getElementById('pDeadline');
  const pSubject = document.getElementById('pSubject');
  const pCurrency = document.getElementById('pCurrency');
  const pCoupon = document.getElementById('pCoupon');
  const btnPApply = document.getElementById('btnPApply');
  const pPriceDisplay = document.getElementById('pPriceDisplay');
  const pRateBreakdown = document.getElementById('pRateBreakdown');
  const pDiscountNote = document.getElementById('pDiscountNote');
  const pFileInput = document.getElementById('pAssignmentFiles');
  const pFileSummary = document.getElementById('pFileSummary');
  const form = document.getElementById('studentPortalSubmitForm');
  const pSubmitMsg = document.getElementById('pSubmitMsg');

  // Show selected files list
  pFileInput.addEventListener('change', () => {
    if (pFileInput.files.length > 0) {
      const names = Array.from(pFileInput.files).map(f => f.name).join(', ');
      pFileSummary.innerHTML = `<i class="fa-solid fa-check-circle" style="color:var(--success);"></i> ${pFileInput.files.length} file(s) selected: <span style="font-weight:normal; color:var(--text-main);">${names}</span>`;
    } else {
      pFileSummary.innerHTML = '';
    }
  });

  function updatePrice() {
    const w = parseInt(pWords.value) || 250;
    const d = parseFloat(pDeadline.value) || 120;
    const s = pSubject.value || 'General';
    const curr = pCurrency.value || 'USD';
    const c = pCoupon.value.trim().toUpperCase();

    fetch(`/api.php?action=price_calc&word_count=${w}&deadline_hours=${d}&subject=${encodeURIComponent(s)}&currency=${curr}&coupon_code=${encodeURIComponent(c)}`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          const data = res.data;
          const formatted = (data.currency === 'INR')
            ? `${data.currency_symbol}${Math.round(data.final_price).toLocaleString('en-IN')}`
            : `${data.currency_symbol}${data.final_price.toFixed(2)}`;

          pPriceDisplay.textContent = formatted;
          const effRateFormatted = (data.currency === 'INR') ? data.effective_rate_per_word.toFixed(2) : data.effective_rate_per_word.toFixed(3);
          const baseRateFormatted = (data.currency === 'INR') ? data.base_rate_per_word.toFixed(2) : data.base_rate_per_word.toFixed(3);
          pRateBreakdown.textContent = `Rate: ${data.currency_symbol}${effRateFormatted}/word (Base: ${data.currency_symbol}${baseRateFormatted} for 3+ days)`;

          if (data.discount_percent > 0) {
            pDiscountNote.style.color = 'var(--success)';
            const discFmt = (data.currency === 'INR') ? Math.round(data.discount_amount).toLocaleString('en-IN') : data.discount_amount.toFixed(2);
            pDiscountNote.textContent = `✓ ${data.discount_percent}% Discount Applied (-${data.currency_symbol}${discFmt})`;
          } else if (c) {
            pDiscountNote.style.color = '#ef4444';
            pDiscountNote.textContent = `✗ ${data.coupon_message || 'Invalid or expired coupon'}`;
          } else {
            pDiscountNote.textContent = '';
          }
        }
      });
  }

  [pWords, pDeadline, pSubject, pCurrency].forEach(el => {
    el.addEventListener('change', updatePrice);
    el.addEventListener('input', updatePrice);
  });
  pCoupon.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      updatePrice();
    }
  });
  let couponTimer = null;
  pCoupon.addEventListener('input', () => {
    clearTimeout(couponTimer);
    couponTimer = setTimeout(updatePrice, 400);
  });
  btnPApply.addEventListener('click', updatePrice);
  updatePrice();

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    pSubmitMsg.innerHTML = '<div class="badge badge-info" style="display:block; padding:0.8rem;"><i class="fa-solid fa-spinner fa-spin"></i> Submitting assignment order and uploading files...</div>';

    fetch('/api.php?action=submit_assignment', {
      method: 'POST',
      body: new FormData(form)
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        pSubmitMsg.innerHTML = `<div class="badge badge-success" style="display:block; padding:0.8rem; font-size:0.95rem;"><i class="fa-solid fa-circle-check"></i> ${data.message} Opening payment checkout...</div>`;
        setTimeout(() => { window.location.href = `/checkout.php?assignment_id=${encodeURIComponent(data.assignment_id)}`; }, 900);
      } else {
        pSubmitMsg.innerHTML = `<div class="badge badge-danger" style="display:block; padding:0.8rem;">${data.message || 'Submission error'}</div>`;
      }
    })
    .catch(err => {
      pSubmitMsg.innerHTML = `<div class="badge badge-danger" style="display:block; padding:0.8rem;">An error occurred during submission.</div>`;
    });
  });
});
</script>

</div>
</div>
</body>
</html>

