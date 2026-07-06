<template>
  <DraggableWidget class="clockWidget" initialX="72%" initialY="8%">
    <template #header>
      <span v-if="!loading">{{ clock.date }}</span>
      <span v-else class="widgetLoading__header-line" aria-hidden="true"></span>
    </template>

    <WidgetLoading v-if="loading" variant="clock"/>
    <div v-else class="clockWidget__time text-3xl font-bold">{{ clock.moment }}</div>
  </DraggableWidget>
</template>

<script setup>
import DraggableWidget from '../widgets/DraggableWidget.vue';
import WidgetLoading from './WidgetLoading.vue';
import { widgetAPI } from '@api/widget.js';
import { unwrapResponse } from '@utils/helpers/apiHelper.js';
import { useServerClock } from '@/views/composables/useServerClock.js';

async function fetchClockData() {
  const response = await widgetAPI.clock();
  return unwrapResponse(response) ?? response?.data ?? {};
}

const { loading, clock } = useServerClock(fetchClockData);
</script>
