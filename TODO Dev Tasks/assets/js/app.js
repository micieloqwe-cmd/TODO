// ==========================================
// app.js — TaskFlow Frontend Logic
// ==========================================

// ---------- TOAST NOTIFICATIONS ----------
const Toast = {
  container: null,

  init() {
    this.container = document.getElementById('toast-container');
    if (!this.container) {
      this.container = document.createElement('div');
      this.container.className = 'toast-container';
      this.container.id = 'toast-container';
      document.body.appendChild(this.container);
    }
  },

  show(message, type = 'info', duration = 3000) {
    const icons = { success: '✓', error: '✕', info: 'ℹ' };
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `<span>${icons[type] || icons.info}</span><span>${message}</span>`;
    this.container.appendChild(toast);
    setTimeout(() => {
      toast.style.animation = 'toastOut 0.3s ease forwards';
      setTimeout(() => toast.remove(), 300);
    }, duration);
  }
};

// ---------- MODAL ----------
const Modal = {
  open(id) {
    const el = document.getElementById(id);
    if (el) { el.classList.add('open'); document.body.style.overflow = 'hidden'; }
  },
  close(id) {
    const el = document.getElementById(id);
    if (el) { el.classList.remove('open'); document.body.style.overflow = ''; }
  },
  closeAll() {
    document.querySelectorAll('.modal-overlay.open').forEach(m => {
      m.classList.remove('open');
    });
    document.body.style.overflow = '';
  }
};

// ---------- TODO CHECKBOX (AJAX toggle) ----------
function toggleStatus(todoId, checkbox) {
  const status = checkbox.checked ? 'done' : 'pending';
  const item = checkbox.closest('.todo-item');
  
  fetch('api/todo_action.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      action: 'toggle',
      id: todoId,
      status,
      csrf_token: document.querySelector('meta[name="csrf"]')?.content
    })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      item.classList.toggle('done', status === 'done');
      const statusTag = item.querySelector('.tag-status');
      if (statusTag) {
        statusTag.className = `tag tag-status-${status}`;
        statusTag.textContent = status === 'done' ? '✓ เสร็จแล้ว' : '⏳ ยังไม่เสร็จ';
      }
      Toast.show(status === 'done' ? 'งานเสร็จแล้ว! 🎉' : 'เปลี่ยนเป็นยังไม่เสร็จ', 'success');
      refreshStats();
    } else {
      checkbox.checked = !checkbox.checked;
      Toast.show(data.message || 'เกิดข้อผิดพลาด', 'error');
    }
  })
  .catch(() => {
    checkbox.checked = !checkbox.checked;
    Toast.show('เชื่อมต่อไม่ได้', 'error');
  });
}

// ---------- REFRESH STATS ----------
function refreshStats() {
  fetch('api/stats.php')
    .then(r => r.json())
    .then(data => {
      if (!data.success) return;
      const s = data.stats;
      
      const setEl = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
      setEl('stat-total', s.total);
      setEl('stat-done', s.done);
      setEl('stat-pending', s.pending);
      setEl('stat-overdue', s.overdue);

      const pct = s.total > 0 ? Math.round((s.done / s.total) * 100) : 0;
      setEl('stat-pct', pct + '%');
      const fill = document.getElementById('progress-fill');
      if (fill) fill.style.width = pct + '%';
    });
}

// ---------- DELETE TODO ----------
function confirmDelete(id, title) {
  document.getElementById('delete-todo-title').textContent = title;
  document.getElementById('delete-todo-id').value = id;
  Modal.open('modal-delete');
}

function deleteTodo() {
  const id = document.getElementById('delete-todo-id').value;
  const csrf = document.querySelector('meta[name="csrf"]')?.content;

  fetch('api/todo_action.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'delete', id, csrf_token: csrf })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      const item = document.querySelector(`.todo-item[data-id="${id}"]`);
      if (item) {
        item.style.animation = 'toastOut 0.3s ease forwards';
        setTimeout(() => { item.remove(); refreshStats(); }, 300);
      }
      Modal.close('modal-delete');
      Toast.show('ลบงานเรียบร้อยแล้ว', 'success');
    } else {
      Toast.show(data.message || 'เกิดข้อผิดพลาด', 'error');
    }
  });
}

// ---------- OPEN EDIT MODAL ----------
function openEdit(id) {
  fetch(`api/todo_action.php?action=get&id=${id}&csrf_token=${document.querySelector('meta[name="csrf"]')?.content}`)
    .then(r => r.json())
    .then(data => {
      if (!data.success) { Toast.show('ไม่สามารถโหลดข้อมูลได้', 'error'); return; }
      const t = data.todo;
      document.getElementById('edit-id').value      = t.id;
      document.getElementById('edit-title').value   = t.title;
      document.getElementById('edit-desc').value    = t.description || '';
      document.getElementById('edit-priority').value= t.priority;
      document.getElementById('edit-due').value     = t.due_date || '';
      Modal.open('modal-edit');
    });
}

// ---------- SAVE EDIT ----------
function saveEdit(e) {
  e.preventDefault();
  const form = e.target;
  const data = {
    action: 'update',
    id:          form.querySelector('#edit-id').value,
    title:       form.querySelector('#edit-title').value.trim(),
    description: form.querySelector('#edit-desc').value.trim(),
    priority:    form.querySelector('#edit-priority').value,
    due_date:    form.querySelector('#edit-due').value,
    csrf_token:  document.querySelector('meta[name="csrf"]')?.content
  };

  if (!data.title) { Toast.show('กรุณากรอกชื่องาน', 'error'); return; }

  fetch('api/todo_action.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  })
  .then(r => r.json())
  .then(res => {
    if (res.success) {
      Modal.close('modal-edit');
      Toast.show('แก้ไขงานเรียบร้อยแล้ว ✓', 'success');
      setTimeout(() => location.reload(), 600);
    } else {
      Toast.show(res.message || 'เกิดข้อผิดพลาด', 'error');
    }
  });
}

// ---------- ADD TODO FORM ----------
function saveAdd(e) {
  e.preventDefault();
  const form = e.target;
  const titleEl = form.querySelector('#add-title');
  const title = titleEl.value.trim();

  if (!title) {
    titleEl.focus();
    Toast.show('กรุณากรอกชื่องาน', 'error');
    return;
  }

  const data = {
    action:      'create',
    title,
    description: form.querySelector('#add-desc').value.trim(),
    priority:    form.querySelector('#add-priority').value,
    due_date:    form.querySelector('#add-due').value,
    csrf_token:  document.querySelector('meta[name="csrf"]')?.content
  };

  const btn = form.querySelector('[type=submit]');
  btn.disabled = true; btn.textContent = 'กำลังบันทึก...';

  fetch('api/todo_action.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  })
  .then(r => r.json())
  .then(res => {
    if (res.success) {
      Modal.close('modal-add');
      Toast.show('เพิ่มงานเรียบร้อยแล้ว 🎉', 'success');
      setTimeout(() => location.reload(), 700);
    } else {
      Toast.show(res.message || 'เกิดข้อผิดพลาด', 'error');
    }
  })
  .finally(() => { btn.disabled = false; btn.textContent = 'เพิ่มงาน'; });
}

// ---------- CLOSE MODAL ON BACKDROP CLICK ----------
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-overlay')) Modal.closeAll();
});

// ---------- FILTER / SEARCH (live) ----------
function applyFilters() {
  const search   = document.getElementById('filter-search')?.value || '';
  const status   = document.getElementById('filter-status')?.value || '';
  const priority = document.getElementById('filter-priority')?.value || '';
  
  const params = new URLSearchParams(window.location.search);
  if (search)   params.set('search', search);   else params.delete('search');
  if (status)   params.set('status', status);   else params.delete('status');
  if (priority) params.set('priority', priority); else params.delete('priority');
  params.set('page', '1');
  
  window.location.search = params.toString();
}

let searchDebounce;
document.addEventListener('DOMContentLoaded', () => {
  Toast.init();
  
  const searchInput = document.getElementById('filter-search');
  if (searchInput) {
    searchInput.addEventListener('input', () => {
      clearTimeout(searchDebounce);
      searchDebounce = setTimeout(applyFilters, 500);
    });
  }
});

// ---------- PROGRESS ANIMATION ON LOAD ----------
window.addEventListener('load', () => {
  const fill = document.getElementById('progress-fill');
  if (fill) {
    const pct = fill.dataset.pct || '0';
    setTimeout(() => { fill.style.width = pct + '%'; }, 200);
  }
});
