<template>
  <main class="wrap">
    <h1>Engineer Test</h1>

    <section class="card">
      <h2>Upload CSV</h2>
      <form @submit.prevent="upload" class="row">
        <input type="file" accept=".csv" @change="onFile" />
        <button :disabled="!file || uploading">{{ uploading? 'Uploading…':'Upload & Import' }}</button>
      </form>
      <p v-if="importResult">Imported: {{ importResult.imported }}, Skipped: {{ importResult.skipped }}</p>
      <ul v-if="importResult?.errors?.length">
        <li v-for="(e,i) in importResult.errors" :key="i" class="error">{{ e }}</li>
      </ul>
    </section>

    <section class="card">
      <div class="row">
        <h2>Employees</h2>
        <button @click="loadEmployees">Refresh</button>
      </div>
      <table class="table">
        <thead>
          <tr>
            <th>Company</th><th>Name</th><th>Email</th><th>Salary</th><th>Action</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="e in employees" :key="e.id">
            <td>{{ e.company }}</td>
            <td>{{ e.name }}</td>
            <td><input v-model="e.email" class="input" /></td>
            <td>{{ asCurrency(e.salary) }}</td>
            <td><button @click="saveEmail(e)">Save</button></td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="card">
      <div class="row">
        <h2>Average Salary by Company</h2>
        <button @click="loadAverages">Refresh</button>
      </div>
      <table class="table">
        <thead><tr><th>Company</th><th>Average Salary</th></tr></thead>
        <tbody>
          <tr v-for="a in averages" :key="a.company">
            <td>{{ a.company }}</td>
            <td>{{ asCurrency(a.avg_salary) }}</td>
          </tr>
        </tbody>
      </table>
    </section>
  </main>
</template>

<script>
const API = (path) => `http://localhost:8088${path}`

export default {
  data(){
    return { file:null, uploading:false, importResult:null, employees:[], averages:[] }
  },
  mounted(){
    this.loadEmployees(); this.loadAverages();
  },
  methods:{
    onFile(ev){ this.file = ev.target.files[0] || null },
    async upload(){
      if(!this.file) return
      this.uploading = true
      const fd = new FormData(); fd.append('file', this.file)
      const res = await fetch(API('/api/upload'), { method:'POST', body: fd })
      this.importResult = await res.json()
      this.uploading = false
      await this.loadEmployees(); await this.loadAverages();
    },
    async loadEmployees(){
      const res = await fetch(API('/api/employees'))
      this.employees = await res.json()
    },
    async saveEmail(e){
      const res = await fetch(API(`/api/employees/${e.id}/email`), {
        method: 'PATCH',
        headers: { 'Content-Type':'application/json' },
        body: JSON.stringify({ email: e.email })
      })
      if(!res.ok){
        const msg = await res.json().catch(()=>({error:'Failed'}))
        alert(msg.error || 'Failed')
      }
    },
    async loadAverages(){
      const res = await fetch(API('/api/companies/avg-salary'))
      this.averages = await res.json()
    },
    asCurrency(v){
      const n = Number(v); return Number.isFinite(n)
        ? n.toLocaleString(undefined,{style:'currency',currency:'USD',maximumFractionDigits:0})
        : v
    }
  }
}
</script>

<style scoped>
.wrap{max-width:900px;margin:2rem auto;font-family:system-ui}
.card{margin:1rem 0;padding:1rem;border:1px solid #ddd;border-radius:8px}
.row{display:flex;align-items:center;gap:8px}
.table{width:100%;margin-top:8px;border-collapse:collapse;border:1px solid #ccc}
.table th,.table td{padding:6px;border:1px solid #ccc;text-align:left}
.table thead th{background:#f6f7fb}
.input{width:100%}
.error{color:#b00}
</style>
