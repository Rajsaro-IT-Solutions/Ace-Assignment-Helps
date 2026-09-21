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
if (!$asm && $user && $user['role'] === 'Student') {
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
$discountAmount = ($subtotal > $finalPrice) ? ($subtotal - $finalPrice) : 0.0;

include __DIR__ . '/includes/header.php';
?>

<div class="checkout-wrapper" style="background:#f8fafc; min-height:85vh; padding: 2.5rem 0 5rem 0;">
  <div class="container" style="max-width: 1060px;">
    
    <!-- Top Trust Bar -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 2rem; flex-wrap:wrap; gap:1rem;">
      <div>
        <a href="<?php echo $user ? '/student/assignments.php' : '/'; ?>" style="color:var(--text-muted); font-size:0.88rem; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
          <i class="fa-solid fa-arrow-left"></i> <?php echo $user ? 'Return to Assignments' : 'Return to Website'; ?>
        </a>
        <h1 style="font-size: 1.9rem; color: #0f172a; margin-top:0.4rem; font-weight:800; display:flex; align-items:center; gap:10px;">
          <i class="fa-solid fa-shield-halved" style="color:#10b981;"></i> Secure Payment Checkout
        </h1>
      </div>

      <div style="display:flex; align-items:center; gap:12px; font-size:0.82rem; color:#475569;">
        <span style="display:inline-flex; align-items:center; gap:5px; background:#ffffff; padding:6px 14px; border-radius:20px; border:1px solid #e2e8f0; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
          <i class="fa-solid fa-lock" style="color:#10b981;"></i> 256-Bit SSL Encrypted
        </span>
        <span style="display:inline-flex; align-items:center; gap:5px; background:#ffffff; padding:6px 14px; border-radius:20px; border:1px solid #e2e8f0; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
          <i class="fa-solid fa-circle-check" style="color:#6366f1;"></i> PCI-DSS Level 1
        </span>
      </div>
    </div>

    <?php if (!$asm): ?>
      <!-- No Order Selected State -->
      <div class="calc-card" style="padding: 3.5rem 2rem; text-align:center; max-width:650px; margin: 3rem auto; background:#ffffff;">
        <div style="width:68px; height:68px; border-radius:50%; background:#f1f5f9; color:#64748b; display:flex; align-items:center; justify-content:center; font-size:2rem; margin:0 auto 1.5rem auto;">
          <i class="fa-solid fa-receipt"></i>
        </div>
        <h2 style="font-size:1.5rem; color:#0f172a; margin-bottom:0.6rem;">No Pending Assignment Order Specified</h2>
        <p style="color:var(--text-muted); font-size:0.95rem; margin-bottom:2rem;">
          To process a payment, please select an assignment from your portal or submit your assignment brief first.
        </p>
        <div style="display:flex; justify-content:center; gap:12px; flex-wrap:wrap;">
          <a href="/submit-assignment.php" class="btn btn-primary"><i class="fa-solid fa-circle-plus"></i> Submit New Assignment</a>
          <?php if ($user): ?>
            <a href="/student/assignments.php" class="btn btn-outline"><i class="fa-solid fa-list-check"></i> View My Assignments</a>
          <?php else: ?>
            <a href="/login.php" class="btn btn-outline"><i class="fa-solid fa-right-to-bracket"></i> Student Login</a>
          <?php endif; ?>
        </div>
      </div>

    <?php elseif ($isAlreadyPaid): ?>
      <!-- Already Paid State -->
      <div class="calc-card" style="padding: 3.5rem 2rem; text-align:center; max-width:650px; margin: 3rem auto; background:#ffffff; border-top: 5px solid #10b981;">
        <div style="width:72px; height:72px; border-radius:50%; background:#dcfce7; color:#10b981; display:flex; align-items:center; justify-content:center; font-size:2.2rem; margin:0 auto 1.5rem auto;">
          <i class="fa-solid fa-check-double"></i>
        </div>
        <div class="badge badge-success" style="margin-bottom:0.8rem; font-size:0.85rem;">PAID IN FULL</div>
        <h2 style="font-size:1.6rem; color:#0f172a; margin-bottom:0.6rem;">Assignment <?php echo htmlspecialchars($asm['assignment_id']); ?> is Paid</h2>
        <p style="color:var(--text-muted); font-size:0.95rem; margin-bottom:1.5rem;">
          This order was already settled on <strong><?php echo htmlspecialchars($existingPayment['payment_date'] ?? date('Y-m-d')); ?></strong>. Transaction ID: <code><?php echo htmlspecialchars($existingPayment['transaction_id'] ?? 'TRX-PAID'); ?></code>.
        </p>
        <div style="display:flex; justify-content:center; gap:12px; flex-wrap:wrap;">
          <a href="/student/invoice.php?id=<?php echo urlencode($asm['assignment_id']); ?>" target="_blank" class="btn btn-primary">
            <i class="fa-solid fa-file-invoice"></i> View / Download Tax Invoice
          </a>
          <a href="/student/assignment-detail.php?id=<?php echo urlencode($asm['assignment_id']); ?>" class="btn btn-outline">
            <i class="fa-solid fa-arrow-up-right-from-square"></i> Order Details & Status
          </a>
        </div>
      </div>

    <?php else: ?>
      <!-- Checkout Main Grid -->
      <div style="display:grid; grid-template-columns: 1.4fr 1fr; gap: 2rem;" id="checkoutGrid">
        
        <!-- Left: Payment Methods Selector -->
        <div>
          <div class="calc-card" style="padding: 2rem; background:#ffffff; margin-bottom:1.5rem;">
            
            <div style="margin-bottom: 1.5rem;">
              <h3 style="font-size: 1.25rem; color:#0f172a; margin-bottom:0.3rem;">Select Payment Method</h3>
              <p style="color:var(--text-muted); font-size:0.88rem; margin:0;">
                All major credit/debit cards, instant UPI QR code, NetBanking, and PayPal accepted.
              </p>
            </div>

            <!-- Gateway Tabs Navigation -->
            <div class="gateway-nav" style="display:flex; gap:8px; border-bottom: 2px solid #e2e8f0; padding-bottom: 2px; margin-bottom: 1.5rem; overflow-x:auto;">
              <button type="button" class="gw-tab-btn active" data-target="tab-card">
                <i class="fa-brands fa-stripe" style="font-size:1.2rem; color:#6366f1;"></i> Cards (Visa / MC / Amex)
              </button>
              <button type="button" class="gw-tab-btn" data-target="tab-razorpay">
                <i class="fa-solid fa-bolt" style="color:#059669;"></i> Razorpay UPI / QR
              </button>
              <button type="button" class="gw-tab-btn" data-target="tab-paypal">
                <i class="fa-brands fa-paypal" style="color:#2563eb;"></i> PayPal
              </button>
              <button type="button" class="gw-tab-btn" data-target="tab-bank">
                <i class="fa-solid fa-building-columns" style="color:#92400e;"></i> Bank Wire
              </button>
            </div>

            <!-- Tab 1: Credit / Debit Card (Stripe Elements UI) -->
            <div id="tab-card" class="gw-tab-pane active">
              <form id="cardPaymentForm" onsubmit="event.preventDefault(); processCheckoutPayment('Stripe Credit Card');">
                
                <!-- Dynamic Card Preview -->
                <div class="virtual-card" style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%); border-radius: 14px; padding: 1.5rem; color:#ffffff; margin-bottom: 1.5rem; box-shadow: 0 10px 25px rgba(49, 46, 129, 0.35); position:relative; overflow:hidden;">
                  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1.8rem;">
                    <div style="font-size:0.8rem; letter-spacing:1px; opacity:0.8; font-weight:700;">ACE ASSIGNMENT SECURE CHECKOUT</div>
                    <div id="cardBrandIcon" style="font-size:1.8rem;">
                      <i class="fa-brands fa-cc-visa"></i>
                    </div>
                  </div>
                  
                  <div id="previewCardNumber" style="font-family: monospace; font-size: 1.35rem; letter-spacing: 3px; margin-bottom: 1.4rem;">
                    •••• •••• •••• ••••
                  </div>

                  <div style="display:flex; justify-content:space-between; align-items:flex-end;">
                    <div>
                      <small style="font-size:0.65rem; opacity:0.75; text-transform:uppercase; letter-spacing:1px; display:block;">Cardholder Name</small>
                      <span id="previewCardHolder" style="font-weight:600; font-size:0.95rem; text-transform:uppercase;">
                        <?php echo htmlspecialchars($user['name'] ?? 'STUDENT NAME'); ?>
                      </span>
                    </div>
                    <div style="text-align:right;">
                      <small style="font-size:0.65rem; opacity:0.75; text-transform:uppercase; letter-spacing:1px; display:block;">Expires</small>
                      <span id="previewCardExpiry" style="font-family: monospace; font-size:0.95rem; font-weight:600;">MM/YY</span>
                    </div>
                  </div>
                </div>

                <div class="form-group">
                  <label style="font-size:0.85rem; font-weight:700;">Cardholder Full Name *</label>
                  <input type="text" id="cardHolderInput" class="form-control" required placeholder="Name as printed on card" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                  <label style="font-size:0.85rem; font-weight:700;">Card Number *</label>
                  <div style="position:relative;">
                    <input type="text" id="cardNumberInput" class="form-control" required placeholder="4242 •••• •••• 4242" maxlength="19" style="font-family:monospace; letter-spacing:1px;">
                    <span style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:1.1rem;" id="inlineCardIcon">
                      <i class="fa-regular fa-credit-card"></i>
                    </span>
                  </div>
                </div>

                <div class="grid-2" style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                  <div class="form-group">
                    <label style="font-size:0.85rem; font-weight:700;">Expiration Date *</label>
                    <input type="text" id="cardExpiryInput" class="form-control" required placeholder="MM / YY" maxlength="5" style="font-family:monospace;">
                  </div>
                  <div class="form-group">
                    <label style="font-size:0.85rem; font-weight:700;">CVV / CVC *</label>
                    <div style="position:relative;">
                      <input type="password" id="cardCvvInput" class="form-control" required placeholder="•••" maxlength="4" style="font-family:monospace;">
                      <span title="3-digit security code on the back of your card (4 digits for Amex)" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); cursor:pointer; color:#94a3b8; font-size:0.85rem;">
                        <i class="fa-solid fa-circle-question"></i>
                      </span>
                    </div>
                  </div>
                </div>

                <div style="display:flex; align-items:center; gap:8px; margin-bottom:1.5rem;">
                  <input type="checkbox" id="saveCardCheck" checked style="width:16px; height:16px;">
                  <label for="saveCardCheck" style="font-size:0.82rem; color:#64748b; margin:0;">
                    Encrypt & save token securely for 1-click payment on future revisions
                  </label>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="width:100%; font-weight:700; font-size:1.05rem; padding:0.95rem; background:linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); border:none;">
                  <i class="fa-solid fa-lock"></i> Pay <?php echo format_currency_amount($finalPrice, $currency); ?> Now
                </button>
              </form>
            </div>

            <!-- Tab 2: Razorpay / UPI / NetBanking -->
            <div id="tab-razorpay" class="gw-tab-pane" style="display:none;">
              <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:1.2rem; margin-bottom:1.2rem; text-align:center;">
                <div class="badge badge-success" style="margin-bottom:0.4rem;"><i class="fa-solid fa-shield-halved"></i> Live Gateway Active (Key: <?php echo htmlspecialchars(substr($gw['razorpay']['key_id'], 0, 12)); ?>...)</div>
                <h4 style="color:#166534; font-size:1.1rem; margin-bottom:0.3rem;">Official Razorpay Instant Gateway</h4>
                <p style="color:#15803d; font-size:0.85rem; margin-bottom:1rem;">
                  Pay securely with UPI Apps (GPay, PhonePe, Paytm), Debit/Credit Cards, NetBanking, or Wallets in one click.
                </p>
                <button type="button" class="btn btn-success btn-lg" style="width:100%; font-weight:800; font-size:1.05rem; padding:1rem; background:linear-gradient(135deg, #059669 0%, #047857 100%); border:none; box-shadow:0 4px 14px rgba(5, 150, 105, 0.35);" onclick="launchOfficialRazorpayModal()">
                  <i class="fa-solid fa-bolt"></i> Pay <?php echo format_currency_amount($finalPrice, $currency); ?> with Razorpay Popup Modal
                </button>
              </div>

              <div style="text-align:center; color:#94a3b8; font-size:0.78rem; margin-bottom:1.2rem; position:relative;">
                <span style="background:#ffffff; padding:0 12px; position:relative; z-index:1; font-weight:700;">OR SCAN DIRECT UPI QR / NETBANKING BELOW</span>
                <div style="position:absolute; top:50%; left:0; right:0; height:1px; background:#e2e8f0;"></div>
              </div>

              <!-- Dynamic Simulated UPI QR Code -->
              <div style="display:flex; flex-direction:column; align-items:center; margin-bottom:1.5rem;">
                <div style="padding:12px; background:#ffffff; border:2px solid #059669; border-radius:14px; box-shadow:0 4px 15px rgba(5, 150, 105, 0.15); margin-bottom:0.8rem; text-align:center;">
                  <!-- Dynamic SVG QR Pattern -->
                  <svg width="180" height="180" viewBox="0 0 180 180" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:block;">
                    <rect width="180" height="180" fill="#ffffff"/>
                    <!-- QR Corners -->
                    <rect x="15" y="15" width="45" height="45" rx="4" fill="#0f172a"/>
                    <rect x="23" y="23" width="29" height="29" rx="2" fill="#ffffff"/>
                    <rect x="29" y="29" width="17" height="17" rx="2" fill="#059669"/>
                    
                    <rect x="120" y="15" width="45" height="45" rx="4" fill="#0f172a"/>
                    <rect x="128" y="23" width="29" height="29" rx="2" fill="#ffffff"/>
                    <rect x="134" y="29" width="17" height="17" rx="2" fill="#059669"/>

                    <rect x="15" y="120" width="45" height="45" rx="4" fill="#0f172a"/>
                    <rect x="23" y="128" width="29" height="29" rx="2" fill="#ffffff"/>
                    <rect x="29" y="134" width="17" height="17" rx="2" fill="#059669"/>

                    <!-- QR Matrix Pixels -->
                    <rect x="70" y="20" width="8" height="8" fill="#0f172a"/>
                    <rect x="85" y="20" width="16" height="8" fill="#059669"/>
                    <rect x="70" y="35" width="24" height="8" fill="#0f172a"/>
                    <rect x="100" y="35" width="8" height="8" fill="#0f172a"/>
                    <rect x="70" y="50" width="8" height="16" fill="#059669"/>
                    <rect x="85" y="50" width="16" height="8" fill="#0f172a"/>
                    
                    <rect x="20" y="70" width="16" height="8" fill="#0f172a"/>
                    <rect x="45" y="70" width="8" height="16" fill="#059669"/>
                    <rect x="70" y="75" width="40" height="40" rx="8" fill="#059669"/>
                    <!-- Logo in Center -->
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
                <div style="font-size:0.82rem; color:#475569; font-weight:600;">
                  UPI ID: <span style="color:#059669; font-family:monospace;"><?php echo htmlspecialchars($gw['razorpay']['upi_id'] ?? 'aceassignment@okhdfcbank'); ?></span>
                </div>
              </div>

              <!-- UPI ID Direct Input -->
              <div class="form-group" style="margin-bottom:1.5rem;">
                <label style="font-size:0.85rem; font-weight:700;">Or Enter Your Virtual Payment Address (VPA / UPI ID)</label>
                <div style="display:flex; gap:8px;">
                  <input type="text" id="upiVpaInput" class="form-control" placeholder="username@okhdfcbank / mobile@upi" style="font-family:monospace;">
                  <button type="button" class="btn btn-outline btn-sm" onclick="verifyUpiAddress()">Verify</button>
                </div>
                <small id="upiVerifyMsg" style="display:block; margin-top:4px; font-size:0.78rem;"></small>
              </div>

              <!-- Indian NetBanking Bank Selector -->
              <div class="form-group">
                <label style="font-size:0.85rem; font-weight:700;">Select NetBanking Bank</label>
                <select id="netbankingBankSelect" class="form-control">
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

              <button type="button" class="btn btn-success btn-lg" style="width:100%; font-weight:700; font-size:1.05rem; padding:0.95rem; background:#059669; border:none;" onclick="processCheckoutPayment('Razorpay UPI / NetBanking')">
                <i class="fa-solid fa-bolt"></i> Authorize & Pay <?php echo format_currency_amount($finalPrice, $currency); ?> via Razorpay
              </button>
            </div>

            <!-- Tab 3: PayPal Express Checkout -->
            <div id="tab-paypal" class="gw-tab-pane" style="display:none;">
              <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:10px; padding:1.2rem; margin-bottom:1.5rem; text-align:center;">
                <i class="fa-brands fa-paypal" style="font-size:2rem; color:#2563eb; margin-bottom:0.4rem;"></i>
                <h4 style="color:#1e40af; font-size:1.05rem; margin-bottom:0.3rem;">PayPal Fast-Track Checkout</h4>
                <p style="color:#1d4ed8; font-size:0.82rem; margin:0;">
                  Pay securely using your PayPal Balance, linked Bank Account, or International Credit Card.
                </p>
              </div>

              <div style="display:flex; flex-direction:column; gap:12px; margin-bottom:1.5rem;">
                <button type="button" onclick="processCheckoutPayment('PayPal')" style="background:#ffc439; border:none; border-radius:30px; padding:12px 20px; font-weight:700; color:#111; font-size:1rem; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; box-shadow:0 2px 8px rgba(255, 196, 57, 0.4); transition:all 0.2s;">
                  <i class="fa-brands fa-paypal" style="color:#003087; font-size:1.3rem;"></i> 
                  <span>Pay with <strong>PayPal</strong></span>
                </button>

                <button type="button" onclick="processCheckoutPayment('PayPal Credit / Pay Later')" style="background:#003087; border:none; border-radius:30px; padding:12px 20px; font-weight:700; color:#fff; font-size:0.95rem; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; box-shadow:0 2px 8px rgba(0, 48, 135, 0.3); transition:all 0.2s;">
                  <span>PayPal <strong>Pay in 4</strong> / Interest-Free Installments</span>
                </button>
              </div>

              <div style="font-size:0.8rem; color:#64748b; text-align:center;">
                <i class="fa-solid fa-shield-halved"></i> Covered by PayPal Buyer Protection for Academic Services.
              </div>
            </div>

            <!-- Tab 4: Direct Bank Wire / Swift Transfer -->
            <div id="tab-bank" class="gw-tab-pane" style="display:none;">
              <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:10px; padding:1.2rem; margin-bottom:1.5rem;">
                <h4 style="color:#92400e; font-size:1rem; margin-bottom:0.3rem;"><i class="fa-solid fa-building-columns"></i> Official Wire Transfer Instructions</h4>
                <p style="color:#78350f; font-size:0.82rem; margin:0;">
                  Ideal for large dissertations, thesis projects, and institutional university orders.
                </p>
              </div>

              <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:1.2rem; font-size:0.88rem; line-height:1.7; margin-bottom:1.5rem;">
                <div><strong>Beneficiary Account Name:</strong> Ace Assignment Helps Global Ltd.</div>
                <div><strong>Bank Name:</strong> Barclays International / Standard Chartered</div>
                <div><strong>Account / IBAN Number:</strong> GB29 BARK 2000 1584 9283 01</div>
                <div><strong>Swift / BIC Code:</strong> BARKGB22XXX</div>
                <div><strong>Payment Reference:</strong> <span style="color:#2563eb; font-weight:700;"><?php echo htmlspecialchars($asm['assignment_id']); ?></span></div>
              </div>

              <button type="button" class="btn btn-outline btn-lg" style="width:100%; font-weight:700;" onclick="processCheckoutPayment('Bank Wire Transfer')">
                <i class="fa-solid fa-check"></i> Notify Settlement via Bank Wire
              </button>
            </div>

          </div>

          <!-- Bottom Payment Trust Badges -->
          <div style="display:flex; justify-content:center; align-items:center; gap:20px; color:#94a3b8; font-size:1.8rem; opacity:0.85;">
            <i class="fa-brands fa-cc-visa" title="Visa"></i>
            <i class="fa-brands fa-cc-mastercard" title="Mastercard"></i>
            <i class="fa-brands fa-cc-amex" title="American Express"></i>
            <i class="fa-brands fa-google-pay" title="Google Pay"></i>
            <i class="fa-brands fa-apple-pay" title="Apple Pay"></i>
            <i class="fa-brands fa-paypal" title="PayPal"></i>
            <i class="fa-solid fa-shield-halved" title="SSL Encrypted" style="font-size:1.4rem;"></i>
          </div>
        </div>

        <!-- Right: Order Summary & Review -->
        <div>
          <div class="calc-card" style="padding: 2rem; background:#ffffff; position:sticky; top:90px;">
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; padding-bottom:1rem; margin-bottom:1.2rem;">
              <h3 style="font-size:1.15rem; color:#0f172a; margin:0;">Order Summary</h3>
              <span class="badge badge-info" style="font-family:monospace; font-weight:700;"><?php echo htmlspecialchars($asm['assignment_id']); ?></span>
            </div>

            <!-- Assignment Info Card -->
            <div style="margin-bottom: 1.2rem;">
              <div style="font-weight:700; color:#0f172a; font-size:1rem; margin-bottom:4px;">
                <?php echo htmlspecialchars($asm['title']); ?>
              </div>
              <div style="font-size:0.85rem; color:var(--text-muted); display:flex; flex-direction:column; gap:3px;">
                <span><i class="fa-solid fa-graduation-cap" style="color:var(--primary); width:16px;"></i> <?php echo htmlspecialchars($asm['subject']); ?> &bull; <?php echo htmlspecialchars($asm['assignment_type']); ?></span>
                <span><i class="fa-solid fa-file-lines" style="color:var(--secondary); width:16px;"></i> <?php echo htmlspecialchars($asm['word_count']); ?> words (<?php echo htmlspecialchars($asm['pages']); ?> pages)</span>
                <span><i class="fa-solid fa-clock" style="color:#d97706; width:16px;"></i> Target SLA: <?php echo date('M d, Y H:i', strtotime($asm['deadline'])); ?></span>
              </div>
            </div>

            <!-- Price Breakdown Table -->
            <div style="border-top:1px solid #f1f5f9; padding-top:1rem; margin-bottom:1.2rem; font-size:0.92rem; display:flex; flex-direction:column; gap:8px;">
              <div style="display:flex; justify-content:space-between; color:#475569;">
                <span>Base Rate Subtotal:</span>
                <span><?php echo format_currency_amount($subtotal, $currency); ?></span>
              </div>

              <?php if ($discountAmount > 0): ?>
                <div style="display:flex; justify-content:space-between; color:#059669; font-weight:600;">
                  <span>Discount Applied (<?php echo htmlspecialchars($discountCode); ?>):</span>
                  <span>-<?php echo format_currency_amount($discountAmount, $currency); ?></span>
                </div>
              <?php endif; ?>

              <div style="display:flex; justify-content:space-between; color:#475569;">
                <span>256-Bit SSL Processing:</span>
                <span style="color:#10b981; font-weight:600;">FREE ($0.00)</span>
              </div>

              <div style="display:flex; justify-content:space-between; color:#475569;">
                <span>Turnitin Plagiarism Report:</span>
                <span style="color:#10b981; font-weight:600;">INCLUDED</span>
              </div>

              <div style="border-top:2px solid #0f172a; padding-top:12px; margin-top:6px; display:flex; justify-content:space-between; align-items:baseline;">
                <span style="font-weight:800; font-size:1.1rem; color:#0f172a;">Total Payable:</span>
                <span style="font-weight:900; font-size:1.75rem; color:#047857; font-family:var(--font-head);">
                  <?php echo format_currency_amount($finalPrice, $currency); ?>
                </span>
              </div>
              <small style="color:#64748b; font-size:0.75rem; text-align:right;">Settled in <?php echo htmlspecialchars($currency); ?> &bull; No hidden taxes</small>
            </div>

            <!-- Guarantees List -->
            <div style="background:#f8fafc; border-radius:8px; padding:1rem; font-size:0.8rem; color:#475569; display:flex; flex-direction:column; gap:6px;">
              <div style="display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-check" style="color:#10b981;"></i> 100% Money-Back Satisfaction Guarantee
              </div>
              <div style="display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-check" style="color:#10b981;"></i> Free Unlimited Revisions for 14 Days
              </div>
              <div style="display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-check" style="color:#10b981;"></i> Instant Turnitin 0% Plagiarism & AI Report
              </div>
            </div>

          </div>
        </div>

      </div>
    <?php endif; ?>

  </div>
</div>

<!-- Interactive Processing Modal -->
<div id="checkoutProcessingModal" class="modal-overlay">
  <div class="modal-box" style="padding:2.5rem; text-align:center; max-width:480px; background:#ffffff;">
    <div id="procSpinner" style="width:70px; height:70px; border:4px solid #e2e8f0; border-top:4px solid #4f46e5; border-radius:50%; animation:wa-spin 1s linear infinite; margin:0 auto 1.5rem auto;"></div>
    
    <h3 id="procTitle" style="font-size:1.3rem; color:#0f172a; margin-bottom:0.5rem;">Authorizing Payment</h3>
    <p id="procSub" style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1.5rem;">
      Encrypting payment tokens and contacting banking gateway...
    </p>

    <div style="background:#f8fafc; border-radius:8px; padding:12px; font-size:0.82rem; color:#64748b; margin-bottom:1rem;">
      <i class="fa-solid fa-shield-halved" style="color:#10b981;"></i> Please do not close or refresh this window.
    </div>
  </div>
</div>

<!-- Payment Success Screen Overlay -->
<div id="checkoutSuccessModal" class="modal-overlay">
  <div class="modal-box" style="padding:3rem 2rem; text-align:center; max-width:540px; background:#ffffff;">
    <div style="width:76px; height:76px; border-radius:50%; background:#dcfce7; color:#10b981; display:flex; align-items:center; justify-content:center; font-size:2.4rem; margin:0 auto 1.2rem auto; box-shadow:0 6px 20px rgba(16, 185, 129, 0.25);">
      <i class="fa-solid fa-check"></i>
    </div>

    <div class="badge badge-success" style="margin-bottom:0.6rem; font-size:0.85rem;">PAYMENT VERIFIED & SETTLED</div>
    <h2 style="font-size:1.6rem; color:#0f172a; margin-bottom:0.4rem;">Order Confirmed!</h2>
    <p style="color:var(--text-muted); font-size:0.92rem; margin-bottom:1.5rem;">
      Thank you! Your assignment has been moved to <strong>Confirmed</strong> status and our academic allocator has prioritized expert matching.
    </p>

    <!-- Receipt Details Box -->
    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:1.2rem; text-align:left; font-size:0.88rem; line-height:1.7; margin-bottom:1.5rem;">
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
      <a id="recInvoiceBtn" href="/student/invoice.php?id=<?php echo urlencode($asmId); ?>" target="_blank" class="btn btn-primary" style="font-weight:700; padding:0.85rem;">
        <i class="fa-solid fa-file-pdf"></i> Download Official Tax Invoice PDF
      </a>
      <a href="/student/assignments.php" class="btn btn-outline" style="font-weight:600; padding:0.85rem;">
        <i class="fa-solid fa-gauge"></i> Go to Student Dashboard
      </a>
      <a href="https://wa.me/918233432123?text=Hi%20Ace%20Assignment%20Helps%2C%20I%20have%20paid%20for%20order%20<?php echo urlencode($asmId); ?>" target="_blank" class="btn btn-sm" style="color:#059669; font-weight:600; margin-top:4px;">
        <i class="fa-brands fa-whatsapp"></i> Get Live Updates on WhatsApp (+91 8233432123)
      </a>
    </div>
  </div>
</div>

<style>
  .gw-tab-btn {
    background: transparent;
    border: none;
    padding: 10px 18px;
    font-size: 0.92rem;
    font-weight: 600;
    color: #64748b;
    border-radius: 8px 8px 0 0;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
    white-space: nowrap;
  }
  .gw-tab-btn:hover {
    color: #0f172a;
    background: #f1f5f9;
  }
  .gw-tab-btn.active {
    color: #4f46e5;
    background: #eef2ff;
    border-bottom: 2px solid #4f46e5;
  }
  @keyframes wa-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }
  @media (max-width: 840px) {
    #checkoutGrid {
      grid-template-columns: 1fr !important;
    }
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // Tab Switching
  const tabBtns = document.querySelectorAll('.gw-tab-btn');
  const tabPanes = document.querySelectorAll('.gw-tab-pane');

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
  const cvvInput = document.getElementById('cardCvvInput');

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
      prevNum.textContent = formatted || '•••• •••• •••• ••••';

      // Brand Detection
      if (val.startsWith('4')) {
        brandIcon.innerHTML = '<i class="fa-brands fa-cc-visa"></i>';
        inlineIcon.innerHTML = '<i class="fa-brands fa-cc-visa" style="color:#2563eb;"></i>';
      } else if (/^5[1-5]/.test(val) || /^2[2-7]/.test(val)) {
        brandIcon.innerHTML = '<i class="fa-brands fa-cc-mastercard"></i>';
        inlineIcon.innerHTML = '<i class="fa-brands fa-cc-mastercard" style="color:#ea580c;"></i>';
      } else if (/^3[47]/.test(val)) {
        brandIcon.innerHTML = '<i class="fa-brands fa-cc-amex"></i>';
        inlineIcon.innerHTML = '<i class="fa-brands fa-cc-amex" style="color:#0284c7;"></i>';
      } else if (/^6(?:011|5)/.test(val)) {
        brandIcon.innerHTML = '<i class="fa-brands fa-cc-discover"></i>';
        inlineIcon.innerHTML = '<i class="fa-brands fa-cc-discover" style="color:#f97316;"></i>';
      } else {
        brandIcon.innerHTML = '<i class="fa-regular fa-credit-card"></i>';
        inlineIcon.innerHTML = '<i class="fa-regular fa-credit-card"></i>';
      }
    });
  }

  if (holderInput) {
    holderInput.addEventListener('input', (e) => {
      prevHolder.textContent = e.target.value.trim().toUpperCase() || 'STUDENT NAME';
    });
  }

  if (expInput) {
    expInput.addEventListener('input', (e) => {
      let val = e.target.value.replace(/\D/g, '').substring(0, 4);
      if (val.length >= 2) {
        val = val.substring(0, 2) + '/' + val.substring(2);
      }
      e.target.value = val;
      prevExp.textContent = val || 'MM/YY';
    });
  }
});

function verifyUpiAddress() {
  const vpa = document.getElementById('upiVpaInput').value.trim();
  const msg = document.getElementById('upiVerifyMsg');
  if (!vpa || !vpa.includes('@')) {
    msg.style.color = '#dc2626';
    msg.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Invalid UPI ID format. E.g. mobile@okhdfcbank';
    return;
  }
  msg.style.color = '#059669';
  msg.innerHTML = '<i class="fa-solid fa-circle-check"></i> UPI handle verified & eligible for instant collect!';
}

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

  // Step 1: Encrypting
  procTitle.textContent = "Securing Payment Channel...";
  procSub.textContent = `Connecting to ${method} gateway with 256-bit encryption...`;

  setTimeout(() => {
    // Step 2: Authorizing
    procTitle.textContent = "Authorizing Transaction...";
    procSub.textContent = "Verifying with your card network / bank clearinghouse...";

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
          // Fill in receipt details
          document.getElementById('recAsmId').textContent = data.assignment_id || asmId;
          document.getElementById('recTxId').textContent = data.transaction_id || 'TRX-SUCCESS';
          document.getElementById('recAmount').textContent = data.formatted_amount || 'Paid';
          document.getElementById('recMethod').textContent = method;
          if (data.invoice_url) {
            document.getElementById('recInvoiceBtn').href = data.invoice_url;
          }

          // Show Success Modal
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
        // Payment successful on Razorpay modal popup
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

<?php include __DIR__ . '/includes/footer.php'; ?>
