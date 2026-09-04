<?php
$pageTitle = "Submit New Assignment";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Student');
$user = Auth::currentUser();
include __DIR__ . '/../includes/portal_header.php';
?>

<div style="max-width: 800px; margin: 0 auto;">
  <div class="calc-card" style="padding: 2rem;">
    <h3 style="margin-bottom:1.5rem; color:var(--primary);"><i class="fa-solid fa-file-circle-plus"></i> Student Assignment Order Form</h3>
    
    <form id="studentPortalSubmitForm" enctype="multipart/form-data">
      <div class="form-group">
        <label>Assignment Title *</label>
        <input type="text" name="title" class="form-control" required placeholder="e.g. Advanced Nursing Clinical Risk Assessment">
      </div>

      <div class="grid-3" style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:1.2rem;">
        <div class="form-group">
          <label>Subject Discipline *</label>
          <select name="subject" id="pSubject" class="form-control">
            <option value="Computer Science">Computer Science & IT</option>
            <option value="Business Management">Business & Management</option>
            <option value="Nursing & Healthcare">Nursing & Healthcare</option>
            <option value="Law & Legal Studies">Law & Legal Studies</option>
            <option value="General">General Academic</option>
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
          </select>
        </div>

        <div class="form-group">
          <label>Deadline Urgency</label>
          <select name="deadline_hours" id="pDeadline" class="form-control">
            <option value="12">12 Hours (Urgent)</option>
            <option value="24">24 Hours</option>
            <option value="48">48 Hours (2 Days)</option>
            <option value="72" selected>72 Hours (3 Days)</option>
            <option value="120">5 Days</option>
          </select>
        </div>
      </div>

      <div class="grid-3" style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:1.2rem;">
        <div class="form-group">
          <label>Word Count</label>
          <input type="number" name="word_count" id="pWords" class="form-control" value="1000" min="250" step="250">
        </div>

        <div class="form-group">
          <label>Reference Style</label>
          <select name="reference_style" class="form-control">
            <option value="APA 7th">APA 7th Edition</option>
            <option value="Harvard">Harvard</option>
            <option value="IEEE">IEEE</option>
            <option value="OSCOLA">OSCOLA (Law)</option>
            <option value="MLA">MLA</option>
          </select>
        </div>

        <div class="form-group">
          <label>Priority</label>
          <select name="priority" class="form-control">
            <option value="Normal">Normal</option>
            <option value="High">High Priority</option>
            <option value="Urgent">Urgent</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label>Instructions & Prompt Requirements *</label>
        <textarea name="instructions" class="form-control" rows="4" required placeholder="Provide clear prompt guidelines..."></textarea>
      </div>

      <div class="form-group">
        <label>Upload Assignment Prompt File (PDF, Docx, Zip)</label>
        <input type="file" name="assignment_file" class="form-control">
      </div>

      <div style="background:rgba(99, 102, 241, 0.1); border:1px solid var(--border-glow); border-radius:var(--radius-sm); padding:1rem; margin-top:1.5rem; display:flex; justify-content:space-between; align-items:center;">
        <div>
          <span style="color:var(--text-muted); font-size:0.85rem;">Estimated Price:</span>
          <div style="font-family:var(--font-head); font-size:1.8rem; font-weight:800; color:var(--secondary);" id="pPriceDisplay">$60.00</div>
        </div>
        <div style="display:flex; gap:8px;">
          <input type="text" name="discount_code" id="pCoupon" class="form-control" placeholder="Code (ACE20)" value="ACE20" style="max-width:140px;">
          <button type="button" id="btnPApply" class="btn btn-outline btn-sm">Apply</button>
        </div>
      </div>

      <div id="pSubmitMsg" style="margin-top:1rem;"></div>

      <button type="submit" class="btn btn-primary" style="width:100%; margin-top:1.5rem;"><i class="fa-solid fa-paper-plane"></i> Submit Order Now</button>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const pWords = document.getElementById('pWords');
  const pDeadline = document.getElementById('pDeadline');
  const pSubject = document.getElementById('pSubject');
  const pCoupon = document.getElementById('pCoupon');
  const btnPApply = document.getElementById('btnPApply');
  const pPriceDisplay = document.getElementById('pPriceDisplay');
  const form = document.getElementById('studentPortalSubmitForm');
  const pSubmitMsg = document.getElementById('pSubmitMsg');

  function updatePrice() {
    const w = parseInt(pWords.value) || 250;
    const d = parseInt(pDeadline.value) || 72;
    const s = pSubject.value;
    const c = pCoupon.value.trim();

    fetch(`/api.php?action=price_calc&word_count=${w}&deadline_hours=${d}&subject=${encodeURIComponent(s)}&coupon_code=${encodeURIComponent(c)}`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          pPriceDisplay.textContent = '$' + res.data.final_price.toFixed(2);
        }
      });
  }

  [pWords, pDeadline, pSubject].forEach(el => el.addEventListener('change', updatePrice));
  btnPApply.addEventListener('click', updatePrice);
  updatePrice();

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    pSubmitMsg.innerHTML = '<div class="badge badge-info"><i class="fa-solid fa-spinner fa-spin"></i> Submitting assignment order...</div>';

    fetch('/api.php?action=submit_assignment', {
      method: 'POST',
      body: new FormData(form)
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        pSubmitMsg.innerHTML = `<div class="badge badge-success">${data.message} Redirecting...</div>`;
        setTimeout(() => { window.location.href = '/student/assignments.php'; }, 1200);
      }
    });
  });
});
</script>

</div>
</div>
</body>
</html>
