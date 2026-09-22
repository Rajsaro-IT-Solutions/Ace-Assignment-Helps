<?php
$pageTitle = "Order Communications & Support";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Expert');
$user = Auth::currentUser();
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

$defaultAsmId = trim($_GET['assignment_id'] ?? '');

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $assignment_id = trim($_POST['assignment_id'] ?? '');

    if (!empty($subject) && !empty($message)) {
        $ticket_id = 'EXP-MSG-' . rand(1000, 9999);
        $uploaded = [];
        if (isset($_FILES['ticket_files'])) {
            $uploaded = handle_uploaded_files('ticket_files', $assignment_id ?: $ticket_id, $user['name'] . ' (Expert Support)', false);
        }

        DataStore::insert('support_tickets', [
            'ticket_id' => $ticket_id,
            'student_id' => $user['id'], // Expert ID
            'expert_id' => $user['id'],
            'assignment_id' => $assignment_id,
            'subject' => '[Expert Inquiry] ' . $subject,
            'message' => $message,
            'status' => 'Open',
            'created_at' => date('Y-m-d H:i:s'),
            'files' => $uploaded,
            'replies' => []
        ]);

        add_notification('Admin', '', "Expert Inquiry: $subject", "Expert {$user['name']} sent a message regarding order $assignment_id", 'info', "/admin/assignments.php");
        $msg = "Message sent to allocation support team successfully!";
    }
}

$tickets = DataStore::filter('support_tickets', function($t) use ($user) {
    return (isset($t['expert_id']) && $t['expert_id'] === $user['id']) || 
           (isset($t['student_id']) && $t['student_id'] === $user['id']);
});
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h2 style="font-size:1.5rem; color:var(--text-main); margin-bottom:0.25rem;">
      <i class="fa-solid fa-comments" style="color:var(--primary);"></i> Order Communications & Support
    </h2>
    <p style="color:var(--text-muted); font-size:0.9rem; margin:0;">
      Communicate directly with academic allocators and platform administrators regarding order instructions or questions.
    </p>
  </div>
</div>

<?php if ($msg): ?>
  <div class="badge badge-success" style="display:block; padding:0.9rem; margin-bottom:1.5rem; font-size:0.95rem; text-align:center;">
    <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($msg); ?>
  </div>
<?php endif; ?>

<!-- Compose Message Card -->
<div class="table-card" style="padding:1.75rem; margin-bottom:2rem;">
  <h3 style="font-size:1.15rem; color:var(--text-main); margin:0 0 1.25rem 0; font-weight:800; border-bottom:1px solid var(--border-color); padding-bottom:0.75rem;">
    <i class="fa-solid fa-paper-plane" style="color:var(--primary);"></i> Send Message to Allocation Team
  </h3>

  <form method="POST" enctype="multipart/form-data">
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.2rem;" id="msgGrid">
      <div class="form-group">
        <label>Related Order / Assignment ID (Optional)</label>
        <input type="text" name="assignment_id" class="form-control" value="<?php echo htmlspecialchars($defaultAsmId); ?>" placeholder="e.g. ACE-2026-000101">
      </div>
      <div class="form-group">
        <label>Subject Topic *</label>
        <input type="text" name="subject" class="form-control" required placeholder="e.g. Clarification on Section 3 dataset">
      </div>
    </div>

    <div class="form-group">
      <label>Detailed Message *</label>
      <textarea name="message" class="form-control" rows="4" required placeholder="Type your query or instructions for the allocator..."></textarea>
    </div>

    <div class="form-group">
      <label>Attach Supporting File / Draft (Optional)</label>
      <input type="file" name="ticket_files[]" class="form-control" multiple>
    </div>

    <button type="submit" class="btn btn-primary" style="font-weight:700; padding:0.75rem 1.75rem;">
      <i class="fa-solid fa-paper-plane"></i> Send to Support
    </button>
  </form>
</div>

<!-- Prior Tickets / Messages Table -->
<div class="table-card">
  <div class="table-header">
    <h3 style="font-size:1.1rem; color:var(--text-main);"><i class="fa-solid fa-clock-rotate-left"></i> Sent Communication Log</h3>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Ticket ID</th>
          <th>Order ID</th>
          <th>Subject</th>
          <th>Sent Date</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($tickets)): ?>
          <tr><td colspan="5" style="text-align:center; padding:2rem; color:var(--text-muted);">No messages or inquiries logged yet.</td></tr>
        <?php else: ?>
          <?php foreach ($tickets as $t): ?>
            <tr>
              <td><strong style="color:var(--secondary); font-family:monospace;"><?php echo htmlspecialchars($t['ticket_id']); ?></strong></td>
              <td><?php echo htmlspecialchars($t['assignment_id'] ?? 'General'); ?></td>
              <td><strong><?php echo htmlspecialchars($t['subject']); ?></strong></td>
              <td><?php echo date('M d, Y H:i', strtotime($t['created_at'])); ?></td>
              <td><span class="badge badge-info"><?php echo htmlspecialchars($t['status']); ?></span></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="/assets/js/portal.js"></script>
</div>
</div>
</div>
</body>
</html>
