<template>
  <Page title="نصب دستی" class="pageAppsManual">
    <FileUploader ref="fileUploaderRef" @select="onSelect"/>

    <div v-if="uploadProgress > 0 && uploadProgress < 100" class="mt-4">
      <div class="h-2 rounded-full bg-white/10 overflow-hidden">
        <div class="h-full bg-primary transition-all" :style="{ width: `${uploadProgress}%` }"/>
      </div>
      <p class="mt-2 text-sm opacity-70">در حال بارگذاری… {{ uploadProgress }}%</p>
    </div>

    <div class="mt-4">
      <Button label="آپلود فایل‌ها" variant="primary" :is-loading="isUploading" @click="uploadFiles"/>
    </div>

    <PageSection v-if="files.length" title="فایل‌های آماده نصب" class="mt-8">
      <div class="grid gap-3">
        <div v-for="file in files" :key="file.filename" class="flex items-center justify-between bg-white/5 rounded-lg p-4">
          <div>
            <strong>{{ file.name || file.package_name }}</strong>
            <span class="block text-sm opacity-70">{{ file.filename }}</span>
          </div>
          <div v-if="file.install_mode" class="text-xs opacity-60 mt-1">
            {{ file.type === 'theme' ? 'قالب' : 'اپ' }} ·
            {{ file.install_mode === 'update' ? 'بروزرسانی' : 'نصب' }}
          </div>
          <div class="flex gap-2">
            <Button label="نصب" variant="primary" size="sm" @click="installFile(file)"/>
            <Button label="حذف" variant="dark" outline size="sm" @click="deleteFile(file)"/>
          </div>
        </div>
      </div>
    </PageSection>
  </Page>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { appAPI } from "@api/app.js";
import { uploadPackageFile } from "@utils/pinion.js";
import { usePackageInstaller } from "@/views/composables/usePackageInstaller.js";
import { toast } from "@global";

const appStore = useAppStore();
const { previewStagedFile } = usePackageInstaller();
const files = ref([]);
const selectedFiles = ref([]);
const isUploading = ref(false);
const uploadProgress = ref(0);
const fileUploaderRef = ref(null);

const loadFiles = async () => {
  const response = await appAPI.files();
  files.value = response.data ?? [];
};

onMounted(loadFiles);

const onSelect = (file) => {
  selectedFiles.value = Array.isArray(file) ? file : [file];
};

const uploadFiles = async () => {
  if (!selectedFiles.value.length) return;
  isUploading.value = true;
  uploadProgress.value = 0;
  try {
    for (const file of selectedFiles.value) {
      await uploadPackageFile(file, {
        onProgress: (value) => {
          uploadProgress.value = value;
        },
      });
    }
    fileUploaderRef.value?.resetFile();
    selectedFiles.value = [];
    uploadProgress.value = 100;
    await loadFiles();
    toast({title: 'فایل‌ها با موفقیت بارگذاری شدند', type: 'success'});
  } catch (error) {
    uploadProgress.value = 0;
    toast({title: 'خطا در بارگذاری فایل', type: 'error'});
    throw error;
  } finally {
    isUploading.value = false;
  }
};

const installFile = async (file) => {
  await previewStagedFile(file.filename);
};

const deleteFile = async (file) => {
  await appAPI.deleteFile(file.filename);
  await loadFiles();
};
</script>
