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
import {closeModal, confirmModal} from '@kolirt/vue-modal'
import {ref} from "vue";
import {appAPI} from "@api/app.js";
import {toast} from "@global";


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

  appAPI.install({
    file: file.value
  }).then((response) => {
    fileUploaderRef.value.resetFile();
    toast({
      title: 'با موفقیت نصب شد',
      type: 'success',
    });
    confirmModal();
  }).catch((error) => {
    console.error("خطا در ارسال فایل:", error);
    toast({
      title: 'خطا در ارسال فایل',
      type: 'error',
    });
  }).finally(() => isLoading.value = false)
};

</script>