<template>
  <ManagerWindow
      mode="auto"
      root-class="managerSimple controlPanelSimple"
      toolbar-class="managerSimple__toolbar controlPanelSimple__toolbar"
      title-class="managerSimple__title controlPanelSimple__title"
      frame-class="managerSimple__frame controlPanelSimple__frame @container"
      close-aria-label="بستن کنترل پنل"
      @close="closeControlPanel"
  >
    <template #toolbar-before>
      <ControlPanelMenuToggle v-if="layout.isCompact"/>
    </template>

    <template #title>
      <Icon :is="saxIcon.control" class="appView__title-icon" size="sm"/>
      <span>کنترل پنل</span>
    </template>

    <div
        class="pageControl pageControl--embedded"
        :class="{
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

      <ControlSidebar class="pageControl__sidebar" embedded/>
      <div class="pageControl__page" :class="{'collapsed': sidebarStore.isCollapsed && !layout.isCompact}">
        <RouterView/>
      </div>
    </div>
  </ManagerWindow>
</template>

<script setup>
import {saxIcon} from '@/const/icons.js';
import Icon from '@/views/components/widgets/Icon.vue';
import ManagerWindow from '@/views/components/layouts/ManagerWindow.vue';
import ControlSidebar from '@/views/pages/control/control-sidebar.vue';
import ControlPanelMenuToggle from '@/views/pages/control/ControlPanelMenuToggle.vue';
import {useSidebarStore} from '@/views/composables/useSidebar.js';
import {useControlPanel} from '@/views/composables/useControlPanel.js';
import {useControlPanelLayoutStore} from '@/stores/modules/controlPanelLayout.js';
import {onMounted, watch} from 'vue';
import {useRoute} from 'vue-router';

const sidebarStore = useSidebarStore();
const layout = useControlPanelLayoutStore();
const {closeControlPanel} = useControlPanel();
const route = useRoute();

onMounted(() => {
  layout.bindViewport();
  layout.syncBreakpoints();
});

watch(() => route.path, () => {
  layout.closeMobileSidebar();
});

watch(() => layout.isCompact, (compact) => {
  if (compact) {
    sidebarStore.setCollapsed(false);
    layout.closeMobileSidebar();
  }
});
</script>
