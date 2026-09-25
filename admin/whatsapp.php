<?php
$pageTitle = "WhatsApp & Live Portal Chat Control Desk";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Admin');
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

$broadcastMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trigger_broadcast'])) {
  $broadcastMsg = "WhatsApp template message broadcasted successfully to targeted students!";
}

$students = DataStore::getCollection('students');
$selectedStudentId = $_GET['student_id'] ?? ($students[0]['student_id'] ?? 'STU-1001');

$chatMessages = DataStore::filter('chat_messages', function ($m) use ($selectedStudentId) {
  return isset($m['student_id']) && $m['student_id'] === $selectedStudentId;
});
usort($chatMessages, function ($a, $b) {
  $t = strcmp($a['timestamp'] ?? '', $b['timestamp'] ?? '');
  return $t !== 0 ? $t : (((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0)));
});
?>

<div style="max-width:1100px; margin:0 auto;">
  <div class="grid-2" style="display:grid; grid-template-columns: 1fr 1.6fr; gap:1.5rem;">
    <!-- Left Column: Student Chat Directory & Broadcast -->
    <div>
      <div class="table-card" style="padding:1.5rem; margin-bottom:1.5rem;">
        <h3 style="font-size:1.1rem; color:var(--text-main); margin-bottom:1rem;"><i class="fa-solid fa-users"
            style="color:var(--primary);"></i> Active Student Threads</h3>
        <div style="display:flex; flex-direction:column; gap:8px;">
          <?php foreach ($students as $stu):
            $isActive = ($stu['student_id'] === $selectedStudentId);
            ?>
            <a href="/admin/whatsapp.php?student_id=<?php echo urlencode($stu['student_id']); ?>"
              style="display:flex; justify-content:space-between; align-items:center; padding:0.8rem 1rem; border-radius:var(--radius-sm); border:1px solid <?php echo $isActive ? 'var(--primary)' : 'var(--portal-border)'; ?>; background:<?php echo $isActive ? '#eff6ff' : '#f8fafc'; ?>; text-decoration:none; color:var(--text-main);">
              <div>
                <strong style="font-size:0.9rem; display:block;"><?php echo htmlspecialchars($stu['name']); ?></strong>
                <small
                  style="color:var(--text-muted); font-size:0.75rem;"><?php echo htmlspecialchars($stu['phone']); ?></small>
              </div>
              <span class="badge badge-info"
                style="font-size:0.7rem;"><?php echo htmlspecialchars($stu['country']); ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Broadcast Widget -->
      <div class="table-card" style="padding:1.5rem;">
        <h4 style="font-size:1rem; color:var(--text-main); margin-bottom:0.8rem;"><i class="fa-brands fa-whatsapp"
            style="color:#059669;"></i> Broadcast Template</h4>
        <?php if ($broadcastMsg): ?>
          <div class="badge badge-success" style="width:100%; padding:0.6rem; margin-bottom:0.8rem; text-align:center;">
            <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($broadcastMsg); ?>
          </div>
        <?php endif; ?>
        <form method="POST">
          <input type="hidden" name="trigger_broadcast" value="1">
          <div class="form-group" style="margin-bottom:0.8rem;">
            <select name="template_type" class="form-control" style="font-size:0.85rem;">
              <option value="promo">Promotional Broadcast (20% Off)</option>
              <option value="status_update">Assignment Status Notification</option>
              <option value="payment_reminder">Pending Invoice Reminder</option>
            </select>
          </div>
          <button type="submit" class="btn btn-success btn-sm" style="width:100%;"><i class="fa-brands fa-whatsapp"></i>
            Send Broadcast</button>
        </form>
      </div>
    </div>

    <!-- Right Column: Live Admin Chat Control Room -->
    <div class="table-card" style="display:flex; flex-direction:column; height:580px; margin-bottom:0;">
      <div class="table-header" style="background:#059669; color:#fff;">
        <div>
          <h3 style="font-size:1rem; color:#fff; margin:0;"><i class="fa-brands fa-whatsapp"></i> Live Portal Admin
            Console</h3>
          <small style="opacity:0.9; font-size:0.75rem;">Replying to Student ID:
            <?php echo htmlspecialchars($selectedStudentId); ?></small>
        </div>
        <!-- External Redirection Optional Button -->
        <a href="https://web.whatsapp.com" target="_blank" class="btn btn-sm"
          style="background:rgba(255,255,255,0.2); color:#fff; border:none;"
          title="Open external WhatsApp Web application">
          <i class="fa-solid fa-arrow-up-right-from-square"></i> WhatsApp Web
        </a>
      </div>

      <div
        style="flex:1; padding:1.2rem; overflow-y:auto; background:#f8fafc; display:flex; flex-direction:column; gap:10px;"
        id="adminChatStream">
        <?php if (empty($chatMessages)): ?>
          <p style="color:var(--text-muted); text-align:center;">No previous messages with this student.</p>
        <?php else: ?>
          <?php foreach ($chatMessages as $msg):
            $isAdmin = in_array($msg['sender_role'], ['Admin', 'Allocator']);
            ?>
            <div class="chat-bubble <?php echo $isAdmin ? 'user' : 'support'; ?>">
              <strong><?php echo htmlspecialchars($msg['sender_name']); ?>
                (<?php echo htmlspecialchars($msg['sender_role']); ?>):</strong>
              <?php echo htmlspecialchars($msg['message']); ?>
              <div style="font-size:0.65rem; opacity:0.7; margin-top:2px; text-align:right;">
                <?php echo htmlspecialchars($msg['timestamp']); ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div style="padding:1rem; background:#ffffff; border-top:1px solid var(--portal-border); display:flex; gap:8px;">
        <input type="text" id="adminChatInput" class="form-control" placeholder="Type reply to student..."
          style="font-size:0.9rem;">
        <button id="btnAdminSendChat" class="btn btn-success"><i class="fa-solid fa-paper-plane"></i> Reply</button>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('adminChatInput');
    const btn = document.getElementById('btnAdminSendChat');
    const stream = document.getElementById('adminChatStream');
    const studentId = "<?php echo $selectedStudentId; ?>";

    function sendAdminMsg() {
      const txt = input.value.trim();
      if (!txt) return;

      const b = document.createElement('div');
      b.className = 'chat-bubble user';
      b.innerHTML = `<strong>Admin Support:</strong> ${escapeHtml(txt)}`;
      stream.appendChild(b);
      stream.scrollTop = stream.scrollHeight;
      input.value = '';

      const fd = new FormData();
      fd.append('message', txt);
      fd.append('student_id', studentId);

      fetch('/api.php?action=send_chat_message', { method: 'POST', body: fd });
    }

    if (btn) btn.addEventListener('click', sendAdminMsg);
    if (input) input.addEventListener('keypress', e => { if (e.key === 'Enter') sendAdminMsg(); });

    if (stream) stream.scrollTop = stream.scrollHeight;
  });
</script>

<script src="/assets/js/portal.js"></script>
</div>
</div>
</body>

</html>