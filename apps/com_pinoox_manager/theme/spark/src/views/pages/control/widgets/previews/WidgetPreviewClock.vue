<template>
  <WidgetPreviewShell :loading="loading">
    <template #header>{{ clock.date || '—' }}</template>
    <div class="widgetPreviewClock__time">{{ clock.moment || '--:--' }}</div>
  </WidgetPreviewShell>
</template>

<script setup>
import { widgetAPI } from '@api/widget.js';
import { unwrapResponse } from '@utils/helpers/apiHelper.js';
import { useServerClock } from '@/views/composables/useServerClock.js';
import WidgetPreviewShell from './WidgetPreviewShell.vue';

async function fetchClockData() {
  const response = await widgetAPI.clock();
  return unwrapResponse(response) ?? response?.data ?? {};
}

const { loading, clock } = useServerClock(fetchClockData);
</script>
