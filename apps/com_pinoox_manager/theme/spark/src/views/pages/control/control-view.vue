<template>
  <div
      class="pageControl"
      :class="{
        'pageControl--embedded': embedded,
        'pageControl--mobile': layout.isCompact,
        'pageControl--compact': layout.isCompact,
        'pageControl--mobileSidebarOpen': layout.mobileSidebarOpen,
        'pageControl--sidebarCollapsed': sidebarStore.isCollapsed && !layout.isCompact,
      }"
  >
    <Transition name="pageControlBackdrop">
      <button
          v-if="layout.isCompact && layout.mobileSidebarOpen"
          type="button"
          class="pageControl__mobileBackdrop"
          aria-label="بستن منو"
          @click="layout.closeMobileSidebar()"
      />
    </Transition>

    <ControlSidebar class="pageControl__sidebar" :embedded="embedded"/>

    <div class="pageControl__page" :class="pageClasses">
      <ControlPanelEmbeddedOutlet v-if="embedded"/>
      <RouterView v-else/>
    </div>
  </div>
</template>

<script setup>
import {computed, onMounted, onUnmounted, watch} from 'vue';
import {useRoute} from 'vue-router';
import ControlSidebar from './control-sidebar.vue';
import ControlPanelEmbeddedOutlet from './ControlPanelEmbeddedOutlet.vue';
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
  collapsed: sidebarStore.isCollapsed && !layout.isCompact,
}));

watch(() => route.path, () => {
  layout.closeMobileSidebar();
});

watch(() => layout.isCompact, (compact) => {
  if (compact) {
    sidebarStore.setCollapsed(false);
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
