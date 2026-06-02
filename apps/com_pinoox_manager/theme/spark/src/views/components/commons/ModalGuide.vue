<template>
  <SimpleModal title="راهنما" size="sm">

    <div class="modal-guide-content" v-html="message"></div>

    <template #footer>
      <Button @click="closeModal" label="بستن" variant="dark"></Button>
    </template>
  </SimpleModal>
</template>

<script setup>
defineOptions({modalGroup: 'default'});

import {closeModal} from '@kolirt/vue-modal'

const props = defineProps({
  message: {type: String}
})
</script>
