<template>
  <Page title="اپلیکیشن‌ها" class="pageApps">
    <template #toolbar>
      <Menu @click="openModalInstallApp" :icon="saxIcon.add" label="نصب اپلکیشن"/>
      <Menu @click="router.push({ name: 'apps-manual' })" :icon="saxIcon.upload" label="نصب دستی"/>
      <Menu @click="globalRouter.push('/market')" :icon="saxIcon.market" label="مارکت"/>
    </template>

    <PageSection
        v-if="stagedFiles.length"
        title="بسته‌های آپلود شده"
        class="mb-8"
    >
      <p class="text-sm opacity-70 mb-4">این بسته‌ها بارگذاری شده‌اند اما هنوز نصب نشده‌اند.</p>
      <div class="grid gap-3">
        <div
            v-for="file in stagedFiles"
            :key="file.filename"
            class="flex flex-wrap items-center justify-between gap-3 bg-white/5 rounded-lg p-4"
        >
          <div class="min-w-0">
            <strong>{{ file.name || file.package_name }}</strong>
            <span class="block text-sm opacity-70" dir="ltr">{{ file.filename }}</span>
          </div>
          <div class="flex gap-2">
            <Button label="نصب" variant="primary" size="sm" @click="installStaged(file)"/>
            <Button label="مشاهده همه" variant="dark" outline size="sm" @click="router.push({ name: 'apps-manual' })"/>
          </div>
        </div>
      </div>
    </PageSection>

    <div v-if="!!appStore.appList && appStore.appList.length>0">
      <div class="grid grid-cols-3 @sm:grid-cols-4 @md:grid-cols-6 @lg:grid-cols-8 gap-4 @sm:gap-6">
        <div
            v-for="app in appStore.appList"
            :key="app.package_name"
            class="flex flex-col items-center justify-center space-y-2 cursor-pointer transition-transform duration-300 ease-in-out hover:scale-110"
            @click="openApp(app)"
        >
          <div class="appIcon-wrap">
            <AppIcon v-bind="appIconProps(app)" size="md"/>
          </div>
          <span class="text-sm text-gray-100">{{ app.name }}</span>
        </div>
      </div>
    </div>

    <PageEmpty
        v-else-if="!stagedFiles.length"
        title="هیچ اپلیکیشنی نصب نشده"
        description="برای افزودن اپلیکیشن جدید، روی دکمه نصب کلیک کنید."
        :icon="saxIcon.apps"
    />
  </Page>
</template>

<script setup>
import {onMounted, ref} from 'vue';
import {useRouter} from "vue-router";
import {saxIcon} from "@/const/icons.js";
import {appAPI} from '@api/app.js';
import {usePackageInstallerStore} from "@/stores/modules/packageInstaller.js";
import {usePackageInstaller} from '@/views/composables/usePackageInstaller.js';
import {appIconProps} from "@utils/helpers/appIconProps.js";
import {useAppStore} from "@/stores/modules/app.js";
import {useControlPanelNavigation} from "@/views/composables/useControlPanelNavigation.js";
import {useGlobalRouter} from "@/views/composables/useGlobalRouter.js";

const router = useRouter();
const globalRouter = useGlobalRouter();
const appStore = useAppStore();
const packageInstallerStore = usePackageInstallerStore();
const {previewStagedFile} = usePackageInstaller();
const {pushAppManager} = useControlPanelNavigation();
const stagedFiles = ref([]);

async function loadStagedFiles() {
  try {
    const response = await appAPI.files();
    stagedFiles.value = (response.data ?? []).slice(0, 3);
  } catch {
    stagedFiles.value = [];
  }
}

onMounted(loadStagedFiles);

function openModalInstallApp() {
  packageInstallerStore.show();
}

function installStaged(file) {
  previewStagedFile(file.filename);
}

function openApp(app) {
  pushAppManager(app.package_name, 'config');
}
</script>
