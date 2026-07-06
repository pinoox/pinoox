<template>
  <div class="empty-state flex flex-col items-center justify-center text-center">
    <Icon v-if="icon" :is="icon" class="pageEmpty__icon mb-4"/>
    <h2 class="text-lg font-semibold text-gray-300">{{ title || "موردی یافت نشد" }}</h2>
    <p v-if="description" class="text-sm text-gray-300 mt-2">{{ description }}</p>
  </div>
</template>

<script setup>
import Icon from '@/views/components/widgets/Icon.vue';

const props = defineProps({
  title: {
    type: String,
    default: "چیزی برای نمایش نیست",
  },
  description: String,
  icon: Object,
});
</script>

<style scoped>
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  height: 50vh;
  width: 100%;
}
</style>
