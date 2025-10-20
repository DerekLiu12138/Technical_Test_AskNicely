<template>
    <main style="padding:24px; max-width:800px; margin:0 auto;">
      <h1>PHP + Vue + Nginx + MySQL</h1>
      <section>
        <button @click="load">Load Employees</button>
        <ul v-if="list.length">
          <li v-for="e in list" :key="e.id">
            {{ e.company }} - {{ e.name }} -
            <input v-model="e.email" style="width:260px" />
            <button @click="updateEmail(e)">Save</button>
            (Salary: {{ e.salary }})
          </li>
        </ul>
      </section>
      <section style="margin-top:24px;">
        <h3>Create</h3>
        <form @submit.prevent="create">
          <input v-model="form.company" placeholder="company" />
          <input v-model="form.name" placeholder="name" />
          <input v-model="form.email" placeholder="email" />
          <input v-model.number="form.salary" placeholder="salary" type="number"/>
          <button type="submit">Add</button>
        </form>
      </section>
    </main>
  </template>
  
  <script setup lang="ts">
  import axios from 'axios'
  import { reactive, ref } from 'vue'
  
  const list = ref<any[]>([])
  const form = reactive({ company:'', name:'', email:'', salary:0 })
  const api = (p:string) => `/api${p}`
  
  async function load(){ const { data } = await axios.get(api('/employees')); list.value = data }
  async function create(){ await axios.post(api('/employees'), form); Object.assign(form,{company:'',name:'',email:'',salary:0}); await load() }
  async function updateEmail(e:any){ await axios.put(api(`/employees/${e.id}`), { email: e.email }); await load() }
  </script>
  