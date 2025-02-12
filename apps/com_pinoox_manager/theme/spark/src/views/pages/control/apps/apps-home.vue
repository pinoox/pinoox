<template>
  <Page title="اپلیکیشن‌ها" class="pageApps">
    <template #toolbar>
      <Menu :icon="saxIcon.add" label="نصب اپلکیشن"/>
    </template>

    <div class="grid grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-6">
      <div
          v-for="app in apps"
          :key="app.name"
          class="flex flex-col items-center justify-center space-y-2 cursor-pointer transition-transform duration-300 ease-in-out hover:scale-110"
      >
        <div class="w-16 h-16 md:w-20 md:h-20 rounded-2xl flex items-center justify-center shadow-lg">
          <img :src="app.icon" :alt="app.name" class="w-16 h-16 md:w-20 md:h-20">
        </div>
        <span class="text-sm text-gray-100">{{ app.name }}</span>
      </div>
    </div>
  </Page>
</template>

<script setup>
import {ref} from "vue";
import {saxIcon} from "@/const/icons.js";

const apps = ref([
  {name: "Safari", icon: new URL('@/assets/media/icons/1.png', import.meta.url).href},
  {name: "Terminal", icon: new URL('@/assets/media/icons/2.png', import.meta.url).href},
]);
</script>