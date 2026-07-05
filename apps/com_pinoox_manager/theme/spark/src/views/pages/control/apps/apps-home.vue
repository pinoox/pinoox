<template>
  <Page title="اپلیکیشن‌ها" class="pageApps">
    <template #toolbar>
      <Menu @click="openModalInstallApp" :icon="saxIcon.add" label="نصب اپلکیشن"/>
      <Menu @click="globalRouter.push('/market')" :icon="saxIcon.market" label="مارکت"/>
    </template>

    <div v-if="stagedCount" class="pageApps__stagedEntry">
      <button type="button" class="pageApps__stagedBtn" @click="openStagedModal">
        <Icon :is="saxIcon.upload" size="sm"/>
        <span>بسته‌های آپلود شده</span>
        <span class="pageApps__stagedBadge">{{ stagedCount }}</span>
      </button>
    </div>

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
        v-else-if="!stagedCount"
        title="هیچ اپلیکیشنی نصب نشده"
        description="برای افزودن اپلیکیشن جدید، روی دکمه نصب کلیک کنید."
        :icon="saxIcon.apps"
    />
  </Page>
</template>

<script setup>
import {computed, onMounted, ref, watch} from 'vue';
import {openModal} from '@kolirt/vue-modal';
import {saxIcon} from '@/const/icons.js';
import {appAPI} from '@api/app.js';
import {usePackageInstallerStore} from '@/stores/modules/packageInstaller.js';
import {appIconProps} from '@utils/helpers/appIconProps.js';
import {useAppStore} from '@/stores/modules/app.js';
import {useControlPanelNavigation} from '@/views/composables/useControlPanelNavigation.js';
import {useGlobalRouter} from '@/views/composables/useGlobalRouter.js';
import ModalStagedPackages from '@/views/pages/control/apps/modal-staged-packages.vue';

const globalRouter = useGlobalRouter();
const appStore = useAppStore();
const packageInstallerStore = usePackageInstallerStore();
const {pushAppManager} = useControlPanelNavigation();
const stagedFiles = ref([]);

const stagedCount = computed(() => stagedFiles.value.length);

async function loadStagedFiles() {
  try {
    const response = await appAPI.files();
    stagedFiles.value = response.data ?? [];
  } catch {
    stagedFiles.value = [];
  }
}

onMounted(loadStagedFiles);

watch(
    () => [packageInstallerStore.phase, packageInstallerStore.visible],
    ([phase, visible]) => {
      if (!visible && (phase === 'success' || phase === 'idle')) {
        loadStagedFiles();
      }
    },
);

function openModalInstallApp() {
  packageInstallerStore.show();
}

function openStagedModal() {
  void openModal(ModalStagedPackages)
      .finally(loadStagedFiles)
      .catch(() => {});
}

function openApp(app) {
  pushAppManager(app.package_name, 'config');
}
</script>
