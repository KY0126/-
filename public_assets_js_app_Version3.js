// public/assets/js/app.js
async function apiGet(path) {
  const res = await fetch(path, { credentials: 'same-origin' });
  if (!res.ok) throw new Error('HTTP ' + res.status);
  return res.json();
}

async function apiPost(path, body) {
  const res = await fetch(path, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body)
  });
  if (!res.ok) throw new Error('HTTP ' + res.status);
  return res.json();
}

function showAlert(containerId, type, message) {
  const container = containerId ? document.getElementById(containerId) : null;
  const html = `<div class="alert alert-${type} alert-dismissible" role="alert">
                  ${escapeHtml(message)}
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>`;
  if (container) container.innerHTML = html;
  else {
    const div = document.createElement('div');
    div.innerHTML = html;
    document.body.prepend(div);
  }
}

function escapeHtml(s) {
  if (!s && s !== 0) return '';
  return String(s)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;')
    .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

function formatDateRange(start, end) {
  if (!start && !end) return '';
  if (!end) return start;
  return `${start} ~ ${end}`;
}

/* Leaflet map helper */
let _activityMap = null;
function initActivityMap(lat, lng, title) {
  if (!_activityMap) {
    _activityMap = L.map('activityMap').setView([lat, lng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap'
    }).addTo(_activityMap);
  } else {
    _activityMap.setView([lat, lng], 15);
    _activityMap.eachLayer(layer => {
      if (layer instanceof L.Marker) _activityMap.removeLayer(layer);
    });
  }
  L.marker([lat, lng]).addTo(_activityMap).bindPopup(title || '').openPopup();
}

/* Chart helper */
function renderBarChart(ctx, labels, data, opts = {}) {
  return new Chart(ctx, {
    type: 'bar',
    data: { labels: labels, datasets: [{ label: opts.label || '數量', data: data, backgroundColor: opts.color || '#4e73df' }] },
    options: opts.options || {}
  });
}