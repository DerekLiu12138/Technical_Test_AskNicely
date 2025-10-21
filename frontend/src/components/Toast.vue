<template>
  <div v-if="msg" class="toast" role="status" aria-live="polite">{{ msg }}</div>
</template>

<script>
export default { name:'Toast', props:{ msg:{ type:String, default:'' } } };
</script>
