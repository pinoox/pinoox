<template>
  <SimpleModal title=" نصب اپلیکیشن" size="sm">

    <FileUploader/>

    <template #footer>
      <Button @click="closeModal" label="لغو" variant="dark"></Button>
      <Button label="نصب" variant="primary"></Button>
    </template>
  </SimpleModal>
</template>

<script setup>
import {closeModal, confirmModal} from '@kolirt/vue-modal'
import {ref} from "vue";

const props = defineProps({
})
</script>