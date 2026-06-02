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
import { onMounted, onUnmounted, ref } from 'vue';
import DraggableWidget from '../widgets/DraggableWidget.vue';
import WidgetLoading from './WidgetLoading.vue';
import { widgetAPI } from '@api/widget.js';
import { unwrapResponse } from '@utils/helpers/apiHelper.js';

const TIMEZONE = 'Asia/Tehran';
const SYNC_INTERVAL_MS = 60_000;

const loading = ref(true);
const clock = ref({ date: '', moment: '' });

let serverOffsetMs = 0;
let tickTimer = null;
let syncTimer = null;

const momentFormatter = new Intl.DateTimeFormat('en-GB', {
  timeZone: TIMEZONE,
  hour: '2-digit',
  minute: '2-digit',
  hour12: false,
});

function formatMoment(date) {
  return momentFormatter.format(date);
}

function tickMoment() {
  clock.value.moment = formatMoment(new Date(Date.now() + serverOffsetMs));
}

async function fetchClock(isInitial = false) {
  try {
    const response = await widgetAPI.clock();
    const data = unwrapResponse(response) ?? response?.data ?? {};
    const timestamp = Number(data.timestamp ?? data.time ?? 0);

    if (timestamp > 0)
      serverOffsetMs = timestamp * 1000 - Date.now();

    if (data.date)
      clock.value.date = data.date;

    tickMoment();
  } finally {
    if (isInitial)
      loading.value = false;
  }
}

onMounted(() => {
  fetchClock(true);
  tickTimer = setInterval(tickMoment, 1000);
  syncTimer = setInterval(() => fetchClock(false), SYNC_INTERVAL_MS);
});

onUnmounted(() => {
  if (tickTimer)
    clearInterval(tickTimer);

  if (syncTimer)
    clearInterval(syncTimer);
});
</script>
