// public/assets/js/auth.js
async function authStatus() {
  try {
    const res = await apiGet('/api/auth.php?action=status');
    if (res.success) return res.data;
    return null;
  } catch (e) {
    console.error(e);
    return null;
  }
}

async function authLogin(payload) {
  return apiPost('/api/auth.php?action=login', payload);
}

async function authLogout() {
  return apiPost('/api/auth.php?action=logout', {});
}