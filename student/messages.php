<?php
$pageTitle = "Support Tickets & Messages";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Student');
$user = Auth::currentUser();
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

$msg = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $assignment_id = trim($_POST['assignment_id'] ?? '');

    if (!empty($subject) && !empty($message)) {
        $ticket_id = 'TCK-' . rand(1000, 9999);
        $uploaded = [];
        if (isset($_FILES['ticket_files'])) {
            $uploaded = handle_uploaded_files('ticket_files', $assignment_id ?: $ticket_id, $user['name'] . ' (Support Ticket)', false);
        }

        DataStore::insert('support_tickets', [
            'ticket_id' => $ticket_id,
            'student_id' => $user['id'],
            'assignment_id' => $assignment_id,
            'subject' => $subject,
            'message' => $message,
            'status' => 'Open',
            'created_at' => date('Y-m-d H:i:s'),
            'files' => $uploaded,
            'replies' => []
        ]);

        add_notification('Admin', null, "New Support Ticket: $subject", "Student: {$user['name']} opened ticket $ticket_id", 'info');
        $msg = "Support ticket $ticket_id submitted successfully!";
    }
}

$tickets = DataStore::filter('support_tickets', function($t) use ($user) {
    return isset($t['student_id']) && $t['student_id'] === $user['id'];
});
?>

<div class="table-card" style="padding:1.5rem; margin-bottom:2rem;">
  <h3 style="font-size:1.1rem; color:var(--text-main); margin-bottom:1rem;"><i class="fa-solid fa-headset" style="color:var(--primary);"></i> Raise Support Ticket</h3>
  <?php if ($msg): ?>
    <div class="badge badge-success" style="display:block; padding:0.8rem; margin-bottom:1rem; font-size:0.9rem;">
      <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($msg); ?>
    </div>
  <?php endif; ?>
  <form method="POST" action="/student/messages.php" enctype="multipart/form-data">
    <div class="grid-2" style="display:grid; grid-template-columns:1fr 1fr; gap:1.2rem;">
      <div class="form-group">
        <label>Related Assignment ID (Optional)</label>
        <input type="text" name="assignment_id" class="form-control" placeholder="e.g. ACE-2026-000101">
      </div>
      <div class="form-group">
        <label>Subject Topic *</label>
        <input type="text" name="subject" class="form-control" required placeholder="e.g. Additional dataset attached">
      </div>
    </div>
    <div class="form-group">
      <label>Message Details *</label>
      <textarea name="message" class="form-control" rows="3" required placeholder="Write details for support team..."></textarea>
    </div>
    <div class="form-group">
      <label><i class="fa-solid fa-cloud-arrow-up"></i> Attach Supporting Files / Screenshots (Any Format)</label>
      <input type="file" name="ticket_files[]" multiple class="form-control">
      <small style="color:var(--text-muted); font-size:0.8rem; display:block; margin-top:4px;">
        Accepts <strong>ANY</strong> format: PDF, DOCX, ZIP, RAR, TXT, PY, IPYNB, XLS, PPTX, Images, etc.
      </small>
    </div>
    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-paper-plane"></i> Submit Ticket</button>
  </form>
</div>

<div class="table-card">
  <div class="table-header">
    <h3 style="font-size:1.1rem; color:var(--text-main);"><i class="fa-solid fa-comments" style="color:var(--primary);"></i> Your Support Tickets</h3>
  </div>

  <div style="padding:1.5rem;">
    <?php if (empty($tickets)): ?>
      <p style="color:var(--text-muted);">No support tickets opened yet.</p>
    <?php else: ?>
      <?php foreach ($tickets as $t): ?>
        <div style="background:#f8fafc; border:1px solid var(--portal-border); border-radius:var(--radius-sm); padding:1.2rem; margin-bottom:1rem;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.6rem; flex-wrap:wrap; gap:0.5rem;">
            <div>
              <span class="badge badge-info"><?php echo htmlspecialchars($t['ticket_id']); ?></span>
              <strong style="color:var(--text-main); margin-left:10px; font-size:1rem;"><?php echo htmlspecialchars($t['subject']); ?></strong>
            </div>
            <span class="badge badge-success"><?php echo htmlspecialchars($t['status']); ?></span>
          </div>
          <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:0.8rem;"><?php echo htmlspecialchars($t['message']); ?></p>

          <?php if (!empty($t['files'])): ?>
            <div style="margin-top:0.6rem; margin-bottom:0.8rem; display:flex; flex-wrap:wrap; gap:8px;">
              <?php foreach ($t['files'] as $f): ?>
                <a href="/<?php echo htmlspecialchars($f['path']); ?>" download class="btn btn-outline btn-sm" style="font-size:0.78rem; padding:3px 8px;">
                  <i class="fa-solid fa-paperclip"></i> <?php echo htmlspecialchars($f['file_name']); ?>
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($t['replies'])): ?>
            <div style="background:#eff6ff; border-left:3px solid var(--primary); padding:0.8rem 1rem; border-radius:4px; margin-top:0.8rem;">
              <small style="color:var(--primary); font-weight:700; display:block; margin-bottom:4px;">Reply from Support Desk:</small>
              <?php foreach ($t['replies'] as $reply): ?>
                <p style="color:var(--text-main); font-size:0.85rem; margin:0;"><?php echo htmlspecialchars($reply['message']); ?></p>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

</div>
</div>
</body>
</html>
