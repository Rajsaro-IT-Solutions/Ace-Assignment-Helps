<?php
$pageTitle = "Live WhatsApp & Support Hub";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Student');
$user = Auth::currentUser();
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

$chatMessages = DataStore::filter('chat_messages', function($m) use ($user) {
    return isset($m['student_id']) && $m['student_id'] === $user['id'];
});
?>

<div style="max-width: 900px; margin:0 auto;">
  <div class="grid-2" style="display:grid; grid-template-columns: 1fr 1.5fr; gap:1.5rem;">
    <!-- Active Triggers Info -->
    <div>
      <div class="table-card" style="padding:1.5rem;">
        <div style="width:48px; height:48px; background:#d1fae5; color:#059669; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.5rem; margin-bottom:1rem;">
          <i class="fa-brands fa-whatsapp"></i>
        </div>
        <h3 style="font-size:1.2rem; color:var(--text-main); margin-bottom:0.5rem;">WhatsApp Integration</h3>
        <p style="color:var(--text-muted); font-size:0.88rem; margin-bottom:1.2rem;">All chat and support messages are synchronized directly on your portal. External WhatsApp app opening is optional.</p>

        <div style="background:#f8fafc; border:1px solid var(--portal-border); border-radius:var(--radius-sm); padding:1rem; margin-bottom:1.2rem;">
          <small style="color:var(--text-muted); font-weight:700; text-transform:uppercase;">Connected Phone Number</small>
          <div style="font-weight:700; color:var(--text-main); font-size:0.95rem; margin-top:2px;"><?php echo htmlspecialchars($user['phone'] ?? '+1 555-234-5678'); ?></div>
        </div>

        <?php
        $supportWa = class_exists('DataStore') ? DataStore::getSetting('whatsapp_phone', '+91 8233432123') : '+91 8233432123';
        $supportWaClean = preg_replace('/[^0-9]/', '', $supportWa);
        if (empty($supportWaClean)) $supportWaClean = '918233432123';
        ?>
        <!-- Optional External Redirection Button -->
        <a href="https://wa.me/<?php echo $supportWaClean; ?>?text=Hi%20AceAssignment!%20Chatting%20from%20Student%20Portal." target="_blank" class="btn btn-outline btn-sm" style="width:100%; text-align:center; display:block;">
          <i class="fa-solid fa-arrow-up-right-from-square"></i> Open in External WhatsApp (<?php echo htmlspecialchars($supportWa); ?>)
        </a>
      </div>
    </div>

    <!-- Live In-Portal WhatsApp Messenger -->
    <div class="table-card" style="display:flex; flex-direction:column; height:500px; margin-bottom:0;">
      <div class="table-header" style="background:#059669; color:#fff;">
        <h3 style="font-size:1rem; color:#fff; margin:0;"><i class="fa-brands fa-whatsapp"></i> Live Portal Support Chat</h3>
        <span class="badge badge-success" style="background:#a7f3d0; color:#047857;">Online</span>
      </div>

      <div style="flex:1; padding:1.2rem; overflow-y:auto; background:#f8fafc; display:flex; flex-direction:column; gap:10px;" id="pageChatStream">
        <div class="chat-bubble support">
          👋 Hello <strong><?php echo htmlspecialchars($user['name']); ?></strong>! You are connected to Ace Support. Send a message below to chat live inside the portal!
        </div>
        <?php foreach ($chatMessages as $msg): 
          $isUser = ($msg['sender_role'] === 'Student');
        ?>
          <div class="chat-bubble <?php echo $isUser ? 'user' : 'support'; ?>">
            <strong><?php echo htmlspecialchars($msg['sender_name']); ?>:</strong> <?php echo htmlspecialchars($msg['message']); ?>
          </div>
        <?php endforeach; ?>
      </div>

      <div style="padding:1rem; background:#ffffff; border-top:1px solid var(--portal-border); display:flex; gap:8px;">
        <input type="text" id="pageChatInput" class="form-control" placeholder="Type your message..." style="font-size:0.9rem;">
        <button id="btnPageSendChat" class="btn btn-success"><i class="fa-solid fa-paper-plane"></i></button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const input = document.getElementById('pageChatInput');
  const btn = document.getElementById('btnPageSendChat');
  const stream = document.getElementById('pageChatStream');

  function sendMsg() {
    const txt = input.value.trim();
    if (!txt) return;

    const b = document.createElement('div');
    b.className = 'chat-bubble user';
    b.innerHTML = `<strong>You:</strong> ${escapeHtml(txt)}`;
    stream.appendChild(b);
    stream.scrollTop = stream.scrollHeight;
    input.value = '';

    const fd = new FormData();
    fd.append('message', txt);

    fetch('/api.php?action=send_chat_message', { method: 'POST', body: fd });
  }

  if (btn) btn.addEventListener('click', sendMsg);
  if (input) input.addEventListener('keypress', e => { if (e.key === 'Enter') sendMsg(); });
});
</script>

</div>
</div>
</body>
</html>
