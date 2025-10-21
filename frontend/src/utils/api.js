const API_BASE = import.meta.env.VITE_API_BASE || 'http://localhost:8088'
const url = (p) => `${API_BASE}${p}`

export const api = {
  async uploadCsv(file){
    const fd = new FormData(); fd.append('file', file)
    const res = await fetch(url('/api/upload'), { method:'POST', body: fd })
    const data = await res.json()
    return { ok: res.ok, data }
  },
  async employees(){
    const res = await fetch(url('/api/employees')); return res.json()
  },
  async updateEmail(id, email){
    const res = await fetch(url(`/api/employees/${id}/email`), {
      method:'PATCH', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ email })
    })
    return res
  },
  async averages(){
    const res = await fetch(url('/api/companies/avg-salary')); return res.json()
  },
}
