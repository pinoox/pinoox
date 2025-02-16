<template>
  <div class="empty-state flex flex-col items-center justify-center text-center">
    <component
        v-if="icon"
        :is="icon"
        class="mb-4 opacity-70"
        :style="{ width: '4rem', height: '4rem' }"
    />
    <h2 class="text-lg font-semibold text-gray-300">{{ title || "موردی یافت نشد" }}</h2>
    <p v-if="description" class="text-sm text-gray-300 mt-2">{{ description }}</p>
  </div>
</template>

<script setup>
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