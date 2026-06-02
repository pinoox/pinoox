<template>
  <div class="widgetLoading" :class="`widgetLoading--${variant}`" aria-busy="true">
    <template v-if="variant === 'ring'">
      <div class="widgetLoading__ring"></div>
      <div class="widgetLoading__lines">
        <span></span>
        <span></span>
      </div>
    </template>

    <template v-else-if="variant === 'clock'">
      <div class="widgetLoading__clock-time"></div>
    </template>
  </div>
</template>

<script setup>
defineProps({
  variant: {
    type: String,
    default: 'ring',
    validator: (value) => ['ring', 'clock'].includes(value),
  },
});
</script>
