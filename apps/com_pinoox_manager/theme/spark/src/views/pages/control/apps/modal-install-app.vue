<template>
  <SimpleModal title=" نصب اپلیکیشن" size="sm">

    <FileUploader ref="fileUploaderRef" @select="selectFile"/>

    <template #footer>
      <Button @click="closeModal" label="لغو" variant="dark"></Button>
      <Button :is-loading="isLoading" @click="install()" label="نصب" variant="primary"></Button>
    </template>
  </SimpleModal>
</template>

<script setup>
defineOptions({modalGroup: 'default'});

import {closeModal, useModalContext} from '@kolirt/vue-modal'
import {ref} from "vue";
import {appAPI} from "@api/app.js";
import {uploadPackageFile, shouldUsePinion} from "@utils/pinion.js";
import {toast} from "@global";

const {confirm} = useModalContext();

const fileUploaderRef = ref(null);
const file = ref(null);
const isLoading = ref(false);

const selectFile = (selectedFile) => {
  file.value = selectedFile;
};

const install = () => {

  if (!file.value) {
    toast({
      title: 'لطفاً یک فایل انتخاب کنید',
      type: 'error',
    });
    return;
  }

  isLoading.value = true;

  const runInstall = async () => {
    if (shouldUsePinion(file.value)) {
      const result = await uploadPackageFile(file.value);
      const filename = result?.filename || file.value.name;
      await appAPI.installPackage(filename);
      return;
    }

    await appAPI.install({file: file.value});
  };

  runInstall().then(() => {
    fileUploaderRef.value.resetFile();
    toast({
      title: 'با موفقیت نصب شد',
      type: 'success',
    });
    confirm();
  }).catch((error) => {
    console.error("خطا در ارسال فایل:", error);
    toast({
      title: 'خطا در ارسال فایل',
      type: 'error',
    });
  }).finally(() => isLoading.value = false)
};

</script>