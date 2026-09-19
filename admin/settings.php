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
    $whatsappPhone = trim($_POST['whatsapp_phone'] ?? '+1 (555) 987-6543');
    $tagline = trim($_POST['tagline'] ?? '');

    DataStore::setSetting('site_name', $siteName);
    DataStore::setSetting('default_currency', $currency);
    DataStore::setSetting('base_price', $basePrice);
    DataStore::setSetting('contact_email', $contactEmail);
    DataStore::setSetting('whatsapp_phone', $whatsappPhone);
    DataStore::setSetting('tagline', $tagline);

    add_audit_log('Admin', $adminUser['id'], 'Update Settings', "Updated platform system settings");
    $msg = "Website settings updated and saved successfully across all pages!";
}

$siteName = DataStore::getSetting('site_name', 'Ace Assignment Helps');
$defaultCurrency = DataStore::getSetting('default_currency', 'USD');
$basePrice = DataStore::getSetting('base_price', '15.00');
$contactEmail = DataStore::getSetting('contact_email', 'support@aceassign.com');
$whatsappPhone = DataStore::getSetting('whatsapp_phone', '+1 (555) 987-6543');
$tagline = DataStore::getSetting('tagline', '#1 University Assignment Assistance (UK, USA, Ireland, Australia, Canada & India)');
?>

<div style="max-width: 750px; margin:0 auto;">
  <div class="calc-card" style="padding:2.5rem;">
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

      <div style="margin-top:1.5rem; display:flex; gap:12px;">
        <button type="submit" class="btn btn-primary" style="flex:1; padding:0.9rem; font-size:1rem;">
          <i class="fa-solid fa-floppy-disk"></i> Save & Apply System Settings
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
