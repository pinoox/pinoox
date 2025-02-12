<template>
  <section class="page">
    <div class="page__header">
      <div class="page__header-title">{{ title }}</div>
    </div>
    <div class="page__content">
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
