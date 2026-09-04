<?php
$pageTitle = "Transparent Pricing & Calculator";
include __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top:3rem;">
  <div class="section-header">
    <div class="badge badge-primary" style="margin-bottom:0.8rem;">Transparent Rates</div>
    <h2>Fair Pricing Built For University Budgets</h2>
    <p>No hidden fees. Free Turnitin plagiarism report, free cover page, free bibliography, and free unlimited revisions included.</p>
  </div>

  <div class="grid-2" style="display:grid; grid-template-columns:1.2fr 1fr; gap:3rem; align-items:start;">
    <div>
      <h3 style="margin-bottom:1.5rem;">Pricing Matrix (Per 250 Words)</h3>
      <div class="table-card">
        <table class="data-table">
          <thead>
            <tr>
              <th>Deadline</th>
              <th>High School</th>
              <th>Undergrad</th>
              <th>Master's</th>
              <th>PhD</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><strong>10+ Days</strong></td>
              <td>$12.75</td>
              <td>$15.00</td>
              <td>$20.25</td>
              <td>$26.25</td>
            </tr>
            <tr>
              <td><strong>5 Days</strong></td>
              <td>$15.30</td>
              <td>$18.00</td>
              <td>$24.30</td>
              <td>$31.50</td>
            </tr>
            <tr>
              <td><strong>3 Days (72h)</strong></td>
              <td>$18.36</td>
              <td>$21.60</td>
              <td>$29.16</td>
              <td>$37.80</td>
            </tr>
            <tr>
              <td><strong>24 Hours</strong></td>
              <td>$22.95</td>
              <td>$27.00</td>
              <td>$36.45</td>
              <td>$47.25</td>
            </tr>
            <tr>
              <td><strong>12 Hours (Urgent)</strong></td>
              <td>$28.05</td>
              <td>$33.00</td>
              <td>$44.55</td>
              <td>$57.75</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div style="background:rgba(16, 185, 129, 0.1); border:1px solid var(--success); border-radius:var(--radius-sm); padding:1.2rem; margin-top:1.5rem;">
        <h5 style="color:var(--success); font-size:1rem; margin-bottom:0.4rem;"><i class="fa-solid fa-gift"></i> Active Promo Coupons</h5>
        <p style="font-size:0.9rem; color:var(--text-muted);">Use code <strong style="color:#fff;">ACE20</strong> for 20% off orders above $100. New students use <strong style="color:#fff;">FIRST15</strong> for 15% off!</p>
      </div>
    </div>

    <div>
      <div class="calc-card">
        <h3><i class="fa-solid fa-calculator" style="color:var(--secondary);"></i> Interactive Calculator</h3>
        <div class="form-group">
          <label>Word Count</label>
          <input type="number" id="calcWordCount" class="form-control" value="1000" min="250" step="250">
          <small style="color:var(--text-muted);" id="calcPagesDisplay">4 pages</small>
        </div>

        <div class="form-group">
          <label>Academic Level</label>
          <select id="calcLevel" class="form-control">
            <option value="High School">High School</option>
            <option value="Undergraduate" selected>Undergraduate</option>
            <option value="Master's">Master's Degree</option>
            <option value="PhD">PhD Doctorate</option>
          </select>
        </div>

        <div class="form-group">
          <label>Deadline Urgency</label>
          <select id="calcDeadline" class="form-control">
            <option value="12">12 Hours (Urgent)</option>
            <option value="24">24 Hours</option>
            <option value="48">48 Hours (2 Days)</option>
            <option value="72" selected>72 Hours (3 Days)</option>
            <option value="120">5 Days</option>
            <option value="240">10 Days</option>
          </select>
        </div>

        <div class="form-group">
          <label>Subject Category</label>
          <select id="calcSubject" class="form-control">
            <option value="Computer Science">Computer Science & IT</option>
            <option value="Business Management">Business Management</option>
            <option value="Nursing & Healthcare">Nursing & Healthcare</option>
            <option value="Law & Legal Studies">Law & Legal Studies</option>
            <option value="General">General Academic</option>
          </select>
        </div>

        <div class="form-group">
          <label>Coupon Code</label>
          <input type="text" id="calcCoupon" class="form-control" value="ACE20" placeholder="ACE20">
          <div id="calcDiscountNotice" style="color:var(--success); font-size:0.8rem; font-weight:700; margin-top:4px;"></div>
        </div>

        <div class="price-display-box">
          <div class="est-label">Estimated Investment</div>
          <div class="est-amount" id="calcAmountDisplay">$0.00</div>
        </div>

        <a href="/submit-assignment.php" class="btn btn-primary" style="width:100%;"><i class="fa-solid fa-paper-plane"></i> Proceed To Order Form</a>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
