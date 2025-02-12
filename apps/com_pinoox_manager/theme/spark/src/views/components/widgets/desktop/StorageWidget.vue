<template>
  <DraggableWidget class="storageWidget"
                   initialX="8%"
                   initialY="12%">
    <template #header>
      <span>فضای ذخیره سازی سرور</span>
    </template>
    <div class="storageWidget__info">
      <strong>حافظه استفاده شده:</strong>
      <span>{{ usedSpace }}GB / {{ totalSpace }}GB</span>
    </div>
    <div class="storageWidget__bar">
      <div class="storage-segment used bg-primary" :style="{ width: `${usedPercentage}%` }"></div>
    </div>
  </DraggableWidget>
</template>

<script setup>
import { ref, computed } from 'vue';
import DraggableWidget from '../DraggableWidget.vue';

const totalSpace = ref(256);
const usedSpace = ref(153);

const usedPercentage = computed(() => (usedSpace.value / totalSpace.value) * 100);
</script>

