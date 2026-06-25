<template>
  <Page :title="app?.name || 'مدیریت اپ'" class="pageAppManager">
    <template #toolbar>
      <Menu v-if="app" :icon="saxIcon.back" label="بازگشت" @click="pushControlPath('/control/apps')"/>
    </template>

    <div v-if="isLoading" class="appManagerLoading">
      <WidgetLoading/>
    </div>

    <template v-else-if="app">
      <header class="appManagerHero">
        <AppIcon v-bind="appIconProps(app)" size="lg"/>
        <div class="appManagerHero__text">
          <h2 class="appManagerHero__title">{{ app.name }}</h2>
          <span class="appManagerHero__package" dir="ltr">{{ packageName }}</span>
        </div>
        <div v-if="heroBadges.length" class="appManagerHero__badges">
          <span v-for="badge in heroBadges" :key="badge" class="appManagerHero__badge">{{ badge }}</span>
        </div>
      </header>

      <nav class="appManagerNav" aria-label="بخش‌های مدیریت اپ">
        <router-link
            v-for="item in navItems"
            :key="item.to"
            :to="item.to"
            class="appManagerNav__link"
        >
          <Icon :is="item.icon" size="xs"/>
          <span>{{ item.label }}</span>
        </router-link>
      </nav>

      <RouterView v-slot="{ Component }">
        <component :is="Component" :package-name="packageName" :app="app"/>
      </RouterView>
    </template>

    <PageEmpty v-else title="اپلیکیشن یافت نشد" description="این اپ نصب نشده یا حذف شده است."/>
  </Page>
</template>

<script setup>
import {computed, onMounted, ref} from 'vue';
import {useRoute} from 'vue-router';
import {saxIcon} from '@/const/icons.js';
import Icon from '@/views/components/widgets/Icon.vue';
import WidgetLoading from '@/views/components/desktop-widgets/WidgetLoading.vue';
import {useAppStore} from '@/stores/modules/app.js';
import {appIconProps} from '@utils/helpers/appIconProps.js';
import {useControlPanelNavigation} from '@/views/composables/useControlPanelNavigation.js';

const route = useRoute();
const appStore = useAppStore();
const {pushControlPath, appManagerPath} = useControlPanelNavigation();
const isLoading = ref(true);
const packageName = computed(() => route.params.package_name);
const app = computed(() => appStore.fetchAppByPackage(packageName.value));

const isSystemApp = computed(() => !!(app.value?.sys_app ?? app.value?.['sys-app']));

const heroBadges = computed(() => {
  const list = [];

  if (isSystemApp.value) {
    list.push('سیستمی');
  }

  if (app.value?.version) {
    list.push(`v${app.value.version}`);
  }

  return list;
});

const navItems = computed(() => [
  {to: appManagerPath(packageName.value, 'details'), label: 'جزئیات', icon: saxIcon.guide},
  {to: appManagerPath(packageName.value, 'config'), label: 'تنظیمات', icon: saxIcon.setting},
  {to: appManagerPath(packageName.value, 'users'), label: 'کاربران', icon: saxIcon.user},
  {to: appManagerPath(packageName.value, 'templates'), label: 'قالب‌ها', icon: saxIcon.appearance},
]);

onMounted(async () => {
  if (!appStore.isLoaded) {
    await appStore.getApps();
  }

  isLoading.value = false;
});
</script>
