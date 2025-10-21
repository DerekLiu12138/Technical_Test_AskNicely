const API_BASE = import.meta.env.VITE_API_BASE || '/api';
const url = (p) => `${API_BASE}${p}`;

async function parseJsonSafe(res) { try { return await res.json(); } catch { return null; } }
async function handle(res) {
  const data = await parseJsonSafe(res);
  if (!res.ok) {
    const err = new Error((data && (data.error || data.message)) || `HTTP ${res.status}`);
    err.status = res.status; err.payload = data;
    throw err;
  }
  return data;
}

export const api = {
  async uploadCsv(file) {
    const fd = new FormData();
    fd.append('file', file);
    const res = await fetch(url('/upload'), { method: 'POST', body: fd });
    const data = await parseJsonSafe(res);
    return { ok: res.ok, data };
  },
  async employees() {
    const res = await fetch(url('/employees'));
    return handle(res);
  },
  async updateEmail(id, email) {
    const res = await fetch(url(`/employees/${id}/email`), {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email })
    });
    return handle(res);
  },
  async averages() {
    const res = await fetch(url('/companies/avg-salary'));
    return handle(res);
  },
};
