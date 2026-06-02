<template>
  <section class="pageSection">
    <div class="pageSection__header">
      <div class="pageSection__header-title">{{ title }}</div>
      <div v-if="$slots.actions" class="pageSection__header-actions">
        <slot name="actions"></slot>
      </div>
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
