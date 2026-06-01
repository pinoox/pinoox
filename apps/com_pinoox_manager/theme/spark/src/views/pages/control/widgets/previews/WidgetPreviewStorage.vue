<template>
  <WidgetPreviewShell>
    <template #header>
      <div class="widgetPreviewStorage__head">
        <span>فضای ذخیره‌سازی</span>
        <span class="widgetPreviewStorage__badge">خودکار</span>
      </div>
    </template>

    <div class="widgetPreviewStorage__stats">
      <div>
        <strong>24.50</strong>
        <span>از 256.00 GB</span>
      </div>
      <span class="widgetPreviewStorage__percent">42%</span>
    </div>

    <div class="widgetPreviewStorage__bar">
      <div class="widgetPreviewStorage__bar-fill" style="width: 42%"></div>
    </div>
  </WidgetPreviewShell>
</template>

<script setup>
import WidgetPreviewShell from './WidgetPreviewShell.vue';
</script>
