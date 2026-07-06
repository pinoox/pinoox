<template>
  <SimpleModal title=" نصب اپلیکیشن" size="sm">
    <p class="text-sm opacity-80 leading-7">
      نصب بسته از پنجره سراسری انجام می‌شود. برای فایل‌های بزرگ، آپلود Pinion فعال است.
    </p>
    <template #footer>
      <Button @click="closeModal" label="لغو" variant="dark"/>
      <Button @click="openGlobalInstaller" label="باز کردن نصب‌کننده" variant="primary"/>
    </template>
  </SimpleModal>
</template>

<script setup>
defineOptions({modalGroup: 'default'});

import {closeModal} from '@kolirt/vue-modal';
import {usePackageInstallerStore} from '@/stores/modules/packageInstaller.js';

const packageInstallerStore = usePackageInstallerStore();

function openGlobalInstaller() {
  packageInstallerStore.show();
  closeModal();
}
</script>
