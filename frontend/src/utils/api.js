const API_BASE = import.meta.env.VITE_API_BASE || 'http://localhost:8088';
const url = (p) => `${API_BASE}${p}`;

// Parse JSON safely; return null if body is not JSON
async function parseJsonSafe(res) {
  try { return await res.json(); } catch { return null; }
}

// Throw on !ok with enriched Error object; otherwise return parsed JSON
async function handle(res) {
  const data = await parseJsonSafe(res);
  if (!res.ok) {
    const err = new Error((data && (data.error || data.message)) || `HTTP ${res.status}`);
    err.status = res.status;
    err.payload = data;
    throw err;
  }
  return data;
}

export const api = {
  // POST /api/upload with multipart/form-data
  async uploadCsv(file) {
    const fd = new FormData();
    fd.append('file', file);
    const res = await fetch(url('/api/upload'), { method: 'POST', body: fd });
    const data = await parseJsonSafe(res);
    return { ok: res.ok, data };
  },

  // GET /api/employees
  async employees() {
    const res = await fetch(url('/api/employees'));
    return handle(res);
  },

  // PATCH /api/employees/:id/email
  async updateEmail(id, email) {
    const res = await fetch(url(`/api/employees/${id}/email`), {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email })
    });
    return handle(res);
  },

  // GET /api/companies/avg-salary
  async averages() {
    const res = await fetch(url('/api/companies/avg-salary'));
    return handle(res);
  },
};
