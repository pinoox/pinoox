<template>
  <PageSection title="جزئیات">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-3xl">
      <div><strong>نسخه:</strong> {{ app.version }}</div>
      <div><strong>توسعه‌دهنده:</strong> {{ app.developer }}</div>
      <div class="md:col-span-2"><strong>توضیحات:</strong> {{ app.description }}</div>
    </div>
  </PageSection>
</template>

<script setup>
defineProps({
  app: Object,
  packageName: String,
});
</script>
