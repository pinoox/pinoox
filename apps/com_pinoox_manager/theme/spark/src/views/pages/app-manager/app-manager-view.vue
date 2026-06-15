<template>
  <Page :title="app?.name || 'مدیریت اپ'" class="pageAppManager">
    <template #toolbar>
      <Menu v-if="app" :icon="saxIcon.back" label="بازگشت" @click="$router.push('/control/apps')"/>
    </template>

    <div v-if="isLoading" class="opacity-70 py-8 text-center">در حال بارگذاری...</div>

    <template v-else-if="app">
      <div class="mb-6 flex items-center gap-4">
        <AppIcon v-bind="controlPanelIconProps(app)" size="lg"/>
        <div>
          <h2 class="text-xl font-bold">{{ app.name }}</h2>
          <p class="opacity-70">{{ app.description }}</p>
        </div>
      </div>

      <nav class="app-manager-nav">
        <router-link :to="`/app-manager/${packageName}/details`">جزئیات</router-link>
        <router-link :to="`/app-manager/${packageName}/config`">تنظیمات</router-link>
        <router-link :to="`/app-manager/${packageName}/users`">کاربران</router-link>
        <router-link :to="`/app-manager/${packageName}/templates`">قالب‌ها</router-link>
      </nav>

      <RouterView v-slot="{ Component }">
        <component :is="Component" :package-name="packageName" :app="app"/>
      </RouterView>
    </template>

    <PageEmpty v-else title="اپلیکیشن یافت نشد" description="این اپ نصب نشده یا حذف شده است."/>
  </Page>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import { saxIcon } from "@/const/icons.js";
import { useAppStore } from "@/stores/modules/app.js";
import { controlPanelIconProps } from "@utils/helpers/appIconProps.js";

const route = useRoute();
const appStore = useAppStore();
const isLoading = ref(true);
const packageName = computed(() => route.params.package_name);
const app = computed(() => appStore.fetchAppByPackage(packageName.value));

onMounted(async () => {
  if (!appStore.isLoaded)
    await appStore.getApps();
  isLoading.value = false;
});
</script>

