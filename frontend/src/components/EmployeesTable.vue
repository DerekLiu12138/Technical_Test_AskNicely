<template>
  <section class="card">
    <div class="row between">
      <h2 id="emp-title">Employees</h2>
      <div class="row">
        <input
          class="input"
          placeholder="Search name/email…"
          v-model="q"
          @input="onSearchInput"
          aria-label="Search employees by name or email"
        >
        <select class="input" v-model="companyFilter" aria-label="Company filter">
          <option value="">All companies</option>
          <option v-for="c in companies" :key="c" :value="c">{{ c }}</option>
        </select>
      </div>
    </div>

    <table class="table" aria-labelledby="emp-title">
      <thead>
        <tr>
          <th
            @click="sortBy('company')"
            :aria-sort="ariaSort('company')"
            role="columnheader"
            tabindex="0"
            @keydown.enter.prevent="sortBy('company')"
          >Company</th>
          <th
            @click="sortBy('name')"
            :aria-sort="ariaSort('name')"
            role="columnheader"
            tabindex="0"
            @keydown.enter.prevent="sortBy('name')"
          >Name</th>
            <th>Email</th>
          <th
            @click="sortBy('salary')"
            :aria-sort="ariaSort('salary')"
            role="columnheader"
            tabindex="0"
            @keydown.enter.prevent="sortBy('salary')"
          >Salary</th>
          <th style="width:110px">Action</th>
        </tr>
      </thead>

      <tbody>
        <tr v-if="loading">
          <td colspan="5">
            <div class="skeleton-line"></div>
            <div class="skeleton-line" style="width:75%"></div>
          </td>
        </tr>

        <tr
          v-for="e in filteredEmployees"
          :key="e.id"
          :class="{ 'row-flash': e._justSaved }"
          v-else
        >
          <td>{{ e.company }}</td>
          <td>{{ e.name }}</td>
          <td>
            <input
              class="input"
              v-model="e.email"
              @blur="validateEmail(e)"
              @keyup.enter="onSave(e)"
            >
            <div v-if="e._error" class="small error">{{ e._error }}</div>
          </td>
          <td>{{ asCurrency(e.salary) }}</td>
          <td>
            <button class="btn" @click="onSave(e)" :disabled="!!e._error || e._saving">
              <span v-if="e._saving" class="spinner" aria-hidden="true"></span>Save
            </button>
          </td>
        </tr>

        <tr v-if="!loading && !filteredEmployees.length">
          <td colspan="5" class="muted small">No employees match current filter.</td>
        </tr>
      </tbody>
    </table>
  </section>
</template>

<script>
import { asCurrency } from '@/utils/format';

// Minimal debounce to avoid excessive re-computation while typing
function debounce(fn, wait = 250) {
  let t;
  return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), wait); };
}

// Locale-aware comparator with numeric handling for salary
function cmp(a, b, key, dir) {
  const mul = dir === 'asc' ? 1 : -1;
  if (key === 'salary') {
    const na = Number(a.salary), nb = Number(b.salary);
    if (na === nb) return 0;
    return (na < nb ? -1 : 1) * mul;
  }
  const sa = (a[key] ?? '').toString();
  const sb = (b[key] ?? '').toString();
  const r = sa.localeCompare(sb, undefined, { sensitivity: 'base' });
  return r * mul;
}

export default {
  name: 'EmployeesTable',
  props: { rows: Array, loading: Boolean },
  emits: ['save'],
  data() {
    return { q: '', companyFilter: '', sort: { key: 'company', dir: 'asc' } };
  },
  computed: {
    companies() {
      return [...new Set((this.rows || []).map(e => e.company))]
        .sort((a, b) => a.localeCompare(b));
    },
    filteredEmployees() {
      let rows = [...(this.rows || [])];
      if (this.q) {
        const s = this.q.toLowerCase();
        rows = rows.filter(r =>
          (r.name || '').toLowerCase().includes(s) ||
          (r.email || '').toLowerCase().includes(s)
        );
      }
      if (this.companyFilter) {
        rows = rows.filter(r => r.company === this.companyFilter);
      }
      const { key, dir } = this.sort;
      rows.sort((a, b) => cmp(a, b, key, dir));
      return rows;
    }
  },
  methods: {
    asCurrency,
    ariaSort(col) { return this.sort.key === col ? (this.sort.dir === 'asc' ? 'ascending' : 'descending') : 'none'; },
    sortBy(k) { this.sort.key === k ? (this.sort.dir = this.sort.dir === 'asc' ? 'desc' : 'asc') : (this.sort = { key: k, dir: 'asc' }); },
    validateEmail(row) {
      row._error = '';
      const email = (row.email || '').trim();
      if (!email) { row._error = 'Email is required'; return; }
      const ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
      if (!ok) row._error = 'Invalid email format';
    },
    onSearchInput: debounce(function () { /* no-op: computed will update */ }, 200),

    async onSave(e) {
      this.validateEmail(e);
      if (e._error) return;
      const old = { email: e._prevEmail ?? e.email };
      e._saving = true;
      try {
        await this.$emit('save', e);
        e._justSaved = true;
        setTimeout(() => (e._justSaved = false), 700);
        e._prevEmail = e.email;
      } catch (err) {
        // Roll back if parent save failed
        e.email = old.email;
      } finally {
        e._saving = false;
      }
    },
  }
};
</script>

<style scoped>
.row-flash { animation: flash-bg 0.7s ease; }
@keyframes flash-bg {
  from { background: rgba(0, 200, 120, .12); }
  to   { background: transparent; }
}
</style>
