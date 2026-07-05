<template>
  <SimpleModal title="بسته‌های آپلود شده" size="md" class="modalStagedPackages">
    <div v-if="isLoading" class="modalStagedPackages__loading">
      <WidgetLoading/>
      <p>در حال بارگذاری لیست…</p>
    </div>

    <p v-else-if="!files.length" class="modalStagedPackages__empty">
      بسته‌ای برای نصب موجود نیست.
    </p>

    <ul v-else class="modalStagedPackages__list">
      <li
          v-for="file in files"
          :key="file.filename"
          class="modalStagedPackages__item"
      >
        <div class="modalStagedPackages__main">
          <AppIcon v-bind="packageIconProps(file)" size="sm" variant="soft"/>
          <div class="modalStagedPackages__info">
            <strong class="modalStagedPackages__name">{{ displayName(file) }}</strong>
            <div class="modalStagedPackages__meta">
              <span v-if="file.uploaded_at_label">{{ file.uploaded_at_label }}</span>
              <span v-if="file.version">ورژن {{ file.version }}</span>
              <span v-if="file.size">{{ file.size }}</span>
            </div>
            <span class="modalStagedPackages__filename" dir="ltr">{{ file.filename }}</span>
          </div>
        </div>
        <div class="modalStagedPackages__actions">
          <Button label="نصب" variant="primary" size="sm" @click="installFile(file)"/>
          <Button
              label="حذف"
              variant="danger"
              outline
              size="sm"
              :is-loading="deletingFilename === file.filename"
              @click="deleteFile(file)"
          />
        </div>
      </li>
    </ul>
  </SimpleModal>
</template>

<script setup>
defineOptions({modalGroup: 'default'});

import {onMounted, ref} from 'vue';
import {closeModal} from '@kolirt/vue-modal';
import SimpleModal from '@/views/components/commons/SimpleModal.vue';
import WidgetLoading from '@/views/components/desktop-widgets/WidgetLoading.vue';
import AppIcon from '@/views/components/widgets/AppIcon.vue';
import {appAPI} from '@api/app.js';
import {packageMetaIconProps} from '@utils/helpers/appIconProps.js';
import {useAppStore} from '@/stores/modules/app.js';
import {usePackageInstaller} from '@/views/composables/usePackageInstaller.js';
import {toast} from '@global';

const appStore = useAppStore();
const {previewStagedFile} = usePackageInstaller();

const files = ref([]);
const isLoading = ref(true);
const deletingFilename = ref('');

function displayName(file) {
    return file.name || file.template_name || file.package_name || file.filename;
}

function packageIconProps(file) {
    return packageMetaIconProps(file, appStore);
}

async function loadFiles() {
    isLoading.value = true;

    try {
        const response = await appAPI.files();
        files.value = response.data ?? [];
    } catch {
        files.value = [];
        toast({title: 'خطا در بارگذاری لیست بسته‌ها', type: 'error'});
    } finally {
        isLoading.value = false;
    }
}

function installFile(file) {
    closeModal();
    previewStagedFile(file.filename);
}

async function deleteFile(file) {
    deletingFilename.value = file.filename;

    try {
        await appAPI.deleteFile(file.filename);
        files.value = files.value.filter((item) => item.filename !== file.filename);
        toast({title: 'بسته حذف شد', type: 'success'});
    } catch {
        toast({title: 'خطا در حذف بسته', type: 'error'});
    } finally {
        deletingFilename.value = '';
    }
}

onMounted(loadFiles);
</script>
