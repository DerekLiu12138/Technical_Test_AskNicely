<template>
  <main class="wrap">
    <header class="topbar">
      <h1>Engineer Test AskNicely</h1>
    </header>

    <UploadCsv :uploading="uploading" :result="importResult" @upload="handleUpload"/>

    <EmployeesTable
      :rows="employees" :loading="loading.emp"
      @refresh="loadEmployees" @save="saveEmail"
    />

    <AveragesTable
      :rows="averages" :loading="loading.avg"
      @refresh="loadAverages"
    />

    <Toast :msg="toast.msg"/>
  </main>
</template>

<script>
import UploadCsv from '@/components/UploadCsv.vue'
import EmployeesTable from '@/components/EmployeesTable.vue'
import AveragesTable from '@/components/AveragesTable.vue'
import Toast from '@/components/Toast.vue'
import { api } from '@/utils/api'

export default {
  components:{ UploadCsv, EmployeesTable, AveragesTable, Toast },
  data(){
    return {
      employees:[], averages:[],
      uploading:false, importResult:null,
      loading:{ emp:false, avg:false },
      toast:{ msg:'', timer:null },
    }
  },
  mounted(){ this.refreshAll() },
  methods:{
    showToast(msg, ms=2200){ this.toast.msg=msg; clearTimeout(this.toast.timer); this.toast.timer=setTimeout(()=>this.toast.msg='', ms) },
    async handleUpload(file){
      this.uploading = true
      try{
        const { ok, data } = await api.uploadCsv(file)
        this.importResult = data
        this.showToast(ok ? `Imported ${data.imported}, skipped ${data.skipped}` : (data.error || 'Import failed'), ok?2200:3000)
      } finally {
        this.uploading = false
        await this.refreshAll()
      }
    },
    async loadEmployees(){
      this.loading.emp = true
      try{ this.employees = (await api.employees()).map(x=>({ ...x, _saving:false, _error:'' })) }
      finally{ this.loading.emp = false }
    },
    async saveEmail(row){
      row._saving = true
      try{
        const res = await api.updateEmail(row.id, row.email)
        if(!res.ok){
          const msg = await res.json().catch(()=>({error:'Failed'}))
          this.showToast(msg.error || 'Failed to save', 3000)
        } else this.showToast('Saved')
      } finally { row._saving = false }
    },
    async loadAverages(){
      this.loading.avg = true
      try{ this.averages = await api.averages() }
      finally{ this.loading.avg = false }
    },
    async refreshAll(){ await Promise.all([this.loadEmployees(), this.loadAverages()]) }
  }
}
</script>

<style>
@import '@/assets/styles.css';
</style>
