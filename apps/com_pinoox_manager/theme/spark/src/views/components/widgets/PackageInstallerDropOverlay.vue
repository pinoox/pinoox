<template>
  <Teleport to="body">
    <div
        v-if="visible"
        class="packageInstallerDropOverlay"
        aria-hidden="true"
    >
      <div class="packageInstallerDropOverlay__panel">
        <Icon :is="saxIcon.upload" size="md"/>
        <p class="packageInstallerDropOverlay__title">رها کنید تا نصب شروع شود</p>
        <p class="packageInstallerDropOverlay__hint">فقط فایل‌های .pinx</p>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import Icon from '@/views/components/widgets/Icon.vue';
import {saxIcon} from '@/const/icons.js';

defineProps({
  visible: {
    type: Boolean,
    default: false,
  },
});
</script>
