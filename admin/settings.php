<?php
$pageTitle = "Website Settings & Configuration";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Admin');
$adminUser = Auth::currentUser();
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

$msg = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $siteName = trim($_POST['site_name'] ?? 'Ace Assignment Helps');
    $currency = trim($_POST['currency'] ?? 'USD');
    $basePrice = trim($_POST['base_price'] ?? '15.00');
    $contactEmail = trim($_POST['contact_email'] ?? 'support@aceassign.com');
    $whatsappPhone = trim($_POST['whatsapp_phone'] ?? '+91 8233432123');
    $tagline = trim($_POST['tagline'] ?? '');

    // Payment Gateway Settings
    $gatewayMode = trim($_POST['payment_gateway_mode'] ?? 'test');
    $stripeEnabled = isset($_POST['stripe_enabled']) ? '1' : '0';
    $stripePubKey = trim($_POST['stripe_publishable_key'] ?? '');
    $stripeSecKey = trim($_POST['stripe_secret_key'] ?? '');

    $razorpayEnabled = isset($_POST['razorpay_enabled']) ? '1' : '0';
    $razorpayKeyId = trim($_POST['razorpay_key_id'] ?? '');
    $razorpayKeySecret = trim($_POST['razorpay_key_secret'] ?? '');
    $razorpayUpiId = trim($_POST['razorpay_upi_id'] ?? '');

    $paypalEnabled = isset($_POST['paypal_enabled']) ? '1' : '0';
    $paypalClientId = trim($_POST['paypal_client_id'] ?? '');
    $paypalMode = trim($_POST['paypal_mode'] ?? 'sandbox');

    DataStore::setSetting('site_name', $siteName);
    DataStore::setSetting('default_currency', $currency);
    DataStore::setSetting('base_price', $basePrice);
    DataStore::setSetting('contact_email', $contactEmail);
    DataStore::setSetting('whatsapp_phone', $whatsappPhone);
    DataStore::setSetting('tagline', $tagline);

    DataStore::setSetting('payment_gateway_mode', $gatewayMode);
    DataStore::setSetting('stripe_enabled', $stripeEnabled);
    DataStore::setSetting('stripe_publishable_key', $stripePubKey);
    DataStore::setSetting('stripe_secret_key', $stripeSecKey);

    DataStore::setSetting('razorpay_enabled', $razorpayEnabled);
    DataStore::setSetting('razorpay_key_id', $razorpayKeyId);
    DataStore::setSetting('razorpay_key_secret', $razorpayKeySecret);
    DataStore::setSetting('razorpay_upi_id', $razorpayUpiId);

    DataStore::setSetting('paypal_enabled', $paypalEnabled);
    DataStore::setSetting('paypal_client_id', $paypalClientId);
    DataStore::setSetting('paypal_mode', $paypalMode);

    add_audit_log('Admin', $adminUser['id'], 'Update Settings', "Updated platform system & payment gateway settings");
    $msg = "Website & Payment Gateway settings saved successfully!";
}

$siteName = DataStore::getSetting('site_name', 'Ace Assignment Helps');
$defaultCurrency = DataStore::getSetting('default_currency', 'USD');
$basePrice = DataStore::getSetting('base_price', '15.00');
$contactEmail = DataStore::getSetting('contact_email', 'support@aceassign.com');
$whatsappPhone = DataStore::getSetting('whatsapp_phone', '+91 8233432123');
$tagline = DataStore::getSetting('tagline', '#1 University Assignment Assistance (UK, USA, Ireland, Australia, Canada & India)');
$gw = get_gateway_config();
?>

<div style="max-width: 850px; margin:0 auto;">
  <div class="calc-card" style="padding:2.5rem; margin-bottom:2rem;">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:1.5rem;">
      <div style="width:48px; height:48px; border-radius:12px; background:rgba(99,102,241,0.1); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:1.4rem;">
        <i class="fa-solid fa-sliders"></i>
      </div>
      <div>
        <h3 style="margin:0; color:var(--text-main); font-size:1.35rem;">Platform & Website Settings</h3>
        <p style="margin:0; color:var(--text-muted); font-size:0.88rem;">Manage live site branding, contact channels, base rates, and currency parameters.</p>
      </div>
    </div>

    <?php if ($msg): ?>
      <div class="badge badge-success" style="width:100%; padding:0.9rem; margin-bottom:1.5rem; text-align:center; font-size:0.95rem; display:block;">
        <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($msg); ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Website / Brand Name *</label>
        <input type="text" name="site_name" class="form-control" required value="<?php echo htmlspecialchars($siteName); ?>">
      </div>

      <div class="form-group">
        <label>Homepage Tagline / Mission Header</label>
        <input type="text" name="tagline" class="form-control" value="<?php echo htmlspecialchars($tagline); ?>">
      </div>

      <div class="grid-2" style="display:grid; grid-template-columns:1fr 1fr; gap:1.2rem;">
        <div class="form-group">
          <label>Default Platform Currency</label>
          <select name="currency" class="form-control">
            <option value="USD" <?php echo $defaultCurrency === 'USD' ? 'selected' : ''; ?>>USD ($)</option>
            <option value="GBP" <?php echo $defaultCurrency === 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
            <option value="EUR" <?php echo $defaultCurrency === 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
            <option value="AUD" <?php echo $defaultCurrency === 'AUD' ? 'selected' : ''; ?>>AUD (A$)</option>
            <option value="CAD" <?php echo $defaultCurrency === 'CAD' ? 'selected' : ''; ?>>CAD (C$)</option>
            <option value="INR" <?php echo $defaultCurrency === 'INR' ? 'selected' : ''; ?>>INR (₹)</option>
          </select>
        </div>

        <div class="form-group">
          <label>Standard Base Price / Page</label>
          <input type="number" name="base_price" class="form-control" value="<?php echo htmlspecialchars($basePrice); ?>" step="0.5" min="5">
          <small style="font-size:0.75rem; color:var(--text-muted);">Used as default starting estimate in pricing calculator.</small>
        </div>
      </div>

      <div class="grid-2" style="display:grid; grid-template-columns:1fr 1fr; gap:1.2rem;">
        <div class="form-group">
          <label><i class="fa-solid fa-envelope"></i> Official Support Email *</label>
          <input type="email" name="contact_email" class="form-control" required value="<?php echo htmlspecialchars($contactEmail); ?>">
        </div>

        <div class="form-group">
          <label><i class="fa-brands fa-whatsapp" style="color:#059669;"></i> WhatsApp Helpline Number *</label>
          <input type="tel" name="whatsapp_phone" class="form-control" required value="<?php echo htmlspecialchars($whatsappPhone); ?>">
        </div>
      </div>

      <!-- Payment Gateways Configuration Card -->
      <div style="margin-top:2.5rem; padding-top:2rem; border-top:2px dashed var(--portal-border);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
          <div style="display:flex; align-items:center; gap:10px;">
            <div style="width:40px; height:40px; border-radius:10px; background:#dcfce7; color:#166534; display:flex; align-items:center; justify-content:center; font-size:1.2rem;">
              <i class="fa-solid fa-credit-card"></i>
            </div>
            <div>
              <h4 style="margin:0; font-size:1.15rem; color:var(--text-main);">Payment Gateway Integrations</h4>
              <p style="margin:0; font-size:0.85rem; color:var(--text-muted);">Configure live or test credentials for Stripe, Razorpay, and PayPal.</p>
            </div>
          </div>
          
          <div>
            <label style="font-size:0.85rem; font-weight:700; margin-right:8px;">Gateway Mode:</label>
            <select name="payment_gateway_mode" class="form-control" style="display:inline-block; width:auto; font-weight:700; color:<?php echo $gw['mode'] === 'live' ? '#dc2626' : '#2563eb'; ?>;">
              <option value="test" <?php echo $gw['mode'] === 'test' ? 'selected' : ''; ?>>🧪 Test / Simulation Mode</option>
              <option value="live" <?php echo $gw['mode'] === 'live' ? 'selected' : ''; ?>>⚡ Live Production Mode</option>
            </select>
          </div>
        </div>

        <!-- Stripe Settings -->
        <div style="background:#f8fafc; border:1px solid var(--portal-border); border-radius:var(--radius-sm); padding:1.2rem; margin-bottom:1.2rem;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
            <div style="font-weight:700; color:#3730a3; font-size:1rem;">
              <i class="fa-brands fa-stripe" style="font-size:1.4rem; vertical-align:middle;"></i> Stripe (International Credit & Debit Cards)
            </div>
            <label style="margin:0; display:flex; align-items:center; gap:6px; cursor:pointer; font-weight:600;">
              <input type="checkbox" name="stripe_enabled" value="1" <?php echo $gw['stripe']['enabled'] ? 'checked' : ''; ?>>
              Enable Stripe
            </label>
          </div>
          <div class="grid-2" style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
            <div class="form-group" style="margin-bottom:0;">
              <label style="font-size:0.8rem;">Stripe Publishable Key</label>
              <input type="text" name="stripe_publishable_key" class="form-control" style="font-family:monospace; font-size:0.85rem;" value="<?php echo htmlspecialchars($gw['stripe']['publishable_key']); ?>" placeholder="pk_test_...">
            </div>
            <div class="form-group" style="margin-bottom:0;">
              <label style="font-size:0.8rem;">Stripe Secret Key</label>
              <input type="password" name="stripe_secret_key" class="form-control" style="font-family:monospace; font-size:0.85rem;" value="<?php echo htmlspecialchars($gw['stripe']['secret_key']); ?>" placeholder="sk_test_...">
            </div>
          </div>
        </div>

        <!-- Razorpay Settings -->
        <div style="background:#f8fafc; border:1px solid var(--portal-border); border-radius:var(--radius-sm); padding:1.2rem; margin-bottom:1.2rem;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
            <div style="font-weight:700; color:#166534; font-size:1rem;">
              <i class="fa-solid fa-bolt" style="color:#059669;"></i> Razorpay (UPI QR Code, GooglePay, PhonePe, NetBanking)
            </div>
            <label style="margin:0; display:flex; align-items:center; gap:6px; cursor:pointer; font-weight:600;">
              <input type="checkbox" name="razorpay_enabled" value="1" <?php echo $gw['razorpay']['enabled'] ? 'checked' : ''; ?>>
              Enable Razorpay
            </label>
          </div>
          <div class="grid-3" style="display:grid; grid-template-columns:1.2fr 1.2fr 1.2fr; gap:1rem;">
            <div class="form-group" style="margin-bottom:0;">
              <label style="font-size:0.8rem;">Razorpay Key ID</label>
              <input type="text" name="razorpay_key_id" class="form-control" style="font-family:monospace; font-size:0.85rem;" value="<?php echo htmlspecialchars($gw['razorpay']['key_id']); ?>" placeholder="rzp_test_...">
            </div>
            <div class="form-group" style="margin-bottom:0;">
              <label style="font-size:0.8rem;">Razorpay Key Secret</label>
              <input type="password" name="razorpay_key_secret" class="form-control" style="font-family:monospace; font-size:0.85rem;" value="<?php echo htmlspecialchars($gw['razorpay']['key_secret']); ?>" placeholder="Secret key">
            </div>
            <div class="form-group" style="margin-bottom:0;">
              <label style="font-size:0.8rem;">Business UPI ID (for QR Code)</label>
              <input type="text" name="razorpay_upi_id" class="form-control" style="font-size:0.85rem;" value="<?php echo htmlspecialchars($gw['razorpay']['upi_id']); ?>" placeholder="business@upi">
            </div>
          </div>
        </div>

        <!-- PayPal Settings -->
        <div style="background:#f8fafc; border:1px solid var(--portal-border); border-radius:var(--radius-sm); padding:1.2rem; margin-bottom:1.5rem;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
            <div style="font-weight:700; color:#1e40af; font-size:1rem;">
              <i class="fa-brands fa-paypal" style="font-size:1.3rem; vertical-align:middle; color:#2563eb;"></i> PayPal Express Checkout
            </div>
            <label style="margin:0; display:flex; align-items:center; gap:6px; cursor:pointer; font-weight:600;">
              <input type="checkbox" name="paypal_enabled" value="1" <?php echo $gw['paypal']['enabled'] ? 'checked' : ''; ?>>
              Enable PayPal
            </label>
          </div>
          <div class="grid-2" style="display:grid; grid-template-columns:2fr 1fr; gap:1rem;">
            <div class="form-group" style="margin-bottom:0;">
              <label style="font-size:0.8rem;">PayPal Client ID</label>
              <input type="text" name="paypal_client_id" class="form-control" style="font-family:monospace; font-size:0.85rem;" value="<?php echo htmlspecialchars($gw['paypal']['client_id']); ?>" placeholder="sb_... or client_id">
            </div>
            <div class="form-group" style="margin-bottom:0;">
              <label style="font-size:0.8rem;">PayPal Environment</label>
              <select name="paypal_mode" class="form-control" style="font-size:0.85rem;">
                <option value="sandbox" <?php echo $gw['paypal']['mode'] === 'sandbox' ? 'selected' : ''; ?>>Sandbox (Testing)</option>
                <option value="live" <?php echo $gw['paypal']['mode'] === 'live' ? 'selected' : ''; ?>>Live (Production)</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <div style="margin-top:1.5rem; display:flex; gap:12px;">
        <button type="submit" class="btn btn-primary" style="flex:1; padding:0.9rem; font-size:1rem;">
          <i class="fa-solid fa-floppy-disk"></i> Save & Apply All Settings
        </button>
      </div>
    </form>
  </div>
</div>

<script src="/assets/js/portal.js"></script>
</div>
</div>
</body>
</html>
