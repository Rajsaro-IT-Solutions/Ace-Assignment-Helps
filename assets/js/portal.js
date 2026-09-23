/**
 * Ace Assignment Helps Portal Dynamic Utilities & Mobile Navigation
 */

function initPortalAll() {
  if (window._portalInitialized) return;
  window._portalInitialized = true;
  initPortalMobileSidebarToggle();
  initTableSearchFilter();
  initModalListeners();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initPortalAll);
} else {
  initPortalAll();
}

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


function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

window.openModal = openModal;
window.closeModal = closeModal;
window.escapeHtml = escapeHtml;

