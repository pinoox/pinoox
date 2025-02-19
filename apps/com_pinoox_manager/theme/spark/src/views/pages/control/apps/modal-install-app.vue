<template>
  <SimpleModal title=" نصب اپلیکیشن" size="sm">

    <FileUploader ref="fileUploaderRef" @select="selectFile"/>

    <template #footer>
      <Button @click="closeModal" label="لغو" variant="dark"></Button>
      <Button @click="install()" label="نصب" variant="primary"></Button>
    </template>
  </SimpleModal>
</template>

<script setup>
import {closeModal, confirmModal} from '@kolirt/vue-modal'
import {ref} from "vue";
import {appAPI} from "@api/app.js";

const fileUploaderRef = ref(null);
const file = ref(null);
const selectFile = (selectedFile) => {
  file.value = selectedFile;
};

const install = () => {
  if (!file.value) {
    //   alert("لطفاً یک فایل انتخاب کنید!");
    // return;
  }

  appAPI.install({
    file: file.value,
    test: '123',
  }).then((response) => {
    fileUploaderRef.value.resetFile();
    //alert("با موفقیت نصب شد!");
    confirmModal();
  }).catch((error) => {
    console.error("خطا در ارسال فایل:", error);
    //alert(error);
  });
};

</script>