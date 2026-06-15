<template>
  <button
      type="button"
      class="controlPanelMenuToggle"
      :class="{ 'is-active': layout.mobileSidebarOpen }"
      aria-label="منوی کنترل پنل"
      @click="layout.toggleMobileSidebar()"
  >
    <LucideIcon :name="lucideSidebar.menu" size="sm"/>
  </button>
</template>

<script setup>
import {lucideSidebar} from '@/const/icons.js';
import LucideIcon from '@/views/components/widgets/LucideIcon.vue';
import {useControlPanelLayoutStore} from '@/stores/modules/controlPanelLayout.js';

const layout = useControlPanelLayoutStore();
</script>
