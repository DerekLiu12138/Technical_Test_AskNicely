<template>
    <section class="card">
      <div class="row between">
        <h2>Employees</h2>
        <div class="row">
          <input class="input" placeholder="Search name/email…" v-model.trim="q">
          <select class="input" v-model="companyFilter" aria-label="Company filter">
            <option value="">All companies</option>
            <option v-for="c in companies" :key="c" :value="c">{{ c }}</option>
          </select>
          <button class="btn ghost" @click="$emit('refresh')" :disabled="loading">
            <span v-if="loading" class="spinner" aria-hidden="true"></span>Refresh
          </button>
        </div>
      </div>
  
      <table class="table">
        <thead>
          <tr>
            <th @click="sortBy('company')" :aria-sort="ariaSort('company')">Company</th>
            <th @click="sortBy('name')" :aria-sort="ariaSort('name')">Name</th>
            <th>Email</th>
            <th @click="sortBy('salary')" :aria-sort="ariaSort('salary')">Salary</th>
            <th style="width:110px">Action</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="e in filteredEmployees" :key="e.id">
            <td>{{ e.company }}</td>
            <td>{{ e.name }}</td>
            <td>
              <input class="input" v-model="e.email" @blur="validateEmail(e)">
              <div v-if="e._error" class="small error">{{ e._error }}</div>
            </td>
            <td>{{ asCurrency(e.salary) }}</td>
            <td>
              <button class="btn" @click="onSave(e)" :disabled="!!e._error || e._saving">
                <span v-if="e._saving" class="spinner" aria-hidden="true"></span>Save
              </button>
            </td>
          </tr>
          <tr v-if="!filteredEmployees.length">
            <td colspan="5" class="muted small">No employees match current filter.</td>
          </tr>
        </tbody>
      </table>
    </section>
  </template>
  
  <script>
  import { asCurrency } from '@/utils/format'
  export default {
    name:'EmployeesTable',
    props:{ rows:Array, loading:Boolean },
    emits:['refresh','save'],
    data(){ return { q:'', companyFilter:'', sort:{ key:'company', dir:'asc' } } },
    computed:{
      companies(){ return [...new Set((this.rows||[]).map(e=>e.company))].sort() },
      filteredEmployees(){
        let rows = [...(this.rows||[])]
        if(this.q){
          const s = this.q.toLowerCase()
          rows = rows.filter(r => r.name.toLowerCase().includes(s) || r.email.toLowerCase().includes(s))
        }
        if(this.companyFilter){ rows = rows.filter(r => r.company === this.companyFilter) }
        const {key, dir} = this.sort
        rows.sort((a,b)=> (a[key]>b[key]?1:a[key]<b[key]? -1:0) * (dir==='asc'?1:-1))
        return rows
      }
    },
    methods:{
      asCurrency,
      ariaSort(col){ return this.sort.key===col ? (this.sort.dir==='asc'?'ascending':'descending') : 'none' },
      sortBy(k){ this.sort.key===k ? (this.sort.dir = this.sort.dir==='asc'?'desc':'asc') : (this.sort={key:k,dir:'asc'}) },
      validateEmail(row){
        row._error = ''
        const email = (row.email || '').trim()
        if(!email) { row._error = 'Email is required'; return }
        const ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
        if(!ok) row._error = 'Invalid email format'
      },
      onSave(e){ this.validateEmail(e); if(!e._error) this.$emit('save', e) },
    }
  }
  </script>
  