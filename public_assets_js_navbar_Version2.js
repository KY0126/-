// public/assets/js/navbar.js
document.addEventListener('DOMContentLoaded', async function() {
  const container = document.getElementById('topNavbar');
  if (!container) return;

  container.innerHTML = renderNavbarSkeleton();
  await refreshNavbar();

  container.addEventListener('click', async function(e) {
    const t = e.target;
    if (t.matches('#btnShowLogin')) {
      const modalEl = document.getElementById('loginModal');
      const modal = new bootstrap.Modal(modalEl);
      modal.show();
      document.getElementById('loginRole').value = 'student';
      toggleLoginRoleFields();
    } else if (t.matches('#btnLogout')) {
      try {
        const res = await authLogout();
        if (res.success) {
          await refreshNavbar();
          location.reload();
        } else showAlert('', 'danger', res.message || 'Logout failed');
      } catch (err) {
        showAlert('', 'danger', err.message || 'Network error');
      }
    } else if (t.matches('#btnLoginSubmit')) {
      const role = document.getElementById('loginRole').value;
      if (role === 'admin') {
        const username = document.getElementById('loginAdminUsername').value.trim();
        const password = document.getElementById('loginAdminPassword').value;
        if (!username || !password) { showAlert('loginAlert', 'warning', '請輸入管理者帳號與密碼'); return; }
        try {
          const res = await authLogin({ role: 'admin', username, password });
          if (res.success) {
            showAlert('', 'success', '登入成功');
            bootstrap.Modal.getInstance(document.getElementById('loginModal')).hide();
            await refreshNavbar();
            location.reload();
          } else showAlert('loginAlert', 'danger', res.message || '登入失敗');
        } catch (err) {
          showAlert('loginAlert', 'danger', err.message || '網路錯誤');
        }
      } else {
        const student_id = document.getElementById('loginStudentId').value.trim();
        if (!student_id) { showAlert('loginAlert', 'warning', '請輸入學生帳號/學號'); return; }
        try {
          const res = await authLogin({ role: 'student', student_id });
          if (res.success) {
            showAlert('', 'success', '學生登入成功');
            bootstrap.Modal.getInstance(document.getElementById('loginModal')).hide();
            await refreshNavbar();
            location.reload();
          } else showAlert('loginAlert', 'danger', res.message || '登入失敗');
        } catch (err) {
          showAlert('loginAlert', 'danger', err.message || '網路錯誤');
        }
      }
    }
  });

  document.addEventListener('change', function(e) {
    if (e.target && e.target.id === 'loginRole') toggleLoginRoleFields();
  });

  async function refreshNavbar() {
    const user = await authStatus();
    const navInner = container.querySelector('.navbar-inner');
    if (!navInner) return;
    navInner.innerHTML = renderNavbarContent(user);
  }

  function renderNavbarSkeleton() {
    return `<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
      <div class="container-fluid">
        <a class="navbar-brand" href="/index.html">研習活動管理</a>
        <div class="collapse navbar-collapse navbar-inner"></div>
        <div class="d-flex align-items-center ms-2" id="navbarActions"></div>
      </div>
    </nav>
    ${renderLoginModal()}`;
  }

  function renderNavbarContent(user) {
    const links = `<ul class="navbar-nav me-auto mb-2 mb-lg-0">
      <li class="nav-item"><a class="nav-link" href="/activities.html">活動管理</a></li>
      <li class="nav-item"><a class="nav-link" href="/registrations.html">報名管理</a></li>
      <li class="nav-item"><a class="nav-link" href="/checkin.html">QR 報到</a></li>
      <li class="nav-item"><a class="nav-link" href="/calendar.html">行事曆</a></li>
      <li class="nav-item"><a class="nav-link" href="/stats.html">出席統計</a></li>
      ${ (user && user.role === 'admin') ? '<li class="nav-item"><a class="nav-link" href="/admin_users.html">帳號管理</a></li>' : '' }
    </ul>`;
    let right = '';
    if (user) {
      if (user.role === 'admin') {
        right = `<span class="me-3 text-light">管理者: ${escapeHtml(user.display_name || user.username)}</span>
                 <button id="btnLogout" class="btn btn-outline-light btn-sm">登出</button>`;
      } else {
        right = `<span class="me-3 text-light">使用者: ${escapeHtml(user.name || user.student_id)}</span>
                 <button id="btnLogout" class="btn btn-outline-light btn-sm">登出</button>`;
      }
    } else {
      right = `<button id="btnShowLogin" class="btn btn-outline-light btn-sm">登入</button>`;
    }
    return `${links}<div class="d-flex">${right}</div>`;
  }

  function renderLoginModal() {
    return `
<div class="modal fade" id="loginModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">登入</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div id="loginAlert"></div>
        <div class="mb-2">
          <label class="form-label">身分</label>
          <select id="loginRole" class="form-select">
            <option value="student">學生 / 使用者</option>
            <option value="admin">平台管理者</option>
          </select>
        </div>

        <div id="studentLoginFields">
          <label class="form-label">學生帳號 / 學號</label>
          <input id="loginStudentId" class="form-control" placeholder="例如 S1001 或 KY0126">
        </div>

        <div id="adminLoginFields" style="display:none;">
          <label class="form-label">管理者帳號</label>
          <input id="loginAdminUsername" class="form-control mb-2" placeholder="username">
          <label class="form-label">密碼</label>
          <input id="loginAdminPassword" type="password" class="form-control" placeholder="password">
        </div>

      </div>
      <div class="modal-footer">
        <button id="btnLoginSubmit" class="btn btn-primary">登入</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
      </div>
    </div>
  </div>
</div>`;
  }

  function toggleLoginRoleFields() {
    const role = document.getElementById('loginRole').value;
    document.getElementById('studentLoginFields').style.display = (role === 'student' ? 'block' : 'none');
    document.getElementById('adminLoginFields').style.display = (role === 'admin' ? 'block' : 'none');
  }
});