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

  // Currency pricing configuration matching table:
  // 3+ days: base rate ($0.011 / ₹1.00)
  // 2 days (48h): $0.016 / ₹1.50
  // 1 day (24h or less): $0.021 / ₹2.00
  const currencyConfigs = {
    'USD': { symbol: '$', rate_3plus: 0.0110, rate_2days: 0.0160, rate_1day: 0.0210, decimals: 2 },
    'INR': { symbol: '₹', rate_3plus: 1.0000, rate_2days: 1.5000, rate_1day: 2.0000, decimals: 0 },
    'GBP': { symbol: '£', rate_3plus: 0.0087, rate_2days: 0.0126, rate_1day: 0.0166, decimals: 2 },
    'EUR': { symbol: '€', rate_3plus: 0.0100, rate_2days: 0.0145, rate_1day: 0.0191, decimals: 2 },
    'AUD': { symbol: 'A$', rate_3plus: 0.0170, rate_2days: 0.0247, rate_1day: 0.0325, decimals: 2 },
    'CAD': { symbol: 'C$', rate_3plus: 0.0150, rate_2days: 0.0218, rate_1day: 0.0286, decimals: 2 }
  };

  function updatePrice() {
    const words = parseInt(wordSel ? wordSel.value : 2000) || 2000;
    const urgencyHours = parseInt(urgencySel ? urgencySel.value : 120) || 120;
    const currKey = countrySel ? countrySel.value : 'USD';
    const code = couponInp ? couponInp.value.trim().toUpperCase() : 'ACE20';

    const cfg = currencyConfigs[currKey] || currencyConfigs['USD'];
    let effectiveRate = cfg.rate_3plus;
    if (urgencyHours <= 24) {
      effectiveRate = cfg.rate_1day;
    } else if (urgencyHours <= 48) {
      effectiveRate = cfg.rate_2days;
    }

    let subtotal = words * effectiveRate;

    let discount = 0;
    if (code === 'ACE20') {
      discount = 0.20;
      if (couponMsg) {
        couponMsg.style.display = 'block';
        couponMsg.innerHTML = '<i class="fa-solid fa-circle-check"></i> Coupon ACE20 Applied! 20% Discount Activated.';
      }
    } else if (code === 'FIRST15') {
      discount = 0.15;
      if (couponMsg) {
        couponMsg.style.display = 'block';
        couponMsg.innerHTML = '<i class="fa-solid fa-circle-check"></i> Coupon FIRST15 Applied! 15% First Order Discount.';
      }
    } else {
      if (couponMsg) {
        couponMsg.style.display = 'none';
      }
    }

    let finalPrice = subtotal * (1 - discount);
    const minPrice = (currKey === 'INR') ? 100 : 1;
    finalPrice = Math.max(minPrice, finalPrice);

    if (cfg.decimals === 0) {
      priceDisplay.textContent = cfg.symbol + Math.round(finalPrice).toLocaleString('en-IN');
    } else {
      priceDisplay.textContent = cfg.symbol + finalPrice.toFixed(2);
    }
  }

  [levelSel, wordSel, urgencySel, countrySel, couponInp].forEach(el => {
    if (el) {
      el.addEventListener('change', updatePrice);
      el.addEventListener('input', updatePrice);
    }
  });

  if (applyBtn) {
    applyBtn.addEventListener('click', (e) => {
      e.preventDefault();
      updatePrice();
    });
  }

  updatePrice();
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
    const pages = Math.ceil(words / 250);
    const deadlineHours = parseInt(deadlineSelect.value) || 72;
    const level = levelSelect.value || 'Undergraduate';
    const subject = subjectSelect.value || 'General';
    const coupon = (couponInput ? couponInput.value.trim().toUpperCase() : '');

    let basePerPage = 15.0;

    const levelMults = {
      'High School': 0.85,
      'Undergraduate': 1.0,
      'Master\'s': 1.35,
      'PhD': 1.75
    };
    const lvlM = levelMults[level] || 1.0;

    let urgencyM = 1.0;
    if (deadlineHours <= 12) urgencyM = 2.2;
    else if (deadlineHours <= 24) urgencyM = 1.8;
    else if (deadlineHours <= 48) urgencyM = 1.4;
    else if (deadlineHours <= 72) urgencyM = 1.2;

    const complexSubjects = ['Computer Science', 'Programming', 'Engineering', 'Medical Sciences', 'Finance', 'Law & Legal Studies'];
    const subjM = complexSubjects.includes(subject) ? 1.15 : 1.0;

    let subtotal = pages * basePerPage * lvlM * urgencyM * subjM;

    let discount = 0;
    if (coupon === 'ACE20') {
      discount = 0.20;
      if (discountNotice) discountNotice.innerHTML = '✨ 20% Discount Applied (ACE20)';
    } else if (coupon === 'FIRST15') {
      discount = 0.15;
      if (discountNotice) discountNotice.innerHTML = '✨ 15% First Order Discount Applied';
    } else {
      if (discountNotice) discountNotice.innerHTML = '';
    }

    let finalPrice = Math.max(10.0, subtotal * (1 - discount));

    if (displayPages) displayPages.textContent = pages + (pages === 1 ? ' page' : ' pages');
    if (displayAmount) displayAmount.textContent = '$' + finalPrice.toFixed(2);
  }

  [wordCountInput, deadlineSelect, levelSelect, subjectSelect, couponInput].forEach(el => {
    if (el) el.addEventListener('input', calculate);
    if (el) el.addEventListener('change', calculate);
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
