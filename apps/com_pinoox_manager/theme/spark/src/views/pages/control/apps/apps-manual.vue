<template>
  <Page title="نصب دستی" class="pageAppsManual">
    <FileUploader ref="fileUploaderRef" @select="onSelect"/>

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
import { useAppStore } from "@/stores/modules/app.js";

const appStore = useAppStore();
const files = ref([]);
const selectedFiles = ref([]);
const isUploading = ref(false);
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
  try {
    const formData = new FormData();
    selectedFiles.value.forEach(f => formData.append('files', f));
    await appAPI.filesUpload(formData);
    fileUploaderRef.value?.resetFile();
    selectedFiles.value = [];
    await loadFiles();
  } finally {
    isUploading.value = false;
  }
};

const installFile = async (file) => {
  await appAPI.installPackage(file.filename);
  await appStore.getApps();
  await loadFiles();
};

const deleteFile = async (file) => {
  await appAPI.deleteFile(file.filename);
  await loadFiles();
};
</script>
