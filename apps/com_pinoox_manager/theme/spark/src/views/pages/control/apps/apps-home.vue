<template>
  <Page title="اپلیکیشن‌ها" class="pageApps">
    <template #toolbar>
      <Menu @click="openModalInstallApp" :icon="saxIcon.add" label="نصب اپلکیشن"/>
    </template>

    <div v-if="!!appStore.appList && appStore.appList.length>0">
      <div class="grid grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-6">
        <div
            v-for="app in appStore.appList"
            :key="app.package"
            class="flex flex-col items-center justify-center space-y-2 cursor-pointer transition-transform duration-300 ease-in-out hover:scale-110"
        >
          <div class="w-16 h-16 md:w-20 md:h-20 rounded-2xl flex items-center justify-center shadow-lg">
            <img :src="app.icon" :alt="app.name" class="w-16 h-16 md:w-20 md:h-20">
          </div>
          <span class="text-sm text-gray-100">{{ app.name }}</span>
        </div>
      </div>
    </div>

    <PageEmpty
        v-else
        title="هیچ مسیری ثبت نشده است"
        description="برای افزودن مسیر جدید، روی دکمه افزودن کلیک کنید."
        :icon="saxIcon.routes"
    />
  </Page>
</template>

<script setup>
import {onMounted, ref} from "vue";
import {saxIcon} from "@/const/icons.js";
import {openModal} from "@kolirt/vue-modal";
import ModalInstallApp from "@views/pages/control/apps/modal-install-app.vue";
import {appAPI} from "@api/app.js";
import {useAppStore} from "@/stores/modules/app.js";

const appStore = useAppStore();
function openModalInstallApp() {
  openModal(ModalInstallApp, {}).then(res => {
      appStore.getApps();
  }).catch(() => {
  })
}
</script>