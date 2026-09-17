/**
 * Ace Assignment Helps Main Website Interactive JS
 * Supports Responsive Mobile Navigation, International Currency Conversion & Price Estimator
 */

document.addEventListener('DOMContentLoaded', () => {
  initMobileMenuToggle();
  initHeroCalculator();
  initPriceCalculator();
  initFaqAccordions();
});

function initMobileMenuToggle() {
  const menuBtn = document.getElementById('mobileMenuBtn');
  const navMenu = document.getElementById('primaryNavMenu');

  if (menuBtn && navMenu) {
    menuBtn.addEventListener('click', () => {
      navMenu.classList.toggle('active');
      const icon = menuBtn.querySelector('i');
      if (icon) {
        if (navMenu.classList.contains('active')) {
          icon.className = 'fa-solid fa-xmark';
        } else {
          icon.className = 'fa-solid fa-bars';
        }
      }
    });
  }
}

function initHeroCalculator() {
  const levelSel = document.getElementById('acad_level');
  const wordSel = document.getElementById('word_count');
  const urgencySel = document.getElementById('urgency');
  const countrySel = document.getElementById('country_curr_select');
  const couponInp = document.getElementById('coupon_input');
  const applyBtn = document.getElementById('apply_coupon_btn');
  const priceDisplay = document.getElementById('final_calc_price');
  const couponMsg = document.getElementById('coupon_msg');

  if (!priceDisplay) return;

  let abortController = null;

  function updatePrice(isExplicitApply = false) {
    const words = parseInt(wordSel ? wordSel.value : 2000) || 2000;
    const urgencyHours = parseInt(urgencySel ? urgencySel.value : 120) || 120;
    const currKey = countrySel ? countrySel.value : 'USD';
    const code = couponInp ? couponInp.value.trim().toUpperCase() : '';
    const level = levelSel ? levelSel.value : 'postgraduate';

    if (isExplicitApply && applyBtn) {
      applyBtn.disabled = true;
      applyBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Applying...';
    }

    if (abortController) {
      abortController.abort();
    }
    abortController = new AbortController();

    const url = `/api.php?action=price_calc&word_count=${words}&deadline_hours=${urgencyHours}&academic_level=${encodeURIComponent(level)}&currency=${currKey}&coupon_code=${encodeURIComponent(code)}`;

    fetch(url, { signal: abortController.signal })
      .then(res => res.json())
      .then(res => {
        if (res.success && res.data) {
          const d = res.data;
          const formatted = (d.currency === 'INR')
            ? `${d.currency_symbol}${Math.round(d.final_price).toLocaleString('en-IN')}`
            : `${d.currency_symbol}${d.final_price.toFixed(d.decimals !== undefined ? d.decimals : 2)}`;

          priceDisplay.textContent = formatted;

          if (couponMsg) {
            if (code && d.coupon_valid) {
              couponMsg.style.display = 'block';
              couponMsg.style.color = 'var(--success)';
              const discFmt = (d.currency === 'INR') ? Math.round(d.discount_amount).toLocaleString('en-IN') : d.discount_amount.toFixed(2);
              couponMsg.innerHTML = `<i class="fa-solid fa-circle-check"></i> ${d.coupon_message || `Coupon ${code} Applied! ${d.discount_percent}% Discount Activated.`} (-${d.currency_symbol}${discFmt})`;
            } else if (code && !d.coupon_valid) {
              couponMsg.style.display = 'block';
              couponMsg.style.color = '#ef4444';
              couponMsg.innerHTML = `<i class="fa-solid fa-circle-xmark"></i> ${d.coupon_message || `Invalid or expired coupon code: "${code}".`}`;
            } else {
              couponMsg.style.display = 'none';
              couponMsg.innerHTML = '';
            }
          }
        }
      })
      .catch(err => {
        if (err.name !== 'AbortError') {
          console.error('Price calculation error:', err);
        }
      })
      .finally(() => {
        if (isExplicitApply && applyBtn) {
          applyBtn.disabled = false;
          applyBtn.innerHTML = 'Apply';
        }
      });
  }

  [levelSel, wordSel, urgencySel, countrySel].forEach(el => {
    if (el) {
      el.addEventListener('change', () => updatePrice(false));
      el.addEventListener('input', () => updatePrice(false));
    }
  });

  if (couponInp) {
    couponInp.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        updatePrice(true);
      }
    });
    let couponDebounce = null;
    couponInp.addEventListener('input', () => {
      clearTimeout(couponDebounce);
      couponDebounce = setTimeout(() => updatePrice(false), 400);
    });
  }

  if (applyBtn) {
    applyBtn.addEventListener('click', (e) => {
      e.preventDefault();
      updatePrice(true);
    });
  }

  updatePrice(false);
}

function initPriceCalculator() {
  const calcForm = document.getElementById('priceCalcForm');
  if (!calcForm) return;

  const wordCountInput = document.getElementById('calcWordCount');
  const deadlineSelect = document.getElementById('calcDeadline');
  const levelSelect = document.getElementById('calcLevel');
  const subjectSelect = document.getElementById('calcSubject');
  const couponInput = document.getElementById('calcCoupon');
  
  const displayPages = document.getElementById('calcPagesDisplay');
  const displayAmount = document.getElementById('calcAmountDisplay');
  const discountNotice = document.getElementById('calcDiscountNotice');

  function calculate() {
    const words = parseInt(wordCountInput.value) || 250;
    const deadlineHours = parseInt(deadlineSelect.value) || 72;
    const level = levelSelect ? levelSelect.value : 'Undergraduate';
    const subject = subjectSelect ? subjectSelect.value : 'General';
    const coupon = (couponInput ? couponInput.value.trim().toUpperCase() : '');

    fetch(`/api.php?action=price_calc&word_count=${words}&deadline_hours=${deadlineHours}&academic_level=${encodeURIComponent(level)}&subject=${encodeURIComponent(subject)}&currency=USD&coupon_code=${encodeURIComponent(coupon)}`)
      .then(res => res.json())
      .then(res => {
        if (res.success && res.data) {
          const d = res.data;
          if (displayPages) displayPages.textContent = d.pages + (d.pages === 1 ? ' page' : ' pages');
          if (displayAmount) displayAmount.textContent = '$' + d.final_price.toFixed(2);
          if (discountNotice) {
            if (coupon && d.coupon_valid) {
              discountNotice.style.color = 'var(--success)';
              discountNotice.innerHTML = `✨ ${d.discount_percent}% Discount Applied (${coupon})`;
            } else if (coupon && !d.coupon_valid) {
              discountNotice.style.color = '#ef4444';
              discountNotice.innerHTML = `✗ ${d.coupon_message || 'Invalid coupon'}`;
            } else {
              discountNotice.innerHTML = '';
            }
          }
        }
      });
  }

  [wordCountInput, deadlineSelect, levelSelect, subjectSelect, couponInput].forEach(el => {
    if (el) {
      el.addEventListener('input', calculate);
      el.addEventListener('change', calculate);
    }
  });

  calculate();
}

function initFaqAccordions() {
  const faqItems = document.querySelectorAll('.faq-item');
  faqItems.forEach(item => {
    const head = item.querySelector('.faq-header');
    if (head) {
      head.addEventListener('click', () => {
        const isOpen = item.classList.contains('active');
        faqItems.forEach(i => i.classList.remove('active'));
        if (!isOpen) {
          item.classList.add('active');
        }
      });
    }
  });
}
