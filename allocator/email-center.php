<?php
$pageTitle = "Email Notification Center";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole(['Allocator', 'Admin']);
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';
$sentMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $template = $_POST['template'] ?? '';
    $sentMsg = "Automated email template '$template' triggered successfully!";
}
?>

<div style="max-width:700px; margin:0 auto;">
  <div class="calc-card" style="padding:2rem; background:#ffffff;">
    <h3 style="margin-bottom:1rem; color:var(--text-main);"><i class="fa-solid fa-paper-plane" style="color:var(--primary);"></i> Send Automated Email Template</h3>
    <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1.5rem;">Select an automatic email template to dispatch to the student or expert:</p>

    <?php if ($sentMsg): ?>
      <div class="badge badge-success" style="width:100%; padding:0.8rem; margin-bottom:1rem; text-align:center; font-size:0.9rem;">
        <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($sentMsg); ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Email Template</label>
        <select name="template" class="form-control">
          <option value="Assignment Submitted Confirmation">Assignment Submitted Confirmation</option>
          <option value="Assignment Allocated to Expert">Assignment Allocated to Expert</option>
          <option value="Assignment Solution Ready for Download">Assignment Solution Ready for Download</option>
          <option value="Deadline Reminder Alert">Deadline Reminder Alert</option>
        </select>
      </div>

      <div class="form-group">
        <label>Assignment ID</label>
        <input type="text" name="assignment_id" class="form-control" value="ACE-2026-000101">
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%; margin-top:1rem;"><i class="fa-solid fa-envelope"></i> Send Email Now</button>
    </form>
  </div>
</div>

</div>
</div>
</body>
</html>
