<template>
  <Page title="مارکت پینوکس" class="pageMarket">
    <template #toolbar>
      <Menu :icon="saxIcon.back" label="بازگشت" @click="$router.push('/')"/>
    </template>

    <div class="mb-4 max-w-md flex gap-2">
      <Input v-model="keyword" placeholder="جستجو..." @keyup.enter="search"/>
      <Button label="جستجو" variant="primary" :is-loading="isLoading" @click="search"/>
    </div>

    <div v-if="apps.length" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
      <div
          v-for="app in apps"
          :key="app.package_name || app.package"
          class="flex flex-col items-center gap-2 cursor-pointer hover:scale-105 transition-transform"
          @click="openApp(app)"
      >
        <img :src="app.icon" :alt="app.name" class="w-16 h-16 rounded-xl object-cover"/>
        <span class="text-sm text-center">{{ app.name }}</span>
      </div>
    </div>
    <PageEmpty v-else-if="!isLoading" title="اپلیکیشنی یافت نشد"/>
  </Page>
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { marketAPI } from "@api/market.js";
import { saxIcon } from "@/const/icons.js";
import { unwrapList } from "@utils/helpers/apiHelper.js";

const router = useRouter();
const keyword = ref('');
const apps = ref([]);
const isLoading = ref(false);

const search = async () => {
  isLoading.value = true;
  try {
    const response = await marketAPI.getApps(keyword.value);
    apps.value = unwrapList(response);
  } finally {
    isLoading.value = false;
  }
};

const openApp = (app) => {
  const packageName = app.package_name || app.package;
  router.push(`/market/${packageName}`);
};

search();
</script>
