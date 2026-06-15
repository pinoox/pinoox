<template>
  <Page title="اپلیکیشن‌ها" class="pageApps">
    <template #toolbar>
      <Menu @click="openModalInstallApp" :icon="saxIcon.add" label="نصب اپلکیشن"/>
      <Menu @click="$router.push('/control/apps/manual')" :icon="saxIcon.upload" label="نصب دستی"/>
      <Menu @click="$router.push('/market')" :icon="saxIcon.market" label="مارکت"/>
    </template>

    <div v-if="!!appStore.appList && appStore.appList.length>0">
      <div class="grid grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-6">
        <div
            v-for="app in appStore.appList"
            :key="app.package_name"
            class="flex flex-col items-center justify-center space-y-2 cursor-pointer transition-transform duration-300 ease-in-out hover:scale-110"
            @click="openApp(app)"
        >
          <div class="appIcon-wrap">
            <AppIcon v-bind="controlPanelIconProps(app)" size="md"/>
          </div>
          <span class="text-sm text-gray-100">{{ app.name }}</span>
        </div>
      </div>
    </div>

    <PageEmpty
        v-else
        title="هیچ اپلیکیشنی نصب نشده"
        description="برای افزودن اپلیکیشن جدید، روی دکمه نصب کلیک کنید."
        :icon="saxIcon.apps"
    />
  </Page>
</template>

<script setup>
import {useRouter} from "vue-router";
import {saxIcon} from "@/const/icons.js";
import {openModal} from "@kolirt/vue-modal";
import ModalInstallApp from "@views/pages/control/apps/modal-install-app.vue";
import {controlPanelIconProps} from "@utils/helpers/appIconProps.js";
import {useAppStore} from "@/stores/modules/app.js";

const router = useRouter();
const appStore = useAppStore();

function openModalInstallApp() {
  openModal(ModalInstallApp, {props: {}}).then(() => {
    appStore.getApps();
  }).catch(() => {});
}

function openApp(app) {
  router.push(`/app-manager/${app.package_name}/config`);
}
</script>
