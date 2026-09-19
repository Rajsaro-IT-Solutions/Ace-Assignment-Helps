/**
 * Ace Assignment Helps Portal Dynamic Utilities & Mobile Navigation
 */

document.addEventListener('DOMContentLoaded', () => {
  initPortalMobileSidebarToggle();
  initTableSearchFilter();
  initModalListeners();
  initLivePortalChat();
});

function initPortalMobileSidebarToggle() {
  const sidebarBtn = document.getElementById('portalSidebarBtn');
  const sidebar = document.querySelector('.portal-sidebar');
  const overlay = document.getElementById('portalSidebarOverlay');

  if (sidebarBtn && sidebar) {
    sidebarBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      sidebar.classList.toggle('active');
      if (overlay) overlay.classList.toggle('active');
    });

    if (overlay) {
      overlay.addEventListener('click', () => {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
      });
    }

    document.addEventListener('click', (e) => {
      if (sidebar.classList.contains('active') && !sidebar.contains(e.target) && e.target !== sidebarBtn) {
        sidebar.classList.remove('active');
        if (overlay) overlay.classList.remove('active');
      }
    });
  }
}

function initTableSearchFilter() {
  const searchInput = document.getElementById('tableSearchInput');
  const statusFilter = document.getElementById('tableStatusFilter');
  const priorityFilter = document.getElementById('tablePriorityFilter');
  const subjectFilter = document.getElementById('tableSubjectFilter');
  const table = document.querySelector('.data-table');

  if (!table) return;

  function filterRows() {
    const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
    const statusVal = statusFilter ? statusFilter.value.toLowerCase() : '';
    const priorityVal = priorityFilter ? priorityFilter.value.toLowerCase() : '';
    const subjectVal = subjectFilter ? subjectFilter.value.toLowerCase() : '';

    const rows = table.querySelectorAll('tbody tr');

    rows.forEach(row => {
      const text = row.innerText.toLowerCase();
      const rowStatus = row.dataset.status ? row.dataset.status.toLowerCase() : '';
      const rowPriority = row.dataset.priority ? row.dataset.priority.toLowerCase() : '';
      const rowSubject = row.dataset.subject ? row.dataset.subject.toLowerCase() : '';

      let matchSearch = !query || text.includes(query);
      let matchStatus = !statusVal || rowStatus === statusVal;
      let matchPriority = !priorityVal || rowPriority === priorityVal;
      let matchSubject = !subjectVal || rowSubject === subjectVal;

      if (matchSearch && matchStatus && matchPriority && matchSubject) {
        row.style.display = '';
      } else {
        row.style.display = 'none';
      }
    });
  }

  [searchInput, statusFilter, priorityFilter, subjectFilter].forEach(el => {
    if (el) {
      el.addEventListener('input', filterRows);
      el.addEventListener('change', filterRows);
    }
  });
}

function initModalListeners() {
  const modalTriggers = document.querySelectorAll('[data-modal-target]');
  modalTriggers.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const targetId = btn.getAttribute('data-modal-target');
      const modal = document.getElementById(targetId);
      if (modal) {
        modal.style.display = 'flex';
      }
    });
  });

  const closeBtns = document.querySelectorAll('.modal-close, [data-modal-close]');
  closeBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const modal = btn.closest('.modal-overlay');
      if (modal) {
        modal.style.display = 'none';
      }
    });
  });
}

function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.style.display = 'flex';
  }
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.style.display = 'none';
  }
}

function initLivePortalChat() {
  const trigger = document.getElementById('portalLiveChatTrigger');
  const drawer = document.getElementById('portalLiveChatDrawer');
  const closeBtn = document.getElementById('closePortalChatDrawer');
  const chatInput = document.getElementById('portalChatInput');
  const sendBtn = document.getElementById('btnSendPortalChat');
  const chatBody = document.getElementById('portalChatBody');

  if (!trigger || !drawer) return;

  function loadMessages() {
    fetch('/api.php?action=get_chat_messages')
      .then(res => res.json())
      .then(data => {
        if (data.success && data.messages.length > 0 && chatBody) {
          chatBody.innerHTML = `
            <div class="chat-bubble support">
              👋 Hello! Welcome to Ace Assignment Helps live support. How can we assist your assignment today?
            </div>
          `;
          data.messages.forEach(msg => {
            const bubble = document.createElement('div');
            const isUser = msg.sender_role === 'Student';
            bubble.className = `chat-bubble ${isUser ? 'user' : 'support'}`;
            bubble.innerHTML = `<strong>${escapeHtml(msg.sender_name)}:</strong> ${escapeHtml(msg.message)}`;
            chatBody.appendChild(bubble);
          });
          chatBody.scrollTop = chatBody.scrollHeight;
        }
      });
  }

  trigger.addEventListener('click', () => {
    drawer.classList.toggle('active');
    if (drawer.classList.contains('active')) {
      loadMessages();
    }
  });

  if (closeBtn) {
    closeBtn.addEventListener('click', () => {
      drawer.classList.remove('active');
    });
  }

  function sendMessage() {
    const text = chatInput.value.trim();
    if (!text) return;
    
    const bubble = document.createElement('div');
    bubble.className = 'chat-bubble user';
    bubble.innerHTML = `${escapeHtml(text)}`;
    chatBody.appendChild(bubble);
    chatBody.scrollTop = chatBody.scrollHeight;
    chatInput.value = '';

    const formData = new FormData();
    formData.append('message', text);

    fetch('/api.php?action=send_chat_message', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        loadMessages();
      }
    });
  }

  if (sendBtn) sendBtn.addEventListener('click', sendMessage);
  if (chatInput) {
    chatInput.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') sendMessage();
    });
  }
}

function escapeHtml(str) {
  if (!str) return '';
  return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
}

window.openModal = openModal;
window.closeModal = closeModal;
window.escapeHtml = escapeHtml;
