<template>
  <button
      type="button"
      class="controlPanelMenuToggle"
      :class="{ 'is-active': layout.mobileSidebarOpen }"
      aria-label="منوی کنترل پنل"
      :aria-expanded="layout.mobileSidebarOpen"
      @click="layout.toggleMobileSidebar()"
  >
    <span class="controlPanelMenuToggle__iconWrap" aria-hidden="true">
      <Transition name="controlPanelMenuIcon" mode="out-in">
        <LucideIcon
            :key="layout.mobileSidebarOpen ? 'close' : 'menu'"
            :name="layout.mobileSidebarOpen ? lucideSidebar.close : lucideSidebar.menu"
            size="sm"
            class="controlPanelMenuToggle__icon"
        />
      </Transition>
    </span>
  </button>
</template>

<script setup>
import {lucideSidebar} from '@/const/icons.js';
import LucideIcon from '@/views/components/widgets/LucideIcon.vue';
import {useControlPanelLayoutStore} from '@/stores/modules/controlPanelLayout.js';

const layout = useControlPanelLayoutStore();
</script>
