<template>
  <div class="widgetPreview">
    <div v-if="loading" class="widgetPreview__frame widgetPreview__frame--loading" aria-busy="true">
      <div class="widgetPreview__loading-header">
        <span class="widgetPreview__shimmer"></span>
      </div>
      <div class="widgetPreview__loading-body">
        <span class="widgetPreview__shimmer widgetPreview__shimmer--ring"></span>
        <div class="widgetPreview__loading-lines">
          <span class="widgetPreview__shimmer"></span>
          <span class="widgetPreview__shimmer"></span>
        </div>
      </div>
    </div>

    <div v-else class="widgetPreview__frame">
      <div v-if="$slots.header" class="widgetPreview__header">
        <slot name="header"/>
      </div>
      <div class="widgetPreview__body">
        <slot/>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  loading: {
    type: Boolean,
    default: false,
  },
});
</script>
