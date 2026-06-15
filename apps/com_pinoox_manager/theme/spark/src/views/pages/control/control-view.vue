<template>
  <div
      class="pageControl"
      :class="{
        'pageControl--embedded': embedded,
        'pageControl--mobile': layout.isMobile,
        'pageControl--mobileSidebarOpen': layout.mobileSidebarOpen,
        'pageControl--sidebarCollapsed': sidebarStore.isCollapsed && !layout.isMobile,
      }"
  >
    <button
        v-if="layout.isMobile && layout.mobileSidebarOpen"
        type="button"
        class="pageControl__mobileBackdrop"
        aria-label="بستن منو"
        @click="layout.closeMobileSidebar()"
    />

    <ControlSidebar class="pageControl__sidebar" :embedded="embedded"/>

    <div class="pageControl__page" :class="pageClasses">
      <RouterView/>
    </div>
  </div>
</template>

<script setup>
import {computed, onMounted, onUnmounted, watch} from 'vue';
import {useRoute} from 'vue-router';
import ControlSidebar from './control-sidebar.vue';
import {useSidebarStore} from '../../composables/useSidebar.js';
import {useControlPanelLayoutStore} from '@/stores/modules/controlPanelLayout.js';

defineProps({
  embedded: {
    type: Boolean,
    default: false,
  },
});

const route = useRoute();
const sidebarStore = useSidebarStore();
const layout = useControlPanelLayoutStore();

const pageClasses = computed(() => ({
  collapsed: sidebarStore.isCollapsed && !layout.isMobile,
}));

watch(() => route.path, () => {
  layout.closeMobileSidebar();
});

watch(() => layout.isMobile, (mobile) => {
  if (mobile) {
    sidebarStore.setCollapsed(true);
    layout.closeMobileSidebar();
  }
});

onMounted(() => {
  layout.bindViewport();
});

onUnmounted(() => {
  layout.closeMobileSidebar();
});
</script>
