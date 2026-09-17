<?php
$pageTitle = "Transparent Per-Word Pricing & Calculator";
include __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top:3rem; padding-bottom:4rem;">
  <div class="section-header">
    <div class="badge badge-primary" style="margin-bottom:0.8rem;">Transparent Per-Word Rates</div>
    <h2>Fair Academic Pricing Built For Students Worldwide</h2>
    <p>Honest per-word pricing with zero hidden fees. Includes Turnitin plagiarism report, free title page,
      bibliography, and unlimited revisions.</p>
  </div>

  <div class="grid-2" style="display:grid; grid-template-columns:1.2fr 1fr; gap:3rem; align-items:start;">
    <div>
      <h3 style="margin-bottom:1.2rem; color:var(--text-main);"><i class="fa-solid fa-table-cells"
          style="color:var(--primary);"></i> Per-Word Pricing Matrix</h3>
      <div class="table-card">
        <table class="data-table">
          <thead>
            <tr>
              <th>Deadline</th>
              <th>Rate / Word (USD)</th>
              <th>Rate / Word (INR)</th>
              <th>1,000 Words (USD)</th>
              <th>1,000 Words (INR)</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><strong style="color:var(--success);"><i class="fa-solid fa-calendar-check"></i> 5+ Days (Min /
                  Standard)</strong></td>
              <td>$0.011 / word</td>
              <td>₹1.00 / word</td>
              <td><strong style="color:var(--secondary);">$11.00</strong></td>
              <td><strong style="color:var(--success);">₹1,000</strong></td>
            </tr>
            <tr>
              <td><strong>4 Days (1 day fast)</strong></td>
              <td>$0.011 / word</td>
              <td>₹1.00 / word</td>
              <td>$11.00</td>
              <td>₹1,000</td>
            </tr>
            <tr>
              <td><strong>3 Days (72 Hours)</strong></td>
              <td>$0.011 / word</td>
              <td>₹1.00 / word</td>
              <td>$11.00</td>
              <td>₹1,000</td>
            </tr>
            <tr>
              <td><strong>2 Days (48 Hours)</strong></td>
              <td>$0.016 / word</td>
              <td>₹1.50 / word</td>
              <td>$16.00</td>
              <td>₹1,500</td>
            </tr>
            <tr>
              <td><strong style="color:#ef4444;"><i class="fa-solid fa-bolt"></i> 1 Day (24h Urgent)</strong></td>
              <td>$0.021 / word</td>
              <td>₹2.00 / word</td>
              <td>$21.00</td>
              <td>₹2,000</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div
        style="background:rgba(16, 185, 129, 0.1); border:1px solid var(--success); border-radius:var(--radius-sm); padding:1.2rem; margin-top:1.5rem;">
        <h5 style="color:var(--success); font-size:1rem; margin-bottom:0.4rem;"><i class="fa-solid fa-gift"></i> Active
          Student Promo Coupons</h5>
        <div style="font-size:0.9rem; color:var(--text-muted); margin:0;">
          <?php 
          $activeCouponsList = DataStore::filter('coupons', function($c) {
              return ($c['status'] ?? '') === 'Active' && (empty($c['expires_at']) || strtotime($c['expires_at'] . ' 23:59:59') >= time());
          });
          if (!empty($activeCouponsList)): 
            foreach ($activeCouponsList as $ac): ?>
              <span style="display:inline-block; margin-right:15px; margin-bottom:4px;">
                Use promo code <strong style="color:var(--text-main);"><?php echo htmlspecialchars($ac['code']); ?></strong> for <?php echo (float)$ac['discount_percent']; ?>% discount!
              </span>
          <?php endforeach; 
          else: ?>
            <span>No active promotional discounts currently running.</span>
          <?php endif; ?>
        </div>
      </div>

      <div
        style="background:rgba(99, 102, 241, 0.08); border:1px solid var(--primary); border-radius:var(--radius-sm); padding:1.2rem; margin-top:1rem;">
        <h5 style="color:var(--primary); font-size:0.95rem; margin-bottom:0.3rem;"><i
            class="fa-solid fa-circle-info"></i> How Our Pricing Works</h5>
        <p style="font-size:0.85rem; color:var(--text-muted); margin:0; line-height:1.5;">
          Our baseline per-word rate applies for standard deadlines of <strong>3 to 5+ days</strong> ($0.011 USD / ₹1.00 INR per word). For expedited 2-day delivery (48 hours), the rate is $0.016 USD / ₹1.50 INR per word. For urgent 1-day delivery (24 hours or less), the rate is $0.021 USD / ₹2.00 INR per word.
        </p>
      </div>
    </div>

    <div>
      <div class="calc-card" style="background:#ffffff; border:1px solid var(--portal-border); padding:2rem;">
        <h3 style="margin-bottom:1.2rem; color:var(--text-main);"><i class="fa-solid fa-calculator"
            style="color:var(--secondary);"></i> Instant Price Calculator</h3>

        <div class="form-group">
          <label><i class="fa-solid fa-globe"></i> Select Currency & Country</label>
          <select id="calcCurrency" class="form-control">
            <option value="USD" selected>🇺🇸 United States (USD $)</option>
            <option value="INR">🇮🇳 India (INR ₹)</option>
            <option value="GBP">🇬🇧 United Kingdom (GBP £)</option>
            <option value="EUR">🇮🇪 Ireland / EU (EUR €)</option>
            <option value="AUD">🇦🇺 Australia (AUD A$)</option>
            <option value="CAD">🇨🇦 Canada (CAD C$)</option>
          </select>
        </div>

        <div class="form-group">
          <label><i class="fa-solid fa-file-lines"></i> Word Count (250 Words = 1 Page)</label>
          <select id="calcWordCount" class="form-control">
            <?php echo render_word_count_options(1000); ?>
          </select>
          <small style="color:var(--text-muted);" id="calcPagesDisplay">4 pages (approx 250 words/page)</small>
        </div>

        <div class="form-group">
          <label>Deadline Urgency</label>
          <select id="calcDeadline" class="form-control">
            <option value="120" selected>5 Days (Standard - Base Rate)</option>
            <option value="96">4 Days (1 Day Expedited)</option>
            <option value="72">3 Days (72 Hours)</option>
            <option value="48">2 Days (48 Hours)</option>
            <option value="24">24 Hours (Urgent)</option>
            <option value="12">12 Hours (Super Urgent)</option>
            <option value="240">10 Days (Relaxed)</option>
          </select>
        </div>

        <div class="form-group">
          <label>Subject Category</label>
          <select id="calcSubject" class="form-control">
            <option value="Computer Science">Computer Science & IT</option>
            <option value="Business Management">Business & Management</option>
            <option value="Nursing & Healthcare">Nursing & Healthcare</option>
            <option value="Law & Legal Studies">Law & Legal Studies</option>
            <option value="General">General Academic</option>
          </select>
        </div>

        <div class="form-group">
          <label>Coupon Code</label>
          <div style="display:flex; gap:8px;">
            <input type="text" id="calcCoupon" class="form-control" value="ACE20" placeholder="e.g. ACE20"
              style="text-transform:uppercase;">
            <button type="button" id="btnCalcApply" class="btn btn-outline btn-sm">Apply</button>
          </div>
          <div id="calcDiscountNotice" style="color:var(--success); font-size:0.8rem; font-weight:700; margin-top:4px;">
          </div>
        </div>

        <!-- File Upload Option (Accepts Any Format) -->
        <div class="form-group">
          <label for="pricing_files"><i class="fa-solid fa-cloud-arrow-up" style="color:var(--secondary);"></i> Upload
            Assignment File(s) (Any Format)</label>
          <div
            style="border: 2px dashed var(--portal-border); padding: 0.8rem 1rem; border-radius: var(--radius-sm); text-align: center; background: #f8fafc;">
            <input type="file" id="pricing_files" name="assignment_files[]" multiple class="form-control"
              style="font-size:0.85rem;">
            <small style="color:var(--text-muted); font-size:0.75rem; display:block; margin-top:4px;">
              Accepts <strong>ANY</strong> format: PDF, DOCX, ZIP, RAR, TXT, PY, IPYNB, XLS, PPTX, Images, etc.
            </small>
            <div id="pricing_files_summary"
              style="margin-top:6px; font-size:0.8rem; color:var(--primary); font-weight:600;"></div>
          </div>
        </div>

        <div class="price-display-box"
          style="margin:1.5rem 0; padding:1.2rem; background:rgba(99, 102, 241, 0.08); border-radius:var(--radius-md); text-align:center;">
          <div class="est-label" style="font-size:0.85rem; color:var(--text-muted);">Estimated Total Investment</div>
          <div class="est-amount" id="calcAmountDisplay"
            style="font-size:2.2rem; font-weight:800; color:var(--secondary);">$11.00</div>
          <small id="calcRatePerWordText" style="color:var(--text-muted); display:block; margin-top:4px;">$0.011 /
            word</small>
        </div>

        <a href="/submit-assignment.php" id="btnPricingOrder" class="btn btn-primary btn-lg"
          style="width:100%; text-align:center; display:block;">
          <i class="fa-solid fa-paper-plane"></i> Proceed To Order Form &rarr;
        </a>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const currencySel = document.getElementById('calcCurrency');
    const wordInp = document.getElementById('calcWordCount');
    const deadlineSel = document.getElementById('calcDeadline');
    const couponInp = document.getElementById('calcCoupon');
    const btnApply = document.getElementById('btnCalcApply');
    const amountDisp = document.getElementById('calcAmountDisplay');
    const rateText = document.getElementById('calcRatePerWordText');
    const pagesDisp = document.getElementById('calcPagesDisplay');
    const discountNotice = document.getElementById('calcDiscountNotice');

    function calculatePrice() {
      const words = parseInt(wordInp.value) || 250;
      const deadline = parseFloat(deadlineSel.value) || 120;
      const curr = currencySel.value || 'USD';
      const coupon = couponInp.value.trim().toUpperCase();

      pagesDisp.textContent = `${Math.ceil(words / 250)} pages (approx 250 words/page)`;

      fetch(`/api.php?action=price_calc&word_count=${words}&deadline_hours=${deadline}&currency=${curr}&coupon_code=${encodeURIComponent(coupon)}`)
        .then(res => res.json())
        .then(res => {
          if (res.success) {
            const d = res.data;
            const formatted = (d.currency === 'INR')
              ? `${d.currency_symbol}${Math.round(d.final_price).toLocaleString('en-IN')}`
              : `${d.currency_symbol}${d.final_price.toFixed(2)}`;

            amountDisp.textContent = formatted;
            const effRateFormatted = (d.currency === 'INR') ? d.effective_rate_per_word.toFixed(2) : d.effective_rate_per_word.toFixed(3);
            const baseRateFormatted = (d.currency === 'INR') ? d.base_rate_per_word.toFixed(2) : d.base_rate_per_word.toFixed(3);
            rateText.textContent = `${d.currency_symbol}${effRateFormatted} / word (Base: ${d.currency_symbol}${baseRateFormatted})`;

            if (d.discount_percent > 0) {
              discountNotice.style.color = 'var(--success)';
              const discFmt = (d.currency === 'INR') ? Math.round(d.discount_amount).toLocaleString('en-IN') : d.discount_amount.toFixed(2);
              discountNotice.textContent = `✓ ${d.discount_percent}% Coupon Discount Applied (-${d.currency_symbol}${discFmt})`;
            } else if (coupon) {
              discountNotice.style.color = '#ef4444';
              discountNotice.textContent = `✗ ${d.coupon_message || 'Invalid or expired coupon'}`;
            } else {
              discountNotice.textContent = '';
            }
          }
        });
    }

    const pricingFileInput = document.getElementById('pricing_files');
    const pricingFileSummary = document.getElementById('pricing_files_summary');
    const btnPricingOrder = document.getElementById('btnPricingOrder');

    if (pricingFileInput) {
      pricingFileInput.addEventListener('change', () => {
        if (pricingFileInput.files.length > 0) {
          const names = Array.from(pricingFileInput.files).map(f => f.name).join(', ');
          pricingFileSummary.innerHTML = `<i class="fa-solid fa-check-circle" style="color:var(--success);"></i> ${pricingFileInput.files.length} file(s) selected: <span style="color:var(--text-main); font-weight:normal;">${names}</span>`;
        } else {
          pricingFileSummary.innerHTML = '';
        }
      });
    }

    function updateOrderUrl() {
      if (btnPricingOrder) {
        const c = currencySel.value || 'USD';
        const w = wordInp.value || '1000';
        const d = deadlineSel.value || '120';
        const cp = couponInp.value.trim() || 'ACE20';
        btnPricingOrder.href = `/submit-assignment.php?currency=${c}&words=${w}&deadline=${d}&coupon=${encodeURIComponent(cp)}`;
      }
    }

    [currencySel, wordInp, deadlineSel, couponInp].forEach(el => {
      if (el) {
        el.addEventListener('change', () => { calculatePrice(); updateOrderUrl(); });
        el.addEventListener('input', () => { calculatePrice(); updateOrderUrl(); });
      }
    });
    if (btnApply) btnApply.addEventListener('click', () => { calculatePrice(); updateOrderUrl(); });
    calculatePrice();
    updateOrderUrl();
  });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>