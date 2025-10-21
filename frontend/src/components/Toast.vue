<template>
    <div v-if="msg" class="toast" role="status">{{ msg }}</div>
  </template>
  
  <script>
  export default { name:'Toast', props:{ msg:{type:String, default:''} } }
  </script>
  