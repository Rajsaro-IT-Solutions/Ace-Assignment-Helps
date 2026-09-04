<?php
$pageTitle = "Contact Us";
include __DIR__ . '/includes/header.php';
$sentMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sentMsg = "Thank you! Your message has been received. Our support team will reply within 15 minutes.";
}
?>

<div class="container" style="max-width: 900px; padding-top: 3rem;">
  <div class="grid-2" style="display:grid; grid-template-columns:1fr 1fr; gap:3rem;">
    <div>
      <div class="badge badge-primary" style="margin-bottom:0.8rem;">24/7 Global Support</div>
      <h2 style="font-size:2.2rem; margin-bottom:1rem;">Get in Touch With Our Support Desk</h2>
      <p style="color:var(--text-muted); margin-bottom:2rem;">Have questions before placing your assignment order? We're available 24/7 via Email, WhatsApp, and live web chat.</p>

      <div style="display:flex; flex-direction:column; gap:1.5rem;">
        <div style="display:flex; align-items:center; gap:15px;">
          <div class="feature-icon" style="margin-bottom:0; width:46px; height:46px; font-size:1.2rem; background:rgba(37, 211, 102, 0.15); color:#25D366;">
            <i class="fa-brands fa-whatsapp"></i>
          </div>
          <div>
            <h5 style="color:#fff;">WhatsApp Quick Helpline</h5>
            <a href="https://wa.me/15559876543" target="_blank" style="color:#25D366; font-weight:700;">+1 (555) 987-6543</a>
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
      <form method="POST">
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
          <textarea name="message" class="form-control" rows="4" required placeholder="Write your question here..."></textarea>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fa-solid fa-paper-plane"></i> Send Message</button>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
