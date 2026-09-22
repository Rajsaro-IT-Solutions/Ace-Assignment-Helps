<?php
$pageTitle = "Secure Payment Checkout";
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$user = Auth::currentUser();
$gw = get_gateway_config();

$asmId = trim($_GET['assignment_id'] ?? $_GET['id'] ?? '');
$asm = null;
$isAlreadyPaid = false;
$existingPayment = null;

if ($asmId) {
    $asm = DataStore::findOne('assignments', 'assignment_id', $asmId);
    if ($asm) {
        $existingPayment = DataStore::findOne('payments', 'assignment_id', $asmId);
        if ($existingPayment && ($existingPayment['status'] ?? '') === 'Paid') {
            $isAlreadyPaid = true;
        }
    }
}

// Fallback: If no assignment passed, try finding most recent unpaid assignment for logged in student
if (!$asm && $user && ($user['role'] ?? '') === 'Student') {
    $studentAsms = DataStore::filter('assignments', function($a) use ($user) {
        return isset($a['student_id']) && $a['student_id'] === $user['id'] && in_array($a['status'], ['Pending Review', 'Waiting for Payment', 'New']);
    });
    if (!empty($studentAsms)) {
        usort($studentAsms, function($a, $b) {
            return strcmp($b['created_at'], $a['created_at']);
        });
        $asm = $studentAsms[0];
        $asmId = $asm['assignment_id'];
    }
}

$currency = $asm['currency'] ?? 'USD';
$finalPrice = $asm ? (float)$asm['final_price'] : 0.0;
$subtotal = $asm ? (float)($asm['price'] ?? $finalPrice) : 0.0;
$discountCode = $asm['discount_code'] ?? '';
$discountAmount = ($subtotal > $finalPrice) ? round($subtotal - $finalPrice, 2) : 0.0;

$isStudent = ($user && ($user['role'] ?? '') === 'Student');

// Include appropriate portal or public header
if ($isStudent) {
    include __DIR__ . '/includes/portal_header.php';
} else {
    include __DIR__ . '/includes/header.php';
}
?>

<!-- Embedded Critical CSS for Checkout Page & Modals -->
<style>
  /* Base Reset & Variables for Checkout */
  :root {
    --co-primary: #4f46e5;
    --co-primary-hover: #4338ca;
    --co-success: #059669;
    --co-success-hover: #047857;
    --co-card-bg: #ffffff;
    --co-border: #e2e8f0;
    --co-text-main: #0f172a;
    --co-text-muted: #64748b;
  }

  .checkout-container-wrap {
    background: #f8fafc;
    min-height: 80vh;
    padding: <?php echo $isStudent ? '1rem 0 3rem 0' : '2.5rem 0 4.5rem 0'; ?>;
  }

  .co-max-width {
    max-width: 1100px;
    margin: 0 auto;
    padding: 0 1.25rem;
  }

  /* Top Navigation & Trust Badges */
  .co-topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.75rem;
    flex-wrap: wrap;
    gap: 1rem;
  }

  .co-back-link {
    color: var(--co-text-muted);
    font-size: 0.88rem;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: color 0.2s ease;
  }
  .co-back-link:hover {
    color: var(--co-primary);
  }

  .co-page-title {
    font-size: 1.8rem;
    color: var(--co-text-main);
    margin: 0.35rem 0 0 0;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 10px;
    letter-spacing: -0.02em;
  }

  .co-trust-pills {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }

  .co-trust-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #ffffff;
    padding: 6px 14px;
    border-radius: 20px;
    border: 1px solid var(--co-border);
    font-size: 0.82rem;
    font-weight: 600;
    color: #334155;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
  }

  /* Main Two-Column Layout */
  .co-grid {
    display: grid;
    grid-template-columns: 1.35fr 1fr;
    gap: 2rem;
    align-items: start;
  }

  @media (max-width: 960px) {
    .co-grid {
      grid-template-columns: 1fr !important;
      gap: 1.5rem;
    }
  }

  /* Payment Selection Box */
  .co-card {
    background: #ffffff;
    border: 1px solid var(--co-border);
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    margin-bottom: 1.5rem;
  }

  /* Segmented Gateway Tabs */
  .co-tab-nav {
    display: flex;
    gap: 6px;
    background: #f1f5f9;
    padding: 6px;
    border-radius: 12px;
    margin-bottom: 1.75rem;
    overflow-x: auto;
  }

  .co-tab-btn {
    flex: 1;
    background: transparent;
    border: none;
    padding: 10px 14px;
    font-size: 0.88rem;
    font-weight: 700;
    color: #64748b;
    border-radius: 8px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s ease;
    white-space: nowrap;
    position: relative;
  }

  .co-tab-btn:hover {
    color: var(--co-text-main);
    background: rgba(255, 255, 255, 0.6);
  }

  .co-tab-btn.active {
    background: #ffffff;
    color: var(--co-primary);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  }

  .co-tab-badge {
    background: #10b981;
    color: #ffffff;
    font-size: 0.65rem;
    font-weight: 800;
    padding: 2px 6px;
    border-radius: 10px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
  }

  /* Virtual Card Preview */
  .co-virtual-card {
    background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%);
    border-radius: 16px;
    padding: 1.6rem;
    color: #ffffff;
    margin-bottom: 1.75rem;
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.25);
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.15);
  }

  .co-virtual-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 250px;
    height: 250px;
    background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, rgba(255,255,255,0) 70%);
    pointer-events: none;
  }

  .co-vcard-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
  }

  .co-chip {
    width: 42px;
    height: 32px;
    background: linear-gradient(135deg, #fbbf24 0%, #d97706 100%);
    border-radius: 6px;
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: inset 0 0 4px rgba(0,0,0,0.3);
  }
  .co-chip::after {
    content: '';
    position: absolute;
    width: 24px;
    height: 18px;
    border: 1px solid rgba(0,0,0,0.25);
    border-radius: 3px;
  }

  .co-vcard-num {
    font-family: 'Courier New', Courier, monospace;
    font-size: 1.35rem;
    letter-spacing: 3px;
    margin-bottom: 1.35rem;
    text-shadow: 0 2px 4px rgba(0,0,0,0.4);
  }

  .co-vcard-bottom {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
  }

  /* Form Elements */
  .co-form-group {
    margin-bottom: 1.25rem;
  }

  .co-label {
    display: block;
    font-size: 0.84rem;
    font-weight: 700;
    color: #334155;
    margin-bottom: 6px;
  }

  .co-input-wrap {
    position: relative;
  }

  .co-input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1.5px solid var(--co-border);
    border-radius: 10px;
    font-size: 0.95rem;
    color: var(--co-text-main);
    background: #ffffff;
    transition: border-color 0.2s, box-shadow 0.2s;
  }

  .co-input:focus {
    outline: none;
    border-color: var(--co-primary);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
  }

  .co-input-icon {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 1.15rem;
  }

  .co-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
  }

  /* Primary Action Buttons */
  .co-btn-pay {
    width: 100%;
    font-weight: 800;
    font-size: 1.05rem;
    padding: 1rem 1.5rem;
    background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
    color: #ffffff;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35);
    transition: all 0.2s ease;
  }
  .co-btn-pay:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(79, 70, 229, 0.45);
  }

  .co-btn-razorpay {
    width: 100%;
    font-weight: 800;
    font-size: 1.05rem;
    padding: 1rem 1.5rem;
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    color: #ffffff;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 4px 14px rgba(5, 150, 105, 0.35);
    transition: all 0.2s ease;
  }
  .co-btn-razorpay:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(5, 150, 105, 0.45);
  }

  /* Order Summary Right Card */
  .co-summary-card {
    background: #ffffff;
    border: 1px solid var(--co-border);
    border-radius: 16px;
    padding: 1.75rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    position: sticky;
    top: 90px;
  }

  .co-summary-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 1rem;
    margin-bottom: 1.25rem;
    gap: 10px;
  }

  .co-asm-badge {
    background: #eef2ff;
    color: #4338ca;
    font-family: monospace;
    font-weight: 800;
    font-size: 0.82rem;
    padding: 5px 10px;
    border-radius: 8px;
    border: 1px solid #c7d2fe;
    white-space: nowrap;
    flex-shrink: 0;
  }

  .co-detail-box {
    background: #f8fafc;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1.25rem;
    border: 1px solid #f1f5f9;
  }

  .co-detail-title {
    font-weight: 700;
    color: var(--co-text-main);
    font-size: 0.98rem;
    margin-bottom: 0.6rem;
    line-height: 1.4;
  }

  .co-meta-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
    font-size: 0.84rem;
    color: #475569;
  }

  .co-meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  /* Interactive Coupon Box */
  .co-coupon-box {
    background: #f8fafc;
    border: 1.5px dashed #cbd5e1;
    border-radius: 10px;
    padding: 0.85rem;
    margin-bottom: 1.25rem;
  }

  .co-coupon-input-group {
    display: flex;
    gap: 8px;
  }

  .co-coupon-input {
    flex: 1;
    padding: 0.55rem 0.85rem;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 0.85rem;
    text-transform: uppercase;
    font-weight: 700;
    font-family: monospace;
  }

  .co-btn-coupon {
    padding: 0.55rem 1rem;
    background: #0f172a;
    color: #ffffff;
    border: none;
    border-radius: 8px;
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
    transition: background 0.2s ease;
  }
  .co-btn-coupon:hover {
    background: #1e293b;
  }

  /* Price Line Items */
  .co-price-rows {
    display: flex;
    flex-direction: column;
    gap: 9px;
    font-size: 0.9rem;
    margin-bottom: 1.25rem;
  }

  .co-price-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: #475569;
  }

  .co-price-total-row {
    border-top: 2px solid #0f172a;
    padding-top: 12px;
    margin-top: 6px;
    display: flex;
    justify-content: space-between;
    align-items: baseline;
  }

  .co-total-amount {
    font-weight: 900;
    font-size: 1.85rem;
    color: #047857;
    font-family: system-ui, -apple-system, sans-serif;
  }

  /* Trust & Guarantee Checks */
  .co-guarantees {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 12px;
    padding: 1rem;
    font-size: 0.82rem;
    color: #166534;
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .co-guarantee-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
  }

  /* CRITICAL: Modal Overlays CSS (Fixed, Hidden by default, Never flow in document) */
  .modal-overlay {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    background: rgba(15, 23, 42, 0.72) !important;
    backdrop-filter: blur(5px) !important;
    -webkit-backdrop-filter: blur(5px) !important;
    z-index: 999999 !important;
    display: none !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 1.25rem !important;
    box-sizing: border-box !important;
  }

  .modal-overlay.active {
    display: flex !important;
  }

  .modal-box {
    background: #ffffff !important;
    border-radius: 18px !important;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35) !important;
    width: 100% !important;
    max-width: 520px !important;
    padding: 2.25rem !important;
    position: relative !important;
    animation: coModalFade 0.25s cubic-bezier(0.16, 1, 0.3, 1) !important;
  }

  @keyframes coModalFade {
    from {
      opacity: 0;
      transform: scale(0.94) translateY(12px);
    }
    to {
      opacity: 1;
      transform: scale(1) translateY(0);
    }
  }

  .co-spinner {
    width: 64px;
    height: 64px;
    border: 4px solid #e2e8f0;
    border-top: 4px solid var(--co-primary);
    border-radius: 50%;
    animation: coSpin 0.9s linear infinite;
    margin: 0 auto 1.25rem auto;
  }

  @keyframes coSpin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }

  /* Copy Button Pill */
  .btn-copy-code {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #334155;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 0.72rem;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: all 0.15s ease;
  }
  .btn-copy-code:hover {
    background: #f1f5f9;
    color: var(--co-primary);
    border-color: var(--co-primary);
  }
</style>

<div class="checkout-container-wrap">
  <div class="co-max-width">

    <!-- Top Breadcrumb & Trust Bar -->
    <div class="co-topbar">
      <div>
        <a href="<?php echo $isStudent ? '/student/assignments.php' : '/'; ?>" class="co-back-link">
          <i class="fa-solid fa-arrow-left"></i> <?php echo $isStudent ? 'Return to My Assignments' : 'Return to Home'; ?>
        </a>
        <h1 class="co-page-title">
          <i class="fa-solid fa-shield-halved" style="color:#10b981;"></i> Secure Payment Checkout
        </h1>
      </div>

      <div class="co-trust-pills">
        <span class="co-trust-pill">
          <i class="fa-solid fa-lock" style="color:#10b981;"></i> 256-Bit SSL Encrypted
        </span>
        <span class="co-trust-pill">
          <i class="fa-solid fa-circle-check" style="color:#6366f1;"></i> PCI-DSS Level 1 Verified
        </span>
        <?php if (!empty($gw['razorpay']['test_mode'])): ?>
          <span class="co-trust-pill" style="border-color:#fef08a; background:#fefce8; color:#854d0e;">
            <i class="fa-solid fa-flask"></i> Sandbox Test Mode
          </span>
        <?php endif; ?>
      </div>
    </div>

    <?php if (!$asm): ?>
      <!-- State 1: No Order Selected -->
      <div class="co-card" style="text-align:center; max-width:620px; margin: 3rem auto; padding:3.5rem 2rem;">
        <div style="width:72px; height:72px; border-radius:50%; background:#f1f5f9; color:#64748b; display:flex; align-items:center; justify-content:center; font-size:2.2rem; margin:0 auto 1.5rem auto;">
          <i class="fa-solid fa-receipt"></i>
        </div>
        <h2 style="font-size:1.5rem; color:#0f172a; margin-bottom:0.6rem; font-weight:800;">No Pending Assignment Order Specified</h2>
        <p style="color:#64748b; font-size:0.95rem; margin-bottom:2rem; line-height:1.6;">
          To proceed with checkout, please select an assignment from your student portal dashboard or submit a new assignment brief.
        </p>
        <div style="display:flex; justify-content:center; gap:12px; flex-wrap:wrap;">
          <?php if ($isStudent): ?>
            <a href="/student/new-assignment.php" class="btn btn-primary"><i class="fa-solid fa-circle-plus"></i> Submit New Assignment</a>
            <a href="/student/assignments.php" class="btn btn-outline"><i class="fa-solid fa-list-check"></i> View My Assignments</a>
          <?php else: ?>
            <a href="/submit-assignment.php" class="btn btn-primary"><i class="fa-solid fa-circle-plus"></i> Submit Assignment</a>
            <a href="/login.php" class="btn btn-outline"><i class="fa-solid fa-right-to-bracket"></i> Student Login</a>
          <?php endif; ?>
        </div>
      </div>

    <?php elseif ($isAlreadyPaid): ?>
      <!-- State 2: Already Paid -->
      <div class="co-card" style="text-align:center; max-width:620px; margin: 3rem auto; padding:3.5rem 2rem; border-top: 5px solid #10b981;">
        <div style="width:72px; height:72px; border-radius:50%; background:#dcfce7; color:#10b981; display:flex; align-items:center; justify-content:center; font-size:2.2rem; margin:0 auto 1.25rem auto;">
          <i class="fa-solid fa-check-double"></i>
        </div>
        <span class="badge badge-success" style="margin-bottom:0.75rem; font-size:0.85rem; padding:6px 14px;">PAID IN FULL</span>
        <h2 style="font-size:1.6rem; color:#0f172a; margin-bottom:0.5rem; font-weight:800;">Order <?php echo htmlspecialchars($asm['assignment_id']); ?> is Settled</h2>
        <p style="color:#64748b; font-size:0.95rem; margin-bottom:1.75rem; line-height:1.6;">
          This assignment has been fully authorized and paid on <strong><?php echo htmlspecialchars($existingPayment['payment_date'] ?? date('Y-m-d')); ?></strong>.<br>
          Reference Transaction ID: <code style="font-weight:700; color:#4f46e5;"><?php echo htmlspecialchars($existingPayment['transaction_id'] ?? 'TRX-SETTLED'); ?></code>
        </p>
        <div style="display:flex; justify-content:center; gap:12px; flex-wrap:wrap;">
          <a href="/student/invoice.php?id=<?php echo urlencode($asm['assignment_id']); ?>" target="_blank" class="btn btn-primary">
            <i class="fa-solid fa-file-invoice"></i> View / Download Tax Invoice
          </a>
          <a href="/student/assignment-detail.php?id=<?php echo urlencode($asm['assignment_id']); ?>" class="btn btn-outline">
            <i class="fa-solid fa-arrow-up-right-from-square"></i> View Assignment Workspace
          </a>
        </div>
      </div>

    <?php else: ?>
      <!-- State 3: Main Active Checkout View -->
      <div class="co-grid" id="checkoutGrid">
        
        <!-- Left Column: Payment Methods -->
        <div>
          <div class="co-card">
            
            <div style="margin-bottom: 1.25rem;">
              <h3 style="font-size: 1.25rem; color:#0f172a; margin:0 0 0.35rem 0; font-weight:800;">Select Payment Method</h3>
              <p style="color:#64748b; font-size:0.88rem; margin:0;">
                All major Credit/Debit Cards, UPI QR Code, NetBanking, and PayPal accepted.
              </p>
            </div>

            <!-- Segmented Gateway Tabs -->
            <div class="co-tab-nav" role="tablist">
              <button type="button" class="co-tab-btn active" data-target="tab-card">
                <i class="fa-solid fa-credit-card" style="color:#6366f1;"></i> Cards (Visa / MC / Amex)
              </button>
              <button type="button" class="co-tab-btn" data-target="tab-razorpay">
                <i class="fa-solid fa-bolt" style="color:#059669;"></i> Razorpay UPI / QR <span class="co-tab-badge">Fast</span>
              </button>
              <button type="button" class="co-tab-btn" data-target="tab-paypal">
                <i class="fa-brands fa-paypal" style="color:#2563eb;"></i> PayPal
              </button>
              <button type="button" class="co-tab-btn" data-target="tab-bank">
                <i class="fa-solid fa-building-columns" style="color:#92400e;"></i> Wire Transfer
              </button>
            </div>

            <!-- Tab 1: Credit / Debit Card -->
            <div id="tab-card" class="co-tab-pane">
              <form id="cardPaymentForm" onsubmit="event.preventDefault(); processCheckoutPayment('Stripe Credit Card');">
                
                <!-- Dynamic 3D Card Preview -->
                <div class="co-virtual-card">
                  <div class="co-vcard-top">
                    <div class="co-chip"></div>
                    <div id="cardBrandIcon" style="font-size: 2rem;">
                      <i class="fa-brands fa-cc-visa"></i>
                    </div>
                  </div>

                  <div id="previewCardNumber" class="co-vcard-num">
                    •••• •••• •••• ••••
                  </div>

                  <div class="co-vcard-bottom">
                    <div>
                      <small style="font-size:0.65rem; opacity:0.75; text-transform:uppercase; letter-spacing:1px; display:block;">Cardholder Name</small>
                      <span id="previewCardHolder" style="font-weight:700; font-size:0.95rem; text-transform:uppercase; letter-spacing:0.5px;">
                        <?php echo htmlspecialchars($user['name'] ?? 'STUDENT NAME'); ?>
                      </span>
                    </div>
                    <div style="text-align:right;">
                      <small style="font-size:0.65rem; opacity:0.75; text-transform:uppercase; letter-spacing:1px; display:block;">Expires</small>
                      <span id="previewCardExpiry" style="font-family: monospace; font-size:0.95rem; font-weight:700;">MM/YY</span>
                    </div>
                  </div>
                </div>

                <div class="co-form-group">
                  <label class="co-label" for="cardHolderInput">Cardholder Full Name *</label>
                  <input type="text" id="cardHolderInput" class="co-input" required placeholder="Name on card" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>">
                </div>

                <div class="co-form-group">
                  <label class="co-label" for="cardNumberInput">Card Number *</label>
                  <div class="co-input-wrap">
                    <input type="text" id="cardNumberInput" class="co-input" required placeholder="4242 •••• •••• 4242" maxlength="19" style="font-family:monospace; letter-spacing:1px;">
                    <span class="co-input-icon" id="inlineCardIcon">
                      <i class="fa-regular fa-credit-card"></i>
                    </span>
                  </div>
                </div>

                <div class="co-row-2">
                  <div class="co-form-group">
                    <label class="co-label" for="cardExpiryInput">Expiration Date *</label>
                    <input type="text" id="cardExpiryInput" class="co-input" required placeholder="MM / YY" maxlength="5" style="font-family:monospace;">
                  </div>
                  <div class="co-form-group">
                    <label class="co-label" for="cardCvvInput">CVV / CVC *</label>
                    <div class="co-input-wrap">
                      <input type="password" id="cardCvvInput" class="co-input" required placeholder="•••" maxlength="4" style="font-family:monospace;">
                      <span class="co-input-icon" title="3 digits on back of Visa/Mastercard, 4 digits for Amex" style="cursor:help;">
                        <i class="fa-solid fa-circle-question" style="font-size:0.95rem;"></i>
                      </span>
                    </div>
                  </div>
                </div>

                <div style="display:flex; align-items:center; gap:8px; margin-bottom:1.5rem;">
                  <input type="checkbox" id="saveCardCheck" checked style="width:16px; height:16px; cursor:pointer;">
                  <label for="saveCardCheck" style="font-size:0.82rem; color:#64748b; margin:0; cursor:pointer;">
                    Encrypt & save token securely for 1-click payment on future revisions
                  </label>
                </div>

                <button type="submit" class="co-btn-pay">
                  <i class="fa-solid fa-lock"></i> Pay <span class="dyn-pay-amount"><?php echo format_currency_amount($finalPrice, $currency); ?></span> Now
                </button>
              </form>
            </div>

            <!-- Tab 2: Razorpay / UPI / QR / NetBanking -->
            <div id="tab-razorpay" class="co-tab-pane" style="display:none;">
              
              <!-- Instant Modal Box -->
              <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; padding:1.25rem; margin-bottom:1.5rem; text-align:center;">
                <div style="display:inline-flex; align-items:center; gap:6px; background:#dcfce7; color:#15803d; padding:4px 10px; border-radius:20px; font-size:0.75rem; font-weight:700; margin-bottom:0.5rem;">
                  <i class="fa-solid fa-shield-halved"></i> Official Razorpay Gateway (Test Key: <?php echo htmlspecialchars(substr($gw['razorpay']['key_id'], 0, 12)); ?>...)
                </div>
                <h4 style="color:#166534; font-size:1.15rem; font-weight:800; margin:0 0 0.35rem 0;">Instant 1-Click Razorpay Gateway</h4>
                <p style="color:#15803d; font-size:0.85rem; margin:0 0 1.25rem 0; line-height:1.5;">
                  Pay in seconds with Google Pay, PhonePe, Paytm, BHIM UPI, NetBanking, Debit/Credit Cards, or Wallets.
                </p>
                <button type="button" class="co-btn-razorpay" onclick="launchOfficialRazorpayModal()">
                  <i class="fa-solid fa-bolt"></i> Pay <span class="dyn-pay-amount"><?php echo format_currency_amount($finalPrice, $currency); ?></span> with Razorpay Modal
                </button>
              </div>

              <!-- Direct UPI Divider -->
              <div style="text-align:center; color:#94a3b8; font-size:0.76rem; font-weight:800; margin-bottom:1.25rem; position:relative;">
                <span style="background:#ffffff; padding:0 12px; position:relative; z-index:1; letter-spacing:0.5px;">OR SCAN DIRECT UPI QR / NETBANKING BELOW</span>
                <div style="position:absolute; top:50%; left:0; right:0; height:1px; background:#e2e8f0;"></div>
              </div>

              <!-- Direct UPI QR Code Box -->
              <div style="display:flex; flex-direction:column; align-items:center; margin-bottom:1.5rem;">
                <div style="padding:14px; background:#ffffff; border:2px solid #059669; border-radius:16px; box-shadow:0 6px 18px rgba(5, 150, 105, 0.12); margin-bottom:0.8rem; text-align:center;">
                  <svg width="180" height="180" viewBox="0 0 180 180" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:block;">
                    <rect width="180" height="180" fill="#ffffff"/>
                    <rect x="15" y="15" width="45" height="45" rx="4" fill="#0f172a"/>
                    <rect x="23" y="23" width="29" height="29" rx="2" fill="#ffffff"/>
                    <rect x="29" y="29" width="17" height="17" rx="2" fill="#059669"/>
                    
                    <rect x="120" y="15" width="45" height="45" rx="4" fill="#0f172a"/>
                    <rect x="128" y="23" width="29" height="29" rx="2" fill="#ffffff"/>
                    <rect x="134" y="29" width="17" height="17" rx="2" fill="#059669"/>

                    <rect x="15" y="120" width="45" height="45" rx="4" fill="#0f172a"/>
                    <rect x="23" y="128" width="29" height="29" rx="2" fill="#ffffff"/>
                    <rect x="29" y="134" width="17" height="17" rx="2" fill="#059669"/>

                    <rect x="70" y="20" width="8" height="8" fill="#0f172a"/>
                    <rect x="85" y="20" width="16" height="8" fill="#059669"/>
                    <rect x="70" y="35" width="24" height="8" fill="#0f172a"/>
                    <rect x="100" y="35" width="8" height="8" fill="#0f172a"/>
                    <rect x="70" y="50" width="8" height="16" fill="#059669"/>
                    <rect x="85" y="50" width="16" height="8" fill="#0f172a"/>
                    
                    <rect x="20" y="70" width="16" height="8" fill="#0f172a"/>
                    <rect x="45" y="70" width="8" height="16" fill="#059669"/>
                    <rect x="70" y="75" width="40" height="40" rx="8" fill="#059669"/>
                    <circle cx="90" cy="95" r="14" fill="#ffffff"/>
                    <text x="90" y="99" font-family="system-ui, sans-serif" font-weight="bold" font-size="11" fill="#059669" text-anchor="middle">AAH</text>

                    <rect x="120" y="70" width="16" height="8" fill="#0f172a"/>
                    <rect x="145" y="70" width="16" height="16" fill="#059669"/>
                    <rect x="120" y="85" width="8" height="24" fill="#0f172a"/>

                    <rect x="70" y="125" width="16" height="8" fill="#059669"/>
                    <rect x="95" y="125" width="8" height="16" fill="#0f172a"/>
                    <rect x="110" y="125" width="16" height="8" fill="#0f172a"/>
                    <rect x="135" y="125" width="25" height="8" fill="#059669"/>
                    <rect x="70" y="145" width="35" height="8" fill="#0f172a"/>
                    <rect x="115" y="145" width="16" height="16" fill="#059669"/>
                    <rect x="140" y="145" width="20" height="8" fill="#0f172a"/>
                  </svg>
                </div>
                
                <div style="display:flex; align-items:center; gap:8px; font-size:0.85rem; color:#334155; font-weight:600;">
                  <span>UPI ID:</span>
                  <code style="background:#f1f5f9; padding:3px 8px; border-radius:6px; color:#059669; font-weight:700;" id="copyUpiVal"><?php echo htmlspecialchars($gw['razorpay']['upi_id'] ?? 'aceassignment@okhdfcbank'); ?></code>
                  <button type="button" class="btn-copy-code" onclick="copyToClipboard('<?php echo htmlspecialchars($gw['razorpay']['upi_id'] ?? 'aceassignment@okhdfcbank'); ?>', this)">
                    <i class="fa-regular fa-copy"></i> Copy
                  </button>
                </div>
              </div>

              <!-- UPI VPA Input Field -->
              <div class="co-form-group">
                <label class="co-label" for="upiVpaInput">Or Enter Your VPA / UPI ID</label>
                <div style="display:flex; gap:8px;">
                  <input type="text" id="upiVpaInput" class="co-input" placeholder="username@okhdfcbank / 9876543210@upi" style="font-family:monospace;">
                  <button type="button" class="btn btn-outline" style="padding:0.75rem 1.25rem; font-weight:700; white-space:nowrap;" onclick="verifyUpiAddress()">Verify</button>
                </div>
                <small id="upiVerifyMsg" style="display:block; margin-top:5px; font-size:0.8rem;"></small>
              </div>

              <!-- NetBanking Bank Selector -->
              <div class="co-form-group">
                <label class="co-label" for="netbankingBankSelect">Select NetBanking Bank</label>
                <select id="netbankingBankSelect" class="co-input">
                  <option value="HDFC Bank">HDFC Bank</option>
                  <option value="ICICI Bank">ICICI Bank</option>
                  <option value="State Bank of India">State Bank of India (SBI)</option>
                  <option value="Axis Bank">Axis Bank</option>
                  <option value="Kotak Mahindra Bank">Kotak Mahindra Bank</option>
                  <option value="Punjab National Bank">Punjab National Bank</option>
                  <option value="Bank of Baroda">Bank of Baroda</option>
                  <option value="Other Bank">Other Indian Commercial Bank</option>
                </select>
              </div>

              <button type="button" class="co-btn-razorpay" onclick="processCheckoutPayment('Razorpay UPI / NetBanking')">
                <i class="fa-solid fa-check"></i> Authorize & Pay <span class="dyn-pay-amount"><?php echo format_currency_amount($finalPrice, $currency); ?></span> via Razorpay
              </button>
            </div>

            <!-- Tab 3: PayPal Express Checkout -->
            <div id="tab-paypal" class="co-tab-pane" style="display:none;">
              <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:12px; padding:1.25rem; margin-bottom:1.5rem; text-align:center;">
                <i class="fa-brands fa-paypal" style="font-size:2.2rem; color:#2563eb; margin-bottom:0.4rem;"></i>
                <h4 style="color:#1e40af; font-size:1.1rem; font-weight:800; margin:0 0 0.35rem 0;">PayPal Fast-Track International</h4>
                <p style="color:#1d4ed8; font-size:0.85rem; margin:0; line-height:1.5;">
                  Pay securely using your PayPal Balance, linked International Bank Account, or Credit Card.
                </p>
              </div>

              <div style="display:flex; flex-direction:column; gap:12px; margin-bottom:1.5rem;">
                <button type="button" onclick="processCheckoutPayment('PayPal')" style="background:#ffc439; border:none; border-radius:30px; padding:14px 20px; font-weight:800; color:#111; font-size:1.05rem; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:10px; box-shadow:0 3px 10px rgba(255, 196, 57, 0.4); transition:transform 0.15s ease;">
                  <i class="fa-brands fa-paypal" style="color:#003087; font-size:1.4rem;"></i> 
                  <span>Pay with <strong>PayPal</strong></span>
                </button>

                <button type="button" onclick="processCheckoutPayment('PayPal Credit / Pay Later')" style="background:#003087; border:none; border-radius:30px; padding:14px 20px; font-weight:800; color:#fff; font-size:1rem; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:10px; box-shadow:0 3px 10px rgba(0, 48, 135, 0.35); transition:transform 0.15s ease;">
                  <span>PayPal <strong>Pay in 4</strong> / Interest-Free Installments</span>
                </button>
              </div>

              <div style="font-size:0.82rem; color:#64748b; text-align:center; background:#f8fafc; border-radius:8px; padding:10px;">
                <i class="fa-solid fa-shield-halved" style="color:#2563eb;"></i> Covered by PayPal Buyer Protection for Academic & Research Writing Services.
              </div>
            </div>

            <!-- Tab 4: Direct Bank Wire / Swift Transfer -->
            <div id="tab-bank" class="co-tab-pane" style="display:none;">
              <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:12px; padding:1.25rem; margin-bottom:1.5rem;">
                <h4 style="color:#92400e; font-size:1.05rem; font-weight:800; margin:0 0 0.35rem 0;">
                  <i class="fa-solid fa-building-columns"></i> Official Wire Transfer Instructions
                </h4>
                <p style="color:#78350f; font-size:0.85rem; margin:0; line-height:1.5;">
                  Recommended for dissertations, theses, and university institutional billing.
                </p>
              </div>

              <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:1.25rem; font-size:0.88rem; line-height:1.8; margin-bottom:1.5rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                  <span style="color:#64748b;">Beneficiary:</span>
                  <strong>Ace Assignment Helps Global Ltd.</strong>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                  <span style="color:#64748b;">Bank Name:</span>
                  <strong>Barclays Bank UK / Standard Chartered</strong>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                  <span style="color:#64748b;">IBAN / Account:</span>
                  <div>
                    <code style="font-weight:700; color:#0f172a;">GB29 BARK 2000 1584 9283 01</code>
                    <button type="button" class="btn-copy-code" style="margin-left:6px;" onclick="copyToClipboard('GB29BARK20001584928301', this)">
                      <i class="fa-regular fa-copy"></i> Copy
                    </button>
                  </div>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                  <span style="color:#64748b;">Swift / BIC:</span>
                  <div>
                    <code style="font-weight:700; color:#0f172a;">BARKGB22XXX</code>
                    <button type="button" class="btn-copy-code" style="margin-left:6px;" onclick="copyToClipboard('BARKGB22XXX', this)">
                      <i class="fa-regular fa-copy"></i> Copy
                    </button>
                  </div>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid #e2e8f0; padding-top:8px; margin-top:6px;">
                  <span style="color:#64748b;">Payment Reference:</span>
                  <div>
                    <strong style="color:#2563eb; font-size:0.95rem;"><?php echo htmlspecialchars($asm['assignment_id']); ?></strong>
                    <button type="button" class="btn-copy-code" style="margin-left:6px;" onclick="copyToClipboard('<?php echo htmlspecialchars($asm['assignment_id']); ?>', this)">
                      <i class="fa-regular fa-copy"></i> Copy
                    </button>
                  </div>
                </div>
              </div>

              <button type="button" class="btn btn-outline btn-lg" style="width:100%; font-weight:700;" onclick="processCheckoutPayment('Bank Wire Transfer')">
                <i class="fa-solid fa-paper-plane"></i> Notify Settlement via Bank Wire
              </button>
            </div>

          </div>

          <!-- Bottom Payment Trust Badges -->
          <div style="display:flex; justify-content:center; align-items:center; gap:22px; color:#94a3b8; font-size:1.8rem; opacity:0.85; flex-wrap:wrap; padding:0.5rem 0;">
            <i class="fa-brands fa-cc-visa" title="Visa"></i>
            <i class="fa-brands fa-cc-mastercard" title="Mastercard"></i>
            <i class="fa-brands fa-cc-amex" title="American Express"></i>
            <i class="fa-brands fa-google-pay" title="Google Pay"></i>
            <i class="fa-brands fa-apple-pay" title="Apple Pay"></i>
            <i class="fa-brands fa-paypal" title="PayPal"></i>
            <i class="fa-solid fa-shield-halved" title="256-Bit SSL Encrypted" style="font-size:1.35rem; color:#10b981;"></i>
          </div>
        </div>

        <!-- Right Column: Sticky Order Summary -->
        <div>
          <div class="co-summary-card">
            
            <div class="co-summary-header">
              <h3 style="font-size:1.2rem; color:#0f172a; margin:0; font-weight:800;">Order Summary</h3>
              <span class="co-asm-badge"><?php echo htmlspecialchars($asm['assignment_id']); ?></span>
            </div>

            <!-- Assignment Info Box -->
            <div class="co-detail-box">
              <div class="co-detail-title">
                <?php echo htmlspecialchars($asm['title']); ?>
              </div>
              <div class="co-meta-list">
                <div class="co-meta-item">
                  <i class="fa-solid fa-graduation-cap" style="color:var(--co-primary); width:16px;"></i>
                  <span><?php echo htmlspecialchars($asm['subject']); ?> &bull; <?php echo htmlspecialchars($asm['assignment_type']); ?></span>
                </div>
                <div class="co-meta-item">
                  <i class="fa-solid fa-file-lines" style="color:#0284c7; width:16px;"></i>
                  <span><?php echo htmlspecialchars($asm['word_count']); ?> words (<?php echo htmlspecialchars($asm['pages']); ?> pages)</span>
                </div>
                <div class="co-meta-item">
                  <i class="fa-solid fa-clock" style="color:#d97706; width:16px;"></i>
                  <span>Target SLA: <?php echo date('M d, Y H:i', strtotime($asm['deadline'])); ?></span>
                </div>
              </div>
            </div>

            <!-- Interactive Promo Code Section -->
            <div class="co-coupon-box" id="couponBox">
              <div style="font-size:0.82rem; font-weight:700; color:#334155; margin-bottom:8px; display:flex; justify-content:space-between; align-items:center;">
                <span><i class="fa-solid fa-tag" style="color:#059669;"></i> Promo / Coupon Code</span>
                <span id="couponStatusBadge" style="display:<?php echo ($discountAmount > 0) ? 'inline-block' : 'none'; ?>; color:#059669; font-size:0.75rem; font-weight:800;">ACTIVE</span>
              </div>

              <!-- State A: When coupon is applied -->
              <div id="coCouponAppliedWrap" style="display:<?php echo ($discountAmount > 0) ? 'flex' : 'none'; ?>; justify-content:space-between; align-items:center; background:#ecfdf5; border:1px solid #a7f3d0; border-radius:8px; padding:6px 10px;">
                <div style="font-size:0.82rem; font-weight:700; color:#065f46;">
                  <span id="coAppliedCodeText"><?php echo htmlspecialchars($discountCode); ?></span>
                  <span style="font-size:0.75rem; color:#047857; font-weight:600; margin-left:4px;">(Discount Applied)</span>
                </div>
                <button type="button" class="btn btn-sm" style="background:#fee2e2; color:#dc2626; border:none; padding:3px 8px; font-weight:700; border-radius:6px; font-size:0.75rem; cursor:pointer;" onclick="removeCheckoutCoupon()">
                  Remove
                </button>
              </div>

              <!-- State B: When no coupon is applied -->
              <div id="coCouponInputWrap" class="co-coupon-input-group" style="display:<?php echo ($discountAmount > 0) ? 'none' : 'flex'; ?>;">
                <input type="text" id="coCouponInput" class="co-coupon-input" placeholder="e.g. SUPER30" value="">
                <button type="button" id="coApplyCouponBtn" class="co-btn-coupon" onclick="applyCheckoutCoupon()">Apply</button>
              </div>

              <small id="coCouponFeedback" style="display:block; margin-top:6px; font-size:0.78rem;"></small>
            </div>

            <!-- Price Breakdown Table -->
            <div class="co-price-rows">
              <div class="co-price-row">
                <span>Base Rate Subtotal:</span>
                <strong id="coSubtotalVal"><?php echo format_currency_amount($subtotal, $currency); ?></strong>
              </div>

              <div class="co-price-row" id="coDiscountRow" style="<?php echo ($discountAmount > 0) ? '' : 'display:none;'; ?>">
                <span style="color:#059669; font-weight:600;">Discount Applied (<span id="coDiscountCodeLbl"><?php echo htmlspecialchars($discountCode); ?></span>):</span>
                <span style="color:#059669; font-weight:700;" id="coDiscountVal">-<?php echo format_currency_amount($discountAmount, $currency); ?></span>
              </div>

              <div class="co-price-row">
                <span>256-Bit SSL Processing:</span>
                <span style="color:#10b981; font-weight:700; font-size:0.85rem;">FREE</span>
              </div>

              <div class="co-price-row">
                <span>Turnitin Plagiarism Report:</span>
                <span style="color:#10b981; font-weight:700; font-size:0.85rem;">INCLUDED</span>
              </div>

              <div class="co-price-total-row">
                <span style="font-weight:800; font-size:1.1rem; color:#0f172a;">Total Payable:</span>
                <div style="text-align:right;">
                  <div class="co-total-amount dyn-pay-amount" id="coTotalVal">
                    <?php echo format_currency_amount($finalPrice, $currency); ?>
                  </div>
                  <small style="color:#64748b; font-size:0.75rem; display:block; margin-top:2px;">
                    Settled in <?php echo htmlspecialchars($currency); ?> &bull; No hidden taxes
                  </small>
                </div>
              </div>
            </div>

            <!-- Guarantees List -->
            <div class="co-guarantees">
              <div class="co-guarantee-item">
                <i class="fa-solid fa-check" style="color:#10b981;"></i> 100% Money-Back Satisfaction Guarantee
              </div>
              <div class="co-guarantee-item">
                <i class="fa-solid fa-check" style="color:#10b981;"></i> Free Unlimited Revisions for 14 Days
              </div>
              <div class="co-guarantee-item">
                <i class="fa-solid fa-check" style="color:#10b981;"></i> Instant Turnitin 0% Plagiarism & AI Report
              </div>
              <div class="co-guarantee-item">
                <i class="fa-solid fa-shield-halved" style="color:#10b981;"></i> Strict Academic Confidentiality & NDA
              </div>
            </div>

          </div>
        </div>

      </div>
    <?php endif; ?>

  </div>
</div>

<!-- =========================================================================
     MODAL OVERLAYS (Fixed position, completely hidden from normal document flow)
     ========================================================================= -->

<!-- Modal 1: Processing Overlay -->
<div id="checkoutProcessingModal" class="modal-overlay" role="dialog" aria-modal="true">
  <div class="modal-box" style="text-align:center; max-width:460px;">
    <div class="co-spinner"></div>
    <h3 id="procTitle" style="font-size:1.35rem; color:#0f172a; margin:0 0 0.5rem 0; font-weight:800;">Authorizing Payment</h3>
    <p id="procSub" style="color:#64748b; font-size:0.92rem; margin:0 0 1.5rem 0; line-height:1.6;">
      Securing payment channel and contacting banking gateway...
    </p>

    <div style="background:#f8fafc; border-radius:10px; padding:12px; font-size:0.82rem; color:#64748b; border:1px solid #e2e8f0;">
      <i class="fa-solid fa-shield-halved" style="color:#10b981;"></i> Please do not close or refresh this window while authorization completes.
    </div>
  </div>
</div>

<!-- Modal 2: Payment Success Screen Overlay -->
<div id="checkoutSuccessModal" class="modal-overlay" role="dialog" aria-modal="true">
  <div class="modal-box" style="text-align:center; max-width:520px; padding:2.5rem 2rem;">
    <div style="width:76px; height:76px; border-radius:50%; background:#dcfce7; color:#10b981; display:flex; align-items:center; justify-content:center; font-size:2.4rem; margin:0 auto 1.25rem auto; box-shadow:0 8px 25px rgba(16, 185, 129, 0.25);">
      <i class="fa-solid fa-check"></i>
    </div>

    <span class="badge badge-success" style="margin-bottom:0.6rem; font-size:0.85rem; padding:5px 12px;">PAYMENT VERIFIED & SETTLED</span>
    <h2 style="font-size:1.65rem; color:#0f172a; margin:0 0 0.4rem 0; font-weight:800;">Order Confirmed!</h2>
    <p style="color:#64748b; font-size:0.92rem; margin:0 0 1.5rem 0; line-height:1.5;">
      Thank you! Your assignment has been moved to <strong>Confirmed</strong> status and our academic allocator has prioritized specialist matching.
    </p>

    <!-- Receipt Details Box -->
    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:1.25rem; text-align:left; font-size:0.88rem; line-height:1.8; margin-bottom:1.5rem;">
      <div style="display:flex; justify-content:space-between;">
        <span style="color:#64748b;">Assignment ID:</span>
        <strong id="recAsmId"><?php echo htmlspecialchars($asmId); ?></strong>
      </div>
      <div style="display:flex; justify-content:space-between;">
        <span style="color:#64748b;">Transaction ID:</span>
        <code style="color:#4f46e5; font-weight:700;" id="recTxId">ch_stripe_...</code>
      </div>
      <div style="display:flex; justify-content:space-between;">
        <span style="color:#64748b;">Amount Settled:</span>
        <strong style="color:#047857;" id="recAmount"><?php echo format_currency_amount($finalPrice, $currency); ?> (<?php echo htmlspecialchars($currency); ?>)</strong>
      </div>
      <div style="display:flex; justify-content:space-between;">
        <span style="color:#64748b;">Payment Method:</span>
        <span id="recMethod">Stripe Credit Card</span>
      </div>
    </div>

    <div style="display:flex; flex-direction:column; gap:10px;">
      <a id="recInvoiceBtn" href="/student/invoice.php?id=<?php echo urlencode($asmId); ?>" target="_blank" class="btn btn-primary" style="font-weight:700; padding:0.9rem; justify-content:center;">
        <i class="fa-solid fa-file-pdf"></i> Download Official Tax Invoice PDF
      </a>
      <a href="/student/assignments.php" class="btn btn-outline" style="font-weight:600; padding:0.9rem; justify-content:center;">
        <i class="fa-solid fa-gauge"></i> Go to Student Dashboard
      </a>
      <a href="https://wa.me/918233432123?text=Hi%20Ace%20Assignment%20Helps%2C%20I%20have%20paid%20for%20order%20<?php echo urlencode($asmId); ?>" target="_blank" class="btn btn-sm" style="color:#059669; font-weight:700; margin-top:4px; justify-content:center;">
        <i class="fa-brands fa-whatsapp"></i> Get Live Updates on WhatsApp (+91 8233432123)
      </a>
    </div>
  </div>
</div>

<!-- =========================================================================
     JAVASCRIPT LOGIC
     ========================================================================= -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  // Tab Switching
  const tabBtns = document.querySelectorAll('.co-tab-btn');
  const tabPanes = document.querySelectorAll('.co-tab-pane');

  tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      tabBtns.forEach(b => b.classList.remove('active'));
      tabPanes.forEach(p => p.style.display = 'none');

      btn.classList.add('active');
      const target = document.getElementById(btn.getAttribute('data-target'));
      if (target) target.style.display = 'block';
    });
  });

  // Credit Card Formatting & Live Card Preview
  const numInput = document.getElementById('cardNumberInput');
  const holderInput = document.getElementById('cardHolderInput');
  const expInput = document.getElementById('cardExpiryInput');

  const prevNum = document.getElementById('previewCardNumber');
  const prevHolder = document.getElementById('previewCardHolder');
  const prevExp = document.getElementById('previewCardExpiry');
  const brandIcon = document.getElementById('cardBrandIcon');
  const inlineIcon = document.getElementById('inlineCardIcon');

  if (numInput) {
    numInput.addEventListener('input', (e) => {
      let val = e.target.value.replace(/\D/g, '').substring(0, 16);
      let formatted = val.match(/.{1,4}/g)?.join(' ') || '';
      e.target.value = formatted;
      if (prevNum) prevNum.textContent = formatted || '•••• •••• •••• ••••';

      // Brand Detection
      if (val.startsWith('4')) {
        if (brandIcon) brandIcon.innerHTML = '<i class="fa-brands fa-cc-visa"></i>';
        if (inlineIcon) inlineIcon.innerHTML = '<i class="fa-brands fa-cc-visa" style="color:#2563eb;"></i>';
      } else if (/^5[1-5]/.test(val) || /^2[2-7]/.test(val)) {
        if (brandIcon) brandIcon.innerHTML = '<i class="fa-brands fa-cc-mastercard"></i>';
        if (inlineIcon) inlineIcon.innerHTML = '<i class="fa-brands fa-cc-mastercard" style="color:#ea580c;"></i>';
      } else if (/^3[47]/.test(val)) {
        if (brandIcon) brandIcon.innerHTML = '<i class="fa-brands fa-cc-amex"></i>';
        if (inlineIcon) inlineIcon.innerHTML = '<i class="fa-brands fa-cc-amex" style="color:#0284c7;"></i>';
      } else if (/^6(?:011|5)/.test(val)) {
        if (brandIcon) brandIcon.innerHTML = '<i class="fa-brands fa-cc-discover"></i>';
        if (inlineIcon) inlineIcon.innerHTML = '<i class="fa-brands fa-cc-discover" style="color:#f97316;"></i>';
      } else {
        if (brandIcon) brandIcon.innerHTML = '<i class="fa-regular fa-credit-card"></i>';
        if (inlineIcon) inlineIcon.innerHTML = '<i class="fa-regular fa-credit-card"></i>';
      }
    });
  }

  if (holderInput) {
    holderInput.addEventListener('input', (e) => {
      if (prevHolder) prevHolder.textContent = e.target.value.trim().toUpperCase() || 'STUDENT NAME';
    });
  }

  if (expInput) {
    expInput.addEventListener('input', (e) => {
      let val = e.target.value.replace(/\D/g, '').substring(0, 4);
      if (val.length >= 2) {
        val = val.substring(0, 2) + '/' + val.substring(2);
      }
      e.target.value = val;
      if (prevExp) prevExp.textContent = val || 'MM/YY';
    });
  }
});

function copyToClipboard(text, btnElem) {
  if (navigator.clipboard) {
    navigator.clipboard.writeText(text).then(() => {
      showCopyFeedback(btnElem);
    }).catch(() => fallbackCopy(text, btnElem));
  } else {
    fallbackCopy(text, btnElem);
  }
}

function fallbackCopy(text, btnElem) {
  const ta = document.createElement('textarea');
  ta.value = text;
  ta.style.position = 'fixed';
  ta.style.opacity = '0';
  document.body.appendChild(ta);
  ta.select();
  document.execCommand('copy');
  document.body.removeChild(ta);
  showCopyFeedback(btnElem);
}

function showCopyFeedback(btnElem) {
  if (!btnElem) return;
  const original = btnElem.innerHTML;
  btnElem.innerHTML = '<i class="fa-solid fa-check" style="color:#10b981;"></i> Copied!';
  setTimeout(() => {
    btnElem.innerHTML = original;
  }, 1800);
}

function verifyUpiAddress() {
  const vpa = document.getElementById('upiVpaInput').value.trim();
  const msg = document.getElementById('upiVerifyMsg');
  if (!vpa || !vpa.includes('@')) {
    msg.style.color = '#dc2626';
    msg.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Invalid UPI ID format. Example: mobile@okhdfcbank';
    return;
  }
  msg.style.color = '#059669';
  msg.innerHTML = '<i class="fa-solid fa-circle-check"></i> UPI ID verified and linked to banking clearinghouse!';
}

// Promo Code AJAX Apply & Remove
function applyCheckoutCoupon() {
  const asmId = "<?php echo htmlspecialchars($asmId); ?>";
  const code = document.getElementById('coCouponInput').value.trim().toUpperCase();
  const feedback = document.getElementById('coCouponFeedback');
  const btn = document.getElementById('coApplyCouponBtn');

  if (!code) {
    feedback.style.color = '#dc2626';
    feedback.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Please enter a promo code.';
    return;
  }

  btn.disabled = true;
  btn.textContent = 'Applying...';

  const bodyData = new URLSearchParams();
  bodyData.append('assignment_id', asmId);
  bodyData.append('code', code);

  fetch('/api.php?action=apply_checkout_coupon', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: bodyData.toString()
  })
  .then(res => res.json())
  .then(data => {
    btn.disabled = false;
    btn.textContent = 'Apply';

    if (data.success) {
      feedback.style.color = '#059669';
      feedback.innerHTML = `<i class="fa-solid fa-circle-check"></i> ${data.message}`;

      // Update price elements
      document.getElementById('coSubtotalVal').textContent = data.formatted_subtotal;
      
      const discRow = document.getElementById('coDiscountRow');
      if (data.discount_amount > 0) {
        discRow.style.display = 'flex';
        document.getElementById('coDiscountCodeLbl').textContent = data.coupon_code;
        document.getElementById('coDiscountVal').textContent = '-' + data.formatted_discount;

        // Switch to applied badge
        const appliedWrap = document.getElementById('coCouponAppliedWrap');
        const inputWrap = document.getElementById('coCouponInputWrap');
        const statusBadge = document.getElementById('couponStatusBadge');
        if (appliedWrap) appliedWrap.style.display = 'flex';
        if (inputWrap) inputWrap.style.display = 'none';
        if (statusBadge) statusBadge.style.display = 'inline-block';
        const codeText = document.getElementById('coAppliedCodeText');
        if (codeText) codeText.textContent = data.coupon_code;
      } else {
        discRow.style.display = 'none';
      }

      // Update all pay amounts on page
      document.querySelectorAll('.dyn-pay-amount').forEach(el => {
        el.textContent = data.formatted_final_price;
      });
      document.getElementById('recAmount').textContent = `${data.formatted_final_price} (${data.currency})`;
    } else {
      feedback.style.color = '#dc2626';
      feedback.innerHTML = `<i class="fa-solid fa-circle-xmark"></i> ${data.message}`;
    }
  })
  .catch(() => {
    btn.disabled = false;
    btn.textContent = 'Apply';
    feedback.style.color = '#dc2626';
    feedback.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Error connecting to server.';
  });
}

function removeCheckoutCoupon() {
  const asmId = "<?php echo htmlspecialchars($asmId); ?>";
  const feedback = document.getElementById('coCouponFeedback');

  const bodyData = new URLSearchParams();
  bodyData.append('assignment_id', asmId);
  bodyData.append('code', '');

  fetch('/api.php?action=apply_checkout_coupon', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: bodyData.toString()
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      document.getElementById('coCouponInput').value = '';
      feedback.style.color = '#64748b';
      feedback.innerHTML = '<i class="fa-solid fa-info-circle"></i> Coupon removed.';

      document.getElementById('coDiscountRow').style.display = 'none';

      const appliedWrap = document.getElementById('coCouponAppliedWrap');
      const inputWrap = document.getElementById('coCouponInputWrap');
      const statusBadge = document.getElementById('couponStatusBadge');
      if (appliedWrap) appliedWrap.style.display = 'none';
      if (inputWrap) inputWrap.style.display = 'flex';
      if (statusBadge) statusBadge.style.display = 'none';

      document.querySelectorAll('.dyn-pay-amount').forEach(el => {
        el.textContent = data.formatted_final_price;
      });
      document.getElementById('recAmount').textContent = `${data.formatted_final_price} (${data.currency})`;
    }
  });
}

// Payment Handlers
function processCheckoutPayment(method) {
  const asmId = "<?php echo htmlspecialchars($asmId); ?>";
  if (!asmId) {
    alert("Missing assignment ID for payment.");
    return;
  }

  const procModal = document.getElementById('checkoutProcessingModal');
  const procTitle = document.getElementById('procTitle');
  const procSub = document.getElementById('procSub');

  // Open processing overlay
  procModal.classList.add('active');

  procTitle.textContent = "Securing Payment Channel...";
  procSub.textContent = `Connecting to ${method} gateway with 256-bit encryption...`;

  setTimeout(() => {
    procTitle.textContent = "Authorizing Transaction...";
    procSub.textContent = "Verifying credentials with banking clearinghouse...";

    const bodyData = new URLSearchParams();
    bodyData.append('assignment_id', asmId);
    bodyData.append('payment_method', method);

    const cardNum = document.getElementById('cardNumberInput') ? document.getElementById('cardNumberInput').value.replace(/\s/g, '') : '';
    if (cardNum) {
      bodyData.append('card_last4', cardNum.slice(-4));
    }
    const upiVpa = document.getElementById('upiVpaInput') ? document.getElementById('upiVpaInput').value.trim() : '';
    if (upiVpa) {
      bodyData.append('upi_vpa', upiVpa);
    }

    fetch('/api.php?action=process_payment', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: bodyData.toString()
    })
    .then(res => res.json())
    .then(data => {
      setTimeout(() => {
        procModal.classList.remove('active');
        if (data.success) {
          document.getElementById('recAsmId').textContent = data.assignment_id || asmId;
          document.getElementById('recTxId').textContent = data.transaction_id || 'TRX-SUCCESS';
          document.getElementById('recAmount').textContent = data.formatted_amount || 'Paid';
          document.getElementById('recMethod').textContent = method;
          if (data.invoice_url) {
            document.getElementById('recInvoiceBtn').href = data.invoice_url;
          }
          document.getElementById('checkoutSuccessModal').classList.add('active');
        } else {
          alert(data.message || 'Payment could not be authorized. Please try again.');
        }
      }, 700);
    })
    .catch(err => {
      procModal.classList.remove('active');
      alert('Network error communicating with payment gateway. Please try again.');
    });

  }, 1000);
}

function launchOfficialRazorpayModal() {
  const asmId = "<?php echo htmlspecialchars($asmId); ?>";
  const studentName = "<?php echo htmlspecialchars($user['name'] ?? 'Student'); ?>";
  const studentEmail = "<?php echo htmlspecialchars($user['email'] ?? 'student@aceassign.com'); ?>";
  const studentPhone = "<?php echo htmlspecialchars($user['phone'] ?? '+91 8233432123'); ?>";

  const procModal = document.getElementById('checkoutProcessingModal');
  const procTitle = document.getElementById('procTitle');
  const procSub = document.getElementById('procSub');

  procModal.classList.add('active');
  procTitle.textContent = "Connecting to Razorpay...";
  procSub.textContent = "Creating secure Razorpay order token...";

  fetch('/api.php?action=create_razorpay_order', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `assignment_id=${encodeURIComponent(asmId)}`
  })
  .then(res => res.json())
  .then(orderData => {
    procModal.classList.remove('active');
    if (!orderData.success) {
      alert(orderData.message || 'Could not initialize Razorpay order.');
      return;
    }

    const options = {
      key: orderData.key_id,
      amount: orderData.amount,
      currency: orderData.currency,
      name: "Ace Assignment Helps",
      description: "Payment for Order " + asmId,
      image: "/assets/image/logo.png",
      order_id: orderData.order_id,
      prefill: {
        name: studentName,
        email: studentEmail,
        contact: studentPhone
      },
      notes: {
        assignment_id: asmId
      },
      theme: {
        color: "#059669"
      },
      handler: function (response) {
        procModal.classList.add('active');
        procTitle.textContent = "Verifying Payment Signature...";
        procSub.textContent = "Confirming transaction with Razorpay clearinghouse...";

        const verifyData = new URLSearchParams();
        verifyData.append('assignment_id', asmId);
        verifyData.append('razorpay_order_id', response.razorpay_order_id || orderData.order_id);
        verifyData.append('razorpay_payment_id', response.razorpay_payment_id);
        verifyData.append('razorpay_signature', response.razorpay_signature || '');

        fetch('/api.php?action=verify_razorpay_payment', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: verifyData.toString()
        })
        .then(r => r.json())
        .then(vData => {
          procModal.classList.remove('active');
          if (vData.success) {
            document.getElementById('recAsmId').textContent = asmId;
            document.getElementById('recTxId').textContent = vData.transaction_id;
            document.getElementById('recAmount').textContent = vData.formatted_amount;
            document.getElementById('recMethod').textContent = 'Razorpay Official Gateway';
            if (vData.invoice_url) {
              document.getElementById('recInvoiceBtn').href = vData.invoice_url;
            }
            document.getElementById('checkoutSuccessModal').classList.add('active');
          } else {
            alert(vData.message || 'Payment verification failed.');
          }
        })
        .catch(err => {
          procModal.classList.remove('active');
          alert('Network error verifying Razorpay payment.');
        });
      },
      modal: {
        ondismiss: function() {
          console.log('Razorpay modal dismissed');
        }
      }
    };

    const rzp = new Razorpay(options);
    rzp.open();
  })
  .catch(err => {
    procModal.classList.remove('active');
    alert('Connection error communicating with Razorpay API.');
  });
}
</script>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<?php
if ($isStudent) {
    // Close the student portal containers opened in portal_header.php
    echo "</div><!-- /.portal-content -->\n";
    echo "</div><!-- /.portal-main -->\n";
    echo "</div><!-- /.portal-wrapper -->\n";
    echo "<script src='/assets/js/portal.js'></script>\n";
    echo "</body>\n</html>";
} else {
    // Close public website
    include __DIR__ . '/includes/footer.php';
}
?>
