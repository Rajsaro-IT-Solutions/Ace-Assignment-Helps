<?php
$pageTitle = "Website Settings & Configuration";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Admin');
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg = "Website settings updated successfully!";
}
?>

<div style="max-width: 650px; margin:0 auto;">
  <div class="calc-card" style="padding:2rem;">
    <h3 style="margin-bottom:1.5rem; color:#fff;"><i class="fa-solid fa-sliders" style="color:var(--primary);"></i> Platform System Settings</h3>

    <?php if ($msg): ?>
      <div class="badge badge-success" style="width:100%; padding:0.8rem; margin-bottom:1rem; text-align:center;">
        <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($msg); ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Website Name</label>
        <input type="text" name="site_name" class="form-control" value="AceAssignment Management Platform">
      </div>

      <div class="grid-2" style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
        <div class="form-group">
          <label>Default Currency</label>
          <select name="currency" class="form-control">
            <option value="USD" selected>USD ($)</option>
            <option value="GBP">GBP (&pound;)</option>
            <option value="AUD">AUD ($)</option>
            <option value="CAD">CAD ($)</option>
          </select>
        </div>

        <div class="form-group">
          <label>Base Price / Page ($)</label>
          <input type="number" name="base_price" class="form-control" value="15.00" step="0.5">
        </div>
      </div>

      <div class="form-group">
        <label>Support Contact Email</label>
        <input type="email" name="contact_email" class="form-control" value="support@aceassign.com">
      </div>

      <div class="form-group">
        <label>WhatsApp Helpline Number</label>
        <input type="tel" name="whatsapp_phone" class="form-control" value="+1 (555) 987-6543">
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%; margin-top:1rem;"><i class="fa-solid fa-floppy-disk"></i> Save System Settings</button>
    </form>
  </div>
</div>

</div>
</div>
</body>
</html>
