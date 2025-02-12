<template>
  <section class="pageSection">
    <div class="pageSection__header">
      <div class="pageSection__header-title">{{ title }}</div>
    </div>
    <div class="pageSection__content">
      <slot></slot>
    </div>
  </section>
</template>

<script setup>

const props = defineProps({
  title: {
    type: String
  }
})
</script>
