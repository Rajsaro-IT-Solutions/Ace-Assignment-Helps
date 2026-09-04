<?php
$pageTitle = "Frequently Asked Questions (FAQs)";
include __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 850px; padding-top: 3rem;">
  <div class="section-header">
    <div class="badge badge-info" style="margin-bottom:0.8rem;">Got Questions?</div>
    <h2>Frequently Asked Questions</h2>
    <p>Everything you need to know about our assignment services, privacy rules, and payment policies.</p>
  </div>

  <div style="display:flex; flex-direction:column; gap:1.2rem; margin-top:2rem;">
    <div class="feature-card">
      <h4 style="color:var(--primary); margin-bottom:0.4rem;"><i class="fa-solid fa-circle-question"></i> Is my identity safe when using AceAssignment?</h4>
      <p style="color:var(--text-muted); font-size:0.95rem;">Yes, 100%. Our platform implements strict Role-Based Access Control (RBAC). Internal experts and allocators can NEVER see your email address, phone number, physical address, or payment details. Only your assignment requirements and masked student code are visible.</p>
    </div>

    <div class="feature-card">
      <h4 style="color:var(--secondary); margin-bottom:0.4rem;"><i class="fa-solid fa-circle-question"></i> Do you provide plagiarism reports?</h4>
      <p style="color:var(--text-muted); font-size:0.95rem;">Every completed assignment includes an official Turnitin similarity report confirming 0% AI and 0% unauthorized copied content.</p>
    </div>

    <div class="feature-card">
      <h4 style="color:var(--success); margin-bottom:0.4rem;"><i class="fa-solid fa-circle-question"></i> What if I need revisions after receiving my solution?</h4>
      <p style="color:var(--text-muted); font-size:0.95rem;">You can raise a revision request within 14 days directly from your Student Portal. Your allocated expert will make all requested changes free of charge.</p>
    </div>

    <div class="feature-card">
      <h4 style="color:var(--accent); margin-bottom:0.4rem;"><i class="fa-solid fa-circle-question"></i> How do WhatsApp notifications work?</h4>
      <p style="color:var(--text-muted); font-size:0.95rem;">When you submit an assignment, you'll receive direct WhatsApp links for tracking status changes, payment reminders, and download links as soon as your paper is ready.</p>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
