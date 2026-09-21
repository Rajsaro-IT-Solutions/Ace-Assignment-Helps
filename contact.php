<?php
$pageTitle = "Contact Us";
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
include __DIR__ . '/includes/header.php';
$sentMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? 'Website Inquiry');
    $msg = trim($_POST['message'] ?? '');

    $uploaded = [];
    if (isset($_FILES['contact_files'])) {
        $uploaded = handle_uploaded_files('contact_files', 'INQ-' . time(), $name . ' (' . $email . ')', false);
    }

    $fileNote = !empty($uploaded) ? " (" . count($uploaded) . " file(s) attached)" : "";
    add_notification('Admin', null, "New Inquiry: $subject", "From: $name ($email)$fileNote\nMessage: $msg", 'info');

    $sentMsg = "Thank you! Your message" . (!empty($uploaded) ? " and " . count($uploaded) . " file(s)" : "") . " have been received. Our support team will reply within 15 minutes.";
}
?>

<div class="container" style="max-width: 900px; padding-top: 3rem;">
  <div class="grid-2" style="display:grid; grid-template-columns:1fr 1fr; gap:3rem;">
    <div>
      <div class="badge badge-primary" style="margin-bottom:0.8rem;">24/7 Global Support</div>
      <h2 style="font-size:2.2rem; margin-bottom:1rem;">Get in Touch With Our Support Desk</h2>
      <p style="color:var(--text-muted); margin-bottom:2rem;">Have questions before placing your assignment order? We're available 24/7 via Email, WhatsApp, and live web chat.</p>

      <div style="display:flex; flex-direction:column; gap:1.5rem;">
        <?php 
        $siteWhatsapp = class_exists('DataStore') ? DataStore::getSetting('whatsapp_phone', '+91 8233432123') : '+91 8233432123';
        $siteWhatsappClean = preg_replace('/[^0-9]/', '', $siteWhatsapp);
        if (empty($siteWhatsappClean)) $siteWhatsappClean = '918233432123';
        ?>
        <div style="display:flex; align-items:center; gap:15px;">
          <div class="feature-icon" style="margin-bottom:0; width:46px; height:46px; font-size:1.2rem; background:rgba(37, 211, 102, 0.15); color:#25D366;">
            <i class="fa-brands fa-whatsapp"></i>
          </div>
          <div>
            <h5 style="color:#fff;">WhatsApp Quick Helpline</h5>
            <a href="https://wa.me/<?php echo $siteWhatsappClean; ?>?text=Hi%20Ace%20Assignment%20Helps%2C%20I%20need%20assistance%20with%20my%20assignment." target="_blank" style="color:#25D366; font-weight:700;"><?php echo htmlspecialchars($siteWhatsapp); ?></a>
          </div>
        </div>

        <div style="display:flex; align-items:center; gap:15px;">
          <div class="feature-icon" style="margin-bottom:0; width:46px; height:46px; font-size:1.2rem;">
            <i class="fa-solid fa-envelope"></i>
          </div>
          <div>
            <h5 style="color:#fff;">Support Email</h5>
            <span style="color:var(--text-muted);">support@aceassign.com</span>
          </div>
        </div>
      </div>
    </div>

    <div class="calc-card">
      <h3 style="margin-bottom:1.2rem;">Send Us a Message</h3>
      <?php if ($sentMsg): ?>
        <div class="badge badge-success" style="width:100%; padding:0.8rem; margin-bottom:1rem; text-align:center;">
          <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($sentMsg); ?>
        </div>
      <?php endif; ?>
      <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
          <label>Your Name *</label>
          <input type="text" name="name" class="form-control" required placeholder="John Doe">
        </div>
        <div class="form-group">
          <label>Email Address *</label>
          <input type="email" name="email" class="form-control" required placeholder="john@example.com">
        </div>
        <div class="form-group">
          <label>Subject</label>
          <input type="text" name="subject" class="form-control" placeholder="Inquiry about assignment order">
        </div>
        <div class="form-group">
          <label>Message *</label>
          <textarea name="message" class="form-control" rows="3" required placeholder="Write your question here..."></textarea>
        </div>
        <div class="form-group">
          <label><i class="fa-solid fa-cloud-arrow-up"></i> Attach Document / Assignment Brief (Any Format)</label>
          <input type="file" name="contact_files[]" multiple class="form-control">
          <small style="color:var(--text-muted); font-size:0.8rem; display:block; margin-top:4px;">
            Accepts <strong>ANY</strong> format: PDF, DOCX, ZIP, RAR, TXT, PY, IPYNB, XLS, PPTX, Images, etc.
          </small>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%; margin-top:0.5rem;"><i class="fa-solid fa-paper-plane"></i> Send Message</button>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
