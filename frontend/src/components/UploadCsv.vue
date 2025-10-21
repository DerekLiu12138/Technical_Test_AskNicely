<template>
    <section class="card">
      <div class="row between">
        <h2>Upload CSV</h2>
        <button class="btn ghost" @click="downloadExample">Example CSV</button>
      </div>
      <form @submit.prevent="onSubmit" class="row">
        <input type="file" accept=".csv" @change="onFile">
        <button class="btn" :disabled="!file || uploading">
          <span v-if="uploading" class="spinner" aria-hidden="true"></span>
          {{ uploading ? 'Uploading…' : 'Upload & Import' }}
        </button>
      </form>
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
    name:'UploadCsv',
    props:{ uploading:Boolean, result:Object },
    emits:['upload'],
    data(){ return { file:null } },
    methods:{
      onFile(e){ this.file = e.target.files[0] || null },
      onSubmit(){ if(this.file) this.$emit('upload', this.file) },
      downloadExample(){
        const csv = [
          'Company Name,Employee Name,Email Address,Salary',
          'ACME Corporation,John Doe,johndoe@acme.com,50000',
          'ACME Corporation,Jane Doe,janedoe@acme.com,55000',
          'ACME Corporation,Bob Smith,bobsmith@acme.com,60000',
          'ACME Corporation,Alice Johnson,alicejohnson@acme.com,65000',
          'Stark Industries,Tony Stark,tony@stark.com,100000',
          'Stark Industries,Pepper Potts,pepper@stark.com,75000',
          'Stark Industries,Happy Hogan,happy@stark.com,60000',
          'Stark Industries,Rhodey Rhodes,rhodey@stark.com,80000',
          'Wayne Enterprises,Bruce Wayne,bruce@wayneenterprises.com,90000',
          'Wayne Enterprises,Alfred Pennyworth,alfred@wayneenterprises.com,50000',
          'Wayne Enterprises,Dick Grayson,dick@wayneenterprises.com,60000',
          'Wayne Enterprises,Barbara Gordon,barbara@wayneenterprises.com,55000',
        ].join('\n')
        const blob = new Blob([csv], {type:'text/csv;charset=utf-8;'})
        const a = document.createElement('a')
        a.href = URL.createObjectURL(blob); a.download = 'employees.csv'; a.click()
        URL.revokeObjectURL(a.href)
      }
    }
  }
  </script>
  