<template>
  <section class="card">
    <div class="row between">
      <h2 id="avg-title">Average Salary by Company</h2>
    </div>

    <table class="table" aria-labelledby="avg-title">
      <caption class="sr-only">Average salary per company</caption>
      <thead>
        <tr>
          <th scope="col">Company</th>
          <th scope="col">Average Salary</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="loading">
          <td colspan="2"><div class="skeleton-line" style="width:60%"></div></td>
        </tr>
        <tr v-for="a in rows" :key="a.company" v-else>
          <td>{{ a.company }}</td>
          <td>{{ asCurrency(a.avg_salary) }}</td>
        </tr>
        <tr v-if="!loading && !rows?.length">
          <td colspan="2" class="muted small">No data yet. Upload a CSV first.</td>
        </tr>
      </tbody>
    </table>
  </section>
</template>

<script>
import { asCurrency } from '@/utils/format';

export default {
  name: 'AveragesTable',
  props: { rows: Array, loading: Boolean },
  methods: { asCurrency }
};
</script>