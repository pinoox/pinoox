<template>
  <DraggableWidget class="clockWidget" initialX="72%" initialY="8%">
    <template #header>
      <span>{{ clock.date }}</span>
    </template>
    <div class="clockWidget__time text-3xl font-bold">{{ clock.moment }}</div>
  </DraggableWidget>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import DraggableWidget from '../widgets/DraggableWidget.vue';
import { widgetAPI } from '@api/widget.js';

const clock = ref({ date: '', moment: '' });
let timer = null;

const fetchClock = async () => {
  const response = await widgetAPI.clock();
  clock.value = response.data;
};

onMounted(() => {
  fetchClock();
  timer = setInterval(fetchClock, 60000);
});

onUnmounted(() => {
  if (timer) clearInterval(timer);
});
</script>
