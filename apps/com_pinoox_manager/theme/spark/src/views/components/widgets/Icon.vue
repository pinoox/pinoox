<template>
  <span class="icon">
    <component :is="is"/>
  </span>
</template>

<script setup>
const props = defineProps({
  is: {},
})
</script>

<style scoped>
.icon {
  width: fit-content;
  height: fit-content;
  svg{
    width: 1.3rem;
    height: 1.3rem;
  }
}
</style>