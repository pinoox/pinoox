<template>
  <ModalRoot class="vue-modal-root">
    <ModalContent :class="['vue-modal-content', sizeClass, $attrs.class]">
      <div class="vue-modal-header">
        <ModalTitle>{{ title }}</ModalTitle>
      </div>
      <div class="vue-modal-body">
        <slot/>
      </div>
      <div v-if="$slots.footer" class="vue-modal-footer">
        <slot name="footer"/>
      </div>
    </ModalContent>
  </ModalRoot>
</template>

<script setup>
import {ModalContent, ModalRoot, ModalTitle} from '@kolirt/vue-modal';
import {computed} from 'vue';

defineOptions({modalGroup: 'default'});

const props = defineProps({
  title: {type: String, default: ''},
  size: {type: String, default: 'md'},
});

const sizeClass = computed(() => `vue-modal--${props.size}`);
</script>
