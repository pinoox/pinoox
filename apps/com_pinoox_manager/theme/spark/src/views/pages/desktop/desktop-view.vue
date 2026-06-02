<template>
  <ClockWidget v-if="showClock"/>
  <StorageWidget v-if="showStorage"/>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import ClockWidget from '@/views/components/desktop-widgets/ClockWidget.vue';
import StorageWidget from '@/views/components/desktop-widgets/StorageWidget.vue';
import { useOptionsStore } from '@/stores/modules/options.js';

const optionsStore = useOptionsStore();

const showClock = computed(() => optionsStore.isWidgetVisible('clock'));
const showStorage = computed(() => optionsStore.isWidgetVisible('storage'));

onMounted(async () => {
  if (!optionsStore.isLoaded)
    await optionsStore.load();
});
</script>
