<?php
$pageTitle = "Submit Assignment";
include __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/auth.php';
$user = Auth::currentUser();
?>

<div class="container" style="max-width: 900px; padding-top: 2rem;">
  <div style="text-align:center; margin-bottom: 2.5rem;">
    <div class="badge badge-primary" style="margin-bottom:0.8rem;"><i class="fa-solid fa-paper-plane"></i> Quick 2-Minute Submission</div>
    <h1 style="font-size:2.5rem; margin-bottom:0.5rem;">Submit Your Assignment Order</h1>
    <p style="color:var(--text-muted);">Fill out your requirements below. Our automated system calculates instant pricing and assigns a top expert immediately.</p>
  </div>

  <form id="studentSubmitForm" enctype="multipart/form-data" class="calc-card" style="padding:2.5rem;">
    
    <!-- Step 1: Personal Information -->
    <h3 style="border-bottom:1px solid var(--border-color); padding-bottom:0.8rem; margin-bottom:1.5rem; color:var(--secondary);">
      <i class="fa-solid fa-user"></i> 1. Personal Information
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

    <div class="grid-3" style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:1.2rem; margin-bottom:2rem;">
      <div class="form-group">
        <label>Phone / WhatsApp Number *</label>
        <input type="tel" name="phone" class="form-control" required value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+1 (555) 000-0000">
      </div>

      <div class="form-group">
        <label>Country *</label>
        <select name="country" class="form-control">
          <option value="United States" selected>United States</option>
          <option value="United Kingdom">United Kingdom</option>
          <option value="Australia">Australia</option>
          <option value="Canada">Canada</option>
          <option value="Germany">Germany</option>
          <option value="India">India</option>
          <option value="Other">Other</option>
        </select>
      </div>

      <div class="form-group">
        <label>University Name</label>
        <input type="text" name="university" class="form-control" placeholder="e.g. Stanford University">
      </div>
    </div>

    <!-- Step 2: Assignment Information -->
    <h3 style="border-bottom:1px solid var(--border-color); padding-bottom:0.8rem; margin-bottom:1.5rem; color:var(--primary);">
      <i class="fa-solid fa-file-signature"></i> 2. Assignment Details
    </h3>

    <div class="form-group">
      <label>Assignment Title *</label>
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
          <option value="12">12 Hours (Urgent)</option>
          <option value="24">24 Hours</option>
          <option value="48">48 Hours (2 Days)</option>
          <option value="72" selected>72 Hours (3 Days)</option>
          <option value="120">5 Days</option>
          <option value="240">10 Days</option>
        </select>
      </div>
    </div>

    <div class="grid-3" style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:1.2rem; margin-bottom:1.2rem;">
      <div class="form-group">
        <label>Word Count (Approx) *</label>
        <input type="number" name="word_count" id="formWords" class="form-control" value="1000" min="250" step="250">
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
          <option value="High">High Priority (+10%)</option>
          <option value="Urgent">Urgent Priority (+25%)</option>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label>Detailed Instructions & Requirements *</label>
      <textarea name="instructions" class="form-control" rows="4" required placeholder="Paste full prompt, rubrics, formatting rules, or special guidelines here..."></textarea>
    </div>

    <!-- Step 3: File Uploads -->
    <h3 style="border-bottom:1px solid var(--border-color); padding-bottom:0.8rem; margin-bottom:1.5rem; margin-top:2rem; color:var(--accent);">
      <i class="fa-solid fa-cloud-arrow-up"></i> 3. Upload Files (Assignment PDF, Data, Images, Zip)
    </h3>

    <div class="form-group">
      <div style="border: 2px dashed var(--border-glow); padding: 2rem; border-radius: var(--radius-sm); text-align: center; background: rgba(15, 23, 42, 0.5);">
        <i class="fa-solid fa-file-pdf" style="font-size: 2.5rem; color: var(--primary); margin-bottom: 0.8rem;"></i>
        <p style="color:var(--text-muted); margin-bottom:0.8rem;">Drag & drop your files here or click to browse</p>
        <input type="file" name="assignment_file" id="assignment_file" class="form-control" style="max-width:350px; margin: 0 auto;">
      </div>
    </div>

    <!-- Step 4: Price & Payment Overview -->
    <div style="background: rgba(99, 102, 241, 0.08); border: 1px solid var(--border-glow); border-radius: var(--radius-md); padding: 1.5rem; margin-top: 2rem;">
      <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
        <div>
          <h4 style="font-size:1.2rem; color:#fff;">Estimated Investment:</h4>
          <span style="color:var(--text-muted); font-size:0.9rem;" id="formPagesText">4 Pages (1000 Words)</span>
        </div>
        <div style="text-align:right;">
          <div style="font-family:var(--font-head); font-size:2.2rem; font-weight:800; color:var(--secondary);" id="formPriceText">$60.00</div>
          <span style="color:var(--success); font-size:0.8rem; font-weight:700;" id="formDiscountBadge"></span>
        </div>
      </div>

      <div style="display:flex; gap:10px; margin-top:1rem;">
        <input type="text" name="discount_code" id="formCoupon" class="form-control" placeholder="Promo / Discount Code" value="ACE20" style="max-width:220px;">
        <button type="button" id="btnApplyCoupon" class="btn btn-outline btn-sm">Apply Code</button>
      </div>
    </div>

    <div style="margin-top:1.5rem; display:flex; align-items:center; gap:10px;">
      <input type="checkbox" id="termsCheck" required checked style="width:18px; height:18px;">
      <label for="termsCheck" style="font-size:0.85rem; color:var(--text-muted);">
        I agree to the <a href="/terms.php" style="color:var(--primary);">Terms of Service</a> and <a href="/privacy.php" style="color:var(--primary);">Privacy Policy</a>.
      </label>
    </div>

    <div id="submitResultMsg" style="margin-top:1rem;"></div>

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
  const formCoupon = document.getElementById('formCoupon');
  const btnApplyCoupon = document.getElementById('btnApplyCoupon');
  const formPriceText = document.getElementById('formPriceText');
  const formPagesText = document.getElementById('formPagesText');
  const formDiscountBadge = document.getElementById('formDiscountBadge');
  const submitResultMsg = document.getElementById('submitResultMsg');

  function updatePrice() {
    const words = parseInt(formWords.value) || 250;
    const pages = Math.ceil(words / 250);
    const deadlineHours = parseInt(formDeadline.value) || 72;
    const subject = formSubject.value || 'General';
    const coupon = formCoupon.value.trim().toUpperCase();

    fetch(`/api.php?action=price_calc&word_count=${words}&deadline_hours=${deadlineHours}&subject=${encodeURIComponent(subject)}&coupon_code=${encodeURIComponent(coupon)}`)
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          formPriceText.textContent = '$' + res.data.final_price.toFixed(2);
          formPagesText.textContent = `${res.data.pages} Pages (${res.data.word_count} Words)`;
          if (res.data.discount_percent > 0) {
            formDiscountBadge.textContent = `✨ ${res.data.discount_percent}% Discount Applied (-\$${res.data.discount_amount.toFixed(2)})`;
          } else {
            formDiscountBadge.textContent = '';
          }
        }
      });
  }

  [formWords, formDeadline, formSubject].forEach(el => {
    el.addEventListener('change', updatePrice);
    el.addEventListener('input', updatePrice);
  });
  btnApplyCoupon.addEventListener('click', updatePrice);
  updatePrice();

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    submitResultMsg.innerHTML = '<div class="badge badge-info"><i class="fa-solid fa-spinner fa-spin"></i> Submitting assignment order...</div>';

    const formData = new FormData(form);

    fetch('/api.php?action=submit_assignment', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        submitResultMsg.innerHTML = `<div class="badge badge-success" style="font-size:1rem; padding:0.8rem 1.2rem;"><i class="fa-solid fa-circle-check"></i> ${data.message} Redirecting to payment...</div>`;
        setTimeout(() => {
          window.location.href = `/student/assignments.php`;
        }, 1500);
      } else {
        submitResultMsg.innerHTML = `<div class="badge badge-danger">${data.message || 'Submission error'}</div>`;
      }
    })
    .catch(err => {
      submitResultMsg.innerHTML = `<div class="badge badge-danger">An error occurred during submission.</div>`;
    });
  });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
