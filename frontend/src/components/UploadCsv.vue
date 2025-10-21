<template>
  <section class="card">
    <div class="row between">
      <h2>Upload CSV</h2>
      <button class="btn ghost" @click="downloadExample">Example CSV</button>
    </div>

    <form
      @submit.prevent="onSubmit"
      class="row"
      @dragover.prevent
      @drop.prevent="onDrop"
    >
      <input
        type="file"
        accept=".csv,text/csv"
        @change="onFile"
        aria-label="Select CSV file"
      >
      <button class="btn" :disabled="!file || uploading">
        <span v-if="uploading" class="spinner" aria-hidden="true"></span>
        {{ uploading ? 'Uploading…' : 'Upload & Import' }}
      </button>
    </form>

    <p v-if="error" class="error small">{{ error }}</p>

    <p v-if="result" class="muted">
      <b>Imported:</b> {{ result.imported }} · <b>Skipped:</b> {{ result.skipped }}
    </p>
    <ul v-if="result?.errors?.length" class="errors">
      <li v-for="(e,i) in result.errors" :key="i">{{ e }}</li>
    </ul>
  </section>
</template>

<script>
export default {
  name: 'UploadCsv',
  props: { uploading: Boolean, result: Object },
  emits: ['upload'],
  data() {
    return { file: null, error: '' };
  },
  methods: {
    // Validate and set the file
    setFile(f) {
      this.error = '';
      if (!f) { this.file = null; return; }
      const okType = /(^text\/csv$)|(\.csv$)/i.test(f.type) || /\.csv$/i.test(f.name);
      if (!okType) { this.error = 'Please select a .csv file'; this.file = null; return; }
      if (f.size > 10 * 1024 * 1024) { this.error = 'File too large (max 10MB)'; this.file = null; return; }
      this.file = f;
    },
    onFile(e) { this.setFile(e.target.files[0]); },
    onDrop(e) { const f = e.dataTransfer.files?.[0]; this.setFile(f); },
    onSubmit() { if (this.file) this.$emit('upload', this.file); },

    // Download an example CSV
    downloadExample() {
      const rows = [
        ['Company Name','Employee Name','Email Address','Salary'],
        ['ACME Corporation','John Doe','johndoe@acme.com',50000],
        ['ACME Corporation','Jane Doe','janedoe@acme.com',55000],
        ['ACME Corporation','Bob Smith','bobsmith@acme.com',60000],
        ['ACME Corporation','Alice Johnson','alicejohnson@acme.com',65000],
        ['Stark Industries','Tony Stark','tony@stark.com',100000],
        ['Stark Industries','Pepper Potts','pepper@stark.com',75000],
        ['Stark Industries','Happy Hogan','happy@stark.com',60000],
        ['Stark Industries','Rhodey Rhodes','rhodey@stark.com',80000],
        ['Wayne Enterprises','Bruce Wayne','bruce@wayneenterprises.com',90000],
        ['Wayne Enterprises','Alfred Pennyworth','alfred@wayneenterprises.com',50000],
        ['Wayne Enterprises','Dick Grayson','dick@wayneenterprises.com',60000],
        ['Wayne Enterprises','Barbara Gordon','barbara@wayneenterprises.com',55000],
      ];
      const csv = rows.map(r => r.join(',')).join('\n');
      const blob = new Blob([new Uint8Array([0xEF,0xBB,0xBF]), csv], { type: 'text/csv;charset=utf-8;' });
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = 'employees.csv';
      a.click();
      URL.revokeObjectURL(a.href);
    }
  }
};
</script>
