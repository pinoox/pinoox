<template>
  <WidgetPreviewShell :loading="loading">
    <template #header>{{ clock.date || '—' }}</template>
    <div class="widgetPreviewClock__time">{{ clock.moment || '--:--' }}</div>
  </WidgetPreviewShell>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { widgetAPI } from '@api/widget.js';
import { unwrapResponse } from '@utils/helpers/apiHelper.js';
import WidgetPreviewShell from './WidgetPreviewShell.vue';

const loading = ref(true);
const clock = ref({ date: '', moment: '' });

onMounted(async () => {
  try {
    const response = await widgetAPI.clock();
    const data = unwrapResponse(response) ?? response?.data ?? {};

    clock.value = {
      date: data.date ?? '',
      moment: data.moment ?? '',
    };
  } finally {
    loading.value = false;
  }
});
</script>
