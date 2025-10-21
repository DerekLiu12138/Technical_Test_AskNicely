<template>
  <main class="wrap">
    <header class="topbar">
      <h1>Engineer Test AskNicely</h1>
    </header>

    <UploadCsv
      :uploading="uploading"
      :result="importResult"
      @upload="handleUpload"
    />

    <section v-if="errors.emp || errors.avg" class="card">
      <p class="error"><b>Load error:</b> {{ errors.emp || errors.avg }}</p>
      <p class="small muted">Reload the page or try again later.</p>
    </section>

    <EmployeesTable
      :rows="employees"
      :loading="loading.emp"
      @save="saveEmail"
    />

    <AveragesTable
      :rows="averages"
      :loading="loading.avg"
    />

    <Toast :msg="toast.msg"/>
  </main>
</template>

<script>
import UploadCsv from '@/components/UploadCsv.vue';
import EmployeesTable from '@/components/EmployeesTable.vue';
import AveragesTable from '@/components/AveragesTable.vue';
import Toast from '@/components/Toast.vue';
import { api } from '@/utils/api';

export default {
  name: 'App',
  components: { UploadCsv, EmployeesTable, AveragesTable, Toast },
  data() {
    return {
      employees: [],
      averages: [],
      uploading: false,
      importResult: null,
      loading: { emp: false, avg: false },
      errors: { emp: '', avg: '' },
      toast: { msg: '', timer: null },
    };
  },
  mounted() {
    // Initial load only. No manual refresh & no auto refresh by design.
    this.refreshAll();
  },
  methods: {
    showToast(msg, ms = 2200) {
      this.toast.msg = msg;
      clearTimeout(this.toast.timer);
      this.toast.timer = setTimeout(() => (this.toast.msg = ''), ms);
    },

    async handleUpload(file) {
      this.uploading = true;
      try {
        const { ok, data } = await api.uploadCsv(file);
        this.importResult = data;
        this.showToast(
          ok
            ? `Imported ${data.imported}, skipped ${data.skipped}`
            : (data?.error || 'Import failed'),
          ok ? 2200 : 3000
        );
      } catch (err) {
        this.importResult = null;
        this.showToast(err.message || 'Upload failed', 3000);
      } finally {
        this.uploading = false;
        // Re-query after import to reflect the latest state.
        await this.refreshAll();
      }
    },

    async loadEmployees() {
      this.loading.emp = true;
      this.errors.emp = '';
      try {
        const list = await api.employees();
        this.employees = list.map(x => ({
          ...x,
          _saving: false,
          _error: '',
          _prevEmail: x.email,
          _justSaved: false
        }));
      } catch (err) {
        this.errors.emp = err.message || 'Failed to load employees';
      } finally {
        this.loading.emp = false;
      }
    },

    async saveEmail(row) {
      try {
        await api.updateEmail(row.id, row.email);
        this.showToast('Saved');
        // Re-query averages only (email change may affect no data,
        // but salary edits could in other versions; kept for consistency).
        this.loadAverages();
      } catch (err) {
        this.showToast(err.message || 'Failed to save', 3000);
        // Throw back so the child can roll back optimistically.
        throw err;
      }
    },

    async loadAverages() {
      this.loading.avg = true;
      this.errors.avg = '';
      try {
        this.averages = await api.averages();
      } catch (err) {
        this.errors.avg = err.message || 'Failed to load averages';
      } finally {
        this.loading.avg = false;
      }
    },

    async refreshAll() {
      await Promise.allSettled([this.loadEmployees(), this.loadAverages()]);
    }
  }
};
</script>

<style>
@import '@/assets/styles.css';
</style>
