
function updateCapacity(scheduleId) {
  const wrap = document.getElementById('cap-' + scheduleId);
  if (!wrap) return;

  fetch('../ajax/capacity.php?schedule_id=' + scheduleId)
    .then(r => r.json())
    .then(data => {
      const fill = wrap.querySelector('.capacity-fill');
      const label = wrap.querySelector('.cap-text');
      const enrollBtn = document.getElementById('enroll-btn-' + scheduleId);
      const pct = (data.current / data.max) * 100;

      fill.style.width = Math.min(pct, 100) + '%';

      
      fill.className = 'capacity-fill';
      if (pct >= 100) fill.classList.add('full');
      else if (pct >= 75) fill.classList.add('high');
      else if (pct >= 50) fill.classList.add('medium');

      // Yazı
      if (label) label.textContent = data.current + ' / ' + data.max;

      // Buton durumu
      if (enrollBtn) {
        if (pct >= 100) {
          enrollBtn.disabled = true;
          enrollBtn.textContent = 'Dolu';
          enrollBtn.classList.remove('btn-primary');
          enrollBtn.classList.add('btn-ghost');
        }
      }
    })
    .catch(() => {/* sessiz hata */});
}


function enrollClass(scheduleId, btn) {
  btn.disabled = true;
  const original = btn.innerHTML;
  btn.innerHTML = '<span class="spinner"></span>';

  fetch('../ajax/enroll.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'schedule_id=' + scheduleId + '&action=enroll'
  })
    .then(r => r.json())
    .then(data => {
      showToast(data.message, data.success ? 'success' : 'danger');
      if (data.success) {
        btn.textContent = '✓ Kayıtlısın';
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-success');
        btn.disabled = true;
        updateCapacity(scheduleId);
      } else {
        btn.innerHTML = original;
        btn.disabled = false;
      }
    })
    .catch(() => {
      btn.innerHTML = original;
      btn.disabled = false;
      showToast('Bir hata oluştu.', 'danger');
    });
}


function cancelEnroll(scheduleId, btn) {
  if (!confirm('Bu dersi iptal etmek istediğinizden emin misiniz?')) return;

  btn.disabled = true;
  const original = btn.innerHTML;
  btn.innerHTML = '<span class="spinner"></span>';

  fetch('../ajax/enroll.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'schedule_id=' + scheduleId + '&action=cancel'
  })
    .then(r => r.json())
    .then(data => {
      showToast(data.message, data.success ? 'success' : 'danger');
      if (data.success) {
        const row = document.getElementById('enrollment-row-' + scheduleId);
        if (row) row.remove();
        updateCapacity(scheduleId);
      } else {
        btn.innerHTML = original;
        btn.disabled = false;
      }
    })
    .catch(() => {
      btn.innerHTML = original;
      btn.disabled = false;
      showToast('Bir hata oluştu.', 'danger');
    });
}


function showToast(message, type = 'info') {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    container.style.cssText = `
      position: fixed; bottom: 1.5rem; right: 1.5rem;
      z-index: 9999; display: flex; flex-direction: column; gap: 0.5rem;
    `;
    document.body.appendChild(container);
  }

  const colors = {
    success: '#22c55e',
    danger:  '#ef4444',
    warning: '#f59e0b',
    info:    '#FF6B00'
  };

  const toast = document.createElement('div');
  toast.style.cssText = `
    background: #1a1a1a; border: 1px solid ${colors[type] || colors.info};
    color: #f0f0f0; padding: 0.8rem 1.2rem; border-radius: 8px;
    font-family: Barlow, sans-serif; font-size: 0.9rem; font-weight: 500;
    box-shadow: 0 4px 20px rgba(0,0,0,0.5);
    animation: fadeUp 0.3s ease;
    max-width: 300px;
  `;
  toast.textContent = message;

  container.appendChild(toast);
  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transition = 'opacity 0.3s';
    setTimeout(() => toast.remove(), 300);
  }, 3000);
}


document.addEventListener('DOMContentLoaded', () => {
  
  document.querySelectorAll('[data-schedule]').forEach(el => {
    const id = el.dataset.schedule;
    updateCapacity(id);
  });


  setInterval(() => {
    document.querySelectorAll('[data-schedule]').forEach(el => {
      updateCapacity(el.dataset.schedule);
    });
  }, 30000);
});
