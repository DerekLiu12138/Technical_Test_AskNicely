<template>
    <section class="card">
      <div class="row between">
        <h2>Average Salary by Company</h2>
        <button class="btn ghost" @click="$emit('refresh')" :disabled="loading">
          <span v-if="loading" class="spinner" aria-hidden="true"></span>Refresh
        </button>
      </div>
      <table class="table">
        <thead><tr><th>Company</th><th>Average Salary</th></tr></thead>
        <tbody>
          <tr v-for="a in rows" :key="a.company">
            <td>{{ a.company }}</td>
            <td>{{ asCurrency(a.avg_salary) }}</td>
          </tr>
          <tr v-if="!rows?.length">
            <td colspan="2" class="muted small">No data yet. Upload a CSV first.</td>
          </tr>
        </tbody>
      </table>
    </section>
  </template>
  
  <script>
  import { asCurrency } from '@/utils/format'
  export default { name:'AveragesTable', props:{ rows:Array, loading:Boolean }, methods:{ asCurrency } }
  </script>
  