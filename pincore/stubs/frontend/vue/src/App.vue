<template>
  <main class="app-shell">
    <h1>{{ title }}</h1>
    <p>Pinoox + Vue theme is ready.</p>
  </main>
</template>

<script setup>
const title = 'Pinoox App';
</script>

<style scoped>
.app-shell {
  font-family: system-ui, sans-serif;
  padding: 2rem;
}
</style>
