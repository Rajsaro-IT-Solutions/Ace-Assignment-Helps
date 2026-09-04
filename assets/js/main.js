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

  // Currency Symbols & Conversion Rates relative to USD ($)
  const currencyRates = {
    'USD': { symbol: '$', rate: 1.0 },
    'GBP': { symbol: '£', rate: 0.79 },
    'EUR': { symbol: '€', rate: 0.92 },
    'AUD': { symbol: 'A$', rate: 1.52 },
    'CAD': { symbol: 'C$', rate: 1.36 },
    'INR': { symbol: '₹', rate: 83.5 }
  };

  function updatePrice() {
    const words = parseInt(wordSel ? wordSel.value : 2000) || 2000;
    const pages = Math.ceil(words / 250);
    const urgencyHours = parseInt(urgencySel ? urgencySel.value : 120) || 120;
    const level = levelSel ? levelSel.value : 'postgraduate';
    const currKey = countrySel ? countrySel.value : 'USD';
    const code = couponInp ? couponInp.value.trim().toUpperCase() : 'ACE20';

    let basePerPageUSD = 15.0;

    let levelMult = 1.0;
    if (level === 'postgraduate') levelMult = 1.3;
    if (level === 'doctorate') levelMult = 1.6;

    let urgencyMult = 1.0;
    if (urgencyHours <= 24) urgencyMult = 1.8;
    else if (urgencyHours <= 48) urgencyMult = 1.4;
    else if (urgencyHours <= 120) urgencyMult = 1.1;

    let subtotalUSD = pages * basePerPageUSD * levelMult * urgencyMult;

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

    let finalUSD = subtotalUSD * (1 - discount);
    const currObj = currencyRates[currKey] || currencyRates['USD'];
    let localPrice = finalUSD * currObj.rate;

    if (currKey === 'INR') {
      priceDisplay.textContent = currObj.symbol + Math.round(localPrice).toLocaleString('en-IN');
    } else {
      priceDisplay.textContent = currObj.symbol + localPrice.toFixed(2);
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
